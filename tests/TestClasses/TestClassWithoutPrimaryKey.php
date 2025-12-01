<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage\TestClasses;

final class TestClassWithoutPrimaryKey
{
    public string $id;
    public string $name;

    /**
     * @param array<string,string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->name = $data["name"];
    }
}
