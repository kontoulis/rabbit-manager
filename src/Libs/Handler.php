<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */
namespace RabbitManager\Libs;


/**
 * Class Handler
 * @package RabbitManager\Libs
 */
abstract class Handler

{

	/**************************************************************************
	 * Message processing return values
	 *************************************************************************/

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

	/** @var \Monolog\Logger */

	protected $logger;
	/**
	 * The name of the logger for the handler.
	 * @var string
	 */

	protected $loggerName = 'Messaging/Handler';

	/**
	 *
	 */
	public function __construct()

	{

		$this->logger = new Logger($this->loggerName);


		$this->messagingBroker = new Broker();

	}


	/**************************************************************************
	 * Message processing method
	 *************************************************************************/


	/**
	 * Tries to process the incoming message.
	 * @param Message $msg
	 * @return int One of the possible return values defined as Handler
	 * constants.
	 */

	abstract public function tryProcessing(Message $msg);

	abstract protected function handleSuccess($msg);
}

