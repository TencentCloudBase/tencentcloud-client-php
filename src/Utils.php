<?php

namespace TencentCloudClient;


class Utils
{
    static function timestamp()
    {
        return time();
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

    public static function firstChar(string $str)
    {
        return substr($str,0, 1);
    }

    public static function lastChar(string $str)
    {

        return substr($str,-1);
    }

    public static function prefix_valid(string $str)
    {
        // $prefix: src/models/index.js
        // $prefix: src/models/
        // $prefix: src/models
        $valid = true;
        if ($str !== '') {
            if (static::firstChar($str) === '/') {
                $valid = false;
            }
            if (strpos($str, '//') !== false) {
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * @param string $str
     *
     * @return bool
     */
    public static function key_valid(string $str)
    {
        $valid = true;
        if (static::firstChar($str) === '/') {
            $valid = false;
        }
        if (strpos($str, '//') !== false) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public static function key_is_real(string $key)
    {
        return static::key_valid($key) && static::lastChar($key) !== '/';
    }

    /**
     * @param string $prefix
     * @param string $key
     *
     * @return string
     */
    public static function key_join(string $prefix, string $key)
    {
        $fullKey = $key;
        if ($prefix !== '') {
            if (static::lastChar($prefix) === '/') {
                $fullKey = "$prefix$key";
            }
            else {
                $fullKey ="$prefix/$key";
            }
        }
        return $fullKey;
    }
}
