<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;
use DateTime;

/**
 * Store some meta-data about a snapshot file.
 */
class SnapshotMetaInfo
{
    /** @var string|null The snapshot's path. */
    public ?string $path;

    /** @var string|null The snapshot's filename. */
    public ?string $filename;

    /** @var DateTime|null When the file was last accessed. */
    public ?DateTime $accessDT;

    /** @var boolean Whether the snapshot is valid (current) on not. */
    public bool $isValid;

    /** @var callable The callback to use to get the file's size. */
    public $getFilesizeCallback;

    /** @var integer|null The size of the snapshot file in bytes. */
    public ?int $size = null;

    /** @var callable The callback used to delete the snapshot file. */
    public $deleteCallback = null;



    /**
     * @param string        $path                The snapshot's path.
     * @param string        $filename            The snapshot's filename.
     * @param DateTime|null $accessDT            When the file was last accessed.
     * @param boolean       $isValid             Whether the snapshot is valid (current) on not.
     * @param callable      $getFilesizeCallback The callback to use to get the file's size.
     */
    public function __construct(
        string $path,
        string $filename,
        DateTime $accessDT,
        bool $isValid,
        callable $getFilesizeCallback
    ) {
        $this->path = $path;
        $this->filename = $filename;
        $this->isValid = $isValid;
        $this->accessDT = $accessDT;
        $this->getFilesizeCallback = $getFilesizeCallback;
        return $this;
    }

    /**
     * Set the callback to delete the snapshot file.
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
     * Delete the snapshot file.
     *
     * @return boolean
     */
    public function delete(): bool
    {
        return $this->deleteCallback ? ($this->deleteCallback)() : false;
    }

    /**
     * Get the file's size.
     *
     * @return integer
     */
    public function getSize(): int
    {
        return $this->size ??= ($this->getFilesizeCallback)();
    }

    /**
     * Generate a readable version of this snapshot.
     *
     * @return string
     */
    public function readable(): string
    {
        return $this->path . ' ' . Str::readableSize($this->getSize());
    }
}
