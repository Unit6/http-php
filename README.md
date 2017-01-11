# unit6/http

A simple [PSR-7 compliant](http://www.php-fig.org/psr/psr-7/) HTTP class for PHP using cURL for transport.

Quick examples can be run on the command line. You'll need to run PHP's built-in server in one terminal and issue the request in the other.

```
$ php -S localhost:8000 example/ServerRequest.php
$ php example/ClientRequest.php
```

**Transport Layer Security**

If you're having trouble with TLS endpoints, you need to ensure your PHP setup contains a certificate authority (CA) bundle otherwise (by default) it will reject all certificates as unverifiable.

1. Download a [CA Extract](https://curl.haxx.se/docs/caextract.html) from Mozilla in PEM format. 

2. Either define it per request using the option `CURLOPT_CAINFO` with [`curl_setopt`](http://php.net/curl-setopt) **or** you can define a path to your CA globally for PHP using the `php.ini` directive [`curl.cainfo`](http://php.net/curl.configuration). 

3. Refer to the `libcurl` documentation on [`CURLOPT_SSL_VERIFYPEER`](https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYPEER.html).


## Requirements

Following required dependencies:

- PHP 5.6.x
- cURL 7.37.x

## TODO

- Unit tests.
- [RFC 6648](https://tools.ietf.org/html/rfc6648): Deprecating the "X-" prefix in headers.
- [RFC 7239](https://tools.ietf.org/html/rfc7239): Proposed forwarded HTTP extension standard covers conventions where non-standard header fields such as `X-Forwarded-For`, `X-Forwarded-By`, and `X-Forwarded-Proto` are used. Instead it proposes: `Forwarded: for=192.0.2.43, for=198.51.100.17;by=203.0.113.60;proto=http;host=example.com`

## License

This project is licensed under the MIT license -- see the `LICENSE.txt` for the full license details.

## Acknowledgements

Some inspiration has been taken from the following projects:

- [DASPRiD/Dash](https://github.com/DASPRiD/Dash)
- [fruux/sabre-http](https://github.com/fruux/sabre-http)
- [guzzle/psr7](https://github.com/guzzle/psr7)
- [lanthaler/IRI](https://github.com/lanthaler/IRI)
- [onoi/http-request](https://github.com/onoi/http-request)
- [phly/http](https://github.com/phly/http)
- [php-mod/curl](https://github.com/php-mod/curl)
- [ptcong/php-http-client](https://github.com/ptcong/php-http-client)
- [shuber/curl](https://github.com/shuber/curl)
- [slimphp/Slim-Http](https://github.com/slimphp/Slim-Http)