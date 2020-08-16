<?php

namespace CodeDistortion\Adapt\DTO;

/**
 * Contains a list of snapshot files and databases.
 */
class CacheListDTO
{
    /**
     * The list of snapshot files.
     *
     * @var SnapshotMetaDTO[]
     */
    public $snapshots = [];

    /**
     * The list of databases (per connection).
     *
     * @var DatabaseMetaDTO[][]
     */
    public $databases = [];


    /**
     * Replace the list of snapshot-paths with a new list.
     *
     * @param SnapshotMetaDTO[] $snapshots The snapshot paths to store.
     * @return static
     */
    public function snapshots(array $snapshots): self
    {
        $this->snapshots = $snapshots;
        return $this;
    }

    /**
     * Replace the list of databases for a particular connection.
     *
     * @param string            $connection The connection these databases were found in.
     * @param DatabaseMetaDTO[] $databases  The databases to store.
     * @return static
     */
    public function databases(string $connection, array $databases): self
    {
        if ($databases) {
            $this->databases[$connection] = $databases;
        } else {
            unset($this->databases[$connection]);
        }
        return $this;
    }

    /**
     * Find out if this object contains a cache of some sort.
     *
     * @return boolean
     */
    public function containsAnyCache(): bool
    {
        return (($this->snapshots) || ($this->databases));
    }
}
