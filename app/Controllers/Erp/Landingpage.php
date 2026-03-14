<?php
namespace App\Controllers\Erp;

use App\Controllers\BaseController;
use App\Models\SystemModel;
use App\Models\UsersModel;
use App\Models\LandingContentModel;

class Landingpage extends BaseController
{
    /**
     * CMS editor page — only super_user can access.
     */
    public function index()
    {
        $SystemModel        = new SystemModel();
        $UsersModel         = new UsersModel();
        $LandingContentModel = new LandingContentModel();

        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');

        if (! $session->has('sup_username')) {
            $session->setFlashdata('err_not_logged_in', lang('Dashboard.err_not_logged_in'));
            return redirect()->to(site_url('erp/login'));
        }

        $user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();

        // Only super_user may manage the landing page CMS
        if ($user_info['user_type'] !== 'super_user') {
            $session->setFlashdata('unauthorized_module', lang('Dashboard.xin_error_unauthorized_module'));
            return redirect()->to(site_url('erp/desk'));
        }

        $xin_system = $SystemModel->where('setting_id', 1)->first();

        // Load all sections
        $data['title']       = 'Landing Page CMS | ' . $xin_system['application_name'];
        $data['path_url']    = 'landing_cms';
        $data['breadcrumbs'] = 'Landing Page CMS';

        $data['hero']         = $LandingContentModel->getSection('hero');
        $data['features']     = $LandingContentModel->getJson('features', 'cards');
        $data['stats']        = $LandingContentModel->getJson('stats', 'figures');
        $data['testimonials'] = $LandingContentModel->getJson('testimonials', 'items');
        $data['faq']          = $LandingContentModel->getJson('faq', 'items');
        $data['contact']      = $LandingContentModel->getSection('contact');
        $data['footer']       = $LandingContentModel->getSection('footer');
        $data['seo']          = $LandingContentModel->getSection('seo');

        $data['subview'] = view('erp/settings/landing_cms', $data);
        return view('erp/layout/layout_main', $data);
    }

    /**
     * POST — save a section (section + content_key + content_value or content_json).
     * Clears cache after save.
     */
    public function save_section()
    {
        $UsersModel          = new UsersModel();
        $LandingContentModel = new LandingContentModel();

        $session  = \Config\Services::session();
        $usession = $session->get('sup_username');

        if (! $session->has('sup_username')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not logged in'])->setStatusCode(401);
        }

        $user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
        if ($user_info['user_type'] !== 'super_user') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $request = \Config\Services::request();
        $section = $request->getPost('section');
        $key     = $request->getPost('content_key');

        if (empty($section) || empty($key)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Section and key are required']);
        }

        // Determine if this is JSON or scalar content
        $jsonData = $request->getPost('content_json');
        $value    = $request->getPost('content_value');

        if ($jsonData !== null) {
            $decoded = is_string($jsonData) ? json_decode($jsonData, true) : $jsonData;
            if (is_array($decoded)) {
                $LandingContentModel->setJson($section, $key, $decoded);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid JSON data']);
            }
        } else {
            $LandingContentModel->setValue($section, $key, $value ?? '');
        }

        // Clear any cached landing content
        $cache = \Config\Services::cache();
        $cache->delete('landing_content');

        return $this->response->setJSON(['status' => 'success', 'message' => 'Section saved successfully']);
    }

    /**
     * POST — handle hero image, testimonial photo uploads.
     * Saves to public/uploads/landing/
     */
    public function upload_image()
    {
        $UsersModel = new UsersModel();
        $session    = \Config\Services::session();
        $usession   = $session->get('sup_username');

        if (! $session->has('sup_username')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not logged in'])->setStatusCode(401);
        }

        $user_info = $UsersModel->where('user_id', $usession['sup_user_id'])->first();
        if ($user_info['user_type'] !== 'super_user') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $request = \Config\Services::request();
        $file    = $request->getFile('image');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No valid file uploaded']);
        }

        // Validate file type
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (! in_array($file->getMimeType(), $allowed)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, SVG']);
        }

        // Max 5 MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File too large. Max 5 MB']);
        }

        $uploadDir = FCPATH . 'uploads/landing/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        $url = base_url('uploads/landing/' . $newName);

        // Optionally save to ci_landing_content if section & key provided
        $section = $request->getPost('section');
        $key     = $request->getPost('content_key');
        if ($section && $key) {
            $LandingContentModel = new LandingContentModel();
            $LandingContentModel->setValue($section, $key, $url);

            // Clear cache
            $cache = \Config\Services::cache();
            $cache->delete('landing_content');
        }

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Image uploaded successfully',
            'url'      => $url,
            'filename' => $newName,
        ]);
    }
}
