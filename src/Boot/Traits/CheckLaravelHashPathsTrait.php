<?php

namespace CodeDistortion\Adapt\Boot\Traits;

use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\LaravelSupport;

trait CheckLaravelHashPathsTrait
{
    /**
     * Check that the hash-paths are valid.
     *
     * @param string[] $hashPaths The files and directories to look through.
     * @return string[]
     * @throws AdaptConfigException When one of the $hashPaths refers to the seeders directory, but it's invalid for
     *                              the current version of Laravel.
     */
    private function checkLaravelHashPaths(array $hashPaths): array
    {
        $filesystem = new Filesystem();
        $seedersDir = database_path('seeders');
        $seedsDir = database_path('seeds');

        foreach ($hashPaths as $path) {

            if (!in_array($path, [$seedersDir, $seedsDir])) {
                continue;
            }

            // realpath return null when the file doesn't exist
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
        return $hashPaths;
    }

    /**
     * Check if the current version of Laravel is >= 8.
     *
     * @return bool
     */
    private function isLaravel8OrLater(): bool
    {
        return version_compare(app()->version(), '8.0.0', '>=');
    }
}
