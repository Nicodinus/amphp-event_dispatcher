<?php

namespace Nicodinus\Async\EventDispatcher;

final class EventListener
{
    /** @var callable */
    protected $stopListenCallback;

    /** @var callable */
    protected $handleCallback;

    //

    /**
     * @param callable $stopListenCallback
     * @param callable $handleCallback
     */
    public function __construct(callable $stopListenCallback, callable $handleCallback)
    {
        $this->stopListenCallback = $stopListenCallback;
        $this->handleCallback = $handleCallback;
    }

    /**
     * @return void
     */
    public function stopListen(): void
    {
        ($this->stopListenCallback)();
    }

    /**
     * @return callable
     */
    public function getHandleCallback(): callable
    {
        return $this->handleCallback;
    }
}