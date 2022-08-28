<?php

use Asika\Autolink\Autolink;

/**
 * Part of php-autolink project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Asika\Autolink\AutolinkStatic;
use PHPUnit\Framework\TestCase;
use Windwalker\Test\Traits\BaseAssertionTrait;

/**
 * The LinkerTest class.
 *
 * @since  1.0
 */
class AutolinkStaticTest extends TestCase
{
    use BaseAssertionTrait;
    
    /**
     * testFacade
     *
     * @return  void
     */
    public function testFacade()
    {
        $url = 'http://google.com';

        self::assertEquals(sprintf('<a href="%s">%s</a>', $url, $url), AutolinkStatic::convert($url));
        self::assertEquals(sprintf('<a href="%s">%s</a>', $url, $url), AutolinkStatic::link($url));
    }
}
