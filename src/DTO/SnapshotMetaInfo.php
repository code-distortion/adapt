<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;
use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * Store some meta-data about a snapshot file.
 */
class SnapshotMetaInfo
{
    /** @var string The snapshot's path. */
    public string $path;

    /** @var string The snapshot's filename. */
    public string $filename;

    /** @var DateTime|null When the file was last accessed. */
    public ?DateTime $accessDT;

    /** @var boolean Whether the snapshot is valid (current) on not. */
    public bool $isValid;

    /** @var callable The callback to use to get the snapshot's size. */
    public $getSizeCallback;

    /** @var integer|null The size of the snapshot file in bytes. */
    public ?int $size = null;

    /** @var callable|null The callback used to delete the snapshot file. */
    public $deleteCallback = null;

    /** @var integer The number of seconds grace-period before stale ones are to be deleted. */
    private int $graceSeconds;



    /**
     * @param string        $path            The snapshot's path.
     * @param string        $filename        The snapshot's filename.
     * @param DateTime|null $accessDT        When the file was last accessed.
     * @param boolean       $isValid         Whether the snapshot is valid (current) on not.
     * @param callable      $getSizeCallback The callback to use to get the snapshot's size.
     * @param integer       $graceSeconds    The number of seconds grace-period before stale ones are to be deleted.
     */
    public function __construct(
        string $path,
        string $filename,
        ?DateTime $accessDT,
        bool $isValid,
        callable $getSizeCallback,
        int $graceSeconds
    ) {
        $this->path = $path;
        $this->filename = $filename;
        $this->isValid = $isValid;
        $this->accessDT = $accessDT;
        $this->getSizeCallback = $getSizeCallback;
        $this->graceSeconds = $graceSeconds;
    }



    /**
     * Set the callback to delete the snapshot.
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
     * Delete the snapshot.
     *
     * @return boolean
     */
    public function delete(): bool
    {
        return $this->deleteCallback ? ($this->deleteCallback)() : false;
    }



    /**
     * Determine if this snapshot should be purged or not.
     *
     * @return boolean
     */
    public function shouldPurgeNow(): bool
    {
        $purgeAfter = $this->getPurgeAfter();
        $nowUTC = new DateTime('now', new DateTimeZone('UTC'));
        return $purgeAfter && $purgeAfter <= $nowUTC;
    }

    /**
     * Determine if this snapshot should be purged or not.
     *
     * @return DateTime|null
     */
    private function getPurgeAfter(): ?DateTime
    {
        if ($this->isValid) {
            return null;
        }
        if (!$this->accessDT) {
            return new DateTime('now', new DateTimeZone('UTC'));
        }

        return (clone $this->accessDT)->add(new DateInterval("PT{$this->graceSeconds}S"));
    }



    /**
     * Get the snapshot's size.
     *
     * @return integer
     */
    public function getSize(): int
    {
        return $this->size ??= ($this->getSizeCallback)();
    }



    /**
     * Generate a readable version of this snapshot.
     *
     * @return string
     */
    public function readable(): string
    {
        return $this->path
            . ' ' . Str::readableSize($this->getSize())
            . ($this->getPurgeAfter() ? ' - Stale' : '');
    }

    /**
     * Generate a readable version of this snapshot.
     *
     * @return string
     */
    public function readableWithPurgeInfo(): string
    {
        $purgeMessage = '';
        $purgeAfter = $this->getPurgeAfter();
        if ($purgeAfter) {
            $nowUTC = new DateTime('now', new DateTimeZone('UTC'));
            $purgeMessage = $purgeAfter > $nowUTC
                ? ' - Stale (will be removed automatically in a while)'
                : ' - Stale (will be removed automatically during the next test-run)';
        }

        return $this->path . ' ' . Str::readableSize($this->getSize()) . $purgeMessage;
    }
}
