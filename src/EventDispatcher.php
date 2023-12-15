<?php

namespace Nicodinus\Async\EventDispatcher;

use Amp\Promise;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use function Amp\call;

final class EventDispatcher implements EventDispatcherInterface, ListenerProviderInterface
{
    /** @var array<string, EventListener[]> */
    protected array $registry;

    //

    /**
     * EventDispatcher constructor.
     */
    public function __construct()
    {
        $this->registry = [];
    }

    /**
     * @param object|class-string $event
     * @param callable $callback
     *
     * @return EventListener
     */
    public function listen($event, callable $callback): EventListener
    {
        if (\is_object($event)) {
            $event = \get_class($event);
        }

        if (!isset($this->registry[$event])) {
            $this->registry[$event] = [];
        }

        $listenerId = null;
        $eventListener = new EventListener(function () use (&$event, &$listenerId) {
            unset($this->registry[$event][$listenerId]);
            if (\sizeof($this->registry[$event]) < 1) {
                unset($this->registry[$event]);
            }
        }, $callback);
        $listenerId = \spl_object_hash($eventListener);

        return $this->registry[$event][$listenerId] = $eventListener;
    }

    /**
     * @inheritDoc
     *
     * @return Promise<object>
     */
    public function dispatch(object $event): Promise
    {
        return call(function () use (&$event) {

            foreach ($this->getListenersForEvent($event) as $eventListener) {

                $event = yield call($eventListener->getHandleCallback(), $event);
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    break;
                }

            }

            return $event;

        });
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<EventListener>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventId = \get_class($event);

        foreach ($this->registry[$eventId] ?? [] as $listener) {
            yield $listener;
        }
    }

}