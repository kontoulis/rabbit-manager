<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */

namespace RabbitManager\Libs;


/**
 * Class Logger
 * @package RabbitManager\Libs
 */
class Logger extends \Monolog\Logger

{
	/**
	 * @var
	 */
	public $logger;

	/**
	 * @param string $name
	 * @param array  $handlers
	 * @param array  $processors
	 */
	function __construct($name = 'main', array $handlers = array(), array $processors = array())
	{
		/* Create new logger */
		parent::__construct($name, $handlers, $processors);
		/* Add default, console handler */

		$handler = new \Monolog\Handler\StreamHandler(

			'php://stderr', \Monolog\Logger::DEBUG

		);

		$handler->setFormatter(

			new \Monolog\Formatter\LineFormatter(

				"[%datetime%] [%channel%.%level_name%] -- %message%\n"

			)

		);

		$this->pushHandler($handler);

	}
}