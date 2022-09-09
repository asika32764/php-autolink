<?php
/**
 * Part of php-autolink project.
 *
 * @copyright  Copyright (C) 2015 LYRASOFT. All rights reserved.
 * @license    MIT; See LICENSE.md
 */

namespace Asika\Autolink;

/**
 * The Linker class.
 *
 * @method  static string  convert($url, $attribs = [])
 * @method  static string  convertEmail($url, $attribs = [])
 * @method  static string  link($url, $attribs = [])
 *
 * @since  1.0
 */
class AutolinkStatic
{
    /**
     * Property instance.
     *
     * @var  Autolink|null
     */
    protected static ?Autolink $instance = null;

    /**
     * getInstance
     *
     * @param array $options
     * @param array $schemes
     *
     * @return  Autolink
     */
    public static function getInstance(array $options = [], array $schemes = []): Autolink
    {
        return static::$instance ??= new Autolink($options, $schemes);
    }

    /**
     * Method to set property instance
     *
     * @param Autolink $instance
     *
     * @return  void
     */
    public static function setInstance(Autolink $instance): void
    {
        static::$instance = $instance;
    }

    /**
     * __callStatic
     *
     * @param string $name
     * @param array  $args
     *
     * @return  mixed
     */
    public static function __callStatic(string $name, array $args): mixed
    {
        $instance = static::getInstance();

        if (is_callable([$instance, $name])) {
            return call_user_func_array([$instance, $name], $args);
        }

        throw new \BadMethodCallException(sprintf('Method: %s::%s not exists.', 'Autolink', $name));
    }
}
