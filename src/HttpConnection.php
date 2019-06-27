<?php

namespace TencentCloudClient;

use GuzzleHttp\Client;
use TencentCloudClient\Http\HttpClientProfile;


class HttpConnection
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var HttpClientProfile
     */
    private $profile;

    function __construct(string $url, HttpClientProfile $clientProfile, array $options = [])
    {
        $this->profile = $clientProfile;
        $this->client = new Client([
            "base_uri" => $url
        ]);
    }

    private function getOptions()
    {
        $options = ["allow_redirects" => false];
        $options["timeout"] = $this->profile->getHttpProfile()->getReqTimeout();
        return $options;
    }

    public function post($uri = '', $options = [])
    {
        return $this->client->post($uri, array_merge($this->getOptions(), $options));
    }

    public function request(string $method, string $uri = '', array $options = [])
    {
        return $this->client->request($method, $uri = '', $options);
    }
}
