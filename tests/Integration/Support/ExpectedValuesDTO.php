<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

class ExpectedValuesDTO
{
    /**
     * The table to expect values in.
     *
     * @var string
     */
    public string $table;

    /**
     * The fields to fetch.
     *
     * @var string[]
     */
    public array $fields = [];

    /**
     * Values expected in this table.
     *
     * @var mixed[]
     */
    public array $values = [];

    /**
     * ExpectedValuesDTO constructor.
     *
     * @param string $table  The table to expect values in.
     * @param array  $fields The fields to check.
     * @param array  $values The values expected in this table.
     */
    public function __construct(string $table, array $fields, array $values)
    {
        $this->table = $table;
        $this->fields = $fields;
        $this->values = $values;
    }
}
