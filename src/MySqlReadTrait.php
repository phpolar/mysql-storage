<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use PDO;
use Pdo\Mysql;

/**
 * Automatically load data into memory.
 */
trait MySqlReadTrait
{
    private readonly Mysql $connection;
    private readonly string $tableName;
    private readonly string $typeClassName;

    public function load(): void
    {
        /**
         * @var \IteratorAggregate
         */
        $rows = $this->connection->query(
            <<<SQL
            TABLE `{$this->tableName}`;
            SQL,
            PDO::FETCH_CLASS,
            $this->typeClassName,
            [],
        );

        /**
         * @var object $row
         */
        foreach ($rows as $row) {
            $this->save(
                key: $this->getPrimaryKey($row),
                data: $row,
            );
        }
    }

    private function getPrimaryKey(object $item): string
    {
        return (string) (\method_exists($item, "getPrimaryKey") === true
            ? $item->getPrimaryKey()
            : (\property_exists($item, "id") === true
                ? $item->id
                : "")
        );
    }

    abstract public function save(string $key, object $data): void;
}
