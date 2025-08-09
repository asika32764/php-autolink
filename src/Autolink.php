<?php

declare(strict_types=1);

namespace Asika\Autolink;

/**
 * The Autolink class.
 *
 * @since  1.0
 */
class Autolink
{
    public AutolinkOptions $options;

    /**
     * Property schemes.
     *
     * @var  array
     */
    protected array $schemes = [
        'http',
        'https',
        'ftp',
        'ftps'
    ];

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
     * Property linkBuilder.
     *
     * @var  callable
     */
    protected $linkBuilder;

    protected ?\Closure $escapeHandler = null;

    /**
     * Class init.
     *
     * @param  AutolinkOptions|array  $options  Basic options.
     * @param  array                  $schemes
     */
    public function __construct(AutolinkOptions|array $options = [], array $schemes = [])
    {
        $this->setOptions($options);

        $this->setSchemes(...array_merge($this->schemes, $schemes));
    }

    /**
     * render
     *
     * @param string $text
     * @param array  $attribs
     *
     * @return  string
     */
    public function convert(string $text, array $attribs = []): string
    {
        $linkNoScheme = $this->getLinkNoScheme();
        $staticDomains = '|localhost';

        if ($linkNoScheme) {
            $schemeRegex = "[(%s)\:\/\/@]*";
            $staticDomains = '';
        } else {
            $schemeRegex = "(%s)\:\/\/";
        }

        $schemeRegex = sprintf($schemeRegex, $this->getSchemes(true));

        $regex = '/(([a-zA-Z]*=")*' . $schemeRegex . "([\-\p{L}\p{N}\p{M}]+\.[\p{L}\p{M}]{2,}$staticDomains)([\/\p{L}\p{N}\p{M}\-._~:?#\[\]@!$&'()*+,;=%\">]*)?)/u";

        return preg_replace_callback(
            $regex,
            function ($matches) use ($attribs, $linkNoScheme) {
                $url = $matches[0];

                preg_match('/[a-zA-Z]*\=\"(.*)/', $url, $inElements);

                if ($inElements) {
                    return $url;
                }

                if (
                    $linkNoScheme
                    && (
                        str_starts_with($url, '://')
                        || str_starts_with($url, '@')
                    )
                ) {
                    return $url;
                }

                $suffix = '';

                if (str_ends_with($url, '.')) {
                    $suffix = '.';
                    $url = substr($url, 0, -1);
                }

                if (str_ends_with($url, ')')) {
                    $suffix = ')';
                    $url = substr($url, 0, -1);
                }

                return $this->link($url, $attribs) . $suffix;
            },
            $text
        );
    }

    /**
     * renderEmail
     *
     * @param string $text
     * @param array  $attribs
     *
     * @return  string
     */
    public function convertEmail(string $text, array $attribs = []): string
    {
        $regex = "/(([a-zA-Z]*=\")*[a-zA-Z0-9!#$%&'*+-\/=?^_`{|}~:]+@[a-zA-Z0-9!#$%&'*+-\/=?^_`{|}~]+\.[a-zA-Z\">]{2,})/";

        return preg_replace_callback(
            $regex,
            function ($matches) use ($attribs) {
                // Email should not start with a dot.
                if (str_starts_with($matches[0], '.')) {
                    return $matches[0];
                }

                preg_match('/[a-zA-Z]*\=\"(.*)/', $matches[0], $inElements);

                if (!$inElements) {
                    $email = $this->isAutoEscape() ? $this->escape($matches[0]) : $matches[0];

                    $attribs['href'] = 'mailto:' . $email;

                    return $this->buildLink($matches[0], $attribs);
                }

                return $matches[0];
            },
            $text
        );
    }

    /**
     * convert
     *
     * @param string $url
     * @param array  $attribs
     *
     * @return  string
     */
    public function link(string $url, array $attribs = []): string
    {
        $content = $url;

        if ($this->isStripScheme()) {
            if (preg_match('!^(' . $this->getSchemes(true) . ')://!i', $content, $m)) {
                $content = substr($content, strlen($m[1]) + 3);
            }
        }

        if ($limit = $this->getTextLimit()) {
            if (is_callable($limit)) {
                $content = $limit($content);
            } else {
                $content = $this->shorten($content, $limit);
            }
        }

        $attribs['href'] = $this->isAutoEscape() ? $this->escape($url) : $url;

        if (($scheme = $this->getLinkNoScheme()) && !str_contains($attribs['href'], '://')) {
            $scheme = is_string($scheme) ? $scheme : 'http';

            $attribs['href'] = $scheme . '://' . $attribs['href'];
        }

        if ($this->isAutoTitle()) {
            $attribs['title'] = $this->escape($url);
        }

        return $this->buildLink($content, $attribs);
    }

    /**
     * buildLink
     *
     * @param string|null $url
     * @param array       $attribs
     *
     * @return  string
     */
    protected function buildLink(?string $url = null, array $attribs = []): string
    {
        if (is_callable($this->linkBuilder)) {
            return (string) ($this->linkBuilder)($url, $attribs);
        }

        return HtmlBuilder::create('a', $attribs, $this->escape($url));
    }

    /**
     * autolinkLabel
     *
     * @param string $text
     * @param int    $limit
     *
     * @return  string
     */
    public function shorten(string $text, int $limit): string
    {
        if (!$limit) {
            return $text;
        }

        if (strlen($text) > $limit) {
            return substr($text, 0, $limit - 3) . '...';
        }

        return $text;
    }

