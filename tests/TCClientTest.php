<?php

namespace TencentCloudClient\Tests;

use TencentCloudClient\Credential;
use TencentCloudClient\Exception\TCException;
use TencentCloudClient\TCClient;
use PHPUnit\Framework\TestCase;

class TCClientTest extends TestCase
{
    public function testRequest()
    {
        $credential = new Credential(Config::$secretId, Config::$secretKey);

        $client = new TCClient(
            Config::$endpoint,
            Config::$version,
            $credential,
            ""
        );

        $result = $client->request("DescribeEnvs", []);

        $this->assertObjectHasAttribute("RequestId", $result);
    }

    public function testRequestFailureWhenActionNotExists()
    {
        $credential = new Credential(Config::$secretId, Config::$secretKey);

        $client = new TCClient(
            Config::$endpoint,
            Config::$version,
            $credential,
            ""
        );

        $this->expectException(TCException::class);
        $client->request("NotExistsAction", []);
    }
}
