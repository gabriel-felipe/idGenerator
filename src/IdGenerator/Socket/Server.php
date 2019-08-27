<?php
namespace IdGenerator\Socket;
use \IdGenerator\IdGenerator;
set_time_limit(0);

class Server
{
  protected $socket;
  protected $connections = [];

  public function __construct()
  {
    global $argv;
    $params = [
      "workerId" => "01",
      "host" => "127.0.0.1",
      "port" => "4317",
    ];
    foreach ($argv as $arg) {
      if (strpos($arg, "=") !== false) {
        $arg = explode("=", $arg);
        $params[trim($arg[0])] = trim($arg[1]);
      }
    }

    define("ID_GENERATOR_MACHINE_ID", $params['workerId']);
    require_once(dirname(__FILE__)."/../IdGenerator.php");

    if ($this->bind($params['host'], $params['port'])) {
      $this->listen();
    }
  }

  public function bind($host, $port)
  {
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket) {
      $this->socket = $socket;
      socket_set_nonblock($socket);
      socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

      $result = socket_bind($socket, $host, $port);
      if ($result) {
        $result = socket_listen($socket, 3) or die("Could not set up socket listener\n");
        if ($result) {
          $this->output("IdGenerator Listening to $host:$port");
          return true;
        }
      }
    }
    $this->outputError();
    return false;
  }


  private function output($message)
  {
    echo "\n $message \n";
  }

  private function outputError()
  {
    $errId = socket_last_error($this->socket);
    echo "\n Error $errId: " . socket_strerror($errId) . "\n";
  }

  private function listen()
  {
    while (true) {
      $this->checkForNewConnections();
      foreach ($this->connections as $key => $conn) {
        if (!$this->processConnection($key)) {
          $this->closeConnectionIfIdle($key);
        }
      }
    }
  }

  private function checkForNewConnections()
  {
    $socket = $this->socket;
    $spawn = socket_accept($socket);
    if ($spawn) {
      socket_set_nonblock($spawn);
      $this->connections[] = [
        "socket" => $spawn,
        "last_activity" => time()
      ];
    }
  }

  private function processConnection($key)
  {
    if (!isset($this->connections[$key])) {
      return false;
    }
    $socket = $this->connections[$key]['socket'];
    $input = socket_read($socket, 1024);
    if ($input) {
      $this->connections[$key]['last_activity'] = time();
      $input = trim($input);
      $message = "UNKNOWN COMMAND $input";
      if ($input === "GET_ID") {
        $id = IdGenerator::getId();
        $this->sendMessage($socket, $id);
        return true;
      }
      if ($input === "QUIT") {
        $message = "SOCKET EXIT";
        $this->sendMessage($socket, $message);
        socket_close($socket);
        unset($this->connections[$key]);
        return true;
      }
      $this->sendMessage($socket, $message);
      return true;
    }
    return false;
  }

  private function sendMessage($socket, $message)
  {
    $message .= "\n";
    socket_write($socket, $message, strlen($message)) or die("Could not write output\n");
  }

  private function closeConnectionIfIdle($key)
  {
    if (!isset($this->connections[$key])) {
      return false;
    }
    $conn = $this->connections[$key];
    if ((time() - $conn['last_activity']) > 50) {
      $message = "CONNECTION CLOSED BECAUSE IT WAS IDLE";
      $socket = $conn['socket'];
      $this->sendMessage($socket, $message);
      socket_close($socket);
      unset($this->connections[$key]);
    }
    return true;
  }
}

new Server();