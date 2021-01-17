<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\Initialise\InitialiseLaravelAdapt;

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
        DatabaseBuilder::resetStaticProps();
        InitialiseLaravelAdapt::resetStaticProps();
    }
}
