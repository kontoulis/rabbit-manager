<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:43 μμ
 */

namespace RabbitManager\Commands;

use RabbitManager\Libs\Broker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueListenCommand extends Command
{

	protected function configure()

	{

		$this->setName('queue:listen')
			->setDescription("Listens to the specified Queue")
			->addArgument("queueName",
				InputArgument::REQUIRED,
				'The message to be added in queue. Usually a json encoded string'
			);

	}


	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$queueName = $input->getArgument("queueName");
		/* Listen to queue */

		$broker = new Broker();

		$broker->listenToQueue(

			$queueName,

			array(

				"\\RabbitManager\\Handlers\\" . $queueName . "Handler"

			)

		);

	}


}