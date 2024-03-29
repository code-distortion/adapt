<?php

namespace CodeDistortion\Adapt\DI;

use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\FilesystemInterface;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;

/**
 * A dependency injection object.
 */
class DIContainer
{
    /** @var LaravelArtisan The LaravelArtisan object to use. */
    public $artisan;

    /** @var LaravelDB The LaravelDB object to use. */
    public $db;

    /** @var Exec The Exec object to use. */
    public $exec;

    /** @var FilesystemInterface The Filesystem object to use. */
    public $filesystem;

    /** @var LogInterface The Log object to use. */
    public $log;


    /**
     * Set the LaravelArtisan object to use.
     *
     * @param LaravelArtisan $artisan The LaravelArtisan object to store.
     * @return static
     */
    public function artisan($artisan): self
    {
        $this->artisan = $artisan;
        return $this;
    }

    /**
     * Set the LaravelDB object to use.
     *
     * @param LaravelDB $db The LaravelDB object to store.
     * @return static
     */
    public function db($db): self
    {
        $this->db = $db;
        return $this;
    }

    /**
     * Set the Exec object to use.
     *
     * @param Exec $exec The Exec object to store.
     * @return static
     */
    public function exec($exec): self
    {
        $this->exec = $exec;
        return $this;
    }

    /**
     * Set the Filesystem object to use.
     *
     * @param FilesystemInterface $filesystem The Filesystem object to store.
     * @return static
     */
    public function filesystem($filesystem): self
    {
        $this->filesystem = $filesystem;
        return $this;
    }

    /**
     * Set the Log object to use.
     *
     * @param LogInterface $log The Log object to store.
     * @return static
     */
    public function log($log): self
    {
        $this->log = $log;
        return $this;
    }
}
