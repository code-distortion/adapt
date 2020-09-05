<?php

namespace CodeDistortion\Adapt\Support;

use Orchestra\Testbench\TestCase;

/**
 * Provides extra miscellaneous Laravel related support functionality.
 */
class LaravelSupport
{
    /**
     * Test to see if this code is running within an orchestra/testbench TestCase.
     *
     * @return boolean
     */
    public static function isRunningInOrchestra(): bool
    {
        $basePath = (string) base_path();
        $realpath = (string) realpath('.');
        if (mb_strpos($basePath, $realpath) === 0) {
            $rest = mb_substr($basePath, mb_strlen($realpath));
            return (mb_substr($rest, 0, mb_strlen('/vendor/orchestra/')) == '/vendor/orchestra/');
        }
        return false;
    }
}
