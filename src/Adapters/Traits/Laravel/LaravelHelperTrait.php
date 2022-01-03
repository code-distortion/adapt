<?php

namespace CodeDistortion\Adapt\Adapters\Traits\Laravel;

/**
 * General Laravel Database-adapter methods.
 */
trait LaravelHelperTrait
{
    /**
     * Retrieve a connection value from the Laravel's config.
     *
     * @param string $var     The name of the setting to get.
     * @param mixed  $default The default value to fall-back to.
     * @return mixed
     */
    protected function conVal($var, $default = null)
    {
        return config("database.connections.{$this->config->connection}.$var", $default);
    }

    /**
     * Retrieve the current connection's database's original name.
     *
     * @return string
     */
    protected function origDBName(): string
    {
        return $this->di->config->origDBName($this->config->connection);
    }
}
