<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\Support\StringSupport as Str;
use DateInterval;
use DateTime;
use DateTimeZone;

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

    /** @var boolean Whether the database is valid (current) on not. */
    public bool $isValid;

    /** @var callable The callback to use to get the database's size. */
    public $getSizeCallback;

    /** @var integer|null The size of the database in bytes. */
    public ?int $size = null;

    /** @var callable The callback used to delete the database file. */
    public $deleteCallback = null;

    /** @var integer The number of seconds grace-period before invalid ones are to be deleted. */
    private int $graceSeconds;



    /**
     * @param string        $connection      The connection the database is within.
     * @param string        $name            The database's name / path.
     * @param DateTime|null $accessDT        When the database was last accessed.
     * @param boolean       $isValid         Whether the database is valid (current) on not.
     * @param callable      $getSizeCallback The callback to use to calculate the database's size.
     * @param integer       $graceSeconds    The number of seconds grace-period before invalid ones are to be deleted.
     */
    public function __construct(
        string $connection,
        string $name,
        ?DateTime $accessDT,
        bool $isValid,
        callable $getSizeCallback,
        int $graceSeconds
    ) {
        $this->connection = $connection;
        $this->name = $name;
        $this->isValid = $isValid;
        $this->accessDT = $accessDT;
        $this->getSizeCallback = $getSizeCallback;
        $this->graceSeconds = $graceSeconds;
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
     * Delete the database.
     *
     * @return boolean
     */
    public function delete(): bool
    {
        return $this->deleteCallback ? ($this->deleteCallback)() : false;
    }



    /**
     * Remove the snapshot if it should be removed.
     *
     * @return void
     */
    public function purgeIfNeeded(): void
    {
        if ($this->shouldPurgeNow()) {
            $this->delete();
        }
    }

    /**
     * Determine if this snapshot should be purged or not.
     *
     * @return boolean
     */
    private function shouldPurgeNow(): bool
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
        return $this->name
            . ' ' . Str::readableSize($this->getSize())
            . ($this->getPurgeAfter() ? ' - Invalid' : '');
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
                ? ' - Invalid (automatic removal: ' . Str::vagueReadableInterval($nowUTC->diff($purgeAfter)) . ')'
                : ' - Invalid (automatic removal: next test-run)';
        }

        return $this->name
            . ' ' . Str::readableSize($this->getSize())
            . $purgeMessage;
    }
}
