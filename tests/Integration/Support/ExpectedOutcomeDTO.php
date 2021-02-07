<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

/**
 * A DTO containing a group of ExpectedValuesDTO's.
 */
class ExpectedOutcomeDTO
{
    /** @var string|null The expected name of the database. */
    public ?string $databaseName = null;

    /** @var string[] The expected tables in the database. */
    public array $expectedTables = [];

    /** @var ExpectedValuesDTO[] Values expected in certain tables. */
    public array $expectedValues = [];


    /**
     * Set the expected database name.
     *
     * @param string $databaseName The expected database name.
     * @return static
     */
    public function databaseName(string $databaseName): self
    {
        $this->databaseName = $databaseName;
        return $this;
    }

    /**
     * Set the expected list of tables.
     *
     * @param string[] $expectedTables The expected tables.
     * @return static
     */
    public function expectedTables(array $expectedTables): self
    {
        $this->expectedTables = $expectedTables;
        return $this;
    }

    /**
     * Add a list of values expected in a table.
     *
     * @param ExpectedValuesDTO $expectedValues Some values to expect in a table.
     * @return static
     */
    public function addExpectedValues(ExpectedValuesDTO $expectedValues): self
    {
        $this->expectedValues[] = $expectedValues;
        return $this;
    }
}
