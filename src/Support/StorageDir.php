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
        string $storageDir,
        FilesystemInterface $filesystem,
        LogInterface $log
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
    ): void {

        if (!$dir) {
            throw AdaptConfigException::cannotCreateStorageDir($dir);
        }

        if ($filesystem->pathExists($dir)) {
            if ($filesystem->isFile($dir)) {
                throw AdaptConfigException::storageDirIsAFile($dir);
            }
        } else {

            try {
                $logTimer = $log->newTimer();

                // create the storage directory
                if (!$filesystem->mkdir($dir, 0744, true)) {
                    throw AdaptConfigException::cannotCreateStorageDir($dir);
                }

                // create a .gitignore file
                if ($createGitIgnore) {
                    $filesystem->writeFile("$dir/.gitignore", 'w', '*' . PHP_EOL . '!.gitignore' . PHP_EOL);
                }

                $log->vDebug("Created adapt-test-storage dir: \"$dir\"", $logTimer);

            } catch (AdaptConfigException $e) {
                throw $e; // just rethrow as is
            } catch (Throwable $e) {
                throw AdaptConfigException::cannotCreateStorageDir($dir, $e);
            }

            if (!$filesystem->dirExists($dir)) {
                throw AdaptConfigException::cannotCreateStorageDir($dir);
            }
        }
    }
}
