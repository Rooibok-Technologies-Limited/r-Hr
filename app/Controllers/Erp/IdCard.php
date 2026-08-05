<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 *
 * Staff ID Card console: live preview (all four faces), issue / regenerate /
 * revoke, single printable card, and bulk print sheets. All data flows through
 * App\Libraries\IdCardService; every query is tenant-scoped. Card MANAGEMENT is
 * restricted to company owners / super-admins; a staff member may only preview
 * and print their own card.
 */
namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Libraries\IdCardService;

class IdCard extends BaseController
{
    private function svc(): IdCardService
    {
        return new IdCardService();
    }

    private function actor(): ?array
    {
        $u = session('sup_username');
        return $u ?: null;
    }

    /** The acting user's DB row (session only carries sup_user_id). */
    private array $meCache = [];
    private function me(): ?array
    {
        $u = $this->actor();
        if (! $u) { return null; }
        $uid = (int) ($u['sup_user_id'] ?? 0);
        if (! isset($this->meCache[$uid])) {
            $this->meCache[$uid] = \Config\Database::connect()->table('ci_erp_users')
                ->where('user_id', $uid)->get()->getRowArray() ?: null;
        }
        return $this->meCache[$uid];
    }

    private function canManage(): bool
    {
        $me = $this->me();
        if (! $me) { return false; }
        return in_array($me['user_type'] ?? '', ['company', 'super_user'], true);
    }

    /** Effective company for the acting session. */
    private function company(): int
    {
        return (int) $this->tenantCompanyId();
    }

    /** May the acting user see this employee's card? owner/super in tenant, or self. */
    private function canView(int $userId): bool
    {
        $u = $this->actor();
        if (! $u) { return false; }
        if ($this->canManage()) { return true; }
        return (int) ($u['sup_user_id'] ?? 0) === $userId;
    }

    // --------------------------------------------------------------- console

    public function index()
    {
        if (! $this->actor()) { return redirect()->to(site_url('erp/login')); }
        helper(['main', 'timehr']);
        $svc = $this->svc();
        $company = $this->company();

        // Default preview employee: for managers, first staff in the tenant;
        // for a plain staff user, themselves.
        $u = $this->actor();
        if ($this->canManage()) {
            $emp = \Config\Database::connect()->table('ci_erp_users')
                ->where('company_id', $company)->where('user_type', 'staff')
                ->orderBy('user_id', 'ASC')->get()->getRowArray();
            $previewId = $emp ? (int) $emp['user_id'] : (int) $u['sup_user_id'];
        } else {
            $previewId = (int) $u['sup_user_id'];
        }

        $settings = $svc->settings($company);
        $data = [
            'title'       => 'Staff ID Cards',
            'path_url'    => 'idcard',
            'breadcrumbs' => 'Staff ID Cards',
            'canManage'   => $this->canManage(),
            'settings'    => $settings,
            'previewId'   => $previewId,
            'employees'   => $this->canManage() ? $this->tenantEmployees($company) : [],
        ];
        $data['subview'] = view('erp/idcard/preview', $data);
        return view('erp/layout/layout_main', $data);
    }

