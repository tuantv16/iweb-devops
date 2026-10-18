<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyễn Văn A',
            'email' => 'a@example.com',
            'phone' => '0912345678',
            'topic' => 'hoi-dap',
            'message' => 'Tôi muốn hỏi về dịch vụ của bạn.',
        ], $overrides);
    }

    public function test_contact_page_is_displayed(): void
    {
        $this->get('/lien-he')->assertOk()->assertSee('Gửi liên hệ');
    }

    public function test_valid_submission_shows_success_and_logs_info(): void
    {
        Log::spy();

        $this->post('/lien-he', $this->validPayload())
            ->assertRedirect('/lien-he')
            ->assertSessionHas('success');

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn ($message, $context) => $message === 'contact.submitted'
                && $context['name'] === 'Nguyễn Văn A'
                && $context['topic'] === 'hoi-dap'
                && ! array_key_exists('email', $context)
                && ! array_key_exists('message', $context));
        Log::shouldNotHaveReceived('warning');
    }

    public function test_invalid_submission_shows_errors_and_logs_warning(): void
    {
        Log::spy();

        $this->post('/lien-he', $this->validPayload(['email' => 'khong-phai-email', 'message' => 'ngắn']))
            ->assertRedirect('/lien-he')
            ->assertSessionHasErrors(['email', 'message'])
            ->assertSessionMissing('success');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message, $context) => $message === 'contact.validation_failed'
                && $context['failed_fields'] === ['email', 'message']);
        Log::shouldNotHaveReceived('info');
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/lien-he', [])
            ->assertSessionHasErrors(['name', 'email', 'topic', 'message'])
            ->assertSessionDoesntHaveErrors('phone');
    }
}
