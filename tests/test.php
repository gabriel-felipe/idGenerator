<?php
error_reporting(E_ALL);
ini_set("display_errors","on");
require_once("../vendor/autoload.php");
$ids = array();
$time = microtime(true);
for ($i=0; $i < 500000; $i++) {
    $ids[] = IdGenerator\IdGenerator::getId();
}
$time = microtime(true) - $time;
echo "500000 ids gerados em $time segundos";
