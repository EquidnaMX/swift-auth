<?php

namespace Equidna\SwifthAuth\Mail;

use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $token;
    public string $email;

    public function __construct(string $email, string $token)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function build()
    {
        $resetUrl = url("/swift-auth/password/{$this->token}?email=" . urlencode($this->email));

        return $this->subject('Restablecer contraseña')
            ->view('swift-auth::emails.password_reset', [
                'resetUrl' => $resetUrl,
                'email' => $this->email,
                'token' => $this->token,
            ]);
    }
}
