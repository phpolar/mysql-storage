<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use Exception;
use PDO;
use Pdo\Mysql;
use PDOStatement;
use Phpolar\MySqlStorage\TestClasses\TestClassWithPrimaryKey;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
#[RunTestsInSeparateProcesses]
final class MySqlStorageIntegrationTest extends TestCase
{
    private Mysql $realConnection;
    private MySqlStorage $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $dbName = \getenv("MYSQL_DATABASE");
        $host = \getenv("MYSQL_HOST");
        $user =  \getenv("MYSQL_USER");
        $password = \getenv("MYSQL_PASSWORD");

        if (\is_string($user) === false) {
            throw new Exception("The 'MYSQL_USER' env variable must be set when running this test.");
        }

        if (\is_string($password) === false) {
            throw new Exception("The 'MYSQL_PASSWORD' env variable must be set when running this test.");
        }

        $this->realConnection = new Mysql(
            dsn: sprintf(
                "mysql:dbname=%s;host=%s",
                $dbName,
                $host
            ),
            username: $user,
            password: $password,
        );

        $this->realConnection->exec(
            <<<SQL
            DROP TABLE IF EXISTS `items`;
            SQL
        );
        $this->realConnection->exec(
            <<<SQL
            CREATE TABLE `items` (`id` INT NOT NULL PRIMARY KEY ,`name` VARCHAR(100) NOT NULL );
            SQL,
        );
        $this->realConnection->exec(
            <<<SQL
            INSERT INTO `items` VALUES
                (1, 'name1'),
                (2, 'name2'),
                (3, 'name3');
            SQL,
        );

        $this->sut = new MySqlStorage(
            connection: $this->realConnection,
            tableName: "items",
            typeClassName: TestClassWithPrimaryKey::class,
        );
    }

    #[Test]
    #[TestDox("Shall load data already stored in the database")]
    public function loads()
    {
        /**
         * @var TestClassWithPrimaryKey
         */
        $item1 = $this->sut->find(1)->orElse(static fn() => $this->assertTrue(false))->tryUnwrap();
        /**
         * @var TestClassWithPrimaryKey
         */
        $item2 = $this->sut->find(2)->orElse(static fn() => $this->assertTrue(false))->tryUnwrap();
        /**
         * @var TestClassWithPrimaryKey
         */
        $item3 = $this->sut->find(3)->orElse(static fn() => $this->assertTrue(false))->tryUnwrap();

        $this->assertSame("name1", $item1->name);
        $this->assertSame("name2", $item2->name);
        $this->assertSame("name3", $item3->name);
    }

    #[Test]
    #[TestDox("Shall insert an item that does not exists in the data set")]
    #[TestWith([["id" => 4, "name" => "name4"]])]
    public function inserts(array $data)
    {
        $item4 = new TestClassWithPrimaryKey($data);
        $result = "";

        $this->sut->save((string) $item4->getPrimaryKey(), $item4);

        $this->sut->persist();

        $stmt = $this->realConnection->prepare(
            <<<SQL
            SELECT `name` FROM `items` WHERE id=:id;
            SQL
        );

        if ($stmt instanceof PDOStatement) {
            $stmt->bindValue(":id", $item4->getPrimaryKey());
            $stmt->execute();
            $stmt->bindColumn("name", $result);
            $stmt->fetch(PDO::FETCH_BOUND);

            $this->assertSame($item4->name, $result);
            return;
        }

        $this->assertTrue(false, "The query failed");
    }

    #[Test]
    #[TestDox("Shall update an item that exists in the data set")]
    #[TestWith([["id" => 2, "name" => "replaced_name4"], "replaced_name4"])]
    public function updates(array $data, string $expectedName)
    {
        $preexistingItem = new TestClassWithPrimaryKey($data);
        $result = "";

        $this->sut->save((string) $preexistingItem->getPrimaryKey(), $preexistingItem);

        $this->sut->persist();

        $stmt = $this->realConnection->prepare(
            <<<SQL
            SELECT `name` FROM `items` WHERE id=:id;
            SQL
        );

        if ($stmt instanceof PDOStatement) {
            $stmt->bindValue(":id", $preexistingItem->getPrimaryKey());
            $stmt->execute();
            $stmt->bindColumn("name", $result);
            $stmt->fetch(PDO::FETCH_BOUND);

            $this->assertSame($expectedName, $result);
            return;
        }

        $this->assertTrue(false, "The query failed");
    }

    #[Test]
    #[TestDox("Shall remove items from the data set")]
    #[TestWith([3])]
    public function deletes(int $id)
    {
        $this->sut->remove($id);

        $this->sut->persist();

        $stmt = $this->realConnection->prepare(
            <<<SQL
            SELECT `name` FROM `items` WHERE id=:id;
            SQL
        );

        if ($stmt instanceof PDOStatement) {
            $stmt->bindValue(":id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_BOUND);

            $this->assertFalse($result);
            return;
        }

        $this->assertTrue(false, "The query failed");
    }
}
