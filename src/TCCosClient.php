<?php


namespace TencentCloudClient;


use function GuzzleHttp\choose_handler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\GuzzleException;

use TencentCloudClient\Exception\TCException;
use TencentCloudClient\Http\HttpClientProfile;

const COMMON_FILTERD_HEADERS = [
    "Content-Length" => true,
    "Content-Type" => true,
    "Connection" => true,
    "Date" => true,
    "Server" => true,
    "x-cos-request-id" => true,
    "x-cos-trace-id" => true
];

class TCCosClient
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
    private $bucket;

    private $appId = "";

    private $endpoint = "";
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

    public function __construct(
        string $region,
        string $bucket,
        string $appId,
        Credential $credential,
        HttpClientProfile $clientProfile = null
    ) {
        $this->region = $region;
        $this->bucket = $bucket;
        $this->appId = $appId;
        $this->credential = $credential;
        $this->sdkVersion = self::$SDK_VERSION;

        $fullBucket = $bucket;
        if (strlen($appId) > strlen($bucket)
            || !strpos($bucket, $appId, -strlen($appId))) {
            $fullBucket = "$bucket-$appId";
        }

        $this->endpoint = "$fullBucket.cos.$this->region.myqcloud.com";

        $this->profile = $clientProfile ? $clientProfile : new HttpClientProfile();
        if ($this->profile->getHttpProfile()->getEndpoint() === null) {
            $this->profile->getHttpProfile()->setEndpoint($this->endpoint);
        }

        $this->signer = new Signer($this->credential);

        $handler = new HandlerStack(choose_handler());

        $handler->push(Middleware::addHostHeader());
        $handler->push(Middleware::addDateHeader());
        $handler->push(Middleware::addUserAgentHeader(""));
        $handler->push(Middleware::addContentLengthLengthHeader());
        // $handler->push(Middleware::encodePath());
        // $handler->push(Middleware::addContentMD5Header());
        $handler->push(Middleware::addAuthorizationHeader($credential));

        $this->client = new Client([
            "base_uri" => "https://$this->endpoint",
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
     * @param string $method
     * @param string $url
     * @param array $options
     * @return object
     * @throws GuzzleException
     * @throws TCException
     */
    public function request(string $method, string $url, array $options = [])
    {
        $response = $this->pureRequest($method, Utils::key_encode($url), $options);

        $headers = $response->getHeaders();
        $body = $response->getBody();

        if ($body->getSize() > 0
            && (isset($headers['Content-Type'])
                && $headers['Content-Type'][0] == 'application/xml')) {
            
            $data = Utils::xml_decode($body->getContents(), false);
            if (isset($data->Code)) {
                throw new TCException(
                    $data->Message,
                    $data->Code,
                    $data->RequestId
                );
            }
        }
        else {
            $data = null;
        }

        $requestId = isset($headers["x-cos-request-id"]) ? $headers["x-cos-request-id"][0] : '';

        $filteredHeaders = [];
        foreach ($headers as $header => $value) {
            if (!array_key_exists($header, COMMON_FILTERD_HEADERS)) {
                $filteredHeaders[$header] = join(";", $headers[$header]);
            }
        }

        return (object)[
            "RequestId" => $requestId,
            "Headers" => $filteredHeaders,
            "Body" => $data,
        ];
    }

    /**
     * @param string $key
     * @param string $cdn
     * @param string $expires
     * @return string
     */
    public function calcObjectUrl(string $key, string $cdn = "", string $expires = TEN_MINUTES) {
        $host = !empty($cdn) ? $cdn : $this->endpoint;
        $auth = $this->signer->calcCosObjectUrlAuthorization(
            "GET",
            $host,
            "/$key",
            $expires
        );
        $key = Utils::key_encode($key);
        $auth = rawurlencode($auth);
        return "https://$host/$key?sign=$auth";
    }
}
