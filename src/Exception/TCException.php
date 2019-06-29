<?php

namespace TencentCloudClient\Exception;


class TCException extends \Exception
{
    /**
     * @var string
     */
    private $eCode;

    /**
     * @var string
     */
    private $eMessage;

    /**
     * @var string
     */
    private $eRequestId;

    /**
     * TCException constructor.
     * @param string $code      异常错误码
     * @param string $message   异常信息
     * @param string $requestId 请求ID
     */
    public function __construct($message = "", $code = "", $requestId = "")
    {
        parent::__construct($message, 0);

        $this->eCode = $code;
        $this->eMessage = $message;
        $this->eRequestId = $requestId;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . PHP_EOL .
            " Code: " . $this->eCode . PHP_EOL .
            " Message: ".$this->eMessage . PHP_EOL .
            " RequestId: ".$this->eRequestId . PHP_EOL;
    }

    /**
     * @return string
     */
    public function getECode()
    {
        return $this->eCode;
    }

    /**
     * @return string
     */
    public function getEMessage()
    {
        return $this->eMessage;
    }

    /**
     * @return string
     */
    public function getERequestId()
    {
        return $this->eRequestId;
    }
}
