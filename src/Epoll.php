<?php

class Epoll
{
    static private $ffi = null;
    private $epfd = 0;
    const EPOLL_CTL_ADD = 1;
    const EPOLL_CTL_MOD = 2;
    const EPOLL_CTL_DEL = 3;
    const EPOLLIN = 0x001;
    const EPOLLPRI = 0x002;
    const EPOLLOUT = 0x004;
    const EPOLLRDNORM = 0x040;
    const EPOLLRDBAND = 0x080;
    const EPOLLWRNORM = 0x100;
    const EPOLLWRBAND = 0x200;
    const EPOLLMSG = 0x400;
    const EPOLLERR = 0x008;
    const EPOLLHUP = 0x010;
    const EPOLLRDHUP = 0x2000;
    const EPOLLEXCLUSIVE = 1 << 28;
    const EPOLLWAKEUP = 1 << 29;
    const EPOLLONESHOT = 1 << 30;
    const EPOLLET = 1 << 3;
    const EPOLL_CLOEXEC = 02000000;

    const EINVAL = 22;
    const EMFILE = 24;
    const ENOMEM = 12;
    const ENFILE = 23;
    const EBADF = 9;
    const EFAULT = 14;
    const EINTR = 4;
    const EEXIST = 17;
    const ELOOP = 40;
    const ENOENT = 2;
    const ENOSPC = 28;
    const EPERM  = 1;

    public function __construct()
    {
        if (self::$ffi === null) {
            self::$ffi =  FFI::cdef('typedef union epoll_data {
            void *ptr;int fd;
            uint32_t u32;
            uint64_t u64;
            } epoll_data_t;
            struct epoll_event {
            uint32_t events; /* Epoll events */
            epoll_data_t data; /* User data variable */
            };
            int epoll_create(int size);
            int epoll_create1 (int __flags)
            int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event);
            int epoll_wait(int epfd, struct epoll_event * events, int maxevents, int timeout);
            int epoll_pwait(int epfd, struct epoll_event *events,int maxevents, int timeout,const sigset_t *sigmask);
            int errno;
            char *strerror(int errno);
            ');
        }
    }
    public function create(int $flags)
    {
        if ($flags === 0 || $flags === self::EPOLL_CLOEXEC) {
            $this->epfd = self::$ffi->epoll_create1($flags);
        } else if ($flags > 0) {
            $this->epfd = self::$ffi->epoll_create($flags);
        } else {
            throw new InvalidArgumentException('Epoll::create() of paramter 1 must be greater than 0');
        }
    }

    public function lastErrno()
    {
        return self::$ffi->errno;
    }

    public function lastError()
    {
        return FFI::string(self::$ffi->strerror(self::$ffi->errno));
    }


    public function ffi()
    {
        return self::$ffi;
    }

    public function initEvents()
    {
        return new EpollEvent($this);
    }

    public function ctl(int $op, int $fd, EpollEvent $events, array $data): int
    {
        return self::$ffi->epoll_ctl($this->epfd, $op, $fd, $events->getEvents());
    }

    public function wait(EpollEvent $event, int $maxevents, int $timeout, $sigmask = null): int
    {
        if ($maxevents <= 0) {
            throw new InvalidArgumentException('Epoll::wait() of paramter 2 must be greater than 0');
        }
        if ($sigmask === null) {
            return self::$ffi->epoll_wait($this->epfd, $event->getEvents(), $maxevents, $timeout);
        } else {
            return self::$ffi->epoll_pwait($this->epfd, $event->getEvents(), $maxevents, $timeout, $sigmask);
        }
    }
}
