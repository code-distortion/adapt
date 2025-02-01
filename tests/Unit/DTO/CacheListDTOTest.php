<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\CacheListDTO;
use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\DTO\SnapshotMetaInfo;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use DateTime;
use PHPUnit\Framework\Attributes\Test;

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
    public static function cacheListDtoDataProvider(): array
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
     *
     * @return void
     */
    #[Test]
    public static function cache_list_dto_can_set_and_get_values()
    {
        $snapshotMetaInfo1 = new SnapshotMetaInfo('', '', new DateTime(), true, function () { return true; }, 14400);
        $snapshotMetaInfo2 = new SnapshotMetaInfo('', '', new DateTime(), true, function () { return true; }, 14400);
        $snapshots = [
            $snapshotMetaInfo1,
            $snapshotMetaInfo2,
        ];

        $databaseMetaInfo1 = new DatabaseMetaInfo('mysql', '', '', new DateTime(), true, function () { return true; }, 14400);
        $databaseMetaInfo2 = new DatabaseMetaInfo('mysql', '', '', new DateTime(), true, function () { return true; }, 14400);

        $databases1 = [
            $databaseMetaInfo1,
            $databaseMetaInfo2,
        ];

        $databaseMetaInfo3 = new DatabaseMetaInfo('mysql', '', '', new DateTime(), true, function () { return true; }, 14400);
        $databaseMetaInfo4 = new DatabaseMetaInfo('mysql', '', '', new DateTime(), true, function () { return true; }, 14400);

        $databases2 = [
            $databaseMetaInfo3,
            $databaseMetaInfo4,
        ];



        $cacheListDTO = new CacheListDTO();
        self::assertFalse($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())->snapshots($snapshots);
        self::assertTrue($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())->databases('mysql', $databases1);
        self::assertTrue($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())->databases('mysql', []);
        self::assertFalse($cacheListDTO->containsAnyCache());

        $cacheListDTO = (new CacheListDTO())
            ->snapshots($snapshots)
            ->databases('mysql', $databases1)
            ->databases('sqlite', $databases2);
        self::assertSame($snapshots, $cacheListDTO->snapshots);
        self::assertSame(
            ['mysql' => $databases1, 'sqlite' => $databases2],
            $cacheListDTO->databases
        );
    }
}
