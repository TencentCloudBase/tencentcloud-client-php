<?php


namespace TencentCloudClient;


class HttpParameter
{
    private $params;

    private $keys = [];
    private $keyValues = [];

    static function from(array $params, $filterEmptyValue = true, $lowerCaseKey = true)
    {
        return new static($params, $filterEmptyValue, $lowerCaseKey);
    }

    public function __construct(array $params, $filterEmptyValue = true, $lowerCaseKey = true)
    {
        $this->params = $params;

        ksort($params);
        foreach ($params as $key => $values) {
            if (!empty($values) || !$filterEmptyValue) {
                $key = $lowerCaseKey ? strtolower($key) : $key;
                $value = is_array($values) ? implode('; ', $values) : $values;
                array_push($this->keys, $key);
                array_push($this->keyValues, "$key" . "=" . rawurlencode($value));
            }
        }
    }

    public function getKeys()
    {
        return $this->keys;
    }

    public function implodeKeys(string $glue = ";")
    {
        return implode($glue, $this->keys);
    }

    public function getKeyValues()
    {
        return $this->keyValues;
    }

    public function implodeKeyValues(string $glue = "&")
    {
        return implode($glue, $this->keyValues);
    }
}
