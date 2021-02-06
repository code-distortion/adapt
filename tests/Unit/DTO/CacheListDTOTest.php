<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\DTO\DatabaseMetaDTO;
use CodeDistortion\Adapt\DTO\SnapshotMetaInfo;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use DateTime;

/**
 * Test the ConfigDTO class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CacheListDTOTest extends PHPUnitTestCase
{
    /**
     * Provide data for the cache_list_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function cacheListDtoDataProvider(): array
    {
        return [
            'projectName' => [
                'method' => 'projectName',
                'params' => ['projectName' => 'my-project'],
            ],
        ];
    }

    /**
     * Test that the CacheListDTO object can set and get values properly.
     *
     * @test
     * @return void
     */
    public function cache_list_dto_can_set_and_get_values(): void
    {
        $snapshotMetaInfo1 = new SnapshotMetaInfo('', '', new DateTime(), true, fn() => true);
        $snapshotMetaInfo2 = new SnapshotMetaInfo('', '', new DateTime(), true, fn() => true);
        $snapshots = [
            $snapshotMetaInfo1,
            $snapshotMetaInfo2,
        ];

        $databaseMetaDTO1 = new DatabaseMetaDTO();
        $databaseMetaDTO2 = new DatabaseMetaDTO();
        $databases1 = [
            $databaseMetaDTO1,
            $databaseMetaDTO2,
        ];

        $databaseMetaDTO3 = new DatabaseMetaDTO();
        $databaseMetaDTO4 = new DatabaseMetaDTO();
        $databases2 = [
            $databaseMetaDTO3,
            $databaseMetaDTO4,
        ];



        $cacheListDTO = new CacheListDTO();
        $this->assertFalse($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())->snapshots($snapshots);
        $this->assertTrue($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())->databases('mysql', $databases1);
        $this->assertTrue($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())->databases('mysql', []);
        $this->assertFalse($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())
            ->snapshots($snapshots)
            ->databases('mysql', $databases1)
            ->databases('sqlite', $databases2);
        $this->assertSame($snapshots, $cacheListDTO->snapshots);
        $this->assertSame(
            ['mysql' => $databases1, 'sqlite' => $databases2],
            $cacheListDTO->databases
        );
    }
}
