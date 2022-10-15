<?php
namespace App\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

class Client
{
  private $exchange;
  private $queue;

  public function __construct($exchange = "test", $queue = "queue")
  {
    $this->exchange = $exchange;
    $this->queue = $queue;
  }

  public function execute($message)
  {
    $url   = !empty(getenv('AMQP_URL')) ? getenv('AMQP_URL') : "amqp://admin:admin@localhost:5672/";
    $url   = parse_url($url);
    $vhost = ($url['path'] == '/' || !isset($url['path'])) ? '/' : substr($url['path'], 1);
    $port  = $url['port'];
    if($url['scheme'] === "amqps") {
        $port = isset($port) ? $port : 5671;
        $ssl_opts = array(
            'capath' => '/etc/ssl/certs'
        );
        $connection = new AMQPSSLConnection($url['host'], $port, $url['user'], $url['pass'], $vhost, $ssl_opts);
    } else {
        $port = isset($port) ? $port : 5672;
        $connection = new AMQPStreamConnection($url['host'], $port, $url['user'], $url['pass'], $vhost);
    }

    $channel = $connection->channel();
    $channel->set_ack_handler(
      function (AMQPMessage $message) {
          // echo "Message acked with content " . $message->body . PHP_EOL;
      }
    );
    $channel->set_nack_handler(
      function (AMQPMessage $message) {
          // echo "Message nacked with content " . $message->body . PHP_EOL;
      }
    );

    $channel->confirm_select();

    $channel->queue_declare(
      $this->queue,    #queue - Queue names may be up to 255 bytes of UTF-8 characters
      false,           #passive - can use this to check whether an exchange exists without modifying the server state
      false,           #durable, make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
      false,           #exclusive - used by only one connection and the queue will be deleted when that connection closes
      false            #auto delete - queue is deleted when last consumer unsubscribes
    );
        
    $channel->exchange_declare($this->exchange, AMQPExchangeType::DIRECT, false, false, true);
    
    $channel->queue_bind($this->queue, $this->exchange);

    $msg = new AMQPMessage(
      $message,
      array('content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
    );
        
    $channel->basic_publish(
      $msg,            #message 
      '',              #exchange
      $this->queue     #routing key (queue)
    );
    
    $channel->wait_for_pending_acks();
    $channel->close();

    $connection->close();
  }
}