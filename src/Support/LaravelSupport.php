<?php

namespace CodeDistortion\Adapt\Support;

use Orchestra\Testbench\TestCase;

class LaravelSupport
{
    /**
     * Test to see if this code is running within an orchestra/testbench TestCase.
     *
     * @return boolean
     */
    public static function isRunningInOrchestra(): bool
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrace as $stackEntry) {
            if (is_a($stackEntry['class'], TestCase::class, true)) {
                return true;
            }
        }
        return false;
    }
}

