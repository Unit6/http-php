<?php
/*
 * This file is part of the HTTP package.
 *
 * (c) Unit6 <team@unit6websites.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// set the default timezone
date_default_timezone_set('UTC');

require realpath(__DIR__ . '/../vendor/autoload.php');

define('HOST', 'localhost');
define('PORT', '9000');
define('ENDPOINT', 'http://' . HOST . ':' . PORT . '/');
define('TARGET', realpath(__DIR__ . '/../example/ServerRequest.php'));
define('USER_AGENT', sprintf('unit6/http 1.0 (%s %s) (php/%s; sapi/%s; curl/%s)',
    php_uname('s'),
    php_uname('m'),
    phpversion(),
    php_sapi_name(),
    curl_version()['version']
));


// Execute the command and store the process ID.
$output = [];
$command = sprintf('php -S %s:%d %s >/dev/null 2>&1 & echo $!', HOST, PORT, TARGET);
exec($command, $output);
$pid = (int) $output[0];

echo PHP_EOL . sprintf('[%s] Web server started on %s (PID: %d)', date('Y-m-d H:i:s'), ENDPOINT, $pid) . PHP_EOL . PHP_EOL;

// Kill the web server when the process ends.
register_shutdown_function(function () use ($pid) {
    exec(sprintf('kill %d', $pid));
    echo  PHP_EOL . sprintf('[%s] Web server stopped (PID: %d)', date('Y-m-d H:i:s'), $pid) . PHP_EOL . PHP_EOL;
});