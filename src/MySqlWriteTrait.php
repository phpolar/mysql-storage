<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use Pdo\Mysql;

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

        if (count($this) < 1) {
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
                \get_object_vars($item)
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

    abstract public function findAll(): array;
}
