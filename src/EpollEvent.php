<?php

class EpollEvent
{
    /**
     * @var FFI\CData
     */
    private $events = null;

    /**
     * init epoll_event struct
     * 
     * @param Epoll $epoll
     * @param int $num      event number
     */
    public function __construct(Epoll $epoll, int $num = 1)
    {
        if ($num > 1) {
            $this->events = $epoll->ffi()->new("epoll_event[$num]");
        } else {
            $this->events = $epoll->ffi()->new('epoll_event');
        }
    }

    /**
     * set Epoll events
     * 
     * @param int $event
     */
    public function setEvent(int $event)
    {
        $this->events->events = $event;
    }

    /**
     * set user data variable
     * 
     * @param array $data
     */
    public function setData(array $data)
    {
        $keys = ['ptr', 'fd', 'u32', 'u64'];
        foreach ($data as $k => $v) {
            if (!in_array($k, $keys)) {
                throw new TypeError("EpollEvent::setData(): key $k is not allow");
            }
            $this->events->data->$k = $v;
        }
    }

    /**
     * get epoll struct 
     * 
     * @return FFI\CData
     */
    public function getEvents(): FFI\CData
    {
        return $this->events;
    }

    public function __get($name)
    {
        return $this->events->$name;
    }
    public function __set($name, $value)
    {
        if ($name === 'data') {
            $this->setData($value);
        }
        $this->events->$name = $value;
    }
}
