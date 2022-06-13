<?php

namespace CodeDistortion\Adapt\Boot\Traits;

use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use Illuminate\Foundation\Application;

trait CheckLaravelChecksumPathsTrait
{
    /**
     * Check that the checksum-paths are valid.
     *
     * @param string[] $checksumPaths The files and directories to look through.
     * @return string[]
     * @throws AdaptConfigException When one of the $checksumPaths refers to the seeders directory, but it's invalid for
     *                              the current version of Laravel.
     */
    private function checkLaravelChecksumPaths(array $checksumPaths): array
    {
        $filesystem = new Filesystem();
        $seedersDir = database_path('seeders');
        $seedsDir = database_path('seeds');

        foreach ($checksumPaths as $path) {

            if (!in_array($path, [$seedersDir, $seedsDir])) {
                continue;
            }

            // realpath returns null when the file doesn't exist
            if ($filesystem->realpath($path)) {
                continue;
            }

            if (($path == $seedersDir) && (!$this->isLaravel8OrLater())) {
                throw AdaptConfigException::seedersDirInvalid($path);
            }
            if (($path == $seedsDir) && ($this->isLaravel8OrLater())) {
                throw AdaptConfigException::seedersDirInvalid($path);
            }
        }
        return $checksumPaths;
    }

    /**
     * Check if the current version of Laravel is >= 8.
     *
     * @return boolean
     */
    private function isLaravel8OrLater(): bool
    {
        /** @var Application $app */
        $app = app();
        return version_compare($app->version(), '8.0.0', '>=');
    }
}
