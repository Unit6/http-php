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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Testing Library PSR-7 Compliance
 *
 * Check for compatibility with PSR-7 HTTP Message.
 */
class LibraryPSR7ComplianceTest extends \PHPUnit_Framework_TestCase
{
    private $request;

    public function setUp()
    {
        $this->request = null;
    }

    public function tearDown()
    {
        unset($this->request);
    }

    public function testUploadFileImplementsPSR7Interfaces()
    {
        $uploadedFile = new UploadedFile('foobar.txt');
        $this->assertInstanceOf('Psr\Http\Message\UploadedFileInterface', $uploadedFile);
    }

    public function testURIImplementsPSR7Interfaces()
    {
        $uri = new URI('http', 'example.org');
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
    }

    public function testStreamImplementsPSR7Interfaces()
    {
        $stream = new Stream(fopen('php://temp', 'w+'));
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
    }

    public function testClientRequestClassImplementsPSR7Interfaces()
    {
        $request = new Client\Request('GET', 'http://www.example.org');
        $this->assertInstanceOf('Psr\Http\Message\MessageInterface', $request);
        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $request);
    }

    public function testClientResponseClassImplementsPSR7Interfaces()
    {
        $response = new Client\Response(200);
        $this->assertInstanceOf('Psr\Http\Message\MessageInterface', $response);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    public function testServerRequestClassImplementsPSR7Interfaces()
    {
        $request = Environment::getRequest();
        $this->assertInstanceOf('Psr\Http\Message\MessageInterface', $request);
        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $request);
        $this->assertInstanceOf('Psr\Http\Message\ServerRequestInterface', $request);
    }

    public function testServerResponseClassImplementsPSR7Interfaces()
    {
        $response = new Server\Response(200);
        $this->assertInstanceOf('Psr\Http\Message\MessageInterface', $response);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }
}