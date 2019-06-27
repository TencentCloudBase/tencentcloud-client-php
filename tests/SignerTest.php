<?php
declare(strict_types=1);

namespace TencentCloudClient\Tests;

use TencentCloudClient\Credential;
use TencentCloudClient\Signer;
use PHPUnit\Framework\TestCase;

class SignerTest extends TestCase
{

    public function testSign()
    {
        $this->assertTrue(true);
    }

    public function testSignTC3()
    {
        $this->assertTrue(true);
    }

    public function testSignCOARequest()
    {
        $secretId = "AKIDQjz3ltompVjBni5LitkWHFlFpwkn9U5q";
        $secretKey = "BQYIM75p8x0iWVFSIgqEKwFprpRSVHlz";

        $signer = new Signer(new Credential($secretId, $secretKey));

        $method = "PUT";
        $path = "/exampleobject(%E8%85%BE%E8%AE%AF%E4%BA%91)";

        $headers = [
            "Date" => "Thu, 16 May 2019 06:45:51 GMT",
            "Host" => "examplebucket-1250000000.cos.ap-beijing.myqcloud.com",
            "Content-Type" => "text/plain",
            "Content-Length" => "13",
            "Content-MD5" => "mQ/fVh815F3k6TAUm8m0eg==",
            "x-cos-acl" => "private",
            "x-cos-grant-read" => 'uin="100000000011"'
        ];

        $authorization = $signer->calcCosRequestAuthorization($method, $path, $headers, [], [
            "keyTime" => "1557989151;1557996351"
        ]);

        $excepted = "q-sign-algorithm=sha1" .
            "&q-ak=AKIDQjz3ltompVjBni5LitkWHFlFpwkn9U5q" .
            "&q-sign-time=1557989151;1557996351" .
            "&q-key-time=1557989151;1557996351" .
            "&q-header-list=content-length;content-md5;content-type;date;host;x-cos-acl;x-cos-grant-read" .
            "&q-url-param-list=" .
            "&q-signature=3b8851a11a569213c17ba8fa7dcf2abec6935172";

        $this->assertEquals($excepted, $authorization);
    }
}
