<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unit6\HTTP;

/**
 * HTTP Message Body
 *
 * This class represents an HTTP message body and encapsulates a
 * streamable resource according to the PSR-7 standard.
 */
class Body extends Stream
{
    /**
     * Create a new Stream.
     *
     * @param resource $stream A PHP resource handle.
     *
     * @throws InvalidArgumentException If argument is not a resource.
     */
    public function __construct($stream = null)
    {
        if ( ! is_resource($stream)) {
            $stream = fopen('php://temp', 'w+');
            #stream_copy_to_stream(fopen('php://input', 'r'), $stream);
            #rewind($stream);
        }

        parent::__construct($stream);
    }

    /**
     * Get body payload
     *
     * @return string
     */
    public function getPayload()
    {
        $this->rewind();

        return $this->getContents();
    }

    /**
     * Return a body with URL encoded query string
     *
     * Generates a URL-encoded query string from the associative (or indexed) array provided.
     *
     * @see http://php.net/http_build_query
     * @see http://php.net/parse_str
     *
     * @param array $data
     *
     * @return self
     */
    public static function toQuery(array $data = [])
    {
        $query = http_build_query($data);

        $body = new self();
        $body->write($query);

        return $body;
    }

    /**
     * Return a body with JSON encoded string
     *
     * Returns a string containing the JSON representation of value.
     *
     * @see http://php.net/json_encode
     * @see http://php.net/json_decode
     *
     * @param array $data
     *
     * @return self
     */
    public static function toJSON(array $data = [])
    {
        $json = json_encode($data);

        $body = new self();
        $body->write($json);

        return $body;
    }
}
