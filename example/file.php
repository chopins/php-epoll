<?php
include_once dirname(__DIR__) .'/src/Epoll.php';
use Toknot\Epoll;
$epoll = new Epoll();
$fp = fopen(__FILE__, 'rb');

$fdno = $epoll->getFdno($fp);

$fdfp = fopen("php://fd/$fdno", 'rb');
echo fread($fdfp, 1024);
