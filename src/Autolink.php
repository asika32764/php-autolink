<?php
/**
 * Part of php-autolink project.
 *
 * @copyright  Copyright (C) 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Asika\Autolink;

use Windwalker\Dom\HtmlElement;

/**
 * The Autolink class.
 *
 * @since  1.0
 */
class Autolink
{
    /**
     * Property options.
     *
     * @var  array
     */
    public $options = array(
        'strip_scheme' => false,
        'text_limit' => false,
        'auto_title' => false,
        'escape' => true,
        'link_no_scheme' => false
    );

    /**
     * Property schemes.
     *
     * @var  array
     */
    protected $schemes = array(
        'http',
        'https',
        'ftp',
        'ftps'
    );

    /**
     * Property linkBuilder.
     *
     * @var  callable
     */
    protected $linkBuilder;

    /**
     * Class init.
     *
     * @param array $options Basic options.
     * @param array $schemes
     */
    public function __construct($options = array(), $schemes = array())
    {
        $this->options = array_merge($this->options, (array) $options);

        $this->setSchemes(array_merge($this->schemes, $schemes));
    }

    /**
     * render
     *
     * @param string $text
     * @param array  $attribs
     *
     * @return  string
     */
    public function convert($text, $attribs = array())
    {
        $self = $this;
        $linkNoScheme = $this->linkNoScheme();

        if ($linkNoScheme) {
            $schemeRegex = "[(%s)\:\/\/]*";
        } else {
            $schemeRegex = "(%s)\:\/\/";
        }

        $schemeRegex = sprintf($schemeRegex, $this->getSchemes(true));

        $regex = '/(([a-zA-Z]*=")*' . $schemeRegex . "[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}([\/a-zA-Z0-9\-._~:?#\[\]@!$&'()*+,;=%\">]*)?)/";

        return preg_replace_callback(
            $regex,
            function ($matches) use ($self, $attribs, $linkNoScheme) {
                preg_match('/[a-zA-Z]*\=\"(.*)/', $matches[0], $inElements);

                if ($inElements) {
                    return $matches[0];
                }

                if ($linkNoScheme && strpos($matches[0], '://') === 0) {
                    return $matches[0];
                }

                return $self->link($matches[0], $attribs);
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
    public function convertEmail($text, $attribs = array())
    {
        $self = $this;

        $regex = "/(([a-zA-Z]*=\")*[a-zA-Z0-9!#$%&'*+-\/=?^_`{|}~:]+@[a-zA-Z0-9!#$%&'*+-\/=?^_`{|}~]+\.[a-zA-Z\">]{2,})/";

        return preg_replace_callback(
            $regex,
            function ($matches) use ($self, $attribs) {
                preg_match('/[a-zA-Z]*\=\"(.*)/', $matches[0], $inElements);

                if (!$inElements) {
                    $email = $self->autoEscape() ? htmlspecialchars($matches[0]) : $matches[0];

                    $attribs['href'] = 'mailto:' . $email;

                    return $self->buildLink($matches[0], $attribs);
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
    public function link($url, $attribs = array())
    {
        $content = $url;

        if ($this->stripScheme()) {
            if (preg_match('!^(' . $this->getSchemes(true) . ')://!i', $content, $m)) {
                $content = substr($content, strlen($m[1]) + 3);
            }
        }

        if ($limit = $this->textLimit()) {
            if (is_callable($limit)) {
                $content = call_user_func($limit, $content);
            } else {
                $content = $this->shorten($content, $limit);
            }
        }

        $attribs['href'] = $this->autoEscape() ? htmlspecialchars($url) : $url;

        if (($scheme = $this->linkNoScheme()) && strpos($attribs['href'], '://') === false) {
            $scheme = is_string($scheme) ? $scheme : 'http';

            $attribs['href'] = $scheme . '://' . $attribs['href'];
        }

        if ($this->autoTitle()) {
            $attribs['title'] = htmlspecialchars($url);
        }

        return $this->buildLink($content, $attribs);
    }

    /**
     * buildLink
     *
     * @param string $url
     * @param array  $attribs
     *
     * @return  string
     */
    protected function buildLink($url = null, $attribs = array())
    {
        if (is_callable($this->linkBuilder)) {
            return call_user_func($this->linkBuilder, $url, $attribs);
        }

        return (string) new HtmlElement('a', htmlspecialchars($url), $attribs);
    }

    /**
     * autolinkLabel
     *
     * @param string $text
     * @param int    $limit
     *
     * @return  string
     */
    public function shorten($text, $limit)
    {
        if (!$limit) {
            return $text;
        }

        if (strlen($text) > $limit) {
            return substr($text, 0, $limit - 3) . '...';
        }

        return $text;
    }

    /**
     * stripScheme
     *
     * @param mixed $value
     *
     * @return  mixed|static
     */
    public function stripScheme($value = null)
    {
        return $this->optionAccess('strip_scheme', $value);
    }

    /**
     * autoEscape
     *
     * @param mixed $value
     *
     * @return  mixed|static
     *
     * @since  1.1.1
     */
    public function autoEscape($value = null)
    {
        return $this->optionAccess('escape', $value);
    }

    /**
     * textLimit
     *
     * @param int|callable $value
     *
     * @return  int|callable|static
     */
    public function textLimit($value = null)
    {
        return $this->optionAccess('text_limit', $value);
    }

    /**
     * autoTitle
     *
     * @param mixed $value
     *
     * @return  mixed|static
     */
    public function autoTitle($value = null)
    {
        return $this->optionAccess('auto_title', $value);
    }

    /**
     * linkNoScheme
     *
     * @param mixed $value
     *
     * @return  mixed|static
     */
    public function linkNoScheme($value = null)
    {
        return $this->optionAccess('link_no_scheme', $value);
    }

    /**
     * optionAccess
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return  mixed|static
     */
    protected function optionAccess($name, $value = null)
    {
        if ($value === null) {
            return isset($this->options[$name]) ? $this->options[$name] : null;
        }

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * addScheme
     *
     * @param string $scheme
     *
     * @return  static
     */
    public function addScheme($scheme)
    {
        $scheme = strtolower($scheme);

        if (!in_array($scheme, $this->schemes)) {
            $this->schemes[] = $scheme;
        }

        return $this;
    }

    /**
     * removeScheme
     *
     * @param string $scheme
     *
     * @return  static
     */
    public function removeScheme($scheme)
    {
        $index = array_search($scheme, $this->schemes);

        if ($index !== false) {
            unset($this->schemes[$index]);
        }

        return $this;
    }

    /**
     * Method to get property Options
     *
     * @return  array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Method to set property options
     *
     * @param array $options
     *
     * @return  static  Return self to support chaining.
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Method to get property Schemes
     *
     * @param bool $regex
     *
     * @return array|string
     */
    public function getSchemes($regex = false)
    {
        if ($regex) {
            return implode('|', $this->schemes);
        }

        return $this->schemes;
    }

    /**
     * Method to set property schemes
     *
     * @param array $schemes
     *
     * @return  static  Return self to support chaining.
     */
    public function setSchemes($schemes)
    {
        $schemes = array_unique(array_map('strtolower', (array) $schemes));

        $this->schemes = $schemes;

        return $this;
    }

    /**
     * Method to get property LinkBuilder
     *
     * @return  callable
     */
    public function getLinkBuilder()
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
    public function setLinkBuilder($linkBuilder)
    {
        if (!is_callable($linkBuilder)) {
            throw new \InvalidArgumentException('Please use a callable or Closure.');
        }

        $this->linkBuilder = $linkBuilder;

        return $this;
    }
}
