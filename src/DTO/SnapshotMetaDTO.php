<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;

/**
 * Store some meta-data about a snapshot file.
 */
class SnapshotMetaDTO
{
    /**
     * The snapshot's path.
     *
     * @var string|null
     */
    public $path = null;

    /**
     * The size of the snapshot file in bytes.
     *
     * @var integer|null
     */
    public $size = null;


    /**
     * Set the snapshot's path.
     *
     * @param string $path The snapshot path.
     * @return static
     */
    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set the snapshot file size.
     *
     * @param integer|null $size The snapshot file size in bytes.
     * @return static
     */
    public function size($size): self
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Generate a readable version of this snapshot.
     *
     * @return string
     */
    public function readable(): string
    {
        return $this->path . (!is_null($this->size) ? ' ' . Str::readableSize($this->size) : '');
    }
}
