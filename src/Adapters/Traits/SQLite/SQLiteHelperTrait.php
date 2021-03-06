<?php

namespace CodeDistortion\Adapt\Adapters\Traits\SQLite;

/**
 * General SQLite Database-adapter methods.
 */
trait SQLiteHelperTrait
{
    /**
     * Check if a memory database will be used or not.
     *
     * @return boolean
     */
    protected function isMemoryDatabase()
    {
        return $this->config->database == ':memory:';
    }

    /**
     * Get the filename part of the given path.
     *
     * @param string $path The path to inspect.
     * @return string
     */
    protected function pickBaseFilename(string $path): string
    {
        $temp = explode('.', $this->pickFilename($path));
        if (count($temp) > 1) {
            array_pop($temp);
        }
        return (string) implode('.', $temp);
    }

    /**
     * Get the filename part of the given path.
     *
     * @param string $path The path to inspect.
     * @return string
     */
    protected function pickFilename(string $path): string
    {
        $temp = explode('/', $path);
        return (string) array_pop($temp);
    }
}
