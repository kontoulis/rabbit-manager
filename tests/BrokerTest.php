<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */
use RabbitManager\Libs\Broker;
use RabbitManager\Libs\Message;

/**
 * Class BrokerTest
 */
class BrokerTest extends PHPUnit_Framework_TestCase {

    /**
     *
     */
    public function testBroker()
    {
        $broker = new Broker();
        $msg = new Message(
            'TestQueue', array(
                "test_param" => "I am a test param",
                "test_mesage" => "I am a test message"
            )
        );

        $broker->sendMessage($msg);

        $messageCount = $broker->getStatus($msg) +1;
        echo "Messages in queue : ". $messageCount ."\n";
    }
}
 