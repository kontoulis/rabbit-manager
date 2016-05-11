<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:36 μμ
 */

namespace RabbitManager\Commands;

use RabbitManager\Libs\Broker;
use RabbitManager\Libs\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueAddCommand extends Command
{

	public function __construct($name = null)
	{

		parent::__construct($name);

	}


	/**
	 *
	 */
	protected function configure()
	{

		$this->setName('queue:add')
			->setDescription('Push message to queue')
			->addArgument('queueName',
				InputArgument::REQUIRED,
				"The name of the queue. The queue will be created if it doesn't exist"
			)->addArgument('message',
				InputArgument::REQUIRED,
				'The message to be added in queue. Usually a json encoded string'
		);

	}


	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @return bool
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$queueName = $input->getArgument("queueName");
		$message = $input->getArgument("message");


		$broker = new Broker();
		/* Makes the AMQP message */
		$msg = new Message($queueName, ["message" => $message]);

		/* Sends the message */

		$broker->sendMessage($msg);


		$output->writeln('<info>Successfully submitted in queue</info>');

	}
}