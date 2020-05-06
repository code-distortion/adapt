<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;

/**
 * Store some meta-data about a database.
 */
class DatabaseMetaDTO
{
    /**
     * The database's name / path.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The size of the database file in bytes.
     *
     * @var integer|null
     */
    public ?int $size = null;


    /**
     * Set the database's name / path.
     *
     * @param string $name The database name.
     * @return static
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the database size.
     *
     * @param integer|null $size The size of the database in bytes.
     * @return static
     */
    public function size(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Generate a readable version of this database.
     *
     * @return string
     */
    public function readable(): string
    {
        return $this->name.(!is_null($this->size) ? ' '.Str::readableSize($this->size) : '');
    }
}
