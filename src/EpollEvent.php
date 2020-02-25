<?php

/**
 * php-gtk (http://toknot.com)
 *
 * @copyright  Copyright (c) 2019 Szopen Xiao (Toknot.com)
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/php-gtk
 * @version    0.1
 */
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
        if($num < 1) {
            throw new InvalidArgumentException('EpollEvent::__construct() of paramter 2 must be greater than 0');
        }
        $this->num = $num;
        if($num > 1) {
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
        if($this->num > 1) {
            if($idx < 0) {
                throw new InvalidArgumentException('EpollEvent::setEvent() of paramter 2 must be >= 0');
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
    public function setData(array $data, $idx = 0)
    {
        $keys = ['ptr', 'fd', 'u32', 'u64'];
        if($this->num > 1) {
            if($idx < 0) {
                throw new InvalidArgumentException('EpollEvent::setData() of paramter 2 must be >= 0');
            }
            $ev = $this->events[$idx];
        } else {
            $ev = $this->events;
        }
        foreach($data as $k => $v) {
            if(!in_array($k, $keys)) {
                throw new TypeError("EpollEvent::setData(): key $k is not allow");
            }
            $ev->data->$k = $v;
        }
    }

    /**
     * get epoll struct 
     * 
     * @param int $idx
     * @return FFI\CData
     */
    public function getEvents($idx = null): FFI\CData
    {
        if($this->num > 1 && $idx >= 0 && $idx !== null) {
            return $this->events[$idx];
        }
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
    {
        
    }

    public function offsetUnset($offset)
    {
        
    }

}
