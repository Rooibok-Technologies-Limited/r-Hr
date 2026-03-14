<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\BroadcastPersonaliser;

/**
 * Unit tests for BroadcastPersonaliser — template token replacement.
 */
class BroadcastPersonaliserTest extends CIUnitTestCase
{
    private BroadcastPersonaliser $personaliser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->personaliser = new BroadcastPersonaliser();
    }

    private function sampleRecipient(array $overrides = []): array
    {
        return array_merge([
            'first_name'       => 'Jane',
            'last_name'        => 'Okello',
            'company_name'     => 'Rooibok Ltd',
            'department_name'  => 'Engineering',
            'designation_name' => 'Senior Developer',
        ], $overrides);
    }

    private function sampleSender(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Admin',
            'last_name'  => 'User',
        ], $overrides);
    }

    // ---------------------------------------------------------------
    //  Individual Token Tests
    // ---------------------------------------------------------------

    public function testFirstNameReplacement(): void
    {
        $result = $this->personaliser->personalise(
            'Hello {{first_name}}!',
            $this->sampleRecipient(),
            $this->sampleSender()
        );

        $this->assertEquals('Hello Jane!', $result);
    }

    public function testFullNameReplacement(): void
    {
        $result = $this->personaliser->personalise(
            'Dear {{full_name}},',
            $this->sampleRecipient(),
            $this->sampleSender()
        );

        $this->assertEquals('Dear Jane Okello,', $result);
    }

    public function testDateReplacement(): void
    {
        $result = $this->personaliser->personalise(
            'Date: {{date}}',
            $this->sampleRecipient(),
            $this->sampleSender()
        );

        $expectedDate = date('d M Y');
        $this->assertEquals("Date: {$expectedDate}", $result);
    }

    public function testCompanyNameReplacement(): void
    {
        $result = $this->personaliser->personalise(
            'Welcome to {{company_name}}.',
            $this->sampleRecipient(),
            $this->sampleSender()
        );

        $this->assertEquals('Welcome to Rooibok Ltd.', $result);
    }

    // ---------------------------------------------------------------
    //  Combined Token Test
    // ---------------------------------------------------------------

    public function testAllTokensInOneTemplate(): void
    {
        $template = 'Dear {{full_name}}, welcome to {{company_name}}. '
                  . 'Your first name is {{first_name}} and today is {{date}}. '
                  . 'You are in {{department}} as {{designation}}. '
                  . 'Message from {{sender_name}}.';

        $result = $this->personaliser->personalise(
            $template,
            $this->sampleRecipient(),
            $this->sampleSender()
        );

        $expectedDate = date('d M Y');

        $this->assertStringContainsString('Jane Okello', $result);
        $this->assertStringContainsString('Rooibok Ltd', $result);
        $this->assertStringContainsString('Jane', $result);
        $this->assertStringContainsString($expectedDate, $result);
        $this->assertStringContainsString('Engineering', $result);
        $this->assertStringContainsString('Senior Developer', $result);
        $this->assertStringContainsString('Admin User', $result);

        // No unresolved tokens should remain
        $this->assertStringNotContainsString('{{', $result);
        $this->assertStringNotContainsString('}}', $result);
    }

    // ---------------------------------------------------------------
    //  Missing / Empty Values
    // ---------------------------------------------------------------

    public function testEmptyValuesDefaultToEmptyString(): void
    {
        $recipient = [
            // first_name and last_name deliberately missing
        ];

        $result = $this->personaliser->personalise(
            'Hello {{first_name}} {{last_name}} from {{company_name}}.',
            $recipient,
            $this->sampleSender()
        );

        // Missing values should be replaced with empty strings
        $this->assertStringNotContainsString('{{first_name}}', $result);
        $this->assertStringNotContainsString('{{last_name}}', $result);
        $this->assertStringNotContainsString('{{company_name}}', $result);
        $this->assertEquals('Hello   from .', $result);
    }

    public function testMissingSenderDefaultsToEmptyString(): void
    {
        $result = $this->personaliser->personalise(
            'From: {{sender_name}}',
            $this->sampleRecipient(),
            [] // empty sender
        );

        $this->assertStringNotContainsString('{{sender_name}}', $result);
    }

    // ---------------------------------------------------------------
    //  Month Token
    // ---------------------------------------------------------------

    public function testMonthReplacement(): void
    {
        $result = $this->personaliser->personalise(
            'Payslip for {{month}}',
            $this->sampleRecipient(),
            $this->sampleSender()
        );

        $expectedMonth = date('F Y');
        $this->assertEquals("Payslip for {$expectedMonth}", $result);
    }
}
