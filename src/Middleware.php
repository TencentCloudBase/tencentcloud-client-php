<?php


namespace TencentCloudClient;

use Closure;
use Psr\Http\Message\RequestInterface;

class Middleware
{
    /**
     * @return Closure
     */
    static function encodePath()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $path = $request->getUri()->getPath();
                $uri = $request->getUri()->withPath(rawurlencode($path));
                return $handler(
                    $request->withUri($uri),
                    $options
                );
            };
        };
    }

    /**
     * @param string $userAgent
     * @return Closure
     */
    static function addUserAgentHeader(string $userAgent)
    {
        return function (callable $handler) use ($userAgent) {
            return function (RequestInterface $request, array $options) use ($handler, $userAgent) {
                return $handler(
                    $request->withHeader("User-Agent", $userAgent),
                    $options
                );
            };
        };
    }

    /**
     * @return Closure
     */
    static function addHostHeader()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                return $handler(
                    $request->withHeader("Host", $request->getUri()->getHost()),
                    $options
                );
            };
        };
    }

    /**
     * @return Closure
     */
    static function addDateHeader()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                return $handler(
                    $request->withHeader("Date", Utils::HttpDate()),
                    $options
                );
            };
        };
    }

    /**
     * @return Closure
     */
    static function addContentLengthLengthHeader()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $body = $request->getBody();
                if ($body && $body->getSize() > 0) {
                    return $handler(
                        $request->withHeader("Content-Length", $body->getSize()),
                        $options
                    );
                }
                return $handler($request, $options);
            };
        };
    }

    /**
     * @return Closure
     */
    static function addContentMD5Header()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $body = $request->getBody();
                if ($body && $body->getSize() > 0) {
                    return $handler($request->withHeader(
                        "Content-MD5",
                        base64_encode(md5($body->getContents()))),
                        $options
                    );
                }
                return $handler($request, $options);
            };
        };
    }

    /**
     * @param $credential
     * @return Closure
     */
    static function addAuthorizationHeader(Credential $credential)
    {
        return function (callable $handler) use ($credential) {
            $signer = new Signer($credential);
            return function (RequestInterface $request, array $options) use ($handler, $credential, $signer) {
                $authorization = $signer->calcCosRequestAuthorization(
                    $request->getMethod(),
                    rawurldecode($request->getUri()->getPath()),
                    $request->getHeaders()
                );

                return $handler(
                    $request->withHeader("Authorization", $authorization),
                    $options
                );
            };
        };
    }
}
