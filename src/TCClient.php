<?php

namespace TencentCloudClient;

use function GuzzleHttp\choose_handler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use TencentCloudClient\Http\HttpClientProfile;
use TencentCloudClient\Exception\TCException;


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
     * @var Client
     */
    private $client = null;

    /**
     * @var Signer
     */
    private $signer = null;

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

        $this->signer = new Signer($this->credential);

        $handler = new HandlerStack(choose_handler());

        $handler->push(Middleware::addHeader("Host", $this->profile->getHttpProfile()->getEndpoint()));
        $handler->push(Middleware::addHeader("X-TC-Region", $this->region));
        $handler->push(Middleware::addHeader("X-TC-Token", $this->credential->getToken()));
        $handler->push(Middleware::addHeader("X-TC-Version", $this->apiVersion));
        $handler->push(Middleware::addHeader("X-TC-RequestClient", $this->sdkVersion));
        $handler->push(Middleware::addUserAgentHeader($this->sdkVersion));
        $handler->push(Middleware::addTCTimestampHeader());
        $handler->push(Middleware::addTC3AuthorizationHeader($this->signer, $this->profile->getSignMethod()));

        $endpoint = $this->profile->getHttpProfile()->getEndpoint();

        $this->client = new Client([
            "base_uri" => "https://$endpoint",
            "handler" => $handler
        ]);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws GuzzleException
     */
    public function pureRequest(string $method, string $url, array $options = [])
    {
        return $this->client->request($method, $url, $options);
    }

    /**
     * @param $action
     * @param array $formData
     *
     * @return mixed
     * @throws GuzzleException
     * @throws TCException
     */
    public function request($action, $formData = [])
    {
        return $this->doRequest("post", $this->path, [
            "headers" => [
                "Content-Type" => "application/json",
                "X-TC-Action" => ucfirst($action)
            ],
            "body" => Utils::json_encode($formData)
        ]);
    }

    /**
     * @param $method
     * @param $url
     * @param $options
     *
     * @return mixed
     * @throws GuzzleException
     * @throws TCException
     */
    public function doRequest($method, $url, $options)
    {
        $resp = $this->client->request($method, $url, array_merge($options, [
            "allow_redirects" => false,
            "timeout" => $this->profile->getHttpProfile()->getReqTimeout(),
        ]));

        if ($resp->getStatusCode() !== self::$HTTP_RSP_OK) {
            throw new TCException($resp->getBody(), $resp->getReasonPhrase());
        }

        $response = Utils::json_decode($resp->getBody()->getContents())->Response;

        if (isset($response->Error)) {
            throw new TCException(
                $response->Error->Message,
                $response->Error->Code,
                $response->RequestId
            );
        }

        return $response;
    }
}
