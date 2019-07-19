<?php


namespace TencentCloudClient;

use Closure;
use Psr\Http\Message\RequestInterface;
use TencentCloudClient\Exception\TCException;
use TencentCloudClient\Http\HttpClientProfile;

class Middleware
{
    /**
     * @param string $key
     * @param string $value
     *
     * @return Closure
     */
    static function addHeader(string $key, string $value)
    {
        return function (callable $handler) use ($key, $value) {
            return function (RequestInterface $request, array $options) use ($handler, $key, $value) {
                return $handler(
                    $request->withHeader($key, $value),
                    $options
                );
            };
        };
    }
    /**
     *
     * @return Closure
     */
    static function addTCTimestampHeader()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                return $handler(
                    $request->withHeader("X-TC-Timestamp", Utils::timestamp()),
                    $options
                );
            };
        };
    }

    /**
     * @param $signer
     * @param $signMethod
     * @return Closure
     */
    static function addTC3AuthorizationHeader(Signer $signer, string $signMethod)
    {
        return function (callable $handler) use ($signer, $signMethod) {
            return function (RequestInterface $request, array $options) use ($handler, $signer, $signMethod) {
                $headers = [];
                foreach ($request->getHeaders() as $header => $value) {
                    $headers[$header] = join(";", $request->getHeaders()[$header]);
                }

                switch ($signMethod) {
                    case HttpClientProfile::$SIGN_TC3_SHA256:
                        $authorization = $signer->calcTC3Authorization(
                            $request->getMethod(),
                            $request->getUri()->getHost(),
                            $request->getUri()->getPath(),
                            $headers,
                            $request->getUri()->getQuery(),
                            $request->getBody()->getContents()
                        );
                        break;
                    case HttpClientProfile::$SIGN_HMAC_SHA256:
                    case HttpClientProfile::$SIGN_HMAC_SHA1:
                    default:
                        throw new TCException("UNSUPPORTED_SIGN_METHOD");
                        break;
                }

                return $handler(
                    $request->withHeader("Authorization", $authorization),
                    $options
                );
            };
        };
    }

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
