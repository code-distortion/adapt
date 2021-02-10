<?php

namespace CodeDistortion\Adapt\DI;

use CodeDistortion\Adapt\DI\Injectable\Laravel\Exec;
use CodeDistortion\Adapt\DI\Injectable\Laravel\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\Laravel\LaravelLog;

/**
 * A dependency injection object.
 */
class DIContainer
{
    /** @var LaravelArtisan The LaravelArtisan object to use. */
    public $artisan;

    /** @var LaravelConfig The LaravelConfig object to use. */
    public $config;

    /** @var LaravelDB The LaravelDB object to use. */
    public $db;

    /** @var callable|null The closure to call to start a database transaction. */
    public $dbTransactionClosure;

    /** @var Exec The Exec object to use. */
    public $exec;

    /** @var Filesystem The Filesystem object to use. */
    public $filesystem;

    /** @var LaravelLog The Log object to use. */
    public $log;


    /**
     * Set the LaravelArtisan object to use.
     *
     * @param LaravelArtisan $artisan The LaravelArtisan object to store.
     * @return static
     */
    public function artisan(LaravelArtisan $artisan)
    {
        $this->artisan = $artisan;
        return $this;
    }

    /**
     * Set the LaravelConfig object to use.
     *
     * @param LaravelConfig $config The LaravelConfig object to store.
     * @return static
     */
    public function config(LaravelConfig $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Set the LaravelDB object to use.
     *
     * @param LaravelDB $db The LaravelDB object to store.
     * @return static
     */
    public function db(LaravelDB $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * Set the closure to call to start a database transaction.
     *
     * @param callable|null $dbTransactionClosure The closure to store.
     * @return static
     */
    public function dbTransactionClosure($dbTransactionClosure)
    {
        $this->dbTransactionClosure = $dbTransactionClosure;
        return $this;
    }

    /**
     * Set the Exec object to use.
     *
     * @param Exec $exec The Exec object to store.
     * @return static
     */
    public function exec(Exec $exec)
    {
        $this->exec = $exec;
        return $this;
    }

    /**
     * Set the Filesystem object to use.
     *
     * @param Filesystem $filesystem The Filesystem object to store.
     * @return static
     */
    public function filesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        return $this;
    }

    /**
     * Set the Log object to use.
     *
     * @param LaravelLog $log The Log object to store.
     * @return static
     */
    public function log(LaravelLog $log)
    {
        $this->log = $log;
        return $this;
    }
}
