<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */
namespace RabbitManager\Libs;

use PhpAmqpLib\Connection\AMQPConnection;

/**
 * Class Broker
 * @package RabbitManager\Libs
 */
class Broker
{

	/**
	 * @var
	 */
	protected $exchange;

	/**
	 * @var
	 */
	protected $queueName;

	/**
	 * @var AMQPConnection
	 */
	protected $connection;

	/**
	 * @var \PhpAmqpLib\Channel\AMQPChannel
	 */
	protected $channel;

	/**
	 * @var
	 */
	protected $consumer_tag;

	/**
	 * @var Logger
	 */
	protected $logger;

	protected $host;

	protected $port;

	protected $user;

	protected $password;

	protected $vhost;

	/**
	 * @param string $host
	 * @param int    $port
	 * @param string $user
	 * @param string $password
	 * @param string $vhost
	 * @throws \Exception
	 */
	function __construct($host = AMPQ_HOST, $port = AMPQ_PORT, $user = AMPQ_USER, $password = AMPQ_PASS, $vhost = AMPQ_VHOST)
	{
		$this->host = $host;
		$this->$port = $port;
		$this->user = $user;
		$this->password = $password;
		$this->vhost = $vhost;

		$this->logger = new Logger();
		try {

			/* Open RabbitMQ connection */

			$this->connection = new AMQPConnection($host, $port, $user, $password, $vhost);

			$this->channel = $this->connection->channel();

		} catch (AMQPRuntimeException $ex) {

			/* Something went wrong apparently... */

			$this->logger->addError(

				'Fatal error while initializing AMQP connection: '

				. $ex->getMessage()

			);

			throw new \Exception(

				'Fatal error while initializing AMQP connection: '

				. $ex->getMessage(),

				$ex->getCode()

			);

		}
	}

	/**
	 * Starts to listen a queue for incoming messages.
	 * @param string $queueName The AMQP queue
	 * @param array  $handlers  Array of handler class instances
	 * @return bool
	 */

	public function listenToQueue($queueName, array $handlers)
	{

		$this->queueName = $queueName;

		/* Look for handlers */

		$handlersMap = array();
		foreach ($handlers as $handlerClassPath) {

			if (!class_exists($handlerClassPath)) {

				$handlerClassPath = "RabbitManager\\Handlers\\$handlerClassPath";

				if (!class_exists($handlerClassPath)) {

					$this->logger->addError(

						"Class $handlerClassPath was not found!"

					);


					return false;

				}

			}

			$handlerOb = new $handlerClassPath();

			$classPathParts = explode("\\", $handlerClassPath);

			$handlersMap[$classPathParts[count(

				$classPathParts

			) - 1]] = $handlerOb;

		}


		/* Create queue */

		$this->channel->queue_declare(

			$queueName, false, true, false, false

		);


		/* Start consuming */

		$this->channel->basic_qos(null, 1, null);

		$this->channel->basic_consume(

			$queueName, '', false, false, false, false, function ($amqpMsg) use ($handlersMap) {


				$msg = Message::fromAMQPMessage($amqpMsg);

				Broker::handleMessage($msg, $handlersMap);

			}

		);

		$this->logger->addInfo(

			"Starting consumption of queue $queueName"

		);


		/* Iterate until ctrl+c is received... */

		while (count($this->channel->callbacks)) {

			$this->channel->wait();

		}

	}

	/**
	 * @param Message $msg
	 * @param array   $handlersMap
	 * @return bool
	 */
	public static function handleMessage(

		Message $msg, array $handlersMap

	)
	{


		$logger = new Logger();

		$logger->addDebug(

			"Message {$msg->getDeliveryTag()} received!"

		);


		/* Try to process the message */

		foreach ($handlersMap as $code => $ob) {

			$logger->addDebug(

				"Trying handler $code..."

			);


			$retVal = $ob->tryProcessing($msg);

			$msg->updateAMQPMessage();

			switch ($retVal) {

				case Handler::RV_SUCCEED_STOP:

					/* Handler succeeded, you MUST stop processing */

					return self::handleSucceedStop($msg, $logger);


				case Handler::RV_SUCCEED_CONTINUE:

					/* Handler succeeded, you SHOULD continue processing */

					self::handleSucceedContinue($msg, $logger);

					continue;


				case Handler::RV_PASS:

					/**
					 * Just continue processing (I have no idea what
					 * happened in the handler)
					 */

					continue;


				case Handler::RV_FAILED_STOP:

					/* Handler failed and MUST stop processing */


					return self::handleFailedStop($msg, $logger);


				case Handler::RV_FAILED_REQUEUE:

					/**
					 * Handler failed and MUST stop processing but the message
					 * will be rescheduled
					 */

					return self::handleFailedRequeue($msg, $logger);


				case Handler::RV_FAILED_CONTINUE:

					/* Well, handler failed, but you may try another */

					self::handleFailedContinue($msg, $logger);

					continue;


				default:

					/* I have no idea what is this response !!! */

					$logger->addError(

						"Invalid response from handler for message "

						. "{$msg->getDeliveryTag()}."

					);


					return false;

			}

		}


		/* If haven't return yet, send an ACK */

		$msg->sendAck();

	}

