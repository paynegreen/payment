<?php

namespace App\Resources;

class Email {
    private $apiKey = null;
    private $emails = null;

    public function __construct()
    {
        $this->apiKey = getenv('ELASTIC_KEY');
    }

    public function send( $to, $template, $message)
    {
        if ($this->isArr($message)) {
           $this->throwBadException($message.' is not an array.');
        }

        if ($this->isMultipleEmail($to)) {
            $this->throwBadException($to.' is not an email');
        }

        if ($this->isString($template)) {
            $this->throwBadException($template.' must be a string');
        }

        $params = $this->emails."&template=".$template;

        foreach ($message as $index => $value) {
            $params .= "&merge_$index=".urlencode(trim(preg_replace('/\s\s+/', '', $value)));
        }

        return $this->requestProcessor($params);
    }

    public function attach($attachments, $data)
    {

    }

    private function throwBadException($message) {
        response()->json([
                'message' => $message
            ], 400)->send();

        die();
    }

    private function isArr($body)
    {
        if(is_array($body)) {
            return false;
        }

        return true;
    }

    private function isEmail($email)
    {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //The email address is valid.
            $this->emails .= "&to=$email";
            return false;
        }

       return true;

    }

    private function isMultipleEmail($email)
    {
        if(is_array($email)) {
            foreach ($email as $value) {
                $this->isEmail($value);
            }
            return false;
        }

        $this->isEmail($email);
    }

    private function isString($string)
    {
        if (is_string(($string))) {
            return false;
        }

        return true;
    }

    private function requestProcessor($params)
    {
        $url = "https://api.elasticemail.com/v2/email/send?apikey=".trim($this->apiKey, ' ').$params;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return json_decode($response);
        }
    }
}
