<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use Phpolar\Storage\{
    DestroyHook,
    InitHook,
    Loadable,
    Persistable
};

/**
 * A set of actions that will be executed when the defined
 * events occur.
 */
final readonly class MySqlStorageLifeCycleHooks implements InitHook, DestroyHook
{
    public function __construct(
        private Loadable & Persistable $storage,
    ) {
    }

    public function onDestroy(): void
    {
        $this->storage->persist();
    }

    public function onInit(): void
    {
        $this->storage->load();
    }
}
