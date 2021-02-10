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
    }
}
