import pika, os, sys, time, random, datetime, sqlite3, json, datetime
from pika.exchange_type import ExchangeType

EXCHANGE      = 'message'
EXCHANGE_TYPE = ExchangeType.topic
QUEUE         = 'queue'
ROUTING_KEY   = 'example.com'
DATABASE      = 'frontend/jobs.db'

def main():
  url    = os.environ.get("RABBITMQ_URL", "amqp://admin:admin@localhost:5672/%2F")
  params = pika.URLParameters(url)
  params.socket_timeout = 5

  def updateJob(jobid):
    sqlcon    = None
    completed = datetime.datetime.now().strftime('%d-%m-%Y %H:%M:%S')

    try:
      query  = "UPDATE jobs SET completedAt = ? WHERE id = ?;"
      sqlcon = sqlite3.connect(DATABASE)
      cursor = sqlcon.cursor()
      cursor.execute(query, (completed, jobid))
      sqlcon.commit()
      cursor.close()
    except sqlite3.Error as error:
      print("Failed to update sqlite table (1)", error)
    finally:
      if sqlcon:
          sqlcon.close()

  def checkJob(jobid):
    sqlcon = None
    result = None

    try:
      sqlcon = sqlite3.connect(DATABASE)
      cursor = sqlcon.cursor()
      cursor.execute("SELECT id FROM jobs WHERE id = ?", (jobid,))
      result = cursor.fetchone()
      cursor.close()
    except sqlite3.Error as error:
      print("Failed to update sqlite table (2)", error)
    finally:
      if sqlcon:
        sqlcon.close()  

    if result:
      return True
    else:
      return False 
  

  def callback(ch, method, properties, body):
    body = json.loads(body)
    if checkJob(body["id"]):
      print(datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S') + ' Processing...')
      print(" [x] Received %r" % body)
      time.sleep(random.randint(3,8))
      updateJob(body["id"])
      print(datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S') + ' Processed !')
    else:
      print("Job deleted from queue!")

    ch.basic_ack(delivery_tag=method.delivery_tag)

  connection = pika.BlockingConnection(params)
  channel    = connection.channel()
  #channel.queue_declare(queue=QUEUE)
  channel.basic_consume(queue=QUEUE, on_message_callback=callback, auto_ack=False)
  channel.start_consuming()

  while True:
      try:
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