    public function stripScheme(bool $value = false): static
    {
        $this->options->stripScheme = $value;

        return $this;
    }

    public function isStripScheme(): bool
    {
        return $this->options->stripScheme;
    }

    public function autoEscape(bool $value = true): static
    {
        $this->options->escape = $value;

        return $this;
    }

    public function isAutoEscape(): bool
    {
        return (bool) $this->options->escape;
    }

    /**
     * @param int|callable|null $value
     *
     * @return  static
     */
    public function textLimit(int|callable|null $value = null): static
    {
        if (is_callable($value)) {
            $value = $value(...);
        }

        $this->options->textLimit = $value;

        return $this;
    }

    public function getTextLimit(): int|callable|null
    {
        $value = $this->options->textLimit;

        // Fix for B/C
        if ($value === false) {
            $value = null;
        }

        return $value;
    }

    public function autoTitle(bool $value = false): static
    {
        $this->options->autoTitle = $value;

        return $this;
    }

    public function isAutoTitle(): bool
    {
        return $this->options->autoTitle;
    }

    /**
     * linkNoScheme
     *
     * @param bool $value
     *
     * @return  static
     */
    public function linkNoScheme(bool|string $value = false): static
    {
        $this->options->linkNoScheme = $value;

        return $this;
    }

    public function getLinkNoScheme(): bool|string
    {
        return $this->options->linkNoScheme;
    }

    /**
     * optionAccess
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return  static
     *
     * @deprecated  Use {@see AutolinkOptions} instead.
     */
    protected function setOption(string $name, mixed $value = null): static
    {
        $name = AutolinkOptions::mapOptionKey($name);

        $this->options->$name = $value;

        return $this;
    }

    /**
     * @param  string      $name
     * @param  mixed|null  $default
     *
     * @return  mixed
     *
     * @deprecated  Use {@see AutolinkOptions} instead.
     */
    protected function getOption(string $name, mixed $default = null): mixed
    {
        $name = AutolinkOptions::mapOptionKey($name);

        return $this->options->$name ?? $default;
    }

    /**
     * @param string ...$schemes
     *
     * @return  static
     */
    public function addScheme(string ...$schemes): static
    {
        foreach ($schemes as $scheme) {
            $scheme = strtolower($scheme);
            $this->schemes[] = $scheme;
        }

        $this->schemes = array_unique($this->schemes);

        return $this;
    }

    /**
     * @param string $scheme
     *
     * @return  static
     */
    public function removeScheme(string $scheme): static
    {
        $index = array_search($scheme, $this->schemes, true);

        if ($index !== false) {
            unset($this->schemes[$index]);
        }

        return $this;
    }

    public function getOptions(): AutolinkOptions
    {
        return $this->options;
    }

    /**
     * Method to set property options
     *
     * @param  AutolinkOptions|array  $options
     *
     * @return  static  Return self to support chaining.
     */
    public function setOptions(AutolinkOptions|array $options): static
    {
        $this->options = AutolinkOptions::wrap($options);

        return $this;
    }

    /**
     * Method to get property Schemes
     *
     * @param bool $regex
     *
     * @return array|string
     */
    public function getSchemes(bool $regex = false): array|string
    {
        if ($regex) {
            return implode('|', $this->schemes);
        }

        return $this->schemes;
    }

    /**
     * Method to set property schemes
     *
     * @param string ...$schemes
     *
     * @return  static  Return self to support chaining.
     */
    public function setSchemes(string ...$schemes): static
    {
        $schemes = array_unique(array_map('strtolower', $schemes));

        $this->schemes = $schemes;

        return $this;
    }

    /**
     * Method to get property LinkBuilder
     *
     * @return  callable
     */
    public function getLinkBuilder(): callable
    {
        return $this->linkBuilder;
    }

    /**
     * Method to set property linkBuilder
     *
     * @param callable $linkBuilder
     *
     * @return  static  Return self to support chaining.
     */
    public function setLinkBuilder(callable $linkBuilder): static
    {
        if (!is_callable($linkBuilder)) {
            throw new \InvalidArgumentException('Please use a callable or Closure.');
        }

        $this->linkBuilder = $linkBuilder;

        return $this;
    }

    /**
     * @param string $url
     * @param int    $lastPartLimit
     * @param int    $dots
     *
     * @return  string
     */
    public static function shortenUrl(string $url, int $lastPartLimit = 15, int $dots = 6): string
    {
        $parsed = array_merge(static::$defaultParsed, parse_url($url));

        // @link  http://php.net/manual/en/function.parse-url.php#106731
        $scheme   = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host     = $parsed['host'] ?? '';
        $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user     = $parsed['user'] ?? '';
        $pass     = isset($parsed['pass']) ? ':' . $parsed['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parsed['path'] ?? '';
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

    public function escape(string $str): string
    {
        return $this->getEscapeHandler()($str);
    }

    public function getEscapeHandler(): ?\Closure
    {
        return $this->escapeHandler
            // PHP 8.1 or higher will escape single quite
            ?? static fn ($str) => htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE);
    }

    public function setEscapeHandler(?\Closure $escapeHandler): static
    {
        $this->escapeHandler = $escapeHandler;

        return $this;
    }
}
