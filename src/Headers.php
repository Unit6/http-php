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
 * Headers
 *
 * This class represents a collection of HTTP headers
 * that is used in both the HTTP request and response objects.
 * It also enables header name case-insensitivity when
 * getting or setting a header value.
 *
 * Each HTTP header can have multiple values. This class
 * stores values into an array for each header name. When
 * you request a header value, you receive an array of values
 * for that header.
 */
class Headers extends Collection implements HeadersInterface
{
    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    protected static $special = [
        // PHP removed these (per CGI/1.1 specification[1]) from the HTTP_ match group.
        'CONTENT_TYPE',
        'CONTENT_LENGTH',
        // When doing HTTP authentication these are provided by the user.
        'PHP_AUTH_USER',
        'PHP_AUTH_PW',
        // When doing Digest HTTP authentication this variable is
        // set to the 'Authorization' header sent by the client
        // (which you should then use to make the appropriate validation).
        'PHP_AUTH_DIGEST',
        // When doing HTTP authentication this variable is set to the authentication type.
        'AUTH_TYPE',
    ];

    /**
     * Create new headers collection with data extracted from
     * the application Environment object
     *
     * @param string|array $headers Header server variables.
     *
     * @return self
     */
    public static function parse($headers)
    {
        $data = [];

        if (is_string($headers)) {
            $headers = preg_replace('/^\r\n/m', '', $headers);
            $headers = preg_replace('/\r\n\s+/m', ' ', $headers);
            preg_match_all('/^([^: ]+):\s(.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers . "\r\n", $matches);

            $data = [];

            foreach ($matches[1] as $key => $value) {
                $data[$value] = (isset($result[$value])
                    ? $result[$value] . "\n" : '') . $matches[2][$key];
            }
        } elseif (is_array($headers)) {
            foreach ($headers as $key => $value) {
                $key = strtoupper($key);

                if (in_array($key, static::$special) || strpos($key, 'HTTP_') === 0) {
                    if ($key !== 'HTTP_CONTENT_LENGTH') {
                        $data[$key] =  $value;
                    }
                }
            }
        }

        return new static($data);
    }

    public function toList()
    {
        $rows = parent::all();

        $headers = [];

        foreach ($rows as $row) {
            $headers[] = $row['originalKey'] . ': '. implode('; ', $row['value']);
        }

        return $headers;
    }

    /**
     * Return array of HTTP header names and values.
     * This method returns the _original_ header name
     * as specified by the end user.
     *
     * @return array
     */
    public function all()
    {
        $rows = parent::all();
        $headers = [];

        foreach ($rows as $row) {
            $headers[$row['originalKey']] = $row['value'];
        }

        return $headers;
    }

    /**
     * Set HTTP header value
     *
     * This method sets a header value. It replaces
     * any values that may already exist for the header name.
     *
     * @param string $key   The case-insensitive header name
     * @param string $value The header value
     *
     * @return void
     */
    public function set($key, $value)
    {
        if ( ! is_array($value)) {
            $value = [$value];
        }

        parent::set($this->normalizeKey($key), [
            'value' => $value,
            'originalKey' => $key
        ]);
    }

    /**
     * Get HTTP header value
     *
     * @param  string  $key     The case-insensitive header name
     * @param  mixed   $default The default value if key does not exist
     *
     * @return string[]
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return parent::get($this->normalizeKey($key))['value'];
        }

        return $default;
    }

    /**
     * Get HTTP header key as originally specified
     *
     * @param  string   $key     The case-insensitive header name
     * @param  mixed    $default The default value if key does not exist
     *
     * @return string
     */
    public function getOriginalKey($key, $default = null)
    {
        if ($this->has($key)) {
            return parent::get($this->normalizeKey($key))['originalKey'];
        }

        return $default;
    }

    /**
     * Add HTTP header value
     *
     * This method appends a header value. Unlike the set() method,
     * this method _appends_ this new value to any values
     * that already exist for this header name.
     *
     * @param string       $key   The case-insensitive header name
     * @param array|string $value The new header value(s)
     *
     * @return void
     */
    public function add($key, $value)
    {
        $oldValues = $this->get($key, []);
        $newValues = is_array($value) ? $value : [$value];
        $this->set($key, array_merge($oldValues, array_values($newValues)));
    }

    /**
     * Does this collection have a given header?
     *
     * @param  string $key The case-insensitive header name
     *
     * @return bool
     */
    public function has($key)
    {
        return parent::has($this->normalizeKey($key));
    }

    /**
     * Remove header from collection
     *
     * @param  string $key The case-insensitive header name
     *
     * @return void
     */
    public function remove($key)
    {
        parent::remove($this->normalizeKey($key));
    }

    /**
     * Normalize header name
     *
     * This method transforms header names into a
     * normalized form. This is how we enable case-insensitive
     * header names in the other methods in this class.
     *
     * @param  string $key The case-insensitive header name
     *
     * @return string Normalized header name
     */
    public function normalizeKey($key)
    {
        $key = strtr(strtolower($key), '_', '-');

        if (strpos($key, 'http-') === 0) {
            $key = substr($key, 5);
        }

        return $key;
    }
}
