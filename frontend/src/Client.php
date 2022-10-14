<?php
namespace App\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Client
{
  private $host;
  private $port;
  private $username;
  private $password;
  private $queue;

  public function __construct($host = "localhost", $port = 5672, $username = "admin", $password = "admin", $queue = "queue")
  {
      $this->host = $host;
      $this->port = $port;
      $this->username = $username;
      $this->password = $password;
      $this->queue = $queue;
  }

  public function execute($message)
  {
    $connection = new AMQPStreamConnection($this->host, $this->port, $this->username, $this->password);
    $channel    = $connection->channel();
    
    $channel->queue_declare(
      $this->queue,    #queue - Queue names may be up to 255 bytes of UTF-8 characters
      false,           #passive - can use this to check whether an exchange exists without modifying the server state
      true,            #durable, make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
      false,           #exclusive - used by only one connection and the queue will be deleted when that connection closes
      false            #auto delete - queue is deleted when last consumer unsubscribes
    );
        
    $msg = new AMQPMessage(
      $message,
      array('delivery_mode' => 2) # make message persistent, so it is not lost if server crashes or quits
    );
        
    $channel->basic_publish(
      $msg,            #message 
      '',              #exchange
      $this->queue     #routing key (queue)
    );
        
    $channel->close();
    $connection->close();
  }
}