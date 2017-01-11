<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unit6\HTTP\Client;

use UnexpectedValueException;

use Unit6\HTTP\AbstractResponse;
use Unit6\HTTP\Headers;
use Unit6\HTTP\HeadersInterface;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Incoming HTTP Server Response
 *
 * Representation of an incoming, server-side HTTP response.
 */
class Response extends AbstractResponse
{
    /**
     * cURL response error
     *
     * @var array
     */
    public static $error;

    /**
     * cURL response info
     *
     * @var array
     */
    public static $info;

    /**
     * Create new HTTP client response.
     *
     * @param int                   $status  The response status code.
     * @param HeadersInterface|null $headers The response headers.
     * @param StreamInterface|null  $body    The response body.
     */
    public function __construct($statusCode = 200, HeadersInterface $headers = null, StreamInterface $body = null)
    {
        parent::__construct($statusCode, $headers, $body);
    }

    /**
     * Normalize a cURL response to ResponseInterface
     *
     * @param array           $error cURL request error
     * @param array           $info  cURL request information
     * @param StreamInterface $body  Response stream
     *
     * @return self
     */
    public static function parse(array $error, array $info, StreamInterface $body)
    {
        static::$info = $info;
        static::$error = $error;

        if ($error['code'] !== CURLE_OK) {
            throw new UnexpectedValueException('cURL error; ' . $error['message'], $error['code']);
        }

        if ( ! isset($info['http_code']) || $info['http_code'] === 0) {
            throw new UnexpectedValueException('cURL error; No HTTP status code was returned');
        }

        $statusCode = $info['http_code'];
        $headers = null;

        // read the response headers block.
        if (isset($info['header_size']) && $info['header_size'] > 0) {
            $body->rewind();
            $responseHeaders = $body->read($info['header_size']);
            // convert response headers to collection.
            $headers = Headers::parse($responseHeaders);
        }

        $response = new self($statusCode, $headers, $body);

        return $response;
    }

    /**
     * Get Last Response Info
     *
     * Return cURL list of info from request.
     *
     * @return array
     */
    public function getInfo()
    {
        return static::$info;
    }

    /**
     * Set Last Response Info
     *
     * @param array $info
     *
     * @return void
     */
    public function setInfo($info)
    {
        static::$info = $info;
    }

    /**
     * Get Last Response Error
     *
     * Return last cURL error.
     *
     * @return array
     */
    public function getError()
    {
        return static::$error;
    }

    /**
     * Set Last Response Error
     *
     * @param array $error
     *
     * @return void
     */
    public function setError($error)
    {
        static::$error = $error;
    }
}