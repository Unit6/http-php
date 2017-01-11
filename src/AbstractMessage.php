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

use InvalidArgumentException;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP Message
 *
 * Requests from a client to a server and responses
 * from a server to a client involve the exchange of
 * different HTTP messages.
 */
abstract class AbstractMessage implements MessageInterface
{
    /**
     * HTTP Version (1.0 or 1.1)
     *
     * HTTP protocol version as a string
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * HTTP Versions
     *
     * Valid list of HTTP protocol version as a string
     *
     * @var string[]
     */
    public static $protocolVersionOptions = ['1.0', '1.1'];

    /**
     * Message body
     *
     * This should be a stream resource
     *
     * @var StreamInterface
     */
    protected $body;

    /**
     * Contains the list of HTTP headers
     *
     * @var HeadersInterface
     */
    protected $headers;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * @param string $version HTTP protocol version
     *
     * @return self
     */
    public function withProtocolVersion($version)
    {
        if ( ! in_array($version, self::$protocolVersionOptions)) {
            throw new InvalidArgumentException('Invalid HTTP protocol version. Supported: ' . implode(', ', self::$protocolVersionOptions));
        }

        $clone = clone $this;
        $clone->protocolVersion = $verison;

        return $clone;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the
     * wire, and each value is an array of strings associated with the header.
     *
     * Each key MUST be a header name, and each value MUST be an
     * array of strings for that header.
     *
     * @return array List of message headers.
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return bool Returns true if any header names match the given
     *              header name using a case-insensitive string comparison.
     *              Returns false if no matching header name is found
     *              in the message.
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return string[] An array of string values as provided for the given header.
     *                  If the header does not appear in the message, this method
     *                  MUST return an empty array.
     */
    public function getHeader($name)
    {
        return $this->headers->get($name, []);
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * @param string $name Case-insensitive header field name.
     *
     * @return string A string of values as provided for the given header
     *                concatenated together using a comma.
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * @param string          $name  Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     *
     * @return self
     *
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $this->parseHeaderName($name);
        $this->parseHeaderValue($value);

        $clone = clone $this;
        $clone->headers->set($name, $value);

        return $clone;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * @param string          $name  Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     *
     * @return self
     *
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        $this->parseHeaderName($name);
        $this->parseHeaderValue($value);

        $clone = clone $this;
        $clone->headers->add($name, $value);

        return $clone;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name Case-insensitive header field name to remove.
     *
     * @return self
     */
    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->remove($name);

        return $clone;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        $body = $this->body;

        /*
        if (is_string($body) || is_null($body)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $body);
            rewind($stream);
            return $stream;
        }
        */

        return $body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * @param StreamInterface $body Body.
     *
     * @return self
     *
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * Validate header name.
     *
     * @param string $name Case-insensitive header field name to add.
     *
     * @return void
     */
    private function parseHeaderName(&$name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Header name is required');
        }

        if ( ! is_string($name)) {
            throw new InvalidArgumentException('Header name must be a string');
        }
    }

    /**
     * Validate header value.
     *
     * @param string|string[] $value Header value(s).
     *
     * @return void
     */
    private function parseHeaderValue(&$value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Header value is required');
        }

        $values = (array) $value;

        foreach ($values as $line) {
            if ( ! is_string($line)) {
                throw new InvalidArgumentException('Header value must be a string');
            }
        }

        $value = $values;
    }
}