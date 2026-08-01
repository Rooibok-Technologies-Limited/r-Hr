<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\SystemModel;
use App\Models\UsersModel;

/**
 * AuditLog — super-admin read-only viewer over ci_audit_log (ROADMAP F1).
 *
 * The trail is written by service('audit'); this controller only reads it:
 * a filtered datatable, a CSV export, and a hash-chain integrity check. There
 * is deliberately no edit/delete path — the log is append-only.
 */
class AuditLog extends BaseController
{
    /** Guard: super admins only. Returns a RedirectResponse when unauthorised. */
    private function guard()
    {
        $session = \Config\Services::session();
        if (! $session->has('sup_username')) {
            $session->setFlashdata('err_not_logged_in', lang('Dashboard.err_not_logged_in'));
            return redirect()->to(site_url('erp/login'));
        }
        $usession  = $session->get('sup_username');
        $user_info = (new UsersModel())->where('user_id', $usession['sup_user_id'])->first();
        if (! $user_info || $user_info['user_type'] !== 'super_user') {
            $session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
            return redirect()->to(site_url('erp/desk'));
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $xin_system = (new SystemModel())->where('setting_id', 1)->first();
        $db = \Config\Database::connect();

        $data = [
            'title'       => 'Audit Log | ' . $xin_system['application_name'],
            'path_url'    => 'audit-log',
            'breadcrumbs' => 'Audit Log',
            // Distinct action slugs for the filter dropdown.
            'actions'     => array_column(
                $db->table('ci_audit_log')->select('action')->distinct()->orderBy('action', 'ASC')->get()->getResultArray(),
                'action'
            ),
        ];
        $data['subview'] = view('erp/audit/audit_log', $data);
        return view('erp/layout/layout_main', $data);
    }

    /** DataTables source: recent rows (capped), honouring filters. */
    public function data()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $rows = $this->filtered()->orderBy('audit_id', 'DESC')->limit(2000)->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                esc($r['created_at']),
                esc($r['action']),
                esc($this->actorLabel($r)),
                esc(trim(($r['entity_type'] ?? '') . ' ' . ($r['entity_id'] ?? ''))),
                esc($r['summary'] ?? ''),
                esc($r['ip'] ?? ''),
            ];
        }

        return $this->response->setJSON(['data' => $out]);
    }

    /** Stream the filtered trail as CSV. */
    public function export_csv()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $rows     = $this->filtered()->orderBy('audit_id', 'DESC')->limit(50000)->get()->getResultArray();
        $filename = 'audit-log-' . date('Ymd-His') . '.csv';

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['audit_id', 'created_at', 'action', 'actor', 'actor_type', 'company_id', 'entity_type', 'entity_id', 'summary', 'ip', 'row_hash']);
        foreach ($rows as $r) {
            fputcsv($fh, [
                $r['audit_id'], $r['created_at'], $r['action'], $this->actorLabel($r),
                $r['actor_type'], $r['company_id'], $r['entity_type'], $r['entity_id'],
                $r['summary'], $r['ip'], $r['row_hash'],
            ]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /** Run the tamper-evidence check over the whole chain. */
    public function verify()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $result = service('audit')->verifyChain();

        return $this->response->setJSON($result);
    }

    // ------------------------------------------------------------------

    /** Build the base query with the request's filters applied. */
    private function filtered()
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('ci_audit_log');

        $action = trim((string) $this->request->getGet('action'));
        if ($action !== '') {
            $builder->where('action', $action);
        }
        $actor = trim((string) $this->request->getGet('actor_user_id'));
        if ($actor !== '' && ctype_digit($actor)) {
            $builder->where('actor_user_id', (int) $actor);
        }
        $from = trim((string) $this->request->getGet('date_from'));
        if ($from !== '') {
            $builder->where('created_at >=', $from . ' 00:00:00');
        }
        $to = trim((string) $this->request->getGet('date_to'));
        if ($to !== '') {
            $builder->where('created_at <=', $to . ' 23:59:59');
        }
        $q = trim((string) $this->request->getGet('q'));
        if ($q !== '') {
            $builder->groupStart()->like('summary', $q)->orLike('entity_id', $q)->groupEnd();
        }

        return $builder;
    }

    /** Human label for the actor column, cached per request. */
    private function actorLabel(array $r): string
    {
        static $names = [];
        $id = $r['actor_user_id'] ?? null;
        if ($id === null) {
            return 'system';
        }
        if (! isset($names[$id])) {
            $u = (new UsersModel())->select('first_name, last_name, username')->where('user_id', $id)->first();
            $names[$id] = $u
                ? trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: ($u['username'] ?? ('#' . $id))
                : ('#' . $id);
        }

        return $names[$id] . ' (' . ($r['actor_type'] ?? 'user') . ')';
    }
}
