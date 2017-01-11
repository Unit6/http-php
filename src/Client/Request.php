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

use InvalidArgumentException;
use RuntimeException;

use ReflectionClass;

use Unit6\HTTP\AbstractRequest;
use Unit6\HTTP\Body;
use Unit6\HTTP\Headers;
use Unit6\HTTP\HeadersInterface;
use Unit6\HTTP\URI;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Outgoing HTTP Client Request
 *
 * Representation of an outgoing, client-side request.
 */
class Request extends AbstractRequest
{
    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
     */
    public $options = [
        'CURLINFO_HEADER_OUT' => true,
        'CURLOPT_CONNECTTIMEOUT' => 0,
        #'CURLOPT_ENCODING' => 'gzip, deflate',
        'CURLOPT_HEADER' => true,
        'CURLOPT_TIMEOUT' => 400,
        'CURLOPT_BUFFERSIZE' => 4096
    ];

    /**
     * The file to read and write cookies to for requests
     *
     * @var string
     */
    protected $cookieFile;

    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
     */
    protected $followRedirects = true;

    /**
     * Stores resource handle for the current cURL request
     *
     * @var resource
     */
    protected $handle;

    /**
     * The referer header to send along with requests
     *
     * @var string
    **/
    protected $referer;

    /**
     * The client user agent to send along with requests
     *
     * @var string
    **/
    protected $userAgent = '';

    /**
     * The number of redirection requests to follow
     *
     * @var int
    **/
    protected $redirectLimit = 0;

    /**
     * Stores last request for cURL request
     *
     * @var array
     */
    protected $error = [];

    /**
     * Create new HTTP request.
     *
     * Adds a host header when none was provided and a host is defined in uri.
     *
     * @param string                $method  The request method
     * @param string|UriInterface   $uri     The request URI object
     * @param HeadersInterface|null $headers The request headers collection
     * @param StreamInterface|null  $body    The request stream
     */
    public function __construct($method, $uri, HeadersInterface $headers = null, StreamInterface $body = null)
    {
        $uri = $uri instanceof UriInterface ? $uri : URI::parse($uri);

        if (is_null($headers)) $headers = new Headers();
        if (is_null($body)) $body = new Body();

        parent::__construct($method, $uri, $headers, $body);
    }

    /**
     * Cleanup Request
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Use HTTP methods
     *
     * Convenience method to perform requests for particular HTTP methods
     *
     * @param string $name      Should be a valid HTTP method.
     * @param array  $arguments Arguments to pass the class.
     *
     * @return Request
     */
    public static function __callStatic($name, array $arguments)
    {
        static::parseMethod($name);

        // prepend the HTTP method to the list of arguments.
        array_unshift($arguments, $name);

        $reflect = new ReflectionClass(__CLASS__);
        $request = $reflect->newInstanceArgs($arguments);

        return $request;
    }

    /**
     * Open cURL handle.
     *
     * @return void
     */
    public function open()
    {
        if ( ! extension_loaded('curl')) {
            throw new RuntimeException('cURL library is not loaded');
        }

        $handle = curl_init();

        if ( ! is_resource($handle) || (get_resource_type($handle) !== 'curl')) {
            throw new RuntimeException('cURL handle could not be initialized');
        }

        $this->handle = $handle;
    }

