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
     * @param mixed  $default The default value to fall back to.
     * @return mixed
     */
    protected function conVal($var, $default = null)
    {
        return config("database.connections.{$this->configDTO->connection}.$var", $default);
    }
}
