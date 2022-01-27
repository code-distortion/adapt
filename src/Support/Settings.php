<?php

namespace CodeDistortion\Adapt\Support;

use CodeDistortion\Adapt\Adapters\LaravelMySQL\LaravelMySQLSnapshot;

/**
 * Common Adapt settings.
 */
class Settings
{
    /** @var string The name of the Adapt config file. */
    const LARAVEL_CONFIG_NAME = 'code_distortion.adapt';

    /** @var string The table that contains Adapt's re-use meta-information. */
    const REUSE_TABLE = '____adapt____';

    /** @var string A version representing the way the reuse-table is structured and used. */
    const REUSE_TABLE_VERSION = '4';

    /** @var string The name of the cookie used to pass database connection details during browser tests. */
    const CONFIG_COOKIE = '____adapt____';

    /** @var string The path that browsers connect to initially (when browser testing) so cookies can then be set. */
    const INITIAL_BROWSER_COOKIE_REQUEST_PATH = '/____adapt____/cookie';

    /** @var string The path used by Adapt when instructing another installation of Adapt to build a database. */
    const REMOTE_BUILD_REQUEST_PATH = '/____adapt____/remote-build';

    /** @var string The http-header used to pass database connection details to other Adapt installations. */
    const SHARE_CONNECTIONS_HTTP_HEADER_NAME = 'adapt-connection-dbs';

    /** @var string The name of the singleton that's registered with Laravel, containe the connection database list. */
    const SHARE_CONNECTIONS_SINGLETON_NAME = 'adapt-connection-dbs';

    /** @var integer The number of seconds grace-period before invalid databases & snapshots are to be deleted. */
    const DEFAULT_INVALIDATION_GRACE_SECONDS = 14400; // 4 hours

    /**
     * A place for BootTestAbstract's first-test flag, which can't have its own
     * (it's not shared between the test-classes the trait is included in).
     *
     * @var boolean
     */
    public static $isFirstTest = true;

    /**
     * Reset anything that should be reset between internal tests of the Adapt package.
     *
     * @return void
     */
    public static function resetStaticProps()
    {
        static::$isFirstTest = true;
        Hasher::resetStaticProps();
        LaravelMySQLSnapshot::resetStaticProps();
    }
}
