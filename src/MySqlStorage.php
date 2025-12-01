<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use Countable;
use Pdo\Mysql;
use Phpolar\Storage\{
    AbstractStorage,
    Loadable,
    Persistable
};

/**
 * Adds support in your application for storing data in MySql Databases
 */
final class MySqlStorage extends AbstractStorage implements Countable, Loadable, Persistable
{
    use MySqlReadTrait;
    use MySqlWriteTrait;


    public function __construct(
        private readonly Mysql $connection,
        private readonly string $tableName,
        private readonly string $typeClassName,
    ) {
        parent::__construct(
            hooks: new MySqlStorageLifeCycleHooks($this)
        );
    }
}
