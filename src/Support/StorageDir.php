<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\FilesystemInterface;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use Throwable;

/**
 * Methods relating to the set up of the storage directory.
 */
class StorageDir
{
    /**
     * Create the storage directory if it doesn't exist.
     *
     * @param string              $storageDir The storage directory to check/create.
     * @param FilesystemInterface $filesystem The filesystem object to use.
     * @param LogInterface        $log        The log object to use.
     * @return void
     * @throws AdaptConfigException When the directory could not be created.
     */
    public static function ensureStorageDirExists(
        $storageDir,
        $filesystem,
        $log
    ) {

        if (!$storageDir) {
            throw AdaptConfigException::cannotCreateStorageDir($storageDir);
        }

        if ($filesystem->pathExists($storageDir)) {
            if ($filesystem->isFile($storageDir)) {
                throw AdaptConfigException::storageDirIsAFile($storageDir);
            }
        } else {

            $e = null;
            try {
                $logTimer = $log->newTimer();

                // create the storage directory
                if ($filesystem->mkdir($storageDir, 0744, true)) {

                    // create a .gitignore file
                    $filesystem->writeFile(
                        $storageDir . '/.gitignore',
                        'w',
                        '*' . PHP_EOL . '!.gitignore' . PHP_EOL
                    );
                }
                $log->debug('Created adapt-test-storage dir: "' . $storageDir . '"', $logTimer);
            } catch (Throwable $e) {
            }

            if (($e) || (!$filesystem->dirExists($storageDir))) {
                throw AdaptConfigException::cannotCreateStorageDir($storageDir, $e);
            }
        }
    }
}
