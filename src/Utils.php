<?php

namespace TencentCloudClient;


class Utils
{
    static function timestamp()
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set("UTC");
        $now = time();
        date_default_timezone_set($timezone);
        return $now;
    }

    static function HttpDate()
    {
        return gmdate('D, d M Y H:i:s T');
    }

    static function json_encode(array $params)
    {
        if (count($params) == 0) {
            return "{}";
        } else {
            return json_encode($params, JSON_UNESCAPED_UNICODE);
        }
    }

    static function json_decode(string $str, bool $assoc = false)
    {
        return json_decode($str, $assoc);
    }

    static function xml_decode(string $xmlString, bool $assoc = false)
    {
        return json_decode(json_encode(simplexml_load_string($xmlString)), $assoc);
    }

    public static function key_encode($key) {
        return str_replace('%2F', '/', rawurlencode($key));
    }

    public static function key_decode($key) {
        return rawurldecode($key);
    }

    public static function key_explode($key) {
        return explode('/', $key && $key[0] == '/' ? substr($key, 1) : $key);
    }
}
