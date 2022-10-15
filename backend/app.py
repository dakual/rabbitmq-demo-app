import pika, os, sys, time, random, datetime, sqlite3, json, datetime, requests
from pika.exchange_type import ExchangeType

EXCHANGE      = 'message'
EXCHANGE_TYPE = ExchangeType.direct
QUEUE         = 'queue'
ROUTING_KEY   = 'example.com'
AMQP_URL      = os.environ.get("AMQP_URL", "amqp://admin:admin@localhost:5672/%2F")
API_URL       = os.environ.get("API_URL", "http://127.0.0.1:8080/api.php")

def main():
  params = pika.URLParameters(AMQP_URL)
  params.socket_timeout = 5

  def updateJob(jobid):
    date = datetime.datetime.now().strftime('%d-%m-%Y %H:%M:%S')
    data = { 'id': jobid, 'action': 'completed', 'timestamp': date }
    r = requests.post(
      API_URL, 
      data=json.dumps(data), 
      headers={'Content-Type': 'application/json'}
    )


  def checkJob(jobid):
    data = { 'id': jobid, 'action': 'check'}
    r = requests.post(
      API_URL, 
      data=json.dumps(data), 
      headers={'Content-Type': 'application/json'}
    )
    return r.json()["status"]
  

  def callback(ch, method, properties, body):
    print("Job Received %r" % body)
    body = json.loads(body)
    if checkJob(body["id"]):
      print(datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S') + ' Processing...')
      time.sleep(random.randint(3,8))
      updateJob(body["id"])
      print(datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S') + ' Processed!')
    else:
      print("Job deleted!")
    ch.basic_ack(delivery_tag=method.delivery_tag)

  connection = pika.BlockingConnection(params)
  channel    = connection.channel()
  channel.queue_declare(queue=QUEUE)
  channel.basic_consume(queue=QUEUE, on_message_callback=callback, auto_ack=False)

  while True:
      try:
        print("Starting consumer...")
        channel.start_consuming()
      except pika.exceptions.ConnectionClosedByBroker:
          break
      except pika.exceptions.AMQPChannelError:
          break
      except pika.exceptions.AMQPConnectionError:
          continue

if "__main__" == __name__:
  try:
    main()
  except KeyboardInterrupt:
    sys.exit(0)







