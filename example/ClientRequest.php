<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require realpath(dirname(__FILE__) . '/../autoload.php');
require realpath(dirname(__FILE__) . '/../vendor/autoload.php');

use Unit6\HTTP\Body;
use Unit6\HTTP\Client;
use Unit6\HTTP\Headers;

$userAgent = sprintf(
    'unit6/http 1.0 (%s %s) (php/%s; sapi/%s; curl/%s)',
    php_uname('s'),
    php_uname('m'),
    phpversion(),
    php_sapi_name(),
    curl_version()['version']
);

$data = [
    'foo' => 'bar',
];

$requestBody = Body::toJSON($data);

$headers = new Headers();
$headers->set('Content-Type', 'application/json');
$headers->set('Content-Length', $requestBody->getSize());
$headers->set('Accept', 'application/json');
$headers->set('X-PHP-Version', phpversion());

$options = [];

$uri = 'http://www.example.org/';
#$uri = 'https://www.example.org/';
#$uri = 'http://localhost:8000/';


/*
$request = Client\Request::get($uri, $headers, $requestBody)
    ->withUserAgent($userAgent)
    ->withReferer('http://localhost/referer')
    ->withBody($requestBody);
*/

#var_dump($request->getMethod(), $request->getUserAgent()); exit;

$request = (new Client\Request('GET', $uri, $headers))
    ->withUserAgent($userAgent)
    ->withReferer('http://localhost/referer')
    ->withBody($requestBody);

try {
    $response = $request->send($options);
} catch (UnexpectedValueException $e) {
    var_dump($e->getMessage()); exit;
}

$responseBody = $response->getBody();

echo 'Status Code: ' . $response->getStatusCode() . PHP_EOL;
echo 'Reason Phrase: ' . $response->getReasonPhrase() . PHP_EOL;
echo 'Contents: ' . PHP_EOL . $responseBody->getContents() . PHP_EOL;
