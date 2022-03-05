<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;

/**
 * Bootstrap Adapt to build a database remotely.
 */
abstract class BootRemoteBuildAbstract implements BootRemoteBuildInterface
{
    /** @var LogInterface The LogInterface to use. */
    protected LogInterface $log;



    /**
     * Set the LogInterface to use.
     *
     * @param LogInterface $log The logger to use.
     * @return static
     */
    public function log(LogInterface $log): self
    {
        $this->log = $log;
        return $this;
    }
}
