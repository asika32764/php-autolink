# PHP Autolink Library

![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/asika32764/php-autolink/ci.yml?style=for-the-badge)
[![Packagist Version](https://img.shields.io/packagist/v/asika/autolink?style=for-the-badge)
](https://packagist.org/packages/asika/autolink)
[![Packagist Downloads](https://img.shields.io/packagist/dt/asika/autolink?style=for-the-badge)](https://packagist.org/packages/asika/autolink)



A library to auto convert URLs to links.

## Table of Content

* [Installation via Composer](#installation-via-composer)
* [Getting Started](#getting-started)
* [Use Autolink Object](#use-autolink-object)
* [Convert Text](#convert-text)
* [Convert Email](#convert-email)
* [Options](#options)
* [Scheme](#scheme)
* [Link Builder](#link-builder)

## Requirement

- Version 2.x require PHP 8.0 or higher.
- Version 1.x supports PHP 5.3 to 7.4

## Installation via Composer

Add this to composer.json require block.

``` json
{
    "require": {
        "asika/autolink": "^2.0"
    }
}
```

## Getting Started

This is a quick start to convert URL to link:

```php
use Asika\Autolink\AutolinkStatic;

$text = AutolinkStatic::convert($text);
$text = AutolinkStatic::convertEmail($text);
```

## Use Autolink Object

Create the object:

```php
use Asika\Autolink\Autolink;

$autolink = new Autolink();
```

Create with options.

```php
$options = [
    'strip_scheme' => false,
    'text_limit' => false,
    'auto_title' => false,
    'escape' => true,
    'link_no_scheme' => false
];

$schemes = ['http', 'https', 'skype', 'itunes'];

$autolink = new Autolink($options, $schemes);
```

## Convert Text

This is an example text:

``` html
This is Simple URL:
http://www.google.com.tw

This is SSL URL:
https://www.google.com.tw

This is URL with multi-level query:
http://example.com/?foo[1]=a&foo[2]=b
```

We convert all URLs.

```php
$text = $autolink->convert($text);
```

Output:

``` html
This is Simple URL:
<a href="http://www.google.com.tw">http://www.google.com.tw</a>

This is SSL URL:
<a href="https://www.google.com.tw">https://www.google.com.tw</a>

This is URL with multi-level query:
<a href="http://example.com/?foo[1]=a&amp;foo[2]=b">http://example.com/?foo[1]=a&amp;foo[2]=b</a>
```

### Add Attributes

```php
$text = $autolink->convert($text, ['class' => 'center']);
```

All link will add this attributes:

```php
This is Simple URL:
<a href="http://www.google.com.tw" class="center">http://www.google.com.tw</a>

This is SSL URL:
<a href="https://www.google.com.tw" class="center">https://www.google.com.tw</a>
```

## Convert Email

Email url has no scheme, we use anoter method to convert them, and it will add `mailto:` at begin of `href`.

```php
$text = $aurolink->convertEmail($text);
```

Output

``` html
<a href="mailto:foo@example.com">foo@example.com</a>

```

## Options

### `text_limit`

We can set this option by constructor or setter:

```php
$auitolink->textLimit(50);

$text = $autolink->convert($text);
```

The link text will be:

```
http://campus.asukademy.com/learning/job/84-fin...
```

Use Your own limit handler by set a callback:

```php
$auitolink->textLimit(function($url) {
    return substr($url, 0, 50) . '...';
});
```

Or use `\Asika\Autolink\LinkHelper::shorten()` Pretty handler:

```php
$auitolink->textLimit(function($url) {
    return \Asika\Autolink\Autolink::shortenUrl($url, 15, 6);
});
```

Output:

``` text
http://campus.asukademy.com/....../84-find-interns......
```

### `auto_title`

Use AutoTitle to force add title on anchor element.

```php
$autolink->autoTitle(true);

$text = $autolink->convert($text);
```

Output:

``` html
<a href="http://www.google.com.tw" title="http://www.google.com.tw">http://www.google.com.tw</a>
```

### `strip_scheme`

Strip Scheme on link text:

```php
$auitolink->stripScheme(true);

$text = $autolink->convert($text);
```

Output

``` html
<a href="http://www.google.com.tw" >www.google.com.tw</a>
```

### `escape`

Auto escape URL, default is `true`:

```php
$auitolink->autoEscape(false);

$text = $autolink->convert($text);

$auitolink->autoEscape(true);

$text = $autolink->convert($text);
```

Output

``` html
<a href="http://www.google.com.tw?foo=bar&yoo=baz" >http://www.google.com.tw?foo=bar&yoo=baz</a>
<a href="http://www.google.com.tw?foo=bar&amp;yoo=baz" >http://www.google.com.tw?foo=bar&amp;yoo=baz</a>
```

### `link_no_scheme`

Convert URL which no scheme. If you pass `TRUE` to this option, Autolink will use
`http` as default scheme, you can also provide your own default scheme.

```php
$auitolink->linkNoScheme('https');

$text = $autolink->convert('www.google.com.tw');
```

Output

``` html
<a href="https://www.google.com.tw" >www.google.com.tw</a>
```

## Scheme

You can add new scheme to convert URL begin with it, for example: `vnc://example.com`

```php
$autolink->addScheme('skype', 'vnc');
```

Default schemes is `http, https, ftp, ftps`.

## Link Builder

If you don't want to use `<a>` element as your link, you can set a callback to build link HTML.

```php
$autolink->setLinkBuilder(function(string $url, array $attribs) {
    $attribs['src'] = htmlspecialchars($url);

    return \Asika\Autolink\HtmlBuilder::create('img', $attribs, null);
});
```
