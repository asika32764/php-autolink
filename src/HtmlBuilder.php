<?php

/**
 * Part of autolink project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Asika\Autolink;

/**
 * The HtmlBuilder class.
 */
class HtmlBuilder
{
    /**
     * Unpaired elements.
     *
     * @var  array
     */
    protected static array $unpairedElements = [
        'img',
        'br',
        'hr',
        'area',
        'param',
        'wbr',
        'base',
        'link',
        'meta',
        'input',
        'option',
        'a',
        'source',
    ];

    /**
     * Property trueValueMapping.
     *
     * @var  array
     */
    protected static array $trueValueMapping = [
        'readonly' => 'readonly',
        'disabled' => 'disabled',
        'multiple' => 'true',
        'checked' => 'checked',
        'selected' => 'selected',
    ];

    /**
     * Create a html element.
     *
     * @param string $name      Element tag name.
     * @param array  $attribs   Element attributes.
     * @param mixed  $content   Element content.
     * @param bool   $forcePair Force pair it.
     *
     * @return  string Created element string.
     */
    public static function create(
        string $name,
        array $attribs = [],
        string $content = '',
        bool $forcePair = false
    ): string {
        $forcePair = $forcePair ?: !in_array(strtolower($name), static::$unpairedElements, true);

        $name = trim($name);

        $tag = '<' . $name;

        $tag .= static::buildAttributes($attribs);

        if ($content !== null) {
            $tag .= '>' . $content . '</' . $name . '>';
        } else {
            $tag .= $forcePair ? '></' . $name . '>' : ' />';
        }

        return $tag;
    }

    /**
     * buildAttributes
     *
     * @param array $attribs
     *
     * @return  string
     */
    public static function buildAttributes(array $attribs): string
    {
        $attribs = static::mapAttrValues($attribs);

        $string = '';

        foreach ((array) $attribs as $key => $value) {
            if ($value === true) {
                $string .= ' ' . $key;

                continue;
            }

            if ($value === null || $value === false) {
                continue;
            }

            $string .= ' ' . $key . '=' . static::quote($value);
        }

        return $string;
    }

    /**
     * quote
     *
     * @param string $value
     *
     * @return  string
     */
    public static function quote(string $value): string
    {
        return '"' . $value . '"';
    }

    /**
     * mapAttrValues
     *
     * @param array $attribs
     *
     * @return  array
     */
    protected static function mapAttrValues(array $attribs): array
    {
        foreach (static::$trueValueMapping as $key => $value) {
            $attribs[$key] = !empty($attribs[$key]) ? $value : null;
        }

        return $attribs;
    }
}
