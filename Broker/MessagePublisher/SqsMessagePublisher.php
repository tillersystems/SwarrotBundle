<?php

namespace Swarrot\SwarrotBundle\Broker\MessagePublisher;

use Aws\Sqs\SqsClient;
use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

final class SqsMessagePublisher implements MessagePublisherInterface
{
    private $channel;
    private $queueUrl;

    const MESSAGE_DEDUPLICATION_ID = 'MessageDeduplicationId';

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
        $messageDeduplicationId = null;
        foreach ($message->getProperties() as $key => $value) {
            if (MESSAGE_DEDUPLICATION_ID === $key) {
                $messageDeduplicationId = $value;
            } else {
                $attributes[$key] = [
                    'DataType' => is_int($value) ? 'Number' : 'String',
                    'StringValue' => $value,
                ];
            }
        }
        $params = [
            'MessageAttributes' => $attributes,
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => json_encode($message->getBody()),
        ];

        if ($this->isFifo($this->queueUrl)) {
            $params = array_merge($params, [
                'MessageGroupId' => $message->getId(),
            ]);

            if (!is_null($messageDeduplicationId)) {
                $params = array_merge($params, [
                    'MessageDeduplicationId' => $messageDeduplicationId,
                ]);
            }
        }

        $this->channel->sendMessage($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeName()
    {
        return $this->queueUrl;
    }

    /**
     * @param string $queue
     *
     * @return bool
     */
    private function isFifo(string $queue): bool
    {
        return false !== strpos($queue, '.fifo');
    }
}
