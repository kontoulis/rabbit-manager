<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */

namespace RabbitManager\Handlers;

use RabbitManager\Libs\Handler;
use RabbitManager\Libs\Message;

/**
 * Class DefaultHandler
 * @package RabbitManager\Handlers
 */
class DefaultHandler extends handler
{

	/**
	 * Tries to process the incoming message.
	 * @param Message $msg
	 * @return int One of the possible return values defined as Handler
	 * constants.
	 */
	public function tryProcessing(Message $msg)
	{
		return $this->handleSuccess($msg->getAMQPMessage()->body);

	}

	/**
	 * @param $msg
	 * @return int
	 */
	protected function handleSuccess($msg)
	{
		echo $msg . "\n";

		return Handler::RV_SUCCEED_CONTINUE;
	}
}