<?php

namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\DepartmentModel;
use App\Models\DesignationModel;
use App\Models\StaffdetailsModel;
use App\Models\BroadcastModel;
use App\Models\BroadcastLogModel;
use App\Models\BroadcastTemplateModel;
use App\Models\NotificationModel;
use App\Libraries\BroadcastPersonaliser;
use App\Libraries\Queue;

class Broadcasts extends BaseController
{
    // ------------------------------------------------------------------
    //  Auth helper — returns user_info or redirects
    // ------------------------------------------------------------------

    private function auth(): ?array
    {
        $session = \Config\Services::session();
        $usession = $session->get('sup_username');

        if (! $session->has('sup_username')) {
            $session->setFlashdata('err_not_logged_in', lang('Dashboard.err_not_logged_in'));
            return null;
        }

        $UsersModel = new UsersModel();
        $user_info  = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

        if (! $user_info) {
            return null;
        }

        return $user_info;
    }

    /**
     * Determine the company_id scoping for the current user.
     */
    private function companyId(array $user_info): ?int
    {
        if ($user_info['user_type'] === 'super_user') {
            return null; // super admin — no company scoping
        }
        if ($user_info['user_type'] === 'company') {
            return (int) $user_info['user_id'];
        }
        // staff
        return (int) $user_info['company_id'];
    }

    // ==================================================================
    //  INDEX — list all broadcasts
    // ==================================================================

    public function index()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $SystemModel    = new SystemModel();
        $BroadcastModel = new BroadcastModel();

        $xin_system = $SystemModel->where('setting_id', 1)->first();

        $companyId = $this->companyId($user_info);

        if ($user_info['user_type'] === 'super_user') {
            $broadcasts = $BroadcastModel->getAll(200);
        } else {
            $broadcasts = $BroadcastModel->getByCompany($companyId, 200);
        }

        // Decode JSONB fields
        $broadcasts = array_map(fn($b) => $BroadcastModel->decodeJson($b), $broadcasts);

        $data['title']       = 'Broadcasts | ' . $xin_system['application_name'];
        $data['path_url']    = 'broadcasts';
        $data['breadcrumbs'] = 'Broadcasts';
        $data['broadcasts']  = $broadcasts;
        $data['user_info']   = $user_info;

        $data['subview'] = view('erp/broadcasts/list', $data);
        return view('erp/layout/layout_main', $data);
    }

    // ==================================================================
    //  CREATE — composer wizard
    // ==================================================================

    public function create()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $SystemModel         = new SystemModel();
        $DepartmentModel     = new DepartmentModel();
        $UsersModel          = new UsersModel();
        $BroadcastTemplateModel = new BroadcastTemplateModel();

        $xin_system = $SystemModel->where('setting_id', 1)->first();
        $companyId  = $this->companyId($user_info);

        // Departments for audience picker
        if ($companyId) {
            $departments = $DepartmentModel->where('company_id', $companyId)->findAll();
            $employees   = $UsersModel->where('company_id', $companyId)
                                      ->where('user_type', 'staff')
                                      ->where('is_active', 1)
                                      ->findAll();
            $templates = $BroadcastTemplateModel->getForCompany($companyId);
        } else {
            $departments = $DepartmentModel->findAll();
            $employees   = $UsersModel->where('user_type', 'staff')
                                      ->where('is_active', 1)
                                      ->findAll();
            $templates = $BroadcastTemplateModel->getAll();
        }

        $data['title']       = 'New Broadcast | ' . $xin_system['application_name'];
        $data['path_url']    = 'broadcasts';
        $data['breadcrumbs'] = 'New Broadcast';
        $data['user_info']   = $user_info;
        $data['departments'] = $departments;
        $data['employees']   = $employees;
        $data['templates']   = $templates;

        $data['subview'] = view('erp/broadcasts/composer', $data);
        return view('erp/layout/layout_main', $data);
    }

    // ==================================================================
    //  SAVE DRAFT
    // ==================================================================

    public function save_draft()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $Return = ['result' => '', 'error' => '', 'csrf_hash' => csrf_hash()];

        $request = $this->request;
        $session = \Config\Services::session();
        $usession = $session->get('sup_username');

        $companyId = $this->companyId($user_info);

        $audienceIds = $request->getPost('audience_ids');
        if (is_array($audienceIds)) {
            $audienceIds = json_encode(array_map('intval', $audienceIds));
        } else {
            $audienceIds = '[]';
        }

        $channels = [];
        if ($request->getPost('channel_inapp'))  $channels[] = 'inapp';
        if ($request->getPost('channel_email'))  $channels[] = 'email';
        if ($request->getPost('channel_sms'))    $channels[] = 'sms';
        if (empty($channels)) $channels[] = 'inapp';

        $data = [
            'company_id'      => $companyId,
            'created_by'      => $usession['sup_user_id'],
            'broadcast_type'  => strip_tags(trim($request->getPost('broadcast_type') ?? 'memo')),
            'subject'         => strip_tags(trim($request->getPost('subject') ?? '')),
            'body_html'       => trim($request->getPost('body_html') ?? ''),
            'body_sms'        => strip_tags(trim($request->getPost('body_sms') ?? '')),
            'audience_type'   => strip_tags(trim($request->getPost('audience_type') ?? 'all_employees')),
            'audience_ids'    => $audienceIds,
            'channels'        => json_encode($channels),
            'status'          => 'draft',
            'total_recipients' => 0,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $BroadcastModel = new BroadcastModel();

        $broadcastId = $request->getPost('broadcast_id');
        if ($broadcastId) {
            // Update existing draft
            $result = $BroadcastModel->update($broadcastId, $data);
        } else {
            $result = $BroadcastModel->insert($data);
            $broadcastId = $BroadcastModel->insertID();
        }

        if ($result) {
            $Return['result']       = 'Draft saved successfully.';
            $Return['broadcast_id'] = $broadcastId;
        } else {
            $Return['error'] = 'Failed to save draft.';
        }

        $this->output($Return);
    }

    // ==================================================================
    //  PREVIEW — personalised output for one sample recipient
    // ==================================================================

    public function preview()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $Return = ['result' => '', 'error' => '', 'csrf_hash' => csrf_hash()];

        $request     = $this->request;
        $personaliser = new BroadcastPersonaliser();
        $companyId   = $this->companyId($user_info);

        $audienceType = $request->getPost('audience_type') ?? 'all_employees';
        $audienceIds  = $request->getPost('audience_ids');
        if (is_string($audienceIds)) {
            $audienceIds = json_decode($audienceIds, true) ?: [];
        }
        if (! is_array($audienceIds)) {
            $audienceIds = [];
        }

        $broadcast = [
            'audience_type' => $audienceType,
            'audience_ids'  => $audienceIds,
        ];

        $recipients = $personaliser->buildRecipientList($broadcast, $companyId ?? 0);

        if (empty($recipients)) {
            $Return['error'] = 'No recipients found for selected audience.';
            $this->output($Return);
            return;
        }

        // Pick first recipient as sample
        $sample = $personaliser->enrichRecipient($recipients[0]);

        $subject  = $request->getPost('subject') ?? '';
        $bodyHtml = $request->getPost('body_html') ?? '';
        $bodySms  = $request->getPost('body_sms') ?? '';

        $sender = $user_info;

        $Return['result']  = 'Preview generated.';
        $Return['preview'] = [
            'recipient_name' => trim(($sample['first_name'] ?? '') . ' ' . ($sample['last_name'] ?? '')),
            'subject'        => $personaliser->personalise($subject, $sample, $sender),
            'body_html'      => $personaliser->personalise($bodyHtml, $sample, $sender),
            'body_sms'       => $personaliser->personalise($bodySms, $sample, $sender),
        ];
        $Return['total_recipients'] = count($recipients);

        $this->output($Return);
    }

    // ==================================================================
    //  SEND — queue one job per recipient via Beanstalkd
    // ==================================================================

    public function send()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $Return = ['result' => '', 'error' => '', 'csrf_hash' => csrf_hash()];

        $request        = $this->request;
        $session        = \Config\Services::session();
        $usession       = $session->get('sup_username');
        $companyId      = $this->companyId($user_info);
        $personaliser   = new BroadcastPersonaliser();

        $broadcastId = $request->getPost('broadcast_id');

        $BroadcastModel    = new BroadcastModel();
        $BroadcastLogModel = new BroadcastLogModel();

        // If no broadcast_id, create from POST data
        if (! $broadcastId) {
            $audienceIds = $request->getPost('audience_ids');
            if (is_array($audienceIds)) {
                $audienceIds = json_encode(array_map('intval', $audienceIds));
            } else {
                $audienceIds = '[]';
            }

            $channels = [];
            if ($request->getPost('channel_inapp'))  $channels[] = 'inapp';
            if ($request->getPost('channel_email'))  $channels[] = 'email';
            if ($request->getPost('channel_sms'))    $channels[] = 'sms';
            if (empty($channels)) $channels[] = 'inapp';

            $scheduledAt = $request->getPost('scheduled_at');
            if (empty($scheduledAt)) {
                $scheduledAt = date('Y-m-d H:i:s');
            }

            $insertData = [
                'company_id'      => $companyId,
                'created_by'      => $usession['sup_user_id'],
                'broadcast_type'  => strip_tags(trim($request->getPost('broadcast_type') ?? 'memo')),
                'subject'         => strip_tags(trim($request->getPost('subject') ?? '')),
                'body_html'       => trim($request->getPost('body_html') ?? ''),
                'body_sms'        => strip_tags(trim($request->getPost('body_sms') ?? '')),
                'audience_type'   => strip_tags(trim($request->getPost('audience_type') ?? 'all_employees')),
                'audience_ids'    => $audienceIds,
                'channels'        => json_encode($channels),
                'status'          => 'queued',
                'scheduled_at'    => $scheduledAt,
                'total_recipients' => 0,
                'created_at'      => date('Y-m-d H:i:s'),
            ];

            $BroadcastModel->insert($insertData);
            $broadcastId = $BroadcastModel->insertID();
        }

        $broadcast = $BroadcastModel->find($broadcastId);
        if (! $broadcast) {
            $Return['error'] = 'Broadcast not found.';
            $this->output($Return);
            return;
        }

        $broadcast = $BroadcastModel->decodeJson($broadcast);

        // Build recipient list
        $recipients = $personaliser->buildRecipientList($broadcast, $companyId ?? 0);

        if (empty($recipients)) {
            $Return['error'] = 'No recipients found for selected audience.';
            $this->output($Return);
            return;
        }

        $channels = $broadcast['channels'];
        if (is_string($channels)) {
            $channels = json_decode($channels, true) ?: [];
        }

        $sender = $user_info;
        $queue  = new Queue();
        $queued = 0;

        foreach ($recipients as $recipient) {
            $enriched = $personaliser->enrichRecipient($recipient);

            $pSubject = $personaliser->personalise($broadcast['subject'] ?? '', $enriched, $sender);
            $pBody    = $personaliser->personalise($broadcast['body_html'] ?? '', $enriched, $sender);
            $pSms     = $personaliser->personalise($broadcast['body_sms'] ?? '', $enriched, $sender);

            // Insert into broadcast log
            $logData = [
                'broadcast_id'        => $broadcastId,
                'recipient_id'        => $recipient['user_id'],
                'recipient_type'      => $recipient['user_type'] ?? 'staff',
                'recipient_email'     => $recipient['email'] ?? '',
                'recipient_phone'     => $recipient['contact_number'] ?? '',
                'personalised_subject' => $pSubject,
                'personalised_body'    => $pBody,
                'personalised_sms'     => $pSms,
                'inapp_sent'          => 0,
                'email_sent'          => 0,
                'sms_sent'            => 0,
                'queued_at'           => date('Y-m-d H:i:s'),
            ];

            $BroadcastLogModel->insert($logData);
            $logId = $BroadcastLogModel->insertID();

            // Push to Beanstalkd 'broadcasts' tube
            $jobPayload = [
                'log_id'       => $logId,
                'broadcast_id' => (int) $broadcastId,
                'recipient_id' => (int) $recipient['user_id'],
                'channels'     => $channels,
                'subject'      => $pSubject,
                'body_html'    => $pBody,
                'body_sms'     => $pSms,
                'email'        => $recipient['email'] ?? '',
                'phone'        => $recipient['contact_number'] ?? '',
                'company_id'   => $companyId,
            ];

            $queue->push('broadcasts', $jobPayload);
            $queued++;
        }

        // Update broadcast status
        $BroadcastModel->update($broadcastId, [
            'status'           => 'queued',
            'total_recipients' => $queued,
            'scheduled_at'     => $broadcast['scheduled_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $Return['result']     = "Broadcast queued successfully.";
        $Return['queued']     = $queued;
        $Return['broadcast_id'] = $broadcastId;

        $this->output($Return);
    }

    // ==================================================================
    //  DETAILS — broadcast info + per-recipient delivery log
    // ==================================================================

    public function details($id = null)
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        if (! $id) {
            return redirect()->to(site_url('erp/broadcasts'));
        }

        $SystemModel       = new SystemModel();
        $BroadcastModel    = new BroadcastModel();
        $BroadcastLogModel = new BroadcastLogModel();
        $UsersModel        = new UsersModel();

        $xin_system = $SystemModel->where('setting_id', 1)->first();

        $broadcast = $BroadcastModel->find($id);
        if (! $broadcast) {
            $session = \Config\Services::session();
            $session->setFlashdata('unauthorized_module', 'Broadcast not found.');
            return redirect()->to(site_url('erp/broadcasts'));
        }

        $broadcast = $BroadcastModel->decodeJson($broadcast);
        $logs      = $BroadcastLogModel->getByBroadcast($id);
        $stats     = $BroadcastLogModel->getStats($id);

        // Enrich logs with recipient names
        foreach ($logs as &$log) {
            $recipientUser = $UsersModel->find($log['recipient_id']);
            $log['recipient_name'] = $recipientUser
                ? trim(($recipientUser['first_name'] ?? '') . ' ' . ($recipientUser['last_name'] ?? ''))
                : 'Unknown';
        }
        unset($log);

        // Get sender name
        $senderUser = $UsersModel->find($broadcast['created_by']);
        $broadcast['sender_name'] = $senderUser
            ? trim(($senderUser['first_name'] ?? '') . ' ' . ($senderUser['last_name'] ?? ''))
            : 'Unknown';

        $data['title']       = 'Broadcast Details | ' . $xin_system['application_name'];
        $data['path_url']    = 'broadcasts';
        $data['breadcrumbs'] = 'Broadcast Details';
        $data['broadcast']   = $broadcast;
        $data['logs']        = $logs;
        $data['stats']       = $stats;
        $data['user_info']   = $user_info;

        $data['subview'] = view('erp/broadcasts/details', $data);
        return view('erp/layout/layout_main', $data);
    }

    // ==================================================================
    //  TEMPLATES — template management
    // ==================================================================

    public function templates()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $SystemModel            = new SystemModel();
        $BroadcastTemplateModel = new BroadcastTemplateModel();

        $xin_system = $SystemModel->where('setting_id', 1)->first();
        $companyId  = $this->companyId($user_info);

        if ($user_info['user_type'] === 'super_user') {
            $templates = $BroadcastTemplateModel->getAll();
        } else {
            $templates = $BroadcastTemplateModel->getForCompany($companyId);
        }

        $data['title']       = 'Broadcast Templates | ' . $xin_system['application_name'];
        $data['path_url']    = 'broadcasts';
        $data['breadcrumbs'] = 'Broadcast Templates';
        $data['templates']   = $templates;
        $data['user_info']   = $user_info;

        // Re-use the composer view's template section or create a simple list
        $data['subview'] = view('erp/broadcasts/list', $data);
        return view('erp/layout/layout_main', $data);
    }

    // ==================================================================
    //  SAVE TEMPLATE
    // ==================================================================

    public function save_template()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            return redirect()->to(site_url('erp/login'));
        }

        $Return = ['result' => '', 'error' => '', 'csrf_hash' => csrf_hash()];

        $request    = $this->request;
        $companyId  = $this->companyId($user_info);

        $templateName = strip_tags(trim($request->getPost('template_name') ?? ''));
        if (empty($templateName)) {
            $Return['error'] = 'Template name is required.';
            $this->output($Return);
            return;
        }

        $data = [
            'company_id'    => $companyId,
            'template_name' => $templateName,
            'subject'       => strip_tags(trim($request->getPost('subject') ?? '')),
            'body_html'     => trim($request->getPost('body_html') ?? ''),
            'body_sms'      => strip_tags(trim($request->getPost('body_sms') ?? '')),
            'category'      => strip_tags(trim($request->getPost('category') ?? 'general')),
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $BroadcastTemplateModel = new BroadcastTemplateModel();

        $templateId = $request->getPost('template_id');
        if ($templateId) {
            $result = $BroadcastTemplateModel->update($templateId, $data);
        } else {
            $result = $BroadcastTemplateModel->insert($data);
            $templateId = $BroadcastTemplateModel->insertID();
        }

        if ($result) {
            $Return['result']      = 'Template saved successfully.';
            $Return['template_id'] = $templateId;
        } else {
            $Return['error'] = 'Failed to save template.';
        }

        $this->output($Return);
    }

    // ==================================================================
    //  RECIPIENT COUNT — AJAX live count
    // ==================================================================

    public function recipient_count()
    {
        $user_info = $this->auth();
        if (! $user_info) {
            $this->output(['count' => 0, 'error' => 'Not authenticated']);
            return;
        }

        $request     = $this->request;
        $personaliser = new BroadcastPersonaliser();
        $companyId   = $this->companyId($user_info);

        $audienceType = $request->getGet('audience_type') ?? 'all_employees';
        $audienceIds  = $request->getGet('audience_ids');

        if (is_string($audienceIds) && ! empty($audienceIds)) {
            $audienceIds = json_decode($audienceIds, true) ?: explode(',', $audienceIds);
            $audienceIds = array_map('intval', $audienceIds);
        } else {
            $audienceIds = [];
        }

        $broadcast = [
            'audience_type' => $audienceType,
            'audience_ids'  => $audienceIds,
        ];

        $recipients = $personaliser->buildRecipientList($broadcast, $companyId ?? 0);

        $this->output([
            'count'     => count($recipients),
            'csrf_hash' => csrf_hash(),
        ]);
    }
}
