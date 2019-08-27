<?php
use IdGenerator\Socket\Client;
error_reporting(E_ALL);

require("../src/IdGenerator/Socket/Client.php");

$client = new Client("127.0.0.1","4317");

var_dump($client->getId());