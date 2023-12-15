<?php

namespace Nicodinus\Async\EventDispatcher\Tests;

use Amp\PHPUnit\AsyncTestCase;
use Nicodinus\Async\EventDispatcher\EventDispatcher;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcherTest extends AsyncTestCase
{
    /**
     * @return \Generator
     *
     * @throws \Throwable
     */
    public function testEventDispatcher(): \Generator
    {
        $this->setTimeout(1000);
        $eventsCount = 100;

        //

        $eventDispatcher = new EventDispatcher();

        $events = [];
        for ($i = 0; $i < $eventsCount; $i++) {

            $events[$i] = new class () implements StoppableEventInterface {

                /** @var bool */
                public bool $isPropagationStopped = false;

                /** @var int */
                public int $id;

                /** @var string|null */
                public ?string $data = null;

                /**
                 * @inheritDoc
                 */
                public function isPropagationStopped(): bool
                {
                    return $this->isPropagationStopped;
                }

            };
            $events[$i]->id = $i;
            $events[$i]->data = \bin2hex(\random_bytes(128));

        }

        $eventDispatcher->listen(\get_class($events[0]), function (object $event) use (&$events) {
            $this->assertSame($events[$event->id]->data, $event->data);
            $this->assertSame($events[$event->id], $event);
            $event->isPropagationStopped = true;
            return $event;
        });

        $eventDispatcher->listen(\get_class($events[0]), function () {
            $this->fail("Test reach unreachable point!");
        })->stopListen();

        $eventDispatcher->listen(\get_class($events[0]), function () {
            $this->fail("Test reach unreachable point!");
        });

        foreach ($events as $event) {
            $event = yield $eventDispatcher->dispatch($event);
            $this->assertTrue($event->isPropagationStopped());
        }

    }
}