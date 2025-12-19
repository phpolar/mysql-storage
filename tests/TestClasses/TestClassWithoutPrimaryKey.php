<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage\TestClasses;

final class TestClassWithoutPrimaryKey
{
    public int $id;
    public string $name;

    /**
     * @param array{'id': int, 'name': string} $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->name = $data["name"];
    }
}
