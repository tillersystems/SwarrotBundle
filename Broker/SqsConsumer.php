<?php

namespace Swarrot\SwarrotBundle\Broker;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swarrot\Consumer;
use Swarrot\Processor\ConfigurableInterface;
use Swarrot\Processor\InitializableInterface;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\SleepyInterface;
use Swarrot\Processor\TerminableInterface;
use Swarrot\SwarrotBundle\Broker\MessageProvider\SqsMessageProvider;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SqsConsumer extends Consumer
{
    /**
     * @var SqsMessageProvider
     */
    protected $messageProvider;

    /**
     * @param SqsMessageProvider $messageProvider
     * @param ProcessorInterface $processor
     * @param OptionsResolver    $optionsResolver
     * @param LoggerInterface    $logger
     */
    public function __construct(SqsMessageProvider $messageProvider, ProcessorInterface $processor, OptionsResolver $optionsResolver = null, LoggerInterface $logger = null)
    {
        $this->messageProvider = $messageProvider;
        $this->processor = $processor;
        $this->optionsResolver = $optionsResolver ?: new OptionsResolver();
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * consume.
     *
     * @param array $options Parameters sent to the processor
     */
    public function consume(array $options = [])
    {
        $this->logger->debug(sprintf(
            'Start consuming queue %s.',
            $this->messageProvider->getQueueName()
        ));

        $this->optionsResolver->setDefaults([
            'poll_interval' => 50000,
            'queue' => $this->messageProvider->getQueueName(),
        ]);

        if ($this->processor instanceof ConfigurableInterface) {
            $this->processor->setDefaultOptions($this->optionsResolver);
        }

        $options = $this->optionsResolver->resolve($options);

        if ($this->processor instanceof InitializableInterface) {
            $this->processor->initialize($options);
        }

        while (true) {
            while (null !== $message = $this->messageProvider->get()) {
                if (false === $this->processor->process($message, $options)) {
                    while (null !== $message = $this->messageProvider->getFromCache()) {
                        $this->processor->process($message, $options);
                    }

                    break 2;
                }
            }

            if ($this->processor instanceof SleepyInterface) {
                if (false === $this->processor->sleep($options)) {
                    break;
                }
            }

            usleep($options['poll_interval']);
        }

        if ($this->processor instanceof TerminableInterface) {
            $this->processor->terminate($options);
        }
    }
}
