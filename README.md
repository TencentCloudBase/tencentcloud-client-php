# Tencent Cloud V3 Client For PHP

```php
use TencentCloudClient\TCClient;
use TencentCloudClient\Credential;

$secretId = "Your SecretId";
$secretKey = "Your SecretKey";

$credential = new Credential($secretId, $secretKey);

$client = new TCClient(
    "tcb.tencentcloudapi.com",
    "2018-06-08",
    $credential,
    ""
);

$result = $client->request("DescribeEnvs");
```

## Release


