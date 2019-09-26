<?php
include dirname(__DIR__) . '/src/Epoll.php';

$epoll = new Epoll();

$epoll = new Epoll();
$fp = fopen(__FILE__, 'rb');

$fdno = $epoll->getFdno($fp, Epoll::RES_TYPE_FILE);
$fdfp = fopen("php://fd/$fdno", 'rb');
echo fread($fdfp, 1024);