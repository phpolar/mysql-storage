<?php

declare(strict_types=1);

namespace Phpolar\Phpolar;

use ArrayIterator;
use Pdo\Mysql;
use PDOStatement;
use Phpolar\MySqlStorage\MySqlStorage;
use Phpolar\MySqlStorage\TestClasses\TestClassWithPrimaryKey;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class MemoryUsageTest extends TestCase
{
    private Mysql&Stub $connectionStub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connectionStub = $this->createStub(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub
            ->method("getIterator")
            ->willReturn(new ArrayIterator([
                new TestClassWithPrimaryKey([
                    "id" => 1,
                    "name" => "name1",
                ]),
            ]));
        $this->connectionStub
            ->method("query")
            ->willReturn($stmtStub);
    }

    #[Test]
    #[TestDox("Memory usage shall be below " . PROJECT_MEMORY_USAGE_THRESHOLD . " bytes")]
    public function shallBeBelowThreshold1()
    {
        $totalUsed = -memory_get_usage();

        $sut = new MySqlStorage(
            connection: $this->connectionStub,
            tableName: "table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $sut->save(
            "2",
            new TestClassWithPrimaryKey([
                "id" => 2,
                "name" => "name2"
            ])
        );

        unset($sut); // destroy

        $totalUsed += memory_get_usage();
        $this->assertGreaterThan(0, $totalUsed);
        $this->assertLessThanOrEqual((int) PROJECT_MEMORY_USAGE_THRESHOLD, $totalUsed);
    }
}
