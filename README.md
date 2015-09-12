# RabbitMQ Manager (Standalone)

RabbitMQ Manager is a standalone php package to easily manage RabbitMQ

  - Built-in command line tools. Simple commands to add/receive messages to/from RabbitMQ
  - Install as Standalone, or add it to your own project
  - Built in Message Handler and Broker

## Install with composer
```bash
$ composer require asmodai/RabbitManager
```
## Or use it as standalone
Add bin/rabbit-manager to /usr/local/bin

```bash
$ sudo ln -s /full/path/to/project/bin/rabbit-manager /usr/local/bin/rabbit-manageer
```
Use the default commands or create your own based on those.

## Dependencies
- A running instance of [RabbitMQ](https://www.rabbitmq.com) ofcourse 

## Testing
Don't forget to run the tests!
```bash
$ vendor/bin/phpunit
```

## Usage
There are two types of Jobs. One to add messages to a queue and one to listen to that queue.
### Command Line
> You can build your own commands based on the ones already defined in the package
>and then adding them in src/manager.php
```php
    $application->addCommands(
    	array(
    		new RabbitManager\Commands\QueueAddCommand,
    		new RabbitManager\Commands\QueueListenCommand,
    		new RabbitManager\Commands\YourCustomCommand,
    	)
    );
```

The package includes 2 basic Commands
You can run those from command line, or even add them to [supervisor](http://supervisord.org/index.html) as workers for as many instances you need

 - queue:add -> Adds a message to the specified queue 
 
```
$ rabbit-manager queue:add [queueName] [message]
```

- queue:listen -> Consumes the messages from a queue

```
$ rabbit-manager queue:listen [queueName]
```

### As a library
You can use the package as a library by creating your own CustomHandler and Broker. (However you can use broker as a four-liner to add a message to queue)

- Adding messages to a queue 

```php
use RabbitManager\Libs\Broker;
use RabbitManager\Libs\Message;

// Your Class and Methods

public function publishMessage($message , $queueName = "TheNameOfTheQueue")
{
    $broker = new Broker(AMPQ_HOST, AMPQ_PORT, AMPQ_USER, AMPQ_PASSWORD , AMPQ_VHOST);
    /* Makes the AMPQ message */
    $msg = new Message($queueName, ["message" => $message]);
    
    /* Sends the message */
    $broker->sendMessage($msg);
    
    $output->writeln('<info>Successfully submitted in queue</info>');
}
```
- Consuming the queue :
  To consume a queue you probabbly need a script to run from the command line, or a script that can run until the       queue is empty.
  A good practice is to have a script just to listen to the Queue and a Handler to do the job with every received message. You can add that file as a worker to the supervisor or just run it as it is.  You could also do all that in the same file.
```php
use RabbitManager\Libs\Broker;

public function listenToQueue($queueName = "TheNameOfTheQueue" )
// Listening to queue
  $broker = new Broker();
  // Here you tell the broker which handler to call in order to parse the message
  // Use a fully qualified Namespace.
  // The broker will call the tryProcessing() method of the specified Handler
  // for every message received from the queue
  $broker->listenToQueue(
  	$queueName,
  	array(
  		"\\RabbitManager\\Handlers\\" . $queueName . "Handler"
  	)
  );

```
```php

use RabbitManager\Libs\Handler;
use RabbitManager\Libs\Message;

class TheNameOfTheQueue extends Handler
{

	/**
	 * Tries to process the incoming message.
	 * @param Message $msg
	 * @return int One of the possible return values defined as Handler
	 * constants.
	 */
	public function tryProcessing(Message $msg)
	{
	  // TODO : Check, modify or validate the message.
	  // If the message is OK, process it
		return $this->handleSuccess($msg->getAMQPMessage()->body);

	}

	/**
	 * @param $msg
	 * @return int
	 */
	protected function handleSuccess($msg)
	{
	  // TODO : Do the processing. Store something in the db,
	  // Send a notification or eanything you are supossed to do with the received message
		echo $msg . "\n";
    
    // Returns and integer to the Broker, and the broker continues accordingly.
    // For a full list of return codes see the section bellow
		return Handler::RV_SUCCEED_CONTINUE;
	}
}
```

### Handler return values
These return values will tell the broker what to do after you process a message
```php
  /**
	 * Pass this message and proceed with the next
	 */
	const RV_PASS = 1;
	/**
	 * Continue and ignore the failed message
	 */
	const RV_FAILED_CONTINUE = 10;
	/**
	 * We failed to do our job with this message (e.g. failed to store it in the database),
	 * Force exit
	 */
	const RV_FAILED_STOP = 11;
	/**
	 * We failed to do our job with this message (e.g. failed to store it in the database),
	 * put it again in the queue
	 */
	const RV_FAILED_REQUEUE = 12;
	/**
	 * Keep listening to the queue after successfully parsing the message
	 */
	const RV_SUCCEED_CONTINUE = 20;
	/**
	 *  Force stop listening after successfully parsing a message
	 */
	const RV_SUCCEED_STOP = 21;
	/**
	 *
	 */
	const RV_SUCCEED = Handler::RV_SUCCEED_CONTINUE;
	/**
	 *
	 */
	const RV_FAILED = Handler::RV_FAILED_CONTINUE;
	/**
	 *
	 */
	const RV_ACK = Handler::RV_SUCCEED;
	/**
	 *
	 */
	const RV_NACK = Handler::RV_FAILED_STOP;
```

### Version
1.0.0

Feel free to give some feedback or ask any questions
