<?php

namespace Swarrot\SwarrotBundle\Broker\MessagePublisher;

use Aws\Sqs\SqsClient;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

final class SqsMessagePublisher implements MessagePublisherInterface
{
    private $channel;
    private $queueUrl;

    /**
     * @param SqsClient $channel
     * @param string    $queueUrl
     */
    public function __construct(SqsClient $channel, string $queueUrl)
    {
        $this->channel = $channel;
        $this->queueUrl = $queueUrl;
    }

    /** {@inheritdoc} */
    public function publish(Message $message, $routingKey = null)
    {
        $attributes = [];
        foreach ($message->getProperties() as $key => $value) {
            $attributes[$key] = [
                'DataType' => 'String',
                'StringValue' => $value,
            ];
        }
        $this->channel->sendMessage([
            'MessageAttributes' => $attributes,
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => json_encode($message->getBody()),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeName()
    {
        return $this->queueUrl;
    }
}
