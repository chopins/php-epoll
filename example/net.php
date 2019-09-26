<?php
include dirname(__DIR__) .'/src/Epoll.php';
const MAX_EVENTS = 10;
const EXIT_FAILURE = 1;

$epoll = new Epoll();
$ev = $epoll->initEvents();
$events = $epoll->initEvents(MAX_EVENTS);
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