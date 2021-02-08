<?php

namespace CodeDistortion\Adapt\Support;

/**
 * Common Adapt settings.
 */
class Settings
{
    /** @var string The name of the Adapt config file. */
    public const LARAVEL_CONFIG_NAME = 'code_distortion.adapt';

    /** @var string The table that contains Adapt's re-use meta-information. */
    public const REUSE_TABLE = '____adapt____';

    /** @var string A version representing the way the reuse-table is structured and used. */
    public const REUSE_TABLE_VERSION = '3';

    /** @var string The name of the cookie used to pass database connection details during browser tests. */
    public const CONFIG_COOKIE = '____adapt____';

    /** @var string The path that browsers connect to initially (when browser testing) so cookies can then be set. */
    public const INITIAL_BROWSER_COOKIE_REQUEST_PATH = '/____adapt____';

    /** @var integer The number of seconds grace-period before invalid databases & snapshots are to be deleted. */
    public const DEFAULT_INVALIDATION_GRACE_SECONDS = 14400; // 4 hours

    /**
     * A place for InitialiseLaravelAdapt's first-test flag, which can't have its own
     * (it's not shared between the test-classes the trait is included in).
     *
     * @var boolean
     */
    public static bool $isFirstTest = true;
}
