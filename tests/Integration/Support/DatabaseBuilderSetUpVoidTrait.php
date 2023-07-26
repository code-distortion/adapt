<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\Support\Settings;

trait DatabaseBuilderSetUpVoidTrait
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Settings::resetStaticProps();

        $dir = self::resolveBaseProjectDir();
        chdir($dir);
        Settings::setProjectRootDir($dir);
    }



    /**
     * Resolve the base project directory.
     *
     * @return string
     */
    private static function resolveBaseProjectDir(): string
    {
        $temp = explode(DIRECTORY_SEPARATOR, __DIR__);
        array_pop($temp);
        array_pop($temp);
        array_pop($temp);
        return implode(DIRECTORY_SEPARATOR, $temp) . DIRECTORY_SEPARATOR;
    }
}
