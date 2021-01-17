<?php

namespace CodeDistortion\Adapt\Support;

/**
 * Common Adapt settings.
 */
class Settings
{
    /**
     * The name of the Adapt config file.
     *
     * @var string
     */
    const LARAVEL_CONFIG_NAME = 'code_distortion.adapt';

    /**
     * The name of the table that contains the reuse information.
     *
     * The version in this name will change if the structure of the table ever changes.
     *
     * @const string
     */
    const REUSE_TABLE = '____adapt____';

    /**
     * A version representing the way the reuse-table is structured and used.
     *
     * @const string
     */
    const REUSE_TABLE_VERSION = '2';

    /**
     * The name of the cookie used to pass database connection details during browser tests.
     *
     * @const string
     */
    const CONNECTIONS_COOKIE = '____adapt____';

    /**
     * The path that browsers connect to initially (when browser testing) so that cookies can then be set.
     *
     * @const string
     */
    const INITIAL_BROWSER_REQUEST_PATH = '/____adapt____';
}
