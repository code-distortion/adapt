<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Adapters\DBAdapter;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DTO\VersionsDTO;

/**
 * Provides functionality related to version detection.
 */
class VersionSupport
{
    /**
     * Get the versions of things.
     *
     * @param DBAdapter    $dbAdapter The database adapter currently being used.
     * @param LogInterface $log       The log object to log with.
     * @return VersionsDTO
     */
    public static function buildVersionDTO($dbAdapter, $log): VersionsDTO
    {
        $logTimer = $log->newTimer();

        $versionsDTO = new VersionsDTO();
        $versionsDTO->osVersion(PHP_OS);
        $versionsDTO->phpVersion(phpversion());
        self::resolvePackageVersions($versionsDTO);
        $dbAdapter->version->resolveDatabaseVersion($versionsDTO);

        $log->vDebug("Detected software versions", $logTimer);

        return $versionsDTO;
    }

    /**
     * Resolve the versions of packages used.
     *
     * @param VersionsDTO $versionsDTO The VersionsDTO object to update.
     * @return void
     */
    private static function resolvePackageVersions(VersionsDTO $versionsDTO)
    {
        $composerLock = file_get_contents("composer.lock");
        if (!$composerLock) {
            return;
        }

        $versionsDTO->adaptVersion(static::getPackageVersion($composerLock, 'code-distortion/adapt'));
        $versionsDTO->phpUnitVersion(static::getPackageVersion($composerLock, 'phpunit/phpunit'));
        $versionsDTO->pestVersion(static::getPackageVersion($composerLock, 'pestphp/pest'));
        $versionsDTO->laravelVersion(static::getPackageVersion($composerLock, 'laravel/framework'));
    }

    /**
     * Look inside composer.lock and find the version of a particular package.
     *
     * @param string $packageName The name of the package to check.
     * @return string|null
     */
    private static function getPackageVersion(string $composerLock, string $packageName)
    {
        $regex = '/"name": "' . preg_quote($packageName, '/') . '",\s*"version": "([^"]+)",/';
        return preg_match($regex, $composerLock, $matches)
            ? $matches[1]
            : null;
    }
}
