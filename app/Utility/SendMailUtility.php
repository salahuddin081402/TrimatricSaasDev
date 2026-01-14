<?php

namespace App\Utility;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMailUtility
{
    public static function sendMail($email, $subject, $view, $data)
    {
        try {
            Mail::to($email)->send(new SendMail($subject, $view, $data));
        } catch (Throwable $e) {
            // silently ignore mail failure
            return 0;
        }

        return 1;
    }

}
