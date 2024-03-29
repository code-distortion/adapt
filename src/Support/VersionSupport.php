<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Adapters\DBAdapter;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DTO\VersionsDTO;
use Illuminate\Foundation\Application;

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
        $versionsDTO->adaptVersion(Settings::PACKAGE_VERSION);

        /** @var Application $app */
        $app = app();
        $versionsDTO->laravelVersion($app->version());

        if (!file_exists(LaravelSupport::basePath("composer.lock"))) {
            return;
        }

        $composerLock = file_get_contents(LaravelSupport::basePath("composer.lock"));
        if (!$composerLock) {
            return;
        }

        $versionsDTO->phpUnitVersion(static::getPackageVersion($composerLock, 'phpunit/phpunit'));
        $versionsDTO->pestVersion(static::getPackageVersion($composerLock, 'pestphp/pest'));
    }

    /**
     * Look inside composer.lock and find the version of a particular package.
     *
     * @param string $composerLock The composer.lock file contents.
     * @param string $packageName  The name of the package to check.
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
