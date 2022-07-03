<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\FilesystemInterface;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use Throwable;

/**
 * Methods relating to the setup of the storage directory.
 */
class StorageDir
{
    /**
     * Make sure the storage directories exist.
     *
     * @param string              $storageDir The storage directory to check/create.
     * @param FilesystemInterface $filesystem The filesystem object to use.
     * @param LogInterface        $log        The log object to use.
     * @return void
     * @throws AdaptConfigException When the directory could not be created.
     */
    public static function ensureStorageDirsExist(
        $storageDir,
        $filesystem,
        $log
    ) {

        StorageDir::ensureStorageDirExists($storageDir, $filesystem, $log, true);
        StorageDir::ensureStorageDirExists(Settings::databaseDir($storageDir), $filesystem, $log, false);
        StorageDir::ensureStorageDirExists(Settings::snapshotDir($storageDir), $filesystem, $log, false);
        StorageDir::ensureStorageDirExists(Settings::shareConfigDir($storageDir), $filesystem, $log, false);
    }

    /**
     * Create a directory if it doesn't exist.
     *
     * @param string              $dir             The storage directory to check/create.
     * @param FilesystemInterface $filesystem      The filesystem object to use.
     * @param LogInterface        $log             The log object to use.
     * @param boolean             $createGitIgnore Whether to create a .gitignore file or not.
     * @return void
     * @throws AdaptConfigException When the directory could not be created.
     */
    private static function ensureStorageDirExists(
        string $dir,
        FilesystemInterface $filesystem,
        LogInterface $log,
        bool $createGitIgnore
    ) {

        if (!$dir) {
            throw AdaptConfigException::cannotCreateStorageDir($dir);
        }

        try {

            if ($filesystem->pathExists($dir)) {
                if ($filesystem->isFile($dir)) {
                    throw AdaptConfigException::storageDirIsAFile($dir);
                }

            } else {
                if (!$filesystem->mkdir($dir, 0744, true)) {
                    throw AdaptConfigException::cannotCreateStorageDir($dir);
                }
                $log->vDebug("Created Adapt storage dir: \"$dir\"");
            }

            static::createGitIgnoreIfNeeded($dir, $filesystem, $log, $createGitIgnore);

        } catch (AdaptConfigException $e) {
            throw $e; // just rethrow as is
        } catch (Throwable $e) {
            throw AdaptConfigException::cannotCreateStorageDir($dir, $e);
        }
    }

    /**
     * Create the .gitignore file if it doesn't exist (and is needed).
     *
     * @param string              $dir             The storage directory to check/create.
     * @param FilesystemInterface $filesystem      The filesystem object to use.
     * @param LogInterface        $log             The log object to use.
     * @param boolean             $createGitIgnore Whether to create a .gitignore file or not.
     * @return void
     * @throws AdaptConfigException When the .gitignore file can't be created.
     */
    private static function createGitIgnoreIfNeeded(
        string $dir,
        FilesystemInterface $filesystem,
        LogInterface $log,
        bool $createGitIgnore
    ) {

        if (!$createGitIgnore) {
            return;
        }

        $path = "$dir/.gitignore";

        if ($filesystem->pathExists($path)) {
            return;
        }

        if (!$filesystem->writeFile($path, 'w', '*' . PHP_EOL . '!.gitignore' . PHP_EOL)) {
            throw AdaptConfigException::cannotCreateGitIgnoreFile($path);
        }

        $log->vDebug("Created .gitignore file: \"$path\"");
    }
}
