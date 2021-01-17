<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\Initialise\InitialiseLaravelAdapt;

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
        DatabaseBuilder::resetStaticProps();
        InitialiseLaravelAdapt::resetStaticProps();
    }
}
