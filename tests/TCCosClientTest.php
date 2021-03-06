<?php

namespace TencentCloudClient\Tests;

use TencentCloudClient\Credential;
use TencentCloudClient\TCCosClient;
use PHPUnit\Framework\TestCase;

class TCCosClientTest extends TestCase
{
    /**
     * @var TCCosClient
     */
    private $cosClient = null;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $credential = new Credential(Config::$secretId, Config::$secretKey);

        $this->cosClient = new TCCosClient(
            Config::$region,
            Config::$bucket,
            Config::$appId,
            $credential
        );
    }

    public function testListObjects()
    {
        $result = $this->cosClient->request("GET", "/");
        $this->assertObjectHasAttribute("RequestId", $result);
        $this->assertObjectHasAttribute("Headers", $result);
        $this->assertObjectHasAttribute("Body", $result);
    }
}
