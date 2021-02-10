<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\Support\Settings;

trait DatabaseBuilderSetUpNoVoidTrait
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Settings::resetStaticProps();
    }
}
