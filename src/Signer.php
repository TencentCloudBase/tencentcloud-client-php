<?php

namespace TencentCloudClient;

use Exception;


class Signer
{
    public static $signMethodMap = ["HmacSHA1" => "SHA1", "HmacSHA256" => "SHA256"];

    /**
     * @param $secretKey
     * @param $signStr
     * @param $signMethod
     * @return string
     * @throws Exception
     */
    public static function sign($secretKey, $signStr, $signMethod)
    {
        return base64_encode(hash_hmac(Signer::$signMethodMap[$signMethod], $signStr, $secretKey, true));
    }

    /**
     * @param $secretKey
     * @param $date
     * @param $service
     * @param $str2sign
     * @return string
     */
    public static function signTC3($secretKey, $date, $service, $str2sign)
    {
        $dateKey = hash_hmac("SHA256", $date, "TC3".$secretKey, true);
        $serviceKey = hash_hmac("SHA256", $service, $dateKey, true);
        $requestKey = hash_hmac("SHA256", "tc3_request", $serviceKey, true);
        return hash_hmac("SHA256", $str2sign, $requestKey);
    }
}
