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
     * @param object $event
     * @param callable $callback
     *
     * @return EventListener
     */
    public function listen(object $event, callable $callback): EventListener
    {
        $eventId = \spl_object_hash($event);

        if (!isset($this->registry[$eventId])) {
            $this->registry[$eventId] = [];
        }

        $listenerId = null;
        $eventListener = new EventListener(function () use (&$eventId, &$listenerId) {
            unset($this->registry[$eventId][$listenerId]);
            if (\sizeof($this->registry[$eventId]) < 1) {
                unset($this->registry[$eventId]);
            }
        }, $callback);
        $listenerId = \spl_object_hash($eventListener);

        return $this->registry[$eventId][$listenerId] = $eventListener;
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
        $eventId = \spl_object_hash($event);

        foreach ($this->registry[$eventId] ?? [] as $listener) {
            yield $listener;
        }
    }

}