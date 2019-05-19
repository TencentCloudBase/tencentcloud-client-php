<?php

namespace TencentCloudClient;


class Utils
{
    /**
     * 获取当前时间戳
     * @return int
     */
    static function timestamp()
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $now = time();
        date_default_timezone_set($timezone);
        return $now;
    }

    static function fromArrayToJSONString(array $params)
    {
        if (count($params) == 0) {
            return "{}";
        } else {
            return json_encode($params, JSON_UNESCAPED_UNICODE);
        }
    }

    static function fromJSONStringToArray(string $str, bool $assoc = false)
    {
        return json_decode($str, $assoc);
    }

    /**
     * @param $params
     * @param bool $filterEmptyQuery
     * @return string
     */
    static function fromArrayToSortedQuerystring($params, $filterEmptyQuery = true)
    {
        ksort($params);
        $sortedParams = [];
        foreach ($params as $key => $value) {
            if (!empty($value) || !$filterEmptyQuery) {
                array_push($sortedParams, $key . "=" . $value);
            }
        }
        var_dump($sortedParams);
        return http_build_query($sortedParams);
    }
}
