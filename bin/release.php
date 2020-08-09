<?php
/**
 * Part of cli project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

// phpcs:disable

use Asika\SimpleConsole\Console;

include_once __DIR__ . '/Console.php';

class Build extends Console
{
    /**
     * Property help.
     *
     * @var  string
     */
    protected $help = <<<HELP
[Usage] php release.php <version> <next_version>

[Options]
    h | help   Show help information
    v          Show more debug information.
    --dry-run  Dry run without git push or commit.
HELP;

    /**
     * doExecute
     *
     * @return  bool|mixed
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function doExecute()
    {
        $currentVersion = trim(file_get_contents(__DIR__ . '/../VERSION'));
        $targetVersion = $this->getArgument(0);

        if (!$targetVersion) {
            $targetVersion = static::versionPlus($currentVersion, 1);
        }

        $this->out('Release version: ' . $targetVersion);

        static::writeVersion($targetVersion);
        $this->replaceDocblockTags($targetVersion);

        $this->exec(sprintf('git commit -am "Release version: %s"', $targetVersion));
        $this->exec(sprintf('git tag %s', $targetVersion));

        $this->exec('git push');
        $this->exec('git push --tags');

        return true;
    }

    /**
     * writeVersion
     *
     * @param string $version
     *
     * @return  bool|int
     *
     * @since  __DEPLOY_VERSION__
     */
    protected static function writeVersion(string $version)
    {
        return file_put_contents(static::versionFile(), $version . "\n");
    }

    /**
     * versionFile
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    protected static function versionFile(): string
    {
        return __DIR__ . '/../VERSION';
    }

    /**
     * versionPlus
     *
     * @param string $version
     * @param int    $offset
     * @param string $suffix
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    protected static function versionPlus(string $version, int $offset, string $suffix = ''): string
    {
        [$version] = explode('-', $version, 2);

        $numbers = explode('.', $version);

        if (!isset($numbers[2])) {
            $numbers[2] = 0;
        }

        $numbers[2] += $offset;

        if ($numbers[2] === 0) {
            unset($numbers[2]);
        }

        $version = implode('.', $numbers);

        if ($suffix) {
            $version .= '-' . $suffix;
        }

        return $version;
    }

    /**
     * replaceDocblockTags
     *
     * @param string $version
     *
     * @return  void
     */
    protected function replaceDocblockTags(string $version): void
    {
        $this->out('Replacing Docblock...');

        $files = new RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                __DIR__ . '/../src',
                \FilesystemIterator::SKIP_DOTS
            )
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            $content = str_replace(
                ['{DEPLOY_VERSION}', '__DEPLOY_VERSION__', '__LICENSE__', '${ORGANIZATION}', '{ORGANIZATION}'],
                [$version, $version, 'LGPL-2.0-or-later', 'LYRASOFT', 'LYRASOFT'],
                $content
            );

            file_put_contents($file->getPathname(), $content);
        }

        $this->exec('git checkout master');
        $this->exec(sprintf('git commit -am "Prepare for %s release."', $version));
        $this->exec('git push origin master');
    }

    /**
     * exec
     *
     * @param   string $command
     *
     * @return  static
     */
    protected function exec($command)
    {
        $this->out('>> ' . $command);

        if (!$this->getOption('dry-run')) {
            system($command);
        }

        return $this;
    }
}

exit((new Build())->execute());
