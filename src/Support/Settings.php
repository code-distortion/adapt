<?php

namespace CodeDistortion\Adapt\Support;

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
    const REUSE_TABLE_VERSION = '3';

    /** @var string The name of the cookie used to pass database connection details during browser tests. */
    const CONFIG_COOKIE = '____adapt____';

    /** @var string The path that browsers connect to initially (when browser testing) so cookies can then be set. */
    const INITIAL_BROWSER_COOKIE_REQUEST_PATH = '/____adapt____';

    /** @var integer The number of seconds grace-period before invalid databases & snapshots are to be deleted. */
    const DEFAULT_INVALIDATION_GRACE_SECONDS = 14400; // 4 hours
}
