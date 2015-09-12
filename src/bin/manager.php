<?php
require_once __DIR__ . '/../../init.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->addCommands(

	array(
		new RabbitManager\Commands\QueueAddCommand,
		new RabbitManager\Commands\QueueListenCommand,
	)
);
$application->run();
