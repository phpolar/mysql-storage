<?php

declare(strict_types=1);

namespace Phpolar\MySqlStorage;

use Countable;
use Phpolar\Storage\Loadable;
use Phpolar\Storage\Persistable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MySqlStorageLifeCycleHooks::class)]
final class MySqlStorageLifeCycleHooksTest extends TestCase
{
    #[Test]
    #[TestDox("Shall call persist on destroy")]
    public function callsPersist()
    {
        /**
         * @var Loadable&Persistable&MockObject
         */
        $storageMock = $this->createMockForIntersectionOfInterfaces(
            [Persistable::class, Loadable::class]
        );
        $storageMock
            ->expects($this->once())
            ->method("persist");

        $sut = new MySqlStorageLifeCycleHooks($storageMock);

        $sut->onDestroy();
    }

    #[Test]
    #[TestDox("Shall call load on init")]
    public function callsLoad()
    {
        /**
         * @var Loadable&Persistable&MockObject
         */
        $storageMock = $this->createMockForIntersectionOfInterfaces(
            [Persistable::class, Loadable::class]
        );
        $storageMock
            ->expects($this->once())
            ->method("load");

        $sut = new MySqlStorageLifeCycleHooks($storageMock);

        $sut->onInit();
    }
}
