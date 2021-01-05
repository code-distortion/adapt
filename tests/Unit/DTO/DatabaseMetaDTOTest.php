<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use App;
use CodeDistortion\Adapt\DTO\DatabaseMetaDTO;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;

/**
 * Test the DatabaseMetaDTO class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class DatabaseMetaDTOTest extends PHPUnitTestCase
{
    /**
     * Provide data for the database_meta_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function databaseMetaDtoDataProvider(): array
    {
        $sizes = [
            [0, '0B'],
            [1, '1B'],
            [1024, '1kB'],
            [1024 * 512, '512kB'],
            [1024 * 1024, '1MB'],
            [1024 * 1024 * 512, '512MB'],
            [1024 * 1024 * 1024, '1GB'],
            [1024 * 1024 * 1024 * 512, '512GB'],
            [1024 * 1024 * 1024 * 1024, '1TB'],
            [1024 * 1024 * 1024 * 1024 * 512, '512TB'],
            [1024 * 1024 * 1024 * 1024 * 1024, '1PB'],
            [1024 * 1024 * 1024 * 1024 * 1024 * 512, '512PB'],
            [1024 * 1024 * 1024 * 1024 * 1024 * 1024, '1EB'],
        ];

        $return = [];
        foreach ($sizes as $size) {
            $return[] = [
                'name' => 'abc',
                'size' => $size[0],
                'expectedReadable' => 'abc ' . $size[1],
            ];
        }
        return $return;
    }

    /**
     * Test that the DatabaseMetaDTO object can set and get values properly.
     *
     * @test
     * @dataProvider databaseMetaDtoDataProvider
     * @param string  $name             The path to set.
     * @param integer $size             The size in bytes to set.
     * @param string  $expectedReadable The expected readable() output.
     * @return void
     */
    public function database_meta_dto_can_set_and_get_values(
        string $name,
        int $size,
        string $expectedReadable
    ) {

        $databaseMetaDTO = (new DatabaseMetaDTO())->name($name)->size($size);
        $this->assertSame($name, $databaseMetaDTO->name);
        $this->assertSame($size, $databaseMetaDTO->size);
        $this->assertSame($expectedReadable, $databaseMetaDTO->readable());
    }
}
