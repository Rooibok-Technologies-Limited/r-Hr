<?php
/**
 * @author Bodo Desderio <rooiboktechltd@gmail.com>
 * @copyright 2026 Rooibok Technologies. All rights reserved.
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Every ci_email_template shipped with the placeholder template_code 'code1', so
 * the Notifier (which resolves the email template by template_code = event slug)
 * could never match one — its email channel was silently dead. Assign each
 * template a real event-slug code so transactional emails resolve and the
 * templates become event-addressable. Idempotent (matches on subject).
 */
class FixEmailTemplateCodes extends Migration
{
    /** subject-fragment => event-slug template_code */
    private array $map = [
        'Forgot Password'              => 'password.reset',
        'Password Changed'             => 'password.changed',
        'Warm Welcome'                 => 'company_registered',
        'Contact Us'                   => 'contact.received',
        'New Project Assigned'         => 'project.assigned',
        'New Task Assigned'            => 'task.assigned',
        'Award Received'               => 'award.received',
        'New Inquiry'                  => 'ticket.created',
        'New Leave Request'            => 'leave.requested',
        'Your Leave Approved'          => 'leave.approved',
        'Your Leave Rejected'          => 'leave.rejected',
        'New Job Posted'               => 'job.posted',
        'Salary Slip'                  => 'payslip.available',
    ];

    public function up()
    {
        if (! $this->db->tableExists('ci_email_template')) {
            return;
        }
        foreach ($this->map as $subjectFragment => $code) {
            $this->db->table('ci_email_template')
                ->like('subject', $subjectFragment)
                ->where('template_code', 'code1')
                ->update(['template_code' => $code]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('ci_email_template')) {
            return;
        }
        // revert only the codes we set
        $this->db->table('ci_email_template')
            ->whereIn('template_code', array_values($this->map))
            ->update(['template_code' => 'code1']);
    }
}
