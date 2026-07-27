<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $code  6位验证码
     * @param  string  $title 邮件标题后缀（如"注册"、"重置密码"）
     */
    public function __construct(
        public string $code,
        public string $title = '注册',
    ) {}

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject("实验室管理系统 - {$this->title}验证码")
            ->view('emails.verification_code')
            ->with([
                'code'  => $this->code,
                'title' => $this->title,
            ]);
    }
}
