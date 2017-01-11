<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Unit6\HTTP\Headers;

/**
 * Test Headers
 *
 * Check for correct operation of the headers collection.
 */
class HeadersTest extends PHPUnit_Framework_TestCase
{
    public function testAddBasicHeaders()
    {
        $headers = new Headers();
        $headers->set('Content-Type', 'application/json');
        $headers->set('Accept', 'application/json');
        $headers->set('X-PHP-Version', phpversion());

        $this->assertInstanceOf('Unit6\HTTP\Headers', $headers);

        $headersList = $headers->toList();

        $this->assertNotEmpty($headersList);
        $this->assertCount(3, $headersList);

        return $headers;
    }

    /**
     * @depends testAddBasicHeaders
     */
    public function testGetHeaderKey(Headers $headers)
    {
        $this->assertTrue($headers->has('x-php-version'));
        $this->assertEquals(phpversion(), $headers->get('x-php-version')[0]);
    }

    /**
     * @depends testAddBasicHeaders
     */
    public function testGetInvalidHeaderKeyWithDefault(Headers $headers)
    {
        $this->assertFalse($headers->has('x-foo'));
        $this->assertEquals('bar', $headers->get('x-foo', 'bar'));
    }

    /**
     * @depends testAddBasicHeaders
     */
    public function testAddRemoveHeaderKey(Headers $headers)
    {
        $this->assertFalse($headers->has('x-foo'));
        $headers->add('x-foo', 'bar');
        $this->assertTrue($headers->has('x-foo'));
        $headers->remove('x-foo');
        $this->assertFalse($headers->has('x-foo'));
    }

    /**
     * @depends testAddBasicHeaders
     */
    public function testGetOriginalHeaderKey(Headers $headers)
    {
        $this->assertEquals('X-PHP-Version', $headers->getOriginalKey('x-php-version'));
    }

    /**
     * @depends testAddBasicHeaders
     */
    public function testGetAll(Headers $headers)
    {
        $headersAll = $headers->all();

        $this->assertCount(3, $headersAll);
        $this->assertArrayHasKey('Content-Type', $headersAll);
        $this->assertArrayHasKey('Accept', $headersAll);
        $this->assertArrayHasKey('X-PHP-Version', $headersAll);
    }
}