<?php

namespace TencentCloudClient\Http;


class HttpClientProfile
{
    /**
     * @var string  HmacSHA1
     */
    public static $SIGN_HMAC_SHA1 = "HmacSHA1";

    /**
     * @var string HmacSHA256
     */
    public static $SIGN_HMAC_SHA256 = "HmacSHA256";

    /**
     * @var string TC3-HMAC-SHA256
     */
    public static $SIGN_TC3_SHA256 = "TC3-HMAC-SHA256";

    /**
     * @var string 签名方法
     */
    private $signMethod;

    /**
     * @var HttpProfile http相关参数
     */
    private $httpProfile;

    /**
     * @var string 忽略内容签名
     */
    private $unsignedPayload = false;

    /**
     * ClientProfile constructor.
     * @param string        $signMethod  签名算法，目前支持SHA256，SHA1
     * @param HttpProfile   $httpProfile http参数类
     */
    public function __construct($signMethod = null, $httpProfile = null)
    {
        $this->signMethod = $signMethod ? $signMethod : HttpClientProfile::$SIGN_TC3_SHA256;
        $this->httpProfile = $httpProfile ? $httpProfile : new HttpProfile();
    }

    /**
     * 设置签名算法
     * @param string $signMethod 签名方法，目前支持SHA256，SHA1, TC3
     */
    public function setSignMethod($signMethod)
    {
        $this->signMethod = $signMethod;
    }

    /**
     * 设置http相关参数
     * @param HttpProfile $httpProfile http相关参数
     */
    public function setHttpProfile($httpProfile)
    {
        $this->httpProfile = $httpProfile;
    }

    /**
     * 获取签名方法
     * @return null|string 签名方法
     */
    public function getSignMethod()
    {
        return $this->signMethod;
    }

    /**
     * 设置是否忽略内容签名
     * @param bool $flag true表示忽略签名
     */
    public function setUnsignedPayload($flag)
    {
        $this->unsignedPayload = $flag;
    }

    /**
     * 获取是否忽略内容签名标志位
     * @return bool
     */
    public function getUnsignedPayload()
    {
        return $this->unsignedPayload;
    }

    /**
     * 获取http选项实例
     * @return null|HttpProfile http选项实例
     */
    public function getHttpProfile()
    {
        return $this->httpProfile;
    }
}