    /** AJAX: render the four faces for an employee, honouring UNSAVED overrides. */
    public function faces()
    {
        if (! $this->actor()) { return $this->response->setStatusCode(401)->setJSON(['ok' => false]); }
        $userId = (int) ($this->request->getGet('user_id') ?: $this->request->getPost('user_id'));
        if (! $userId || ! $this->canView($userId)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }
        $company = $this->company();
        $svc = $this->svc();
        // Managers may auto-issue (so a preview always has a token/QR); a plain
        // staff preview must not silently issue on someone's behalf.
        $data = $svc->buildCardData($company, $userId, $this->canManage());
        if (! $data) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        // Overlay live (unsaved) overrides from the settings form.
        $data['settings'] = $this->applyOverrides($data['settings']);

        $faces = [];
        foreach (['portrait', 'landscape'] as $o) {
            foreach (['front', 'back'] as $side) {
                $d = $data; $d['orientation'] = $o;
                $faces[$o . '_' . $side] = (string) view('erp/idcard/face', ['c' => $d, 'side' => $side]);
            }
        }
        return $this->response->setJSON([
            'ok'         => true,
            'faces'      => $faces,
            'verify_url' => $data['verify_url'],
            'status'     => $data['status'],
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /** Merge whitelisted preview overrides onto a settings array (no persistence). */
    private function applyOverrides(array $settings): array
    {
        $req = $this->request;
        foreach (['color_primary','color_secondary','color_accent','color_dark','color_light','color_bg','color_text','color_muted'] as $k) {
            $v = $req->getGet($k) ?: $req->getPost($k);
            if ($v) { $settings[$k] = hex_color($v, $settings[$k] ?? '#000000'); }
        }
        $terms = $req->getGet('terms') ?? $req->getPost('terms');
        if ($terms !== null && $terms !== '') { $settings['terms'] = strip_tags((string) $terms); }
        foreach (['show_logo','enable_qr'] as $k) {
            $v = $req->getGet($k) ?? $req->getPost($k);
            if ($v !== null && $v !== '') { $settings[$k] = (int) $v; }
        }
        $fields = $req->getGet('fields') ?? $req->getPost('fields');
        if (is_array($fields)) {
            foreach ($settings['fields'] as $fk => $fv) {
                $settings['fields'][$fk] = ! empty($fields[$fk]);
            }
        }
        return $settings;
    }

    // ------------------------------------------------------- template builder

    /** ID-card settings / template builder page (managers only). */
    public function settingsPage()
    {
        if (! $this->canManage()) { return redirect()->to(site_url('erp/id-cards')); }
        helper(['main', 'timehr']);
        $company = $this->company();
        $data = [
            'title'       => 'ID Card Template Builder',
            'path_url'    => 'idcard',
            'breadcrumbs' => 'ID Card Settings',
            'settings'    => $this->svc()->settings($company),
            'employees'   => $this->tenantEmployees($company),
            'previewId'   => ($this->tenantEmployees($company)[0]['user_id'] ?? (int) $this->actor()['sup_user_id']),
        ];
        $data['subview'] = view('erp/idcard/settings', $data);
        return view('erp/layout/layout_main', $data);
    }

    /** Persist the tenant's card settings from the builder form. */
    public function saveSettings()
    {
        if (! $this->canManage()) { return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']); }
        $r = $this->request;
        $fields = [];
        foreach (IdCardService::DEFAULT_FIELDS as $k => $v) {
            $fields[$k] = $r->getPost('field_' . $k) ? true : false;
        }
        $data = [
            'template'                 => 'abstract_organic',
            'default_orientation'      => $this->svc()->normalizeOrientation($r->getPost('default_orientation')),
            'allow_orientation_choice' => $r->getPost('allow_orientation_choice') ? 1 : 0,
            'show_logo'                => $r->getPost('show_logo') ? 1 : 0,
            'enable_qr'                => $r->getPost('enable_qr') ? 1 : 0,
            'fields'                   => json_encode($fields),
            'id_prefix'                => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $r->getPost('id_prefix')) ?: 'RT'),
            'id_pattern'               => (string) ($r->getPost('id_pattern') ?: '{PREFIX}-{YEAR}-{SEQUENCE}'),
            'seq_length'               => max(1, min(10, (int) $r->getPost('seq_length'))),
            'validity_years'           => max(1, min(20, (int) $r->getPost('validity_years'))),
            'terms'                    => strip_tags((string) $r->getPost('terms')),
        ];
        foreach (['color_primary','color_secondary','color_accent','color_dark','color_light','color_bg','color_text','color_muted'] as $ck) {
            $v = $r->getPost($ck);
            $data[$ck] = $v ? hex_color($v, '#000000') : null;
        }
        $this->svc()->saveSettings($this->company(), $data);
        service('audit')->record('id_card.settings_updated', ['entity_type' => 'company', 'summary' => 'ID card template settings updated']);
        return $this->response->setJSON(['ok' => true, 'csrf_hash' => csrf_hash()]);
    }

    // ---------------------------------------------------------- lifecycle API

    public function generate()
    {
        if (! $this->canManage()) { return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']); }
        $userId = (int) $this->request->getPost('user_id');
        $orient = $this->request->getPost('orientation');
        $regen  = (int) $this->request->getPost('regenerate') === 1;
        if (! $userId) { return $this->response->setJSON(['ok' => false, 'error' => 'No employee', 'csrf_hash' => csrf_hash()]); }
        $company = $this->company();
        if (! $this->svc()->buildCardData($company, $userId, false)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Employee not in your company', 'csrf_hash' => csrf_hash()]);
        }
        $card = $this->svc()->issue($company, $userId, (int) $this->actor()['sup_user_id'], $regen, $orient);
        return $this->response->setJSON([
            'ok' => true, 'card_number' => $card['card_number'], 'status' => $card['status'],
            'orientation' => $card['orientation'], 'expiry' => $card['expiry_date'], 'csrf_hash' => csrf_hash(),
        ]);
    }

    public function revoke()
    {
        if (! $this->canManage()) { return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Not authorized']); }
        $userId = (int) $this->request->getPost('user_id');
        $ok = $this->svc()->revoke($this->company(), $userId, (int) $this->actor()['sup_user_id']);
        return $this->response->setJSON(['ok' => $ok, 'csrf_hash' => csrf_hash()]);
    }

    // --------------------------------------------------------- printable card

    /** Standalone printable card (both sides) for a single employee. */
    public function card($id = null)
    {
        if (! $this->actor()) { return redirect()->to(site_url('erp/login')); }
        // Accept an encoded segment (:num after udecode), a raw numeric id, or a
        // ?uid= query (used by the console print button for managers).
        $uidQ   = (int) $this->request->getGet('uid');
        $userId = $uidQ ?: (int) udecode((string) $id);
        if (! $userId) { $userId = (int) $id; }
        if (! $this->canView($userId)) { return $this->response->setStatusCode(403)->setBody('Not authorized'); }
        $orient = $this->request->getGet('o');
        $data = $this->svc()->buildCardData($this->company(), $userId, $this->canManage(), $orient);
        if (! $data) { return $this->response->setStatusCode(404)->setBody('Card not available'); }
        return view('erp/idcard/print', ['c' => $data, 'sheet' => false]);
    }

    /** Bulk print sheet for selected employees (POST ids[]). */
    public function bulk()
    {
        if (! $this->canManage()) { return $this->response->setStatusCode(403)->setBody('Not authorized'); }
        $ids    = (array) ($this->request->getPost('ids') ?: []);
        $orient = $this->request->getPost('orientation');
        $company = $this->company();
        $svc = $this->svc();
        $cards = [];
        foreach ($ids as $rawId) {
            $uid = (int) $rawId;
            if (! $uid) { continue; }
            $d = $svc->buildCardData($company, $uid, true, $orient);
            if ($d) { $cards[] = $d; }
        }
        if (! $cards) { return $this->response->setBody('No valid employees selected.'); }
        service('audit')->record('id_card.bulk_generated', [
            'entity_type' => 'employee', 'summary' => 'Bulk ID cards generated (' . count($cards) . ')',
        ]);
        return view('erp/idcard/sheet', ['cards' => $cards]);
    }

    // ------------------------------------------------------------- helpers

    private function tenantEmployees(int $company): array
    {
        return \Config\Database::connect()->table('ci_erp_users')
            ->select('user_id, first_name, last_name')
            ->where('company_id', $company)->where('user_type', 'staff')
            ->orderBy('first_name', 'ASC')->get()->getResultArray();
    }
}
