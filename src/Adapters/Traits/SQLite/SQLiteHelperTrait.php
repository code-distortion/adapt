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
     * @param string|null $name The database name, otherwise it's picked from the ConfigDTO.
     * @return boolean
     */
    protected function isMemoryDatabase(?string $name = null)
    {
        return ($name ?? $this->configDTO->database) == ':memory:';
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
        return implode('.', $temp);
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
