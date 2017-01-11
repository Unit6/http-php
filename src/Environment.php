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

/**
 * Environment
 *
 * This class decouples from the global PHP environment and permits the
 * the description of server environmental variables in a request.
 */
class Environment extends Collection
{
    /**
     * Environment Header
     *
     * @var HeadersInterface
     */
    protected $headers;

    /**
     * Create new collection
     *
     * @param array $items Pre-populate collection with this key-value array
     */
    public function __construct(array $items = [])
    {
        parent::__construct($items);

        $this->headers = Headers::parse($this->all());
    }

    public static function getRequest(array $settings = [])
    {
        $environment = new self((empty($settings) ? $_SERVER : $settings));

        $serverParams = $environment->all();

        $uri = $environment->getURI();
        $headers = $environment->getHeaders();
        $cookies = $environment->getCookies();
        $body = $environment->getBody();
        $uploadedFiles = $environment->getFiles();
        $method = $environment->getMethod();

        $request = new Server\Request($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        if ($method === 'POST' &&
            in_array($request->getMediaType(), [
                'application/x-www-form-urlencoded',
                'multipart/form-data'
            ])
        ) {
            // parsed body must be $_POST
            $request = $request->withParsedBody($_POST);
        }

        return $request;
    }

    /**
     * Get headers from environment
     *
     * @return HeadersInterface
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return cookies from environment Cookie header
     *
     * @return CookiesInterface
     */
    public function getCookies()
    {
        $cookieHeader = $this->headers->get('Cookie', []);

        return Cookies::parseHeader($cookieHeader);
    }

    /**
     * Read from STDIN to stream
     *
     * Provides a PSR-7 implementation of a reusable raw request body
     *
     * @return StreamInterface
     */
    public function getBody()
    {
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);

        return new Body($stream);
    }

    /**
     * Attempts to retrieve server $_FILES.
     *
     * @return UploadedFile
     */
    public function getFiles()
    {
        $files = [];

        if ($this->has('UPLOADED_FILES')) {
            $files = $this->get('UPLOADED_FILES');
        } elseif (isset($_FILES)) {
            $files = $_FILES;
        }

        return UploadedFile::parse($files);
    }

    /**
     * Get HTTP method
     *
     * @return string
     */
    public function getMethod()
    {
        $default = (PHP_SAPI === 'cli' ? 'GET' : null);

        if ($this->has('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            $method = $this->get('HTTP_X_HTTP_METHOD_OVERRIDE', $default);
        } else {
            $method = $this->get('REQUEST_METHOD', $default);
        }

        return strtoupper($method);
    }

    /**
     * Create new URI from environment.
     *
     * @return URI
     */
    public function getURI()
    {
        $parts = [];

        // Proxy Protocol Override
        if ($this->has('HTTP_X_FORWARDED_PROTO')) {
            $scheme = $this->get('HTTP_X_FORWARDED_PROTO');
            if (strtolower($scheme) === 'https') {
                $this->set('SERVER_PORT', 443);
                $this->set('HTTPS', 'on');
            }
        }

        // Scheme
        $secure = $this->get('HTTPS');
        $parts['scheme'] = (empty($secure) || $secure === 'off' ? 'http' : 'https');

        // Authority: Username and password
        $parts['user'] = $this->get('PHP_AUTH_USER', '');
        $parts['pass'] = $this->get('PHP_AUTH_PW', '');

        // Authority: Host
        if ($this->has('HTTP_HOST')) {
            $host = $this->get('HTTP_HOST');
        } else {
            $host = $this->get('SERVER_NAME');
        }

        // Authority: Port
        $port = (int) $this->get('SERVER_PORT', 80);

        // Authority: Parse Host for Port.
        if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
            $host = $matches[1];
            if ($matches[2]) {
                $port = (int) substr($matches[2], 1);
            }
        } else {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                $port = (int) substr($host, $pos + 1);
                $host = strstr($host, ':', true);
            }
        }

        // Authority: Port override.
        if ($this->has('HTTP_X_FORWARDED_PORT')) {
            $port = (int) $this->get('HTTP_X_FORWARDED_PORT');
        }

        $parts['host'] = $host;
        $parts['port'] = $port;

        // Determine script path.

        // Path
        $requestUri = parse_url($this->get('REQUEST_URI'), PHP_URL_PATH);
        $requestScriptName = parse_url($this->get('SCRIPT_NAME'), PHP_URL_PATH);
        $requestScriptNameDir = dirname($requestScriptName);
        $basePath = '';

        $path = $requestUri;

        if (stripos($requestUri, $requestScriptName) === 0) {
            $basePath = $requestScriptName;
        } elseif ($requestScriptNameDir !== '/' &&
            stripos($requestUri, $requestScriptNameDir) === 0) {
            $basePath = $requestScriptNameDir;
        }

        // Offset the path, if they are different.
        if ($basePath && stripos($requestUri, $basePath) !== 0) {
            $path = ltrim(substr($requestUri, strlen($basePath)), '/');
        }

        $parts['path'] = $path;
        $parts['query'] = $this->get('QUERY_STRING', '');
        $parts['fragment'] = '';

        return URI::parse($parts);
    }

    /**
     * Create mock environment
     *
     * @param  array $userData Array of custom environment keys and values
     *
     * @return array
     */
    public static function mock(array $userData = [])
    {
        return array_merge([
            'SERVER_PROTOCOL'      => 'HTTP/1.1',
            'REQUEST_METHOD'       => 'GET',
            'SCRIPT_NAME'          => '',
            'REQUEST_URI'          => '',
            'QUERY_STRING'         => '',
            'SERVER_NAME'          => 'localhost',
            'SERVER_PORT'          => 80,
            'HTTP_HOST'            => 'localhost',
            'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT'      => 'Slim Framework',
            'REMOTE_ADDR'          => '127.0.0.1',
            'REQUEST_TIME'         => time(),
            'REQUEST_TIME_FLOAT'   => microtime(true),
        ], $userData);
    }
}
