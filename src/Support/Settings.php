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
}
