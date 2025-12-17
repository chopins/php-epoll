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
            $code = \file_get_contents(__DIR__ . '/php.h');
            $code = \str_replace(
                ['__SYMTABLE_CACHE_SIZE__', '__ZEND_ARRAY_SIZE__', 'zend_long'],
                [self::SYMTABLE_CACHE_SIZE, self::ZEND_ARRAY_SIZE, \PHP_INT_SIZE == 8 ? 'int64_t' : 'int32_t'],
                $code
            );
            if(\PHP_ZTS) {
                $code .= 'void *tsrm_get_ls_cache(void);size_t executor_globals_offset;';
            } else {
                $code .= 'zend_executor_globals executor_globals;';
            }
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
     * @return int         if error return -1, otherwise return greater then 0
     */
    public function getFdno($resource): int
    {
        if (!is_resource($resource)) {
            throw new TypeError('Epoll::getFdno() of paramter 1 must be resource');
        }
        if(\PHP_ZTS) {
            $tsrm = self::$ffi->cast('char*', self::$ffi->tsrm_get_ls_cache());
            $cex = self::$ffi->cast('zend_executor_globals*', $tsrm + self::$ffi->executor_globals_offset)->current_execute_data;
        } else {
            $cex = self::$ffi->executor_globals->current_execute_data;
        }
        $ex = self::$ffi->cast('zval*', $cex);
        $zvalSize = FFI::sizeof(self::$ffi->type('zval'));
        $exSize = FFI::sizeof(self::$ffi->type('zend_execute_data'));
        $arg = $ex + (($exSize + $zvalSize - 1) / $zvalSize);
        $stream = self::$ffi->cast('php_stream', $arg->res->ptr);
        $meta =  \stream_get_meta_data($resource);
        if($meta['stream_type'] == 'STDIO' && $meta['wrapper_type'] == 'plainfile') {
            $io = self::$ffi->cast('php_stdio_stream_data', $stream->abstract);
            return $io->fd;
        } elseif(strpos($meta['stream_type'],'tcp_socket') === 0) {
            $sock = self::$ffi->cast('php_netstream_data', $stream->abstract);
            return $sock->php_sock;
        } else {
            throw new \RuntimeException('unsupport stream type');
        }
        return -1;
    }
}
