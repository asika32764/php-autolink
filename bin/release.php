<?php

declare(strict_types=1);

namespace App;

use Asika\SimpleConsole\Console;
use Asika\SimpleConsole\ExecResult;

require __DIR__ . '/Console.php';

$app = new class () extends Console
{
    protected array $scripts = [];

    protected bool $isDryRun {
        get {
            return (bool) $this->get('dry-run');
        }
    }

    protected function configure(): void
    {
        $this->addParameter('version', type: static::STRING)
            ->description('Can be <version> name or major|minor|patch|alpha|beta|rc')
            ->default('patch');

        $this->addParameter('suffix', type: static::STRING)
            ->description('The suffix type. Can be alpha|beta|rc');

        $this->addParameter('--dry-run|-d', type: static::BOOLEAN)
            ->description('Run process but do not execute any commands.');

        $this->addParameter('--from', type: static::STRING)
            ->description('The version to release from. Default is the current version.')
            ->required(true);
    }

    protected function doExecute(): int
    {
        foreach ($this->scripts as $script) {
            $this->exec($script);
        }

        $currentVersion = $this->get('from') ?: trim(file_get_contents(__DIR__ . '/../VERSION'));
        $targetVersion = (string) $this->get('version');
        $targetSuffix = (string) $this->get('suffix');

        if (in_array($targetVersion, ['alpha', 'beta', 'rc'])) {
            $targetSuffix = $targetVersion;
            $targetVersion = 'patch';
        }

        $targetVersion = static::versionPush($currentVersion, $targetVersion, $targetSuffix);

        $this->writeln('Release version: ' . $targetVersion);

        if (!$this->isDryRun) {
            static::writeVersion($targetVersion);
        }

        $this->exec(sprintf('git commit -am "Release version: %s"', $targetVersion));
        $this->exec(sprintf('git tag %s', $targetVersion));

        $this->exec('git push');
        $this->exec('git push --tags');

        return static::SUCCESS;
    }

    protected static function writeVersion(string $version): false|int
    {
        return file_put_contents(static::versionFile(), $version . "\n");
    }

    protected static function versionFile(): string
    {
        return __DIR__ . '/../VERSION';
    }

    protected static function versionPush(
        string $currentVersion,
        string $targetVersion,
        string $targetSuffix,
    ): string {
        [$major, $minor, $patch, $suffixType, $suffixVersion] = static::parseVersion($currentVersion);

        switch ($targetVersion) {
            case 'major':
                $major++;
                $minor = $patch = 0;
                if ($targetSuffix) {
                    $suffixType = $targetSuffix;
                    $suffixVersion = 1;
                } else {
                    $suffixType = '';
                    $suffixVersion = 0;
                }
                break;

            case 'minor':
                $minor++;
                $patch = 0;
                if ($targetSuffix) {
                    $suffixType = $targetSuffix;
                    $suffixVersion = 1;
                } else {
                    $suffixType = '';
                    $suffixVersion = 0;
                }
                break;

            case 'patch':
                if (!$suffixType) {
                    $patch++;
                }
                if ($targetSuffix) {
                    if ($suffixType === $targetSuffix) {
                        $suffixVersion++;
                    } else {
                        $suffixType = $targetSuffix;
                        $suffixVersion = 1;
                    }
                } else {
                    $suffixType = '';
                    $suffixVersion = 0;
                }
                break;

            default:
                return $targetVersion;
        }

        $currentVersion = $major . '.' . $minor . '.' . $patch;

        if ($suffixType) {
            $currentVersion .= '-' . $suffixType . '.' . $suffixVersion;
        }

        return $currentVersion;
    }

    public static function parseVersion(string $currentVersion): array
    {
        [$currentVersion, $prerelease] = explode('-', $currentVersion, 2) + ['', ''];

        [$major, $minor, $patch] = explode('.', $currentVersion, 3) + ['', '0', '0'];
        $major = (int) $major;
        $minor = (int) $minor;
        $patch = (int) $patch;
        $prereleaseType = '';
        $prereleaseVersion = 0;

        if ($prerelease) {
            $matched = preg_match('/(rc|beta|alpha)[.-]?(\d+)/i', $prerelease, $matches);

            if ($matched) {
                $prereleaseType = strtolower($matches[1]);
                $prereleaseVersion = (int) $matches[2];
            }
        }

        return [$major, $minor, $patch, $prereleaseType, $prereleaseVersion];
    }

    public function exec(string $cmd, \Closure|null|false $output = null, bool $showCmd = true): ExecResult
    {
        $this->writeln('>> ' . ($this->isDryRun ? '(Dry Run) ' : '') . $cmd);

        if (!$this->isDryRun) {
            return parent::exec($cmd, $output, false);
        }

        return new ExecResult();
    }

    public function addScript(string $script): static
    {
        $this->scripts[] = $script;

        return $this;
    }
};

$app->execute();
