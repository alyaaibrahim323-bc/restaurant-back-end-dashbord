<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $type; // 'login' أو 'reset'

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($otp, $type = 'login')
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->type === 'reset'
            ? 'إعادة تعيين كلمة المرور'
            : 'كود التحقق لتسجيل الدخول';

        return $this->subject($subject)
                    ->view('emails.otp')
                    ->with([
                        'otp' => $this->otp,
                        'type' => $this->type
                    ]);
    }
}
