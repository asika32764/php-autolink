<?php
/**
 * Part of php-autolink project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Asika\Autolink\Autolink;

/**
 * The AutolinkTest class.
 *
 * @since  1.0
 */
class AutolinkTest extends \PHPUnit\Framework\TestCase
{
    use \Windwalker\Test\Traits\BaseAssertionTrait;

    /**
     * Property instance.
     *
     * @var  Autolink
     */
    protected $instance;

    /**
     * setUp
     *
     * @return  void
     */
    public function setUp(): void
    {
        $this->instance = new Autolink();
    }

    /**
     * testConvert
     *
     * @return  void
     */
    public function testConvert()
    {
        $text = <<<TEXT
This is Simple URL:
http://www.google.com.tw

This is SSL URL:
https://www.google.com.tw

This is URL with path:
http://www.google.com.tw/images

This is URL with query:
http://www.google.com.tw/search?q=foo&num=100

This is URL with multi-level query:
http://example.com/?foo[1]=a&foo[2]=b

This is URL with fragment:
http://example.com/path#top

This is URL inline: http://example.com/path#top with test.

This is URL in HTML:
<a href="http://example.com/path?foo[1]=a&foo[2]=b">LINK</a>
<a href="http://example.com/path?foo[1]=a&foo[2]=b">http://example.com/path?foo[1]=a&foo[2]=b</a>
<img src="http://example.com/path?foo[1]=a&foo[2]=b" width="100"/>
<div data-target="http://example.com/path?foo[1]=a&foo[2]=b" wdith="100"/></div>
<p>http://example.com/path?foo[1]=a&foo[2]=b</p>

TEXT;

        $html = <<<HTML
This is Simple URL:
<a href="http://www.google.com.tw">http://www.google.com.tw</a>

This is SSL URL:
<a href="https://www.google.com.tw">https://www.google.com.tw</a>

This is URL with path:
<a href="http://www.google.com.tw/images">http://www.google.com.tw/images</a>

This is URL with query:
<a href="http://www.google.com.tw/search?q=foo&amp;num=100">http://www.google.com.tw/search?q=foo&amp;num=100</a>

This is URL with multi-level query:
<a href="http://example.com/?foo[1]=a&amp;foo[2]=b">http://example.com/?foo[1]=a&amp;foo[2]=b</a>

This is URL with fragment:
<a href="http://example.com/path#top">http://example.com/path#top</a>

This is URL inline: <a href="http://example.com/path#top">http://example.com/path#top</a> with test.

This is URL in HTML:
<a href="http://example.com/path?foo[1]=a&foo[2]=b">LINK</a>
<a href="http://example.com/path?foo[1]=a&foo[2]=b">http://example.com/path?foo[1]=a&foo[2]=b</a>
<img src="http://example.com/path?foo[1]=a&foo[2]=b" width="100"/>
<div data-target="http://example.com/path?foo[1]=a&foo[2]=b" wdith="100"/></div>
<p><a href="http://example.com/path?foo[1]=a&amp;foo[2]=b">http://example.com/path?foo[1]=a&amp;foo[2]=b</a></p>

HTML;

        self::assertStringSafeEquals($html, $this->instance->convert($text));
    }

    /**
     * testConvert
     *
     * @return  void
     */
    public function testLink()
    {
        $url = 'http://www.google.com';

        self::assertEquals(
            '<a foo="bar" href="http://www.google.com">http://www.google.com</a>',
            $this->instance->link($url, ['foo' => 'bar'])
        );

        $this->instance->stripScheme(true);

        self::assertEquals(
            '<a foo="bar" href="http://www.google.com">www.google.com</a>',
            $this->instance->link($url, ['foo' => 'bar'])
        );

        $this->instance->autoTitle(true);

        self::assertEquals(
            '<a foo="bar" href="http://www.google.com" title="http://www.google.com">www.google.com</a>',
            $this->instance->link($url, ['foo' => 'bar'])
        );
    }

    /**
     * testTextLimit
     *
     * @return  void
     */
    public function testTextLimit()
    {
        $url = 'http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html';

        $this->instance->textLimit(50);

        self::assertEquals(
            '<a href="http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html">http://campus.asukademy.com/learning/job/84-fin...</a>',
            $this->instance->link($url)
        );

        $this->instance->textLimit(function ($url) {
            return \Asika\Autolink\Autolink::shortenUrl($url);
        });

        self::assertEquals(
            '<a href="http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html">http://campus.asukademy.com/....../84-find-interns......</a>',
            $this->instance->link($url)
        );
    }

    /**
     * testAutoTitle
     *
     * @return  void
     */
    public function testAutoTitle()
    {
        $url = 'http://example.com/path?foo["1"]=a&foo[\'2\']=b';

        $this->instance->autoTitle(true);

        self::assertEquals(
            '<a foo="bar" href="http://example.com/path?foo[&quot;1&quot;]=a&amp;foo[&#039;2&#039;]=b" title="http://example.com/path?foo[&quot;1&quot;]=a&amp;foo[&#039;2&#039;]=b">http://example.com/path?foo[&quot;1&quot;]=a&amp;foo[&#039;2&#039;]=b</a>',
            $this->instance->link($url, ['foo' => 'bar'])
        );
    }

    /**
     * testStripScheme
     *
     * @return  void
     */
    public function testStripScheme()
    {
        $this->instance->stripScheme(true);

        $url = 'http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html';

        self::assertEquals(
            '<a href="http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html">campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html</a>',
            $this->instance->link($url)
        );
    }

