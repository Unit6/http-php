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

/**
 * Test Body
 *
 * Check for correct operation of the Body collection.
 */
class BodyTest extends PHPUnit_Framework_TestCase
{
    public function testCreateBodyQuery()
    {
        $data = [
            'foo' => 'bar',
        ];

        $body = Body::toQuery($data);

        $this->assertInstanceOf('Unit6\HTTP\Body', $body);

        $this->assertEquals(http_build_query(['foo' => 'bar']), sprintf('%s', $body));
    }

    public function testCreateBodyJSON()
    {
        $data = [
            'foo' => 'bar',
        ];

        $body = Body::toJSON($data);

        $this->assertInstanceOf('Unit6\HTTP\Body', $body);

        $this->assertEquals(json_encode(['foo' => 'bar']), sprintf('%s', $body));

        return $body;
    }

    /**
     * @depends testCreateBodyJSON
     */
    public function testGetBodyPayload(Body $body)
    {
        $this->assertEquals(json_encode(['foo' => 'bar']), $body->getPayload());
    }
}