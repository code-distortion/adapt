<?php

namespace CodeDistortion\Adapt\Tests\Unit\DTO;

use CodeDistortion\Adapt\DTO\DatabaseMetaInfo;
use CodeDistortion\Adapt\Tests\PHPUnitTestCase;
use DateTime;
use DateTimeZone;

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
                'driver' => 'mysql',
                'connection' => 'con',
                'name' => 'abc',
                'accessDT' => new DateTime('now', new DateTimeZone('UTC')),
                'isValid' => true,
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
     * @param string   $driver           The Laravel driver used.
     * @param string   $connection       The connection to use.
     * @param string   $name             The database name to set.
     * @param DateTime $accessDT         The accessed-at DateTime to set.
     * @param boolean  $isValid          Whether the snapshot is valid or not (old).
     * @param integer  $size             The size in bytes to set.
     * @param string   $expectedReadable The expected readable() output.
     * @return void
     */
    public function database_meta_dto_can_set_and_get_values(
        string $driver,
        string $connection,
        string $name,
        DateTime $accessDT,
        bool $isValid,
        int $size,
        string $expectedReadable
    ): void {

        $getSizeCallback = fn() => $size;

        $calledDeleteCallback = false;
        $deleteCallback = function () use (&$calledDeleteCallback) {
            $calledDeleteCallback = true;
            return true;
        };

        $databaseMetaDTO = (new DatabaseMetaInfo(
            $driver,
            $connection,
            $name,
            $accessDT,
            $isValid,
            $getSizeCallback,
            14400
        ))
            ->setDeleteCallback($deleteCallback);

        $this->assertSame($driver, $databaseMetaDTO->driver);
        $this->assertSame($connection, $databaseMetaDTO->connection);
        $this->assertSame($name, $databaseMetaDTO->name);
        $this->assertSame($accessDT, $databaseMetaDTO->accessDT);
        $this->assertSame($isValid, $databaseMetaDTO->isValid);
        $this->assertSame($size, $databaseMetaDTO->getSize());
        $this->assertSame($expectedReadable, $databaseMetaDTO->readable());
        $databaseMetaDTO->delete();
        $this->assertTrue($calledDeleteCallback);
    }
}