    /**
     * Close any open cURL handles.
     *
     * @return void
     */
    public function close()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }

    /**
     * Get request cookie file
     *
     * @return string
     */
    public function getCookieFile()
    {
        return $this->cookieFile;
    }

    /**
     * Set cookie file
     *
     * Used within requests.
     *
     * @return self
     */
    public function withCookieFile($cookieFile)
    {
        $clone = clone $this;
        $clone->cookieFile = $cookieFile;

        return $clone;
    }

    /**
     * Get request redirect limit
     *
     * @return string
     */
    public function getRedirectLimit()
    {
        return $this->redirectLimit;
    }

    /**
     * Get request user agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set request user agent
     *
     * Used within requests.
     *
     * @return self
     */
    public function withUserAgent($userAgent)
    {
        $clone = clone $this;
        $clone->userAgent = $userAgent;

        return $clone;
    }

    /**
     * Get request referer
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set request Referer
     *
     * Used within requests.
     *
     * @return self
     */
    public function withReferer($referer)
    {
        $clone = clone $this;
        $clone->referer = $referer;

        return $clone;
    }

    /**
     * Set options for handle before send
     *
     * Apply all transport handle options ready for sending.
     *
     * @param array    $options Handle options for request
     * @param resource $handle  Handle for request.
     *
     * @return void
     */
    public function setHandle(&$handle, array $options = [])
    {
        // copy the options to avoid altering the defaults.
        $options = array_merge($this->options, $options);

        $method = $this->getMethod();

        $options['CURLOPT_CUSTOMREQUEST'] = $method;

        switch (strtoupper($method)) {
            case 'HEAD':
                // Do the download request without getting the body.
                $options['CURLOPT_NOBODY'] = true;
                break;
            case 'GET':
                // Forces GET if curl handle has been used previously.
                // Automatically sets CURLOPT_NOBODY to 0 and CURLOPT_UPLOAD to 0.
                $options['CURLOPT_HTTPGET'] = true;
                break;
            case 'POST':
                // Regular POST using "Content-Type: application/x-www-form-urlencoded"
                $options['CURLOPT_POST'] = true;
                break;
        }

        $body = $this->getBody();

        if ($body instanceof StreamInterface && $body->getSize() > 0) {
            if ( ! $this->hasHeader('Content-Length')) {
                $this->headers->set('Content-Length', $body->getSize());
            }

            $options['CURLOPT_POSTFIELDS'] = $body->getPayload();

            #$stream = fopen('php://temp', 'r+');
            #curl_setopt($handle, CURLOPT_INFILE, $stream);
        }

        $headers = $this->headers->toList();

        $options['CURLOPT_HTTPHEADER'] = $headers;

        if ($uri = $this->getUri()) {
            $options['CURLOPT_URL'] = sprintf('%s', $uri);
        }

        if ($userAgent = $this->getUserAgent()) {
            $options['CURLOPT_USERAGENT'] = $userAgent;
        }

        if ($referer = $this->getReferer()) {
            $options['CURLOPT_REFERER'] = $referer;
        }

        if ($cookieFile = $this->getCookieFile()) {
            $options['CURLOPT_COOKIEFILE'] = $cookieFile;
            $options['CURLOPT_COOKIEJAR'] = $cookieFile;
        }

        if ($redirectLimit = $this->getRedirectLimit()) {
            $options['CURLOPT_FOLLOWLOCATION'] = ($redirectLimit > 0);
        }

        // set this one last:
        // http://php.net/curl-setopt#99082
        $options['CURLOPT_RETURNTRANSFER'] = true;

        foreach ($options as $key => $value) {
            if ( ! defined($key)) {
                throw new InvalidArgumentException(sprintf('cURL error; Invalid option: "%s"', $key));
            }

            curl_setopt($handle, constant($key), $value);
        }
    }

    /**
     * Send Request
     *
     * Execute the client request by sending it via the transport handler.
     *
     * @return Client\Response
     */
    public function send(array $options = [])
    {
        $this->open();

        // copy the handle to avoid diluting the resource.
        $handle = curl_copy_handle( $this->handle );


        // response stream.
        $stream = fopen('php://temp', 'w+');
        $body = new Body($stream);

        $options['CURLOPT_FILE'] = $stream;

        $this->setHandle($handle, $options);

        if (curl_getinfo($handle, CURLINFO_EFFECTIVE_URL) === '') {
            throw new InvalidArgumentException('cURL error; Attempted request with invalid url');
        }

        curl_setopt($handle, CURLOPT_WRITEFUNCTION, function ($curl, $data) use ($body) {
            return $body->write($data);
        });

        curl_exec($handle);

        $info = curl_getinfo($handle);

        $error = [
            'code'    => curl_errno( $handle ),
            'message' => curl_error( $handle )
        ];

        return Response::parse($error, $info, $body);
    }
}