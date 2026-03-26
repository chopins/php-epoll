<?php

/**
 * php-epoll (http://toknot.com)
 *
 * @copyright  Copyright (c) 2019-2020 Szopen Xiao (Toknot.com)
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/php-epoll
 * @version    0.1
 */

namespace Toknot;

use FFI;
use InvalidArgumentException;
use ErrorException;
use TypeError;
use Toknot\EpollEvent;

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
    const EPERM = 1;
    const RES_TYPE_FILE = 1;
    const RES_TYPE_NET = 2;

    const SYMTABLE_CACHE_SIZE = 32;
    const ZEND_ARRAY_SIZE = 48 + \PHP_INT_SIZE;
    const ZVAL_SIZE = 16;

    public function __construct()
    {
        if (self::$ffi === null) {
            $zend_long = \PHP_INT_SIZE == 8 ? 'int64_t' : 'int32_t';
            $code = <<<C
            typedef union epoll_data{void *ptr;int fd;uint32_t u32;uint64_t u64;} epoll_data_t;
            typedef struct epoll_event{uint32_t events;epoll_data_t data;} epoll_event;
            int epoll_create(int size);
            int epoll_create1(int __flags);
            int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event);
            int epoll_wait(int epfd, struct epoll_event *events, int maxevents, int timeout);
            int epoll_pwait(int epfd, struct epoll_event *events, int maxevents, int timeout, const unsigned long *sigmask);
            int errno;
            char *strerror(int errno);
            typedef struct{void *res;uint32_t type_info;uint32_t num_args;} zval;
            int zend_eval_string(const char *str, zval *retval_ptr, const char *string_name);
            typedef struct _zend_resource {uint32_t gc[2];$zend_long handle;int type;void *ptr;} zend_resource;
            typedef struct _php_stream  {const void *ops;void *abstract;} php_stream;
            typedef struct {void *file;int fd;} php_stdio_stream_data;
            typedef struct {int php_sock;} php_netstream_data;
            C;
            self::$ffi = FFI::cdef($code);
        }
    }

    /**
     * open an epoll file descriptor
     *
     *  @param int $flags
     */
    public function create(int $flags)
    {
        if ($flags === 0 || $flags === self::EPOLL_CLOEXEC) {
            $this->epfd = self::$ffi->epoll_create1($flags);
        } else if ($flags > 0) {
            $this->epfd = self::$ffi->epoll_create($flags);
        } else {
            throw new InvalidArgumentException('Epoll::create() of paramter 1 must be greater than 0');
        }
        if ($this->epfd < 0) {
            throw new ErrorException('create epoll file descriptor error');
        }
    }

    /**
     * get last error code
     *
     * @return int
     */
    public function lastErrno(): int
    {
        return self::$ffi->errno;
    }

    /**
     * get last error message
     *
     * @return string
     */
    public function lastError(): string
    {
        return FFI::string(self::$ffi->strerror(self::$ffi->errno));
    }

    /**
     * get current ffi instance of Epoll
     *
     * @return FFI
     */
    public function ffi(): FFI
    {
        return self::$ffi;
    }

    /**
     * init epoll events
     *
     * @param int $num
     * @return EpollEvent
     */
    public function initEvents(int $num = 1): EpollEvent
    {
        if ($num < 1) {
            throw new InvalidArgumentException('Epoll::initEvents() of paramter 1 must be greater than 0');
        }
        return new EpollEvent($this, $num);
    }

    /**
     * control interface for an epoll file descriptor
     *
     * @param int $op
     * @param int $fd
     * @param EpollEvent $events
     * @return int
     */
    public function ctl(int $op, int $fd, EpollEvent $events): int
    {
        return self::$ffi->epoll_ctl($this->epfd, $op, $fd, FFI::addr($events->getEvents()));
    }

    /**
     * wait for an I/O event on an epoll file descriptor
     *
     * @param EpollEvent $event
     * @param int $maxevents
     * @param int $timeout
     * @param int sigmask
     * @return int
     */
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

    /**
     * get id from file descriptor of php resource
     *
     * @param mixed $resource  php resource
     * @return int  if error return -1, otherwise return greater then 0
     */
    function getFdno($resource)
    {
        if(!is_resource($resource)) {
            throw new TypeError('Epoll::getFdno() of paramter 1 must be resource');
        }
        $zval = self::$ffi->new('zval', false);
        self::$ffi->zend_eval_string('$resource;', FFI::addr($zval), __FILE__);
        $res = self::$ffi->cast('zend_resource', $zval->res);
        $stream = self::$ffi->cast('php_stream', $res->ptr);
        $meta =  \stream_get_meta_data($resource);
        if ($meta['stream_type'] == 'STDIO') {
            $io = self::$ffi->cast('php_stdio_stream_data', $stream->abstract);
            return $io->fd;
        } elseif ($meta['stream_type'] == 'generic_socket' || strpos($meta['stream_type'], 'tcp_socket') === 0) {
            $sock = self::$ffi->cast('php_netstream_data', $stream->abstract);
            return $sock->php_sock;
        }
        return -1;
    }
}
