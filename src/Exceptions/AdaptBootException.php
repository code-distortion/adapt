<?php

namespace CodeDistortion\Adapt\Exceptions;

/**
 * Exceptions generated when booting a builder.
 */
class AdaptBootException extends AdaptException
{
    /**
     * Thrown when a no PropBag was set when booting a builder.
     *
     * @return self
     */
    public static function propBagNotSet(): self
    {
        return new self('No prob-bag was set');
    }

    /**
     * Thrown when the --recreate-databases option has been added when --parallel testing.
     *
     * Because Adapt dynamically decides which database/s to use based on the settings for each test, it's not
     * practical to pre-determine which ones to rebuild. And because of the nature of parallel testing, it's also not
     * possible to simply remove oll of the databases before running the tests.
     *
     * @return self
     */
    public static function parallelTestingSaysRebuildDBs(): self
    {
        return new self('Instead of using --recreate-databases, please use "php artisan adapt:remove (--force)"');
    }
}
