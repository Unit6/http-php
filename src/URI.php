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

use Psr\Http\Message\UriInterface;

/**
 * Value object representing a URI.
 *
 * This interface is meant to represent URIs according to RFC 3986 and to
 * provide methods for most common operations. Additional functionality for
 * working with URIs can be provided on top of the interface or externally.
 * Its primary use is for HTTP requests, but may also be used in other
 * contexts.
 *
 * Instances of this interface are considered immutable; all methods that
 * might change state MUST be implemented such that they retain the internal
 * state of the current instance and return an instance that contains the
 * changed state.
 *
 * Typically the Host header will be also be present in the request message.
 * For server-side requests, the scheme will typically be discoverable in the
 * server parameters.
 *
 * @link http://tools.ietf.org/html/rfc3986 (the URI specification)
 */
class URI implements UriInterface
{
    /**
     * URI scheme (without "://" suffix)
     *
     * @var string
     */
    protected $scheme = '';

    /**
     * Valid URI scheme
     *
     * @var string[]
     */
    protected static $schemeOptions = ['', 'https', 'http'];

    /**
     * URI user
     *
     * @var string
     */
    protected $user = '';

    /**
     * URI password
     *
     * @var string
     */
    protected $password = '';

    /**
     * URI host
     *
     * @var string
     */
    protected $host = '';

    /**
     * URI port number
     *
     * @var null|int
     */
    protected $port;

    /**
     * URI path
     *
     * @var string
     */
    protected $path = '';

    /**
     * URI query string (without "?" prefix)
     *
     * @var string
     */
    protected $query = '';

    /**
     * URI fragment string (without "#" prefix)
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * Create new URI.
     *
     * @param string $scheme   URI scheme.
     * @param string $host     URI host.
     * @param int    $port     URI port number.
     * @param string $path     URI path.
     * @param string $query    URI query string.
     * @param string $fragment URI fragment.
     * @param string $user     URI user.
     * @param string $password URI password.
     */
    public function __construct($scheme, $host, $port = null, $path = '/', $query = '', $fragment = '', $user = '', $password = '')
    {
        self::parseScheme($scheme);
        self::parsePort($port);
        self::parseQuery($query);
        self::parseFragment($fragment);

        if (empty($path)) {
            $path = '/';
        } else {
            self::parsePath($path);
        }

        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Create a normalized URI instances from a URL.
     *
     * Attempt to use PHP's `parse_url` in the first instance,
     * before attempting to parse the string using the regular
     * expression as specified by RFC 3986.
     *
     * @see http://php.net/parse_url
     * @see http://tools.ietf.org/html/rfc3986#appendix-B
     *
     * @param array|string $uri The non-normalized URI.
     *
     * @return URI A normalized URI instance.
     */
    public static function parse($uri)
    {
        $parts = [];

        if (is_array($uri)) {
            $parts = $uri;
        } elseif (is_string($uri)) {
            $parts = static::fromString($uri);
        }

        $scheme   = (isset($parts['scheme'])   ? $parts['scheme']   : null);
        $host     = (isset($parts['host'])     ? $parts['host']     : null);
        $port     = (isset($parts['port'])     ? $parts['port']     : null);
        $path     = (isset($parts['path'])     ? $parts['path']     : null);
        $query    = (isset($parts['query'])    ? $parts['query']    : null);
        $fragment = (isset($parts['fragment']) ? $parts['fragment'] : null);
        $user     = (isset($parts['user'])     ? $parts['user']     : null);
        $password = (isset($parts['pass'])     ? $parts['pass']     : null);

        return new self($scheme, $host, $port, $path, $query, $fragment, $user, $password);
    }

    /**
     * Parse a URI string
     *
     * @param string $uri URI as a string.
     *
     * @return array
     */
    public static function fromString($uri)
    {
        $parts = [];

        $parts = parse_url($uri);

        if (empty($parts)) {
            $regex = '|^((?P<scheme>[^:/?#]+):)?' .
                        '((?P<doubleslash>//)(?P<authority>[^/?#]*))?(?P<path>[^?#]*)' .
                        '((?P<querydef>\?)(?P<query>[^#]*))?(#(?P<fragment>.*))?|';
            preg_match($regex, $uri, $match);

            if ( ! empty($match['scheme'])) {
                $parts['scheme'] = $match['scheme'];
            }

            // Parse authority
            if ('//' === $match['doubleslash']) {
                if (0 === strlen($match['authority'])) {
                    $parts['host'] = '';
                } else {
                    $authority = $match['authority'];

                    // Split authority into userinfo and host
                    // (use last @ to ignore unescaped @ symbols)
                    if (false !== ($pos = strrpos($authority, '@'))) {
                        $userInfo = substr($authority, 0, $pos);

                        // Detect whether there is a password.
                        if (strpos($userInfo, ':') > 0) {
                            list($parts['user'], $parts['pass']) = explode(':', $userInfo);
                        } else {
                            $parts['user'] = $userInfo;
                        }

                        $authority = substr($authority, $pos + 1);
                    }

                    // Split authority into host and port
                    $hostEnd = 0;
                    if ((strlen($authority) > 0) &&
                        ('[' === $authority[0]) &&
                        (false !== ($pos = strpos($authority, ']')))) {
                        $hostEnd = $pos;
                    }

                    if ((false !== ($pos = strrpos($authority, ':'))) && ($pos > $hostEnd)) {
                        $parts['host'] = substr($authority, 0, $pos);
                        $parts['port'] = substr($authority, $pos + 1);
                    } else {
                        $parts['host'] = $authority;
                    }
                }
            }

            // The path is always present but might be empty
            $parts['path'] = $match['path'];

            if ( ! empty($match['querydef'])) {
                $parts['query'] = $match['query'];
            }

            if (isset($match['fragment'])) {
                $parts['fragment'] = $match['fragment'];
            }
        }

        return $parts;
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     *
     *  @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();

        $host = $this->getHost();
        $port = $this->getPort();

        $authority = ($userInfo ? $userInfo . '@' : '') . $host .
                     ($port !== null ? ':' . $port : '');

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->user . ($this->password ? ':' . $this->password : '');
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        // Does this URI use a standard port?
        $standardPort = ($this->scheme === 'http'  && $this->port === 80) ||
                        ($this->scheme === 'https' && $this->port === 443);

        return $this->port && ! $standardPort ? $this->port : null;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     *
     * @return self A new instance with the specified scheme.
     *
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        self::parseScheme($scheme);

        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password ?: '';

        return $clone;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        self::parsePort($port);

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     *
     * @return self A new instance with the specified path.
     *
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        self::parsePath($path);

        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     *
     * @return self A new instance with the specified query string.
     *
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        self::parseQuery($query);

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     *
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        self::parseFragment($fragment);

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        $path = '/' . ltrim($path, '/');

        $uri = ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . $path
            . ($query ? '?' . $query : '')
            . ($fragment ? '#' . $fragment : '');

        return $uri;
    }

    /**
     * Filter URI scheme.
     *
     * @param  string $scheme Raw URI scheme.
     *
     * @return string
     *
     * @throws InvalidArgumentException If the URI scheme is invalid.
     */
    protected static function parseScheme(&$scheme)
    {
        if ( ! is_string($scheme) && ! method_exists($scheme, '__toString')) {
            throw new InvalidArgumentException('URI scheme must be a string');
        }

        $scheme = str_replace('://', '', strtolower($scheme));

        if ( ! in_array($scheme, self::$schemeOptions)) {
            throw new InvalidArgumentException(sprintf('URI scheme must be one of: "%s"', implode('", "', self::$schemeOptions)));
        }
    }

    /**
     * Filter URI port.
     *
     * @param  null|int $port The URI port number.
     *
     * @return null|int
     *
     * @throws InvalidArgumentException If the port is invalid.
     */
    protected static function parsePort(&$port)
    {
        if ( ! is_null($port) && ! is_integer($port)) {
            throw new InvalidArgumentException('URI port invalid; must be null or an integer');
        }

        if (is_integer($port) && ! ($port >= 1 && $port <= 65535)) {
            throw new InvalidArgumentException('URI port invalid; must be between 1 and 65535 (inclusive)');
        }
    }

    /**
     * Filter URI path.
     *
     * This method percent-encodes all reserved
     * characters in the provided path string. This method
     * will NOT double-encode characters that are already
     * percent-encoded.
     *
     * @param  string $path The raw URI path.
     *
     * @return string The RFC 3986 percent-encoded URI path.
     *
     * @link   http://www.faqs.org/rfcs/rfc3986.html
     */
    protected static function parsePath(&$path)
    {
        if ( ! is_string($path)) {
            throw new InvalidArgumentException('URI path must be a string');
        }

        $pattern = '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/';

        $callback = function ($match) {
            return rawurlencode($match[0]);
        };

        $path = preg_replace_callback($pattern, $callback, $path);
    }

    /**
     * Filters the query string or fragment of a URI.
     *
     * @param string $query The raw uri query string.
     * @return string The percent-encoded query string.
     */
    protected static function parseQuery(&$query)
    {
        if ( ! is_null($query) && ! is_string($query) && ! method_exists($query, '__toString')) {
            throw new InvalidArgumentException('URI query must be a string');
        }

        $query = ltrim($query, '?');

        $pattern = '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/';

        $callback = function ($match) {
            return rawurlencode($match[0]);
        };

        $query = preg_replace_callback($pattern, $callback, $query);
    }

    /**
     * Filters the query fragment of a URI.
     *
     * @param string $query The raw uri query string.
     * @return string The percent-encoded query string.
     */
    protected static function parseFragment(&$fragment)
    {
        if ( ! is_null($fragment) && ! is_string($fragment) && ! method_exists($fragment, '__toString')) {
            throw new InvalidArgumentException('URI fragment must be a string');
        }

        $fragment = ltrim($fragment, '#');

        $pattern = '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/';

        $callback = function ($match) {
            return rawurlencode($match[0]);
        };

        $fragment = preg_replace_callback($pattern, $callback, $fragment);
    }
}
