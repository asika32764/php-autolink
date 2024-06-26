<?php

declare(strict_types=1);

namespace Asika\Autolink;

/**
 * The LinkHelper class.
 *
 * @since  1.0
 *
 * @deprecated
 */
class LinkHelper
{
    /**
     * Property defaultParsed.
     *
     * @var  array
     */
    protected static array $defaultParsed = [
        'scheme' => null,
        'user' => null,
        'pass' => null,
        'host' => null,
        'port' => null,
        'path' => null,
        'query' => null,
        'fragment' => null
    ];

    /**
     * @param string $url
     * @param int    $lastPartLimit
     * @param int    $dots
     *
     * @return  string
     *
     * @since  1.1.1
     *
     * @deprecated  Use Autolink::shortenUrl() instead.
     */
    public static function shorten(string $url, int $lastPartLimit = 15, int $dots = 6): string
    {
        $parsed = array_merge(static::$defaultParsed, parse_url($url));

        // @link  http://php.net/manual/en/function.parse-url.php#106731
        $scheme   = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host     = isset($parsed['host']) ? $parsed['host'] : '';
        $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user     = isset($parsed['user']) ? $parsed['user'] : '';
        $pass     = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed['path']) ? $parsed['path'] : '';
        $query    = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        $first = $scheme . $user . $pass . $host . $port . '/';

        $last = $path . $query . $fragment;

        if (!$last) {
            return $first;
        }

        if (strlen($last) <= $lastPartLimit) {
            return $first . $last;
        }

        $last = explode('/', $last);
        $last = array_pop($last);

        if (strlen($last) > $lastPartLimit) {
            $last = '/' . substr($last, 0, $lastPartLimit) . str_repeat('.', $dots);
        }

        return $first . str_repeat('.', $dots) . $last;
    }
}
