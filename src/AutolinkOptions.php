<?php

declare(strict_types=1);

namespace Asika\Autolink;

class AutolinkOptions
{
    public function __construct(
        public bool $stripScheme = false,
        public int|\Closure|null $textLimit = null,
        public bool $autoTitle = false,
        public bool $escape = true,
        public bool|string $linkNoScheme = false,
    )
    {
    }

    public static function wrap(AutolinkOptions|array $options): AutolinkOptions
    {
        if ($options instanceof static) {
            return $options;
        }

        return new static(
            stripScheme: $options['strip_scheme'] ?? false,
            textLimit: $options['text_limit'] ?? null,
            autoTitle: $options['auto_title'] ?? false,
            escape: $options['escape'] ?? true,
            linkNoScheme: $options['link_no_scheme'] ?? false,
        );
    }

    public static function mapOptionKey(string $key): string
    {
        return match ($key) {
            'strip_scheme' => 'stripScheme',
            'text_limit' => 'textLimit',
            'auto_title' => 'autoTitle',
            'escape' => 'escape',
            'link_no_scheme' => 'linkNoScheme',
            default => $key,
        };
    }
}
