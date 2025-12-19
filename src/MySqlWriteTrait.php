<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use DateTimeImmutable;
use Pdo\Mysql;
use Stringable;

/**
 * Automatically persist data to the database.
 */
trait MySqlWriteTrait
{
    private readonly Mysql $connection;
    private readonly string $tableName;
    private readonly string $typeClassName;

    public function persist(): void
    {
        /**
         * @var object[]
         */
        $items = $this->findAll();

        if ($this->count() < 1) {
            return;
        }

        $this->upsertItems(itemsToUpsert: $items);
        $this->deleteRemovedItems(itemsToKeep: $items);
    }

    /**
     * Insert items or update if they already exist.
     *
     * @param object[] $itemsToUpsert
     */
    private function upsertItems(array $itemsToUpsert): void
    {
        $columns = \array_keys(\get_object_vars($itemsToUpsert[0]));
        $columnNames = join(", ", array_map(static fn(string $col) => sprintf("`%s`", $col), $columns));

        $bindVariables = join(", ", array_map(static fn(string $col) => sprintf(":%s", $col), $columns));
        $updateClause = join(
            ", ",
            array_map(
                static fn(string $col) => sprintf("`%s`=:%s", $col, $col),
                \array_filter($columns, static fn(string $col) => $col !== "id")
            )
        );

        $stmt = $this->connection->prepare(
            query: <<<SQL
            INSERT INTO `{$this->tableName}` ($columnNames)
            VALUES ($bindVariables)
              ON DUPLICATE KEY UPDATE $updateClause
            SQL,
        );

        if ($stmt === false) {
            // @codeCoverageIgnoreStart
            $this->clear();
            return; // an exception will be thrown
            // @codeCoverageIgnoreEnd
        }

        foreach ($itemsToUpsert as $item) {
            $stmt->execute(
                $this->convertObjVars(
                    \get_object_vars($item)
                )
            );
        }
    }

    /**
     * Delete items that were removed from the in-memory collection.
     *
     * @param object[] $itemsToKeep
     */
    private function deleteRemovedItems(array $itemsToKeep): void
    {
        $ids = \array_column($itemsToKeep, "id");
        $placeholders = join(", ", \array_fill(0, count($ids), "?"));

        $stmt = $this->connection->prepare(
            count($ids) === 0
                ?
                <<<SQL
            TRUNCATE `{$this->tableName}`
            SQL
                :
                <<<SQL
            DELETE FROM `{$this->tableName}`
            WHERE `id` NOT IN ($placeholders);
            SQL
        );

        if ($stmt === false) {
            // @codeCoverageIgnoreStart
            $this->clear();
            return; // an exception will be thrown
            // @codeCoverageIgnoreEnd
        }

        $stmt->execute($ids);
    }

    /**
     * @param array<string,mixed> $objVars
     * @return array<string,string|bool|float|int|null>
     */
    private function convertObjVars(array $objVars): array
    {
        return array_map(
            static fn(mixed $item) => match (true) {
                $item instanceof DateTimeImmutable => $item->format(DATE_ATOM),
                $item instanceof Stringable => (string) $item,
                is_scalar($item) => $item,
                is_array($item) => json_encode($item),
                is_object($item) => serialize($item),
                $item === null => $item,
                default => null,
            },
            $objVars,
        );
    }

    abstract public function clear(): void;

    abstract public function count(): int;

    abstract public function findAll(): array;
}
