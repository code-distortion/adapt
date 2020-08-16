<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use App;
use CodeDistortion\Adapt\DTO\SnapshotMetaDTO;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;

/**
 * Test the SnapshotMetaDTO class
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class SnapshotMetaDTOTest extends PHPUnitTestCase
{
    /**
     * Provide data for the snapshot_meta_dto_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public function snapshotMetaDtoDataProvider(): array
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
                'path' => '/var/www/database/adapt-test-storage/abc.mysql',
                'size' => $size[0],
                'expectedReadable' => '/var/www/database/adapt-test-storage/abc.mysql '.$size[1],
            ];
        }
        return $return;
    }

    /**
     * Test that the SnapshotMetaDTO object can set and get values properly.
     *
     * @test
     * @dataProvider snapshotMetaDtoDataProvider
     * @param string  $path             The path to set.
     * @param integer $size             The size in bytes to set.
     * @param string  $expectedReadable The expected readable() output.
     * @return void
     */
    public function snapshot_meta_dto_can_set_and_get_values(
        string $path,
        int $size,
        string $expectedReadable
    ) {

        $snapshotMetaDTO = (new SnapshotMetaDTO)->path($path)->size($size);
        $this->assertSame($path, $snapshotMetaDTO->path);
        $this->assertSame($size, $snapshotMetaDTO->size);
        $this->assertSame($expectedReadable, $snapshotMetaDTO->readable());
    }
}
