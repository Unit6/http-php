<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Unit6\HTTP\Body;
use Unit6\HTTP\Client;
use Unit6\HTTP\Headers;
use Unit6\HTTP\URI;

/**
 * Test Client Requests
 *
 * Check for correct operation of the standard features.
 */
class ClientRequestTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    public function testCreateClientRequestWithBody()
    {
        $data = [
            'foo' => 'bar',
        ];

        $requestBody = Body::toJSON($data);

        $headers = new Headers();
        $headers->set('Content-Type', 'application/json');
        $headers->set('Content-Length', $requestBody->getSize());
        $headers->set('Accept', 'application/json');
        $headers->set('X-PHP-Version', phpversion());

        $request = new Client\Request('GET', 'http://www.example.org', $headers, $requestBody);

        $this->assertTrue($request->getBody() instanceof Body);
        $this->assertEquals($requestBody->getSize(), $request->getHeaderLine('Content-Length'));
    }

    public function testCreateClientRequestWithHeaders()
    {
        $headers = new Headers();
        $headers->set('Content-Type', 'application/json');
        $headers->set('Accept', 'application/json');
        $headers->set('X-PHP-Version', phpversion());

        $request = new Client\Request('GET', 'http://www.example.org', $headers);

        $this->assertNotEmpty($request->getHeaders());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testSupportedMethods()
    {
        $methods = Client\Request::$methodOptions;

        $this->assertNotEmpty($methods);

        return $methods;
    }

    /**
     * @depends testSupportedMethods
     */
    public function testCreateClientRequestWithStaticMethod(array $methods)
    {
        foreach ($methods as $method) {
            $fn = 'Unit6\HTTP\Client\Request::' . $method;
            $request = call_user_func($fn, 'http://www.example.org');
            $this->assertTrue($request instanceof Client\Request);
        }
    }

    /**
     * @depends testSupportedMethods
     */
    public function testCreateClientRequestWithSupportedMethod(array $methods)
    {
        foreach ($methods as $method) {
            $request = new Client\Request($method, 'http://www.example.org');
            $this->assertTrue($request instanceof Client\Request);
        }
    }

    public function testCreateClientRequestWithInstanceURI()
    {
        $uri = new URI('http', 'www.example.org');

        $request = new Client\Request('GET', $uri);

        $this->assertTrue($request->getURI() instanceof URI);
    }

    public function testCreateClientRequestWithStringURI()
    {
        $request = new Client\Request('GET', 'http://www.example.org');

        $this->assertTrue($request->getURI() instanceof URI);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateClientRequestExceptionWithInvalidURI()
    {
        $request = new Client\Request('GET', 'foo');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateClientRequestExceptionWithInvalidHTTPMethod()
    {
        $request = new Client\Request('FOO', 'http://www.example.org');
    }

    public function testRequestWithHTTP()
    {
        $request = new Client\Request('GET', 'http://example.org');

        $this->assertInstanceOf('Unit6\HTTP\Client\Request', $request);

        $response = $request->send();

        $responseBody = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $this->assertEquals(1270, strlen($responseBody->getContents()));

        return $request;
    }

    public function testRequestWithHTTPS()
    {
        $request = new Client\Request('GET', 'https://example.org');

        $this->assertInstanceOf('Unit6\HTTP\Client\Request', $request);

        $response = $request->send();

        $responseBody = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $this->assertEquals(1270, strlen($responseBody->getContents()));

        return $request;
    }

    public function testBuildExampleGetRequest()
    {
        $data = [
            'foo' => 'bar',
        ];

        $requestBody = Body::toJSON($data);

        $headers = new Headers();
        $headers->set('Content-Type', 'application/json');
        $headers->set('Content-Length', $requestBody->getSize());
        $headers->set('Accept', 'application/json');
        $headers->set('X-PHP-Version', phpversion());

        $request = (new Client\Request('GET', ENDPOINT, $headers))
            ->withUserAgent(USER_AGENT)
            ->withReferer('http://localhost/referer')
            ->withBody($requestBody);

        $this->assertInstanceOf('Unit6\HTTP\Client\Request', $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals(USER_AGENT, $request->getUserAgent());

        return $request;
    }

    /**
     * @depends testBuildExampleGetRequest
     */
    public function testSendExampleGetRequest(Client\Request $request)
    {
        $options = [];

        try {
            $response = $request->send($options);
        } catch (UnexpectedValueException $e) {
            var_dump($e->getMessage()); exit;
        }

        $responseBody = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $data = json_decode($responseBody->getContents(), true);

        $this->assertEquals('GET', $data['request_method']);
        $this->assertEquals(['foo' => 'bar'], $data['contents']);
        $this->assertEquals(ENDPOINT, $data['uri']);
    }

    public function testBuildExampleGetRequestUsingStatic()
    {
        $data = [
            'foo' => 'bar',
        ];

        $requestBody = Body::toJSON($data);

        $headers = new Headers();
        $headers->set('Content-Type', 'application/json');
        $headers->set('Content-Length', $requestBody->getSize());
        $headers->set('Accept', 'application/json');
        $headers->set('X-PHP-Version', phpversion());

        $request = Client\Request::get(ENDPOINT, $headers, $requestBody)
            ->withUserAgent(USER_AGENT)
            ->withReferer('http://localhost/referer')
            ->withBody($requestBody);

        $this->assertInstanceOf('Unit6\HTTP\Client\Request', $request);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals(USER_AGENT, $request->getUserAgent());

        return $request;
    }

    /**
     * @depends testBuildExampleGetRequestUsingStatic
     */
    public function testSendExampleGetRequestFromStatic(Client\Request $request)
    {
        $options = [];

        try {
            $response = $request->send($options);
        } catch (UnexpectedValueException $e) {
            var_dump($e->getMessage()); exit;
        }

        $responseBody = $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $data = json_decode($responseBody->getContents(), true);

        $this->assertEquals('GET', $data['request_method']);
        $this->assertEquals(['foo' => 'bar'], $data['contents']);
        $this->assertEquals(ENDPOINT, $data['uri']);
    }
}