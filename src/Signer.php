<?php

namespace TencentCloudClient;


const ONE_MINUTES = "1 minutes";
const TEN_MINUTES = "10 minutes";

class Signer
{
    public static $signMethodMap = ["HmacSHA1" => "SHA1", "HmacSHA256" => "SHA256"];

    private $credential;

    public function __construct(Credential $credential)
    {
        $this->credential = $credential;
    }

    /**
     * @param $secretKey
     * @param $signStr
     * @param $signMethod
     *
     * @return string
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
     *
     * @return string
     */
    public static function signTC3($secretKey, $date, $service, $str2sign)
    {
        $dateKey = hash_hmac("SHA256", $date, "TC3".$secretKey, true);
        $serviceKey = hash_hmac("SHA256", $service, $dateKey, true);
        $requestKey = hash_hmac("SHA256", "tc3_request", $serviceKey, true);
        return hash_hmac("SHA256", $str2sign, $requestKey);
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $headers
     * @param array $parameters
     * @param array $options
     *
     * @return string
     */
    public function calcCosRequestAuthorization(
        string $method,
        string $path,
        array $headers = [],
        array $parameters = [],
        array $options = []
    ) {
        return $this->calcCosAuthorization(
            $method,
            $path,
            HttpParameter::from($headers),
            HttpParameter::from($parameters),
            $expires = TEN_MINUTES,
            $options
        );
    }

    /**
     * @param string $method
     * @param string $host
     * @param string $path
     * @param string $expires
     *
     * @return string
     */
    public function calcCosObjectUrlAuthorization(
        string $method,
        string $host,
        string $path,
        string $expires = TEN_MINUTES
    ) {
        return $this->calcCosAuthorization(
            $method,
            $path,
            HttpParameter::from(["host" => $host]),
            HttpParameter::from([]),
            $expires
        );
    }

    /**
     * @param string $method
     * @param string $path
     * @param HttpParameter $headerParameter
     * @param HttpParameter $queryParameter
     * @param string $expires
     * @param array $options
     *
     * @return string
     */
    private function calcCosAuthorization(
        string $method,
        string $path,
        HttpParameter $headerParameter,
        HttpParameter $queryParameter,
        string $expires = TEN_MINUTES,
        array $options = []
    ) {

        $secretId = $this->credential->getSecretId();
        $secretKey = $this->credential->getSecretKey();

        $httpQueryString = $queryParameter->implodeKeyValues();
        $httpHeaderString = $headerParameter->implodeKeyValues();

        $httpHeaderKeyString = $headerParameter->implodeKeys();
        $httpQueryKeyString = $queryParameter->implodeKeys();

        $keyTime = (string)(Utils::timestamp() - 60) . ";" . (string)(strtotime($expires));

        // Use for unit test
        if (array_key_exists("keyTime", $options)) {
            $keyTime = $options["keyTime"];
        }

        $method = strtolower($method);
        $path = rawurldecode($path);

        $sha1edHttpString = sha1("$method\n$path\n$httpQueryString\n$httpHeaderString\n");
        $signedKeyTime = hash_hmac("SHA1", $keyTime, $secretKey);
        $signature = hash_hmac("SHA1", "sha1\n$keyTime\n$sha1edHttpString\n", $signedKeyTime);

        $authorization = "" .
            "q-sign-algorithm=sha1" .
            "&q-ak=$secretId" .
            "&q-sign-time=$keyTime" .
            "&q-key-time=$keyTime" .
            "&q-header-list=$httpHeaderKeyString" .
            "&q-url-param-list=$httpQueryKeyString" .
            "&q-signature=$signature";

        return $authorization;
    }
}
