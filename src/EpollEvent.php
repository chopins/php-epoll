<?php

class EpollEvent
{
    private $events = null;

    public function __construct(Epoll $epoll)
    {
        $this->events = $epoll->ffi()->new('epoll_event');
    }

    public function setEvent($event)
    {
        $this->events->events = $event;
    }

    public function setData($data)
    {
        foreach ($data as $k => $v) {
            $this->events->data->$k = $v;
        }
    }

    public function getEvents()
    {
        return $this->events;
    }
}
