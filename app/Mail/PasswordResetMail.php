<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Themed password-reset email.
 *
 * NOTE: Laravel's password broker sends its own ResetPassword notification
 * by default. We override `sendPasswordResetNotification` on the User model
 * to route it through this mailable so users get MarketLink branding, the
 * 30-minute expiry callout and PKR-friendly copy.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;

    public string $userName;

    public int $expiryMinutes;

    public function __construct(string $email, string $token, int $expiryMinutes = 30)
    {
        $this->resetUrl = route('password.reset', ['token' => $token, 'email' => $email]);
        $this->userName = '';
        $this->expiryMinutes = $expiryMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your MarketLink password (valid 30 minutes)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'resetUrl' => $this->resetUrl,
                'userName' => $this->userName,
                'expiryMinutes' => $this->expiryMinutes,
            ],
        );
    }
}
