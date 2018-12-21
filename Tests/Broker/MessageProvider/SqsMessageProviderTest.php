<?php

namespace Swarrot\SwarrotBundle\Tests\Broker\MessageProvider;

use Aws\Sqs\SqsClient;
use Guzzle\Service\Resource\Model;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Swarrot\Broker\Message;
use Swarrot\SwarrotBundle\Broker\MessageProvider\SqsMessageProvider;

class SqsMessageProviderTest extends TestCase
{
    private $sqsClient;
    private $provider;

    public function setUp()
    {
        $this->sqsClient = $this->prophesize(SqsClient::class);
        $this->provider = new SqsMessageProvider(
         $this->sqsClient->reveal(),
         'foobar'
     );
    }

    public function test_it_is_initializable()
    {
        $this->assertInstanceOf(SqsMessageProvider::class, $this->provider);
    }

    public function test_it_provides_message()
    {
        $result = $this->prophesize(Model::class);
        $message = [
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
        ];

        $this->sqsClient->receiveMessage(Argument::type('array'))->shouldBeCalled()->willReturn($result->reveal());
        $result->get('Messages')->willReturn($message);

        $this->assertInstanceOf(Message::class, $this->provider->get());
    }
}
