<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage\TestClasses;

final class TestClassWithPrimaryKey
{
    public string|int $id;
    public string $name;

    /**
     * @param array<string,string|int> $data
     */
    public function __construct(array $data = [])
    {
        if (count($data) > 0) {
            $this->id = $data["id"];
            $this->name = (string) $data["name"];
        }
    }

    public function getPrimaryKey(): string|int
    {
        return $this->id;
    }
}