    public function testAddScheme()
    {
        $url = 'ftp://example.com';

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));

        $url = 'ftps://example.com';

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));

        $url = 'https://example.com';

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));

        $url = 'skype://example.com';

        self::assertEquals($url, $this->instance->convert($url));

        $this->instance->addScheme('skype');

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));
    }

    public function testLinkNoScheme()
    {
        $this->instance->linkNoScheme('http');

        $url = 'ftp://example.com';

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));

        $url = 'example.com';

        self::assertEquals('<a href="http://' . $url . '">' . $url . '</a>', $this->instance->convert($url));

        $url = 'https://example.com';

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));

        $url = 'skype://example.com';

        self::assertEquals($url, $this->instance->convert($url));

        $this->instance->addScheme('skype');

        self::assertEquals('<a href="' . $url . '">' . $url . '</a>', $this->instance->convert($url));
    }

    /**
     * testGetAndSetScheme
     *
     * @return  void
     */
    public function testGetAndSetScheme()
    {
        $autolink = new Autolink([], ['a', 'b', 'http']);

        self::assertEquals(['http', 'https', 'ftp', 'ftps', 'a', 'b'], $autolink->getSchemes());

        self::assertEquals('http|https|ftp|ftps|a|b', $autolink->getSchemes(true));

        $autolink->setSchemes('skype');

        self::assertEquals(['skype'], $autolink->getSchemes());

        $autolink->setSchemes('mailto');

        self::assertEquals(['mailto'], $autolink->getSchemes());

        $autolink->setSchemes('mailto', 'mailto');

        self::assertEquals(['mailto'], $autolink->getSchemes());

        $autolink->removeScheme('mailto');

        self::assertEquals([], $autolink->getSchemes());
    }

    public function testAutoEscape()
    {
        $autolink = new Autolink();

        $url = 'https://example.com/?foo=bar&yoo=baz';

        self::assertEquals('<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>', $autolink->convert($url));

        $autolink->autoEscape(false);

        self::assertEquals('<a href="' . $url . '">' . htmlspecialchars($url) . '</a>', $autolink->convert($url));

        $url = 'hello+admin&test@example.org';

        $autolink->autoEscape(true);

        self::assertEquals('<a href="mailto:' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a>', $autolink->convertEmail($url));

        $autolink->autoEscape(false);

        self::assertEquals('<a href="mailto:' . $url . '">' . htmlspecialchars($url) . '</a>', $autolink->convertEmail($url));
    }

    public function testConvertEmail()
    {
        $text = <<<TEXT
This is Simple Email:
sakura@flower.com

This is Email inline: sakura@flower.com with test.

This is Email in HTML:
<a href="sakura@flower.com">LINK</a>
<a href="mailto:sakura@flower.com">sakura@flower.com</a>
<div data-target="sakura@flower.com" wdith="100"/></div>
<div data-target="mailto:sakura@flower.com" wdith="100"/></div>

My email address is sakura@flower.com.

and emails are coming in between(sakura@flower.com). 
TEXT;

        $html = <<<HTML
This is Simple Email:
<a href="mailto:sakura@flower.com">sakura@flower.com</a>

This is Email inline: <a href="mailto:sakura@flower.com">sakura@flower.com</a> with test.

This is Email in HTML:
<a href="sakura@flower.com">LINK</a>
<a href="mailto:sakura@flower.com">sakura@flower.com</a>
<div data-target="sakura@flower.com" wdith="100"/></div>
<div data-target="mailto:sakura@flower.com" wdith="100"/></div>

My email address is <a href="mailto:sakura@flower.com">sakura@flower.com</a>.

and emails are coming in between(<a href="mailto:sakura@flower.com">sakura@flower.com</a>).
HTML;

        self::assertStringSafeEquals($html, $this->instance->convertEmail($text));
    }

    /**
     * testSetLinkBuilder
     *
     * @return  void
     */
    public function testGetAndSetLinkBuilder()
    {
        $this->instance->setLinkBuilder(function ($url, $attribs) {
            return $url . json_encode($attribs);
        });

        self::assertEquals('http://google.com{"foo":"bar","href":"http:\/\/google.com"}', $this->instance->link('http://google.com', ['foo' => 'bar']));

        self::assertInstanceOf('Closure', $this->instance->getLinkBuilder());
    }

    /**
     * urlProvider
     *
     * @return  array
     */
    public function urlProvider()
    {
        return [
            [
                'http://www.projectup.net/blog/index.php?option=com_content&view=article&id=15726:-agile-&catid=8:pmp-pm&Itemid=18',
                'http://www.projectup.net/....../index.php?optio......',
                15,
                6
            ],
            [
                'http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html',
                'http://campus.asukademy.com/....../84-find-interns......',
                15,
                6
            ],
            [
                'http://user:pass@campus.asukademy.com:8888/learning/job/84-find-internship-opportunity-through-platform.html',
                'http://user:pass@campus.asukademy.com:8888/....../84-find-interns......',
                15,
                6
            ],
            [
                'http://campus.asukademy.com/learning/job/84-find-internship-opportunity-through-platform.html',
                'http://campus.asukademy.com/.../84-fi...',
                5,
                3
            ]
        ];
    }

    /**
     * testShorten
     *
     * @param $url
     * @param $expect
     * @param $limit
     * @param $dots
     *
     * @dataProvider  urlProvider
     *
     */
    public function testShortenUrl($url, $expect, $limit, $dots)
    {
        self::assertEquals($expect, \Asika\Autolink\Autolink::shortenUrl($url, $limit, $dots));
    }
}
