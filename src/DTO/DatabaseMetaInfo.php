<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;
use DateTime;

/**
 * Store some meta-data about a database.
 */
class DatabaseMetaInfo
{
    /** @var string The connection the database is within. */
    public string $connection;

    /** @var string The database's name / path. */
    public string $name;

    /** @var DateTime|null When the file was last accessed. */
    public ?DateTime $accessDT;

    /** @var boolean Whether the database matches the "current" databases or not */
    public bool $matchesOrigDB;

    /** @var boolean Whether the database is valid (current) on not. */
    public bool $isValid;

    /** @var callable The callback to use to get the database's size. */
    public $getSizeCallback;

    /** @var integer|null The size of the database in bytes. */
    public ?int $size = null;

    /** @var callable The callback used to delete the database file. */
    public $deleteCallback = null;



    /**
     * @param string        $connection      The connection the database is within.
     * @param string        $name            The database's name / path.
     * @param DateTime|null $accessDT        When the database was last accessed.
     * @param boolean       $matchesOrigDB   Whether the database matches the "current" databases or not.
     * @param boolean       $isValid         Whether the database is valid (current) on not.
     * @param callable      $getSizeCallback The callback to use to calculate the database's size.
     */
    public function __construct(
        string $connection,
        string $name,
        DateTime $accessDT,
        bool $matchesOrigDB,
        bool $isValid,
        callable $getSizeCallback
    ) {
        $this->connection = $connection;
        $this->name = $name;
        $this->matchesOrigDB = $matchesOrigDB;
        $this->isValid = $isValid;
        $this->accessDT = $accessDT;
        $this->getSizeCallback = $getSizeCallback;
        return $this;
    }

    /**
     * Set the callback to delete the database.
     *
     * @param callable $deleteCallback The callback to call.
     * @return $this
     */
    public function setDeleteCallback(callable $deleteCallback): self
    {
        $this->deleteCallback = $deleteCallback;
        return $this;
    }

    /**
     * Remove the snapshot if it should be removed.
     */
    public function purgeIfNeeded(): void
    {
        if ($this->shouldBePurged()) {
            $this->delete();
        }
    }

    /**
     * Determine if this snapshot should be purged or not.
     *
     * @return boolean
     */
    private function shouldBePurged(): bool
    {
        return !$this->isValid;
    }

    /**
     * Delete the database.
     *
     * @return boolean
     */
    public function delete(): bool
    {
        return $this->deleteCallback ? ($this->deleteCallback)() : false;
    }

    /**
     * Get the database's size.
     *
     * @return integer
     */
    public function getSize(): int
    {
        return $this->size ??= ($this->getSizeCallback)();
    }

    /**
     * Generate a readable version of this database.
     *
     * @return string
     */
    public function readable(): string
    {
        return $this->name . ' ' . Str::readableSize($this->getSize());
    }
}
