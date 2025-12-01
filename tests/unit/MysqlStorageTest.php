<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use ArrayIterator;
use PDO;
use Pdo\Mysql;
use PDOStatement;
use Phpolar\MySqlStorage\TestClasses\TestClassWithoutPrimaryKey;
use Phpolar\MySqlStorage\TestClasses\TestClassWithPrimaryKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(MySqlStorage::class)]
#[UsesClass(MySqlStorageLifeCycleHooks::class)]
final class MySqlStorageTest extends TestCase
{
    private MySqlStorage $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $connectionStub = $this->createStub(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $connectionStub->method("query")->willReturn($stmtStub);
        $stmtStub->method("getIterator")->willReturn(new ArrayIterator([]));
        $this->sut = new MySqlStorage(
            connection: $connectionStub,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );
    }


    #[Test]
    #[TestDox("Shall allow for counting of its items")]
    public function countability()
    {
        $this->sut->save(
            key: "id1",
            data: new TestClassWithPrimaryKey([
                "id" => "id1",
                "name" => "name1"
            ]),
        );
        $this->sut->save(
            key: "id2",
            data: new TestClassWithPrimaryKey([
                "id" => "id2",
                "name" => "name2"
            ]),
        );
        $this->sut->save(
            key: "id3",
            data: new TestClassWithPrimaryKey([
                "id" => "id3",
                "name" => "name3"
            ]),
        );

        $this->assertCount(
            expectedCount: 3,
            haystack: $this->sut,
        );
    }

    #[Test]
    #[TestDox("Shall load items from the database")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    public function loading(array $data)
    {
        $connectionMock = $this->createMock(Mysql::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock
            ->expects($this->any())
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator(
                    [
                        new TestClassWithPrimaryKey($data),
                    ]
                )
            );
        $connectionMock
            ->expects($this->once())
            ->method("query")
            ->with(
                <<<SQL
                TABLE "test_table";
                SQL,
                PDO::FETCH_CLASS,
                TestClassWithPrimaryKey::class,
            )
            ->willReturn(
                $stmtMock,
            );

        new MySqlStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );
    }

    #[Test]
    #[TestDox("Shall use id property if getPrimaryKey method does not exist")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    public function loadswithid(array $data)
    {
        $item1 = new TestClassWithoutPrimaryKey($data);
        $connectionStub = $this->createStub(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator([
                    $item1,
                ]),
            );
        $connectionStub
            ->method("query")
            ->willReturn($stmtStub);

        $sut = new MySqlStorage(
            connection: $connectionStub,
            tableName: "test_table",
            typeClassName: TestClassWithoutPrimaryKey::class
        );

        $this->assertSame("id1", $sut->findKey($item1));
    }

    #[Test]
    #[TestDox("Shall use string representation of item getPrimaryKey method and id property does not exist")]
    #[TestWith([["name" => "name1"]])]
    public function loadswithstringrepresentation(array $data)
    {
        $item1 = (object) $data;
        $connectionStub = $this->createStub(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator([
                    $item1,
                ]),
            );
        $connectionStub
            ->method("query")
            ->willReturn($stmtStub);

        $sut = new MySqlStorage(
            connection: $connectionStub,
            tableName: "test_table",
            typeClassName: stdClass::class
        );

        $this->assertEmpty($sut->findKey($item1));
    }

    #[Test]
    #[TestDox("Shall persist items to the database")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    #[TestWith([["id" => "id2", "name" => "name2"]])]
    public function persists(array $data)
    {
        $connectionStub = $this->createStub(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock
            ->expects($this->exactly(1))
            ->method("execute")
            ->with($data);
        $stmtStub
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator([])
            );

        $connectionStub
            ->method("query")
            ->with(
                <<<SQL
                TABLE "test_table";
                SQL,
                PDO::FETCH_CLASS,
                TestClassWithPrimaryKey::class,
            )
            ->willReturn($stmtStub);

        $connectionStub
            ->method("prepare")
            ->willReturnOnConsecutiveCalls(
                $stmtMock,
                $this->createStub(PDOStatement::class),
            );

        $item = new TestClassWithPrimaryKey($data);
        $sut = new MySqlStorage(
            connection: $connectionStub,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $sut->save(
            $item->getPrimaryKey(),
            $item,
        );

        $sut->persist();

        // cleaning up here because the destructor will be called
        // before the tearDown method
        $sut->clear();
    }


    #[Test]
    #[TestDox("Shall persist items to the database")]
    #[TestWith([
        [
            ["id" => "id1", "name" => "name1"],
            ["id" => "id2", "name" => "name2"],
            ["id" => "id3", "name" => "name3"],
            ["id" => "id4", "name" => "name4"],
        ],
        ["id2", "id4"],
    ])]
    public function deletes(array $items, array $idsToRemove)
    {
        $connectionMock = $this->createStub(Mysql::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator(
                    array_map(
                        static fn(array $data) => new TestClassWithPrimaryKey($data),
                        $items,
                    )
                )
            );
        $connectionMock
            ->method("query")
            ->with(
                <<<SQL
                TABLE "test_table";
                SQL,
                PDO::FETCH_CLASS,
                TestClassWithPrimaryKey::class,
            )
            ->willReturn($stmtStub);
        $connectionMock
            ->method("prepare")
            ->willReturnOnConsecutiveCalls(
                $this->createStub(PDOStatement::class),
                $stmtMock
            );
        $stmtMock
            ->expects($this->once())
            ->method("execute");

        $sut = new MySqlStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        /**
         * @var string $idToRemove
         */
        foreach ($idsToRemove as $idToRemove) {
            $sut->remove($idToRemove);
        }

        $sut->persist();

        // cleaning up here because the destructor will be called
        // before the tearDown method
        $sut->clear();
    }

    #[Test]
    #[TestDox("Shall not attempt to persist anything to the database if there are no items")]
    public function notpersists()
    {
        $connectionMock = $this->createMock(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock
            ->expects($this->never())
            ->method("execute");
        $stmtStub
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator([])
            );

        $connectionMock
            ->expects($this->any())
            ->method("query")
            ->with(
                <<<SQL
                TABLE "test_table";
                SQL,
                PDO::FETCH_CLASS,
                TestClassWithPrimaryKey::class,
            )
            ->willReturn($stmtStub);

        $connectionMock
            ->expects($this->never())
            ->method("prepare");

        $sut = new MySqlStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $sut->persist();
    }

    #[Test]
    #[TestDox("Shall not attempt to execute the statement if prepare fails")]
    public function notexecute()
    {
        $connectionStub = $this->createStub(Mysql::class);
        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtStub
            ->method("getIterator")
            ->willReturn(
                new ArrayIterator([])
            );
        $connectionStub
            ->method("query")
            ->with(
                <<<SQL
                TABLE "test_table";
                SQL,
                PDO::FETCH_CLASS,
                TestClassWithPrimaryKey::class,
            )
            ->willReturn($stmtStub);
        $connectionStub
            ->method("prepare")
            ->willReturn(false);

        $stmtMock
            ->expects($this->never())
            ->method("execute");

        $sut = new MySqlStorage(
            connection: $connectionStub,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $sut->persist();

        $this->assertCount(0, $sut);
    }
}
