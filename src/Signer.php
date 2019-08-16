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
     * @param $method
     * @param $endpoint
     * @param $path
     * @param $headers
     * @param $querystring
     * @param $payload
     *
     * @return string
     */
    public function calcTC3Authorization($method, $endpoint, $path, $headers, $querystring, $payload)
    {
        $timestamp = $headers["X-TC-Timestamp"];

        $canonicalUri = $path;
        $canonicalHeaders =
            "content-type:" . $headers["Content-Type"] . "\n" .
            "host:" . $headers["Host"] . "\n";
        $signedHeaders = "content-type;host";
        $payloadHash = hash("SHA256", $payload);

        // 1. 拼接规范请求串
        $canonicalRequest =
            $method . "\n" .
            $canonicalUri . "\n" .
            $querystring . "\n" .
            $canonicalHeaders . "\n" .
            $signedHeaders . "\n" .
            $payloadHash;

        $date = gmdate("Y-m-d", $timestamp);
        $service = explode(".", $endpoint)[0];

        $algorithm = "TC3-HMAC-SHA256";
        $credentialScope = $date . "/" . $service . "/" . "tc3_request";
        $hashedCanonicalRequest = hash("SHA256", $canonicalRequest);

        // 2. 拼接待签名字符串
        $stringToSign =
            $algorithm . "\n" .
            $timestamp . "\n" .
            $credentialScope . "\n" .
            $hashedCanonicalRequest;

        // 3. 计算签名
        $secretKey = $this->credential->getSecretKey();
        $signature = Signer::signTC3($secretKey, $date, $service, $stringToSign);

        // 4. 拼接 Authorization
        $secretId = $this->credential->getSecretId();
        $authorization =
            "$algorithm Credential=$secretId/$credentialScope" . ", " .
            "SignedHeaders=$signedHeaders" . ", " .
            "Signature=$signature";

        return $authorization;
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
