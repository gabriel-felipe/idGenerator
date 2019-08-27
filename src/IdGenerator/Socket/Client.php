<?php

namespace IdGenerator\Socket;

use \IdGenerator\IdGenerator;

class Client
{
  protected $host;
  protected $port;
  protected $conn = null;

  public function __construct($host, $port)
  {
    require_once(dirname(__FILE__)."/../IdGenerator.php");
    $this->host = $host;
    $this->port = $port;
    if (!$this->connect()) {
      trigger_error("Couldn't connect to socket, will fallback to use the PHP version", E_USER_WARNING);
    }
    $self = $this;
    register_shutdown_function(function () use ($self) {
      $self->closeConnection();
    });
  }

  public function connect($retry = 0)
  {
    if ($this->conn) {
      $this->closeConnection();
    }
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket) {
      $result = socket_connect($socket, $this->host, $this->port);
      if ($result) {
        $this->conn = $socket;
        return true;
      }
    }
    if ($retry < 3) {
      usleep(200);
      return $this->connect($retry + 1);
    }
    return false;
  }

  public function closeConnection()
  {
    $this->execCommand("QUIT");
    @socket_close($this->conn);
    $this->conn = null;
  }

  public function execCommand($message)
  {
    if ($this->conn) {
      if (socket_write($this->conn, $message, strlen($message))) {
        $result = socket_read($this->conn, 1024);
        $result = trim($result);
        return $result;
      } else {
        echo "here";
        if ($this->connect()) {
          return $this->execCommand($message);
        } else {
          trigger_error("Couldn't write to socket", E_USER_WARNING);
          return false;
        }
      }
    }
    return false;
  }

  public function getId()
  {
    if ($this->conn) {
      $id = $this->execCommand("GET_ID");
      if ($id === false) {
        trigger_error("Couldn't connect to socket, will use the PHP version", E_USER_WARNING);
      } else {
        return $id;
      }
    }
    $id = IdGenerator::getId();
    return $id;
  }
}