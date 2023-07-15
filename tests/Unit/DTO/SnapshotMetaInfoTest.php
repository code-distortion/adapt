<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\SnapshotMetaInfo;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use DateTime;
use DateTimeZone;

/**
 * Test the SnapshotMetaInfo class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class SnapshotMetaInfoTest extends PHPUnitTestCase
{
    /**
     * Provide data for the snapshot_meta_info_can_set_and_get_values test.
     *
     * @return mixed[][]
     */
    public static function snapshotMetaInfoDataProvider(): array
    {
        $sizes = [
            [0, '0B'],
            [1, '1B'],
            [1024, '1KB'],
            [1024 * 512, '512KB'],
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
                'filename' => 'abc.mysql',
                'accessDT' => new DateTime('now', new DateTimeZone('UTC')),
                'isValid' => true,
                'size' => $size[0],
                'expectedReadable' => "\"/var/www/database/adapt-test-storage/abc.mysql\" $size[1]",
            ];
        }
        return $return;
    }

    /**
     * Test that the SnapshotMetaInfo object can set and get values properly.
     *
     * @test
     * @dataProvider snapshotMetaInfoDataProvider
     * @param string   $path             The path to set.
     * @param string   $filename         The filename to set.
     * @param DateTime $accessDT         The accessed-at DateTime to set.
     * @param boolean  $isValid          Whether the snapshot is valid or not (old).
     * @param integer  $size             The size in bytes to set.
     * @param string   $expectedReadable The expected readable() output.
     * @return void
     */
    public static function snapshot_meta_info_can_set_and_get_values(
        string $path,
        string $filename,
        DateTime $accessDT,
        bool $isValid,
        int $size,
        string $expectedReadable
    ): void {

        $filesizeCallback = fn() => $size;

        $calledDeleteCallback = false;
        $deleteCallback = function () use (&$calledDeleteCallback) {
            $calledDeleteCallback = true;
            return true;
        };

        $snapshotMetaInfo = (new SnapshotMetaInfo($path, $filename, $accessDT, $isValid, $filesizeCallback, 14400))
            ->setDeleteCallback($deleteCallback);

        self::assertSame($path, $snapshotMetaInfo->path);
        self::assertSame($filename, $snapshotMetaInfo->filename);
        self::assertSame($accessDT, $snapshotMetaInfo->accessDT);
        self::assertSame($isValid, $snapshotMetaInfo->isValid);
        self::assertSame($size, $snapshotMetaInfo->getSize());
        self::assertSame($expectedReadable, $snapshotMetaInfo->readable());
        $snapshotMetaInfo->delete();
        self::assertTrue($calledDeleteCallback);
    }
}
