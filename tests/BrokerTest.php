<?php
/**
 * Package : RabbitMQ Manager
 * User: kontoulis
 * Date: 12/9/2015
 * Time: 1:24 μμ
 */
use RabbitManager\Libs\Broker;
use RabbitManager\Libs\Message;

require_once __DIR__.'/../src/config.php';
/**
 * Class BrokerTest
 */
class BrokerTest extends PHPUnit_Framework_TestCase {

    private $broker;

    function __construct($name = 'Rabbit Manager', $data=[], $dataName='')
    {
        $this->broker = new Broker();
        $this->broker->setQueue('TestQueue');
        parent::__construct($name, $data, $dataName);
    }

    /**
     *
     */
    public function testBroker()
    {
        $msg = new Message(
            'TestQueue', array(
                "test_param" => "I am a test param",
                "test_mesage" => "I am a test message"
            )
        );
        $this->broker->sendMessage($msg);

        $messageCount = $this->broker->getStatus($msg) +1;
        echo "Messages in queue : ". $messageCount ."\n";
    }

    public function testBatch(){
        $messages = [];
        for($i = 0; $i < 10; $i++) {
            $messages[$i] = array(
                    "test_param" => "I am a test param $i",
                    "test_mesage" => "I am a test message $i"
                );

        }

        $this->broker->publish_batch($messages);

        echo "Added ". count($messages) ." in queue \n";
    }

//    public function testConsume(){
//        var_dump($this->broker->getChannel()->queue_declare('TestQueue',false, true, false, false));
//        $this->broker->listenToQueue(\RabbitManager\Handlers\DefaultHandler::class,null,true);
//        echo "\nComplete\n";
//    }
}
 