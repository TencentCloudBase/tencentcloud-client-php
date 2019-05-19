<?php

namespace TencentCloudClient;


class Credential
{
    /**
     * @var string secretId
     */
    private $secretId;

    /**
     * @var string secretKey
     */
    private $secretKey;

    /**
     * @var string token
     */
    private $token = "";

    /**
     * Credential constructor.
     * @param string $secretId      secretId
     * @param string $secretKey     secretKey
     * @param string $token         token
     */
    public function __construct($secretId, $secretKey, $token = "")
    {
        $this->secretId = $secretId;
        $this->secretKey = $secretKey;
        $this->token = $token;
    }

    /**
     * @return string secretId
     */
    public function getSecretId()
    {
        return $this->secretId;
    }

    /**
     * @return string secretKey
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @return string token
     */
    public function getToken()
    {
        return $this->token;
    }
}
