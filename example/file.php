<?php
use Toknot\Epoll;
include_once dirname(__DIR__) . '/vendor/autoload.php';

$epoll = new Epoll();
$fp = fopen(__FILE__, 'rb');

$fdno = $epoll->getFdno($fp, Epoll::RES_TYPE_FILE);
$fdfp = fopen("php://fd/$fdno", 'rb');
echo fread($fdfp, 1024);