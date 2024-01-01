<?php

namespace CodeDistortion\Adapt\Support;

/**
 * Provides platform related functionality.
 */
class PlatformSupport
{
    /**
     * Work out if the current platform is Windows.
     *
     * @return boolean
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Work out if a GitHub Action is running this script.
     *
     * @return boolean
     */
    public static function isRunningGitHubActions(): bool
    {
        return (isset($_SERVER['GITHUB_ACTIONS'])) && ($_SERVER['GITHUB_ACTIONS'] === 'true');
    }
}
