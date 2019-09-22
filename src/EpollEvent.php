<?php

class EpollEvent implements \ArrayAccess
{
    /**
     * @var FFI\CData
     */
    private $events = null;
    private $num = 1;
    /**
     * init epoll_event struct
     * 
     * @param Epoll $epoll
     * @param int $num      event number
     */
    public function __construct(Epoll $epoll, int $num = 1)
    {
        if ($num < 1) {
            throw new InvalidArgumentException('EpollEvent::__construct() of paramter 2 must be greater than 0');
        }
        $this->num = $num;
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
     * @param int $idx
     */
    public function setEvent(int $event, int $idx = 0)
    {
        if ($this->num > 1) {
            if ($idx < 1) {
                throw new InvalidArgumentException('EpollEvent::setEvent() of paramter 2 muset be greater than 0');
            }
            $this->events[$idx]->events = $event;
        } else {
            $this->events->events = $event;
        }
    }

    /**
     * set user data variable
     * 
     * @param array $data
     * @param int $idx
     */
    public function setData(array $data, $idx = 1)
    {
        $keys = ['ptr', 'fd', 'u32', 'u64'];
        if ($this->num > 1) {
            if ($idx < 1) {
                throw new InvalidArgumentException('EpollEvent::setData() of paramter 2 muset be greater than 0');
            }
            $ev = $this->events[$idx];
        } else {
            $ev = $this->events;
        }
        foreach ($data as $k => $v) {
            if (!in_array($k, $keys)) {
                throw new TypeError("EpollEvent::setData(): key $k is not allow");
            }
            $ev->data->$k = $v;
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

    public function offsetExists($offset): bool
    {
        return isset($this->events[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->events[$offset];
    }
    public function offsetSet($offset, $value)
    { }
    public function offsetUnset($offset)
    { }
}