	/**
	 * @param null $msg
	 * @return int
	 */
	public function getStatus($msg = null)
	{

		$request = [

			"count"    => 10,

			"requeue"  => true,

			"encoding" => "auto"

		];

		if (!is_null($msg) && strlen($msg->queueName) > 0) {

			$queueName = $msg->queueName;

		} else {

			$queueName = $this->queueName;

		}

		$ch = curl_init();

		$url = "http://{$this->host}:{$this->port}/api/queues/%2F/" .

			$queueName . '/get';


		$fields = json_encode($request);

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_POST, count($fields));

		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

		curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->password);

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);


		$result = curl_exec($ch);
		curl_close($ch);


		$data = json_decode($result);

		$messages = $data;
		if (!empty($messages)) {
			if (is_array($messages)) {
				return $messages[0]->message_count;
			} else {
				return $messages->message_count;
			}
		} else {
			return -1;
		}

	}

	/**
	 * @param Message $msg
	 * @param Logger  $logger
	 * @return bool
	 */

	protected static function handleSucceedStop(Message $msg, Logger $logger)
	{

		$logger->addDebug(

			"Message {$msg->getDeliveryTag()}"

			. " was successfully processed by handler!"

		);

		$msg->sendAck();

		$remaining = self::getStatus($msg);

		if ($remaining < 1) {

			$logger->addDebug(" remaining $remaining messages \n");

			$logger->addDebug("exit broker");

			exit(1);

		}

		return true;

	}

	/**
	 * @param Message $msg
	 * @param Logger  $logger
	 * @return bool
	 */
	protected static function handleSucceedContinue(

		Message $msg, Logger $logger

	)
	{


		$logger->addDebug(

			"Message {$msg->getDeliveryTag()}"

			. " was successfully processed by handler!"

			. " Execution proceeds."

		);

		$remaining = self::getStatus($msg);

		if ($remaining < 1) {

			$logger->addDebug(" remaining $remaining messages \n");

			$logger->addDebug("No messages but broker stays alive");

		}

		return true;

	}


	/**
	 * @param Message $msg
	 * @param         $logger
	 * @return bool
	 */
	protected static function handleFailedStop(Message $msg, $logger)
	{

		$logger->addError(

			"Handler failed for message"

			. " {$msg->getDeliveryTag()}."

			. " Execution stops but message is not rescheduled."

		);

		$msg->sendNack();

		exit(1);

	}


	/**
	 * @param Message $msg
	 * @param         $logger
	 * @return bool
	 */
	protected static function handleFailedRequeue(Message $msg, $logger)
	{

		$logger->addError(

			"Handler failed for message"

			. " {$msg->getDeliveryTag()}."

			. " Execution stops but message is rescheduled."

		);

		$msg->sendNack();

		$msg->republish();

		return true;

	}


	/**
	 * @param Message $msg
	 * @param         $logger
	 * @return bool
	 */
	protected static function handleFailedContinue(Message $msg, $logger)
	{

		$logger->addError(

			"Handler failed for message"

			. " {$msg->getDeliveryTag()}."

			. " Execution will continue!"

		);

		return true;

	}

	/**
	 * @param Message $msg
	 */

	public function sendMessage(Message $msg)
	{

		/* Create the message */

		$amqpMessage = $msg->getAMQPMessage();


		/* Create queue */

		$this->channel->queue_declare(

			$msg->queueName, false, true, false, false

		);


		/* Publish message */

		$this->channel->basic_publish(

			$amqpMessage, '', $msg->queueName

		);


	}

}