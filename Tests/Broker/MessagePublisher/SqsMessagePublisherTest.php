<?php

namespace Swarrot\SwarrotBundle\Tests\Broker\MessagePublisher;

use Aws\Sqs\SqsClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Swarrot\Broker\Message;
use Swarrot\SwarrotBundle\Broker\MessagePublisher\SqsMessagePublisher;

class SqsMessagePublisherTest extends TestCase
{
    private $sqsClient;
    private $publisher;

    public function setUp()
    {
        $this->sqsClient = $this->prophesize(SqsClient::class);
        $this->publisher = new SqsMessagePublisher(
            $this->sqsClient->reveal(),
            'foobar'
        );
    }


    public function test_it_is_initializable()
    {
        $this->assertInstanceOf(SqsMessagePublisher::class, $this->publisher);
    }

    public function test_publish_message()
    {
        $message = new Message(
            'mockBody',
            [
                'StringParam' => 'mockEntityName',
                'NumberParam' => 1,
            ]
        );

        $params = [
            'MessageAttributes' => [
                'StringParam' => [
                    'DataType' => 'String',
                    'StringValue' => 'mockEntityName',
                ],
                'NumberParam' => [
                    'DataType' => 'Number',
                    'StringValue' => 1,
                ],
            ],
            'QueueUrl' => 'type(string)',
            'MessageBody' => json_encode($message->getBody()),
        ];

        $this->sqsClient->sendMessage($params)->shouldBeCalled();

        $this->publisher->publish($message);
    }
}
