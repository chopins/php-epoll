# php-epoll
PHP bindings to the linux epoll API.

### Requirements
* PHP >= 7.4
* PHP FFI extension available
* Linux > 2.6
* toknot/ffi-extend>=0.1

### Install
use composer install:
```
composer require toknot/php-epoll

```
include composer autoload file : `./vendor/autoload.php`

## Reference

* `Epoll::__construct()`
* `Epoll::create(int $flags)`

  open an epoll file descriptor
* `Epoll::ctl(int $op, int $fd, EpollEvent $events): int`

  control interface for an epoll file descriptor
* `Epoll::wait(EpollEvent $event, int $maxevents, int $timeout, $sigmask = null): int`

  wait for an I/O event on an epoll file descriptor
* `Epoll::getFdno(resource $file, int $type): int`

  get id from file descriptor of php resource
* `Epoll::lastErrno(): int`

  get last error code
* `Epoll::lastError(): string`

  get last error message
* `Epoll::ffi(): FFI`
* `Epoll::initEvents($num): EpollEvent`
* `EpollEvent::__construct(Epoll $epoll,$num)`
* `EpollEvent::setEvent($event, $idx)`

  set Epoll events
* `EpollEvent::setData($data, $idx)`

  set user data variable
* `EpollEvent::getEvents($idx): FFI\CData`



## Simple Example

php resource to file descriptor
```php
$epoll = new Epoll();
$fp = fopen(__FILE__, 'rb');
$fdno = $epoll->getFdno($fp, Epoll::RES_TYPE_FILE);
$fdfp = fopen("php://fd/$fdno", 'rb');
echo fread($fdfp, 1024);
```

epoll example from `man epoll`

```php
const MAX_EVENTS = 10;
const EXIT_FAILURE = 1;

$epoll = new Epoll();
$ev = $epoll->initEvents(MAX_EVENTS);
$events = $epoll->initEvents();
$stream = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
$listen_sock = $epoll->getFdno($stream, Epoll::RES_TYPE_NET);

function perror($str) {
    fprintf(STDERR, $str);
}

$epollfd = $epoll->create(0);

$ev->setEvent(Epoll::EPOLLIN);
$ev->setData(['fd' => $listen_sock]);

if ($epoll->ctl(Epoll::EPOLL_CTL_ADD, $listen_sock, $ev) == -1) {
    perror("epoll_ctl: listen_sock");
    exit(EXIT_FAILURE);
}

for (;;) {
    $nfds = $epoll->wait($events, MAX_EVENTS, -1);
    if ($nfds == -1) {
        perror("epoll_wait");
        exit(EXIT_FAILURE);
    }

    for ($n = 0; $n < $nfds; ++$n) {
        if ($events[$n]->data->fd == $listen_sock) {
            $conn_sock = stream_socket_accept($stream);
            if (!$conn_sock) {
                perror("accept");
                exit(EXIT_FAILURE);
            }
            stream_set_blocking($conn_sock, false);
            $ev->setEvent(Epoll::EPOLLIN | Epoll::EPOLLET);
            $connFdno = $epoll->getFdno($conn_sock, Epoll::RES_TYPE_NET);
            $ev->setData(['fd' => $connFdno]);
            if ($epoll->ctl(Epoll::EPOLL_CTL_ADD, $connFdno,
                        $ev) == -1) {
                perror("epoll_ctl: conn_sock");
                exit(EXIT_FAILURE);
            }
        } else {
            do_use_fd($events[$n]->data->fd);
        }
    }
}
```