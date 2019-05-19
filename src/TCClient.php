<?php

namespace TencentCloudClient;

use TencentCloudClient\Http\HttpClientProfile;
use TencentCloudClient\Exception\TCException;
use Psr\Http\Message\ResponseInterface;


class TCClient
{
    /**
     * @var string SDK版本
     */
    public static $SDK_VERSION = Version::Version;

    /**
     * @var string sdk版本号
     */
    private $sdkVersion;

    /**
     * @var integer http响应码200
     */
    public static $HTTP_RSP_OK = 200;

    /**
     * @var Credential 凭证类实例，保存认证相关字段
     */
    private $credential;

    /**
     * @var HttpClientProfile 会话配置信息类
     */
    private $profile = null;

    /**
     * @var string 产品地域
     */
    private $region;

    /**
     * @var string 请求路径
     */
    private $path = "/";

    /**
     * @var string api版本号
     */
    private $apiVersion;

    /**
     * @param $sdkVersion
     */
    public static function setVersion($sdkVersion)
    {
        self::$SDK_VERSION = $sdkVersion;
    }

    /**
     * AbstractClient
     * @param string        $endpoint       请求域名
     * @param string        $version        api版本
     * @param Credential    $credential     认证信息实例
     * @param string        $region         产品地域
     * @param HttpClientProfile $clientProfile
     */
    function __construct($endpoint, $version, $credential, $region, $clientProfile = null)
    {
        $this->region = $region;
        $this->path = "/";
        $this->credential = $credential;
        $this->sdkVersion = self::$SDK_VERSION;
        $this->apiVersion = $version;

        $this->profile = $clientProfile ? $clientProfile : new HttpClientProfile();
        if ($this->profile->getHttpProfile()->getEndpoint() === null) {
            $this->profile->getHttpProfile()->setEndpoint($endpoint);
        }
    }

    /**
     * @param string    $action          方法名
     * @param array     [$formData=[]]   参数列表
     * @return mixed
     * @throws TCException
     */
    public function request($action, $formData = [])
    {
        return $this->doRequest($action, $formData, array());
    }

    /**
     * @param $action
     * @param $request
     * @param $options
     * @return object
     * @throws TCException
     */
    protected function doRequest($action, $request, $options)
    {
        $resp = $this->post($action, $request, $options);

        if ($resp->getStatusCode() !== self::$HTTP_RSP_OK) {
            throw new TCException($resp->getBody(), $resp->getReasonPhrase());
        }

        $response = Utils::fromJSONStringToArray($resp->getBody())->Response;

        if (isset($response->Error)) {
            throw new TCException(
                $response->Error->Message,
                $response->Error->Code,
                $response->RequestId
            );
        }

        return $response;
    }

    /**
     * @param $action
     * @param $request
     * @param $options
     * @return ResponseInterface
     * @throws TCException
     */
    private function post($action, $request, $options)
    {
        $headers = [];
        $headers["Content-Type"] = "application/json";
        $querystring = "";
        $payload = Utils::fromArrayToJSONString($request);

        switch ($this->profile->getSignMethod()) {
            case HttpClientProfile::$SIGN_TC3_SHA256:
                $headers = $this->attachTC3RequestHeaders($headers, $action, $querystring, $payload);
                break;
            case HttpClientProfile::$SIGN_HMAC_SHA256:
            case HttpClientProfile::$SIGN_HMAC_SHA1:
            default:
                throw new TCException("UNSUPPORTED_SIGN_METHOD");
                break;
        }

        return $this->createHTTPConnection()->post($this->path, array_merge($options, [
            "headers" => $headers,
            "body" => $payload
        ]));
    }

    /**
     * @param $headers
     * @param $action
     * @param $querystring
     * @param $payload
     * @return array
     */
    private function attachTC3RequestHeaders($headers, $action, $querystring, $payload)
    {
        $headers["Host"] = $this->profile->getHttpProfile()->getEndpoint();
        $headers["X-TC-Timestamp"] = Utils::timestamp();
        $headers["X-TC-Action"] = ucfirst($action);
        $headers["X-TC-Version"] = $this->apiVersion;
        $headers["X-TC-RequestClient"] = $this->sdkVersion;
        $headers["X-TC-Region"] = $this->region;
        $headers["X-TC-Token"] = $this->credential->getToken();
        $headers["Authorization"] = $this->calcTC3Authorization($headers, $querystring, $payload);

        return $headers;
    }

    private function calcTC3Authorization($headers, $querystring, $payload)
    {
        $endpoint = $this->profile->getHttpProfile()->getEndpoint();
        $method = $this->profile->getHttpProfile()->getReqMethod();

        $timestamp = $headers["X-TC-Timestamp"];

        $date = date("Y-m-d", $timestamp);
        $service = explode(".", $endpoint)[0];

        $canonicalUri = $this->path;
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

    private function createHTTPConnection()
    {
        return new HttpConnection(
            $this->getUrl(),
            $this->profile);
    }

    private function getUrl()
    {
        return $this->profile->getHttpProfile()->getProtocol()
            . $this->profile->getHttpProfile()->getEndpoint();
    }
}
