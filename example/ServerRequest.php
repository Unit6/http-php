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

use Unit6\HTTP;

$request = HTTP\Environment::getRequest();

$uri = $request->getUri();
$requestBody = $request->getBody();

$response = [];
$response['uri'] = sprintf('%s', $uri);
$response['contents'] = json_decode($requestBody->getContents());

foreach ($_SERVER as $key => $value) $response[strtolower($key)] = $value;

echo json_encode($response);
