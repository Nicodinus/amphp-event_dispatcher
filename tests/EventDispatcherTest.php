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
            $event = new class () implements StoppableEventInterface {

                /** @var bool */
                public bool $isPropagationStopped = false;

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
            $event->data = \bin2hex(\random_bytes(128));
            $events[] = $event;

            $eventDispatcher->listen($event, function (object $_event) use ($event) {
                $this->assertSame($event, $_event);
                $this->assertSame($event->data, $_event->data);
                $_event->isPropagationStopped = true;
                return $_event;
            });

            $eventDispatcher->listen($event, function () {
                $this->fail("Test reach unreachable point!");
            })->stopListen();

            $eventDispatcher->listen($event, function () {
                $this->fail("Test reach unreachable point!");
            });

        }

        foreach ($events as $event) {
            $event = yield $eventDispatcher->dispatch($event);
            $this->assertTrue($event->isPropagationStopped());
        }

    }
}