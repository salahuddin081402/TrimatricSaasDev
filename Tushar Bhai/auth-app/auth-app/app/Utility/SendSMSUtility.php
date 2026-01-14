<?php

namespace App\Utility;

class SendSMSUtility
{
    public static function sendSMS($to, $body)
    {
        $url = "https://sms.mram.com.bd/smsapi";
        $data = [
            "api_key" => "C300241868d3a85abde232.22008655",
            "type" => "text",
            "contacts" => "$to",
            "senderid" => "8809601016257",
            "msg" => "$body",
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        // dd($response);

        return $response;
    }

}
