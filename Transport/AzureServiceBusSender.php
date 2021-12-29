<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport;

use HttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AzureServiceBusSender implements SenderInterface
{
    private $connection;
    private $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);
        $delay = null !== $delayStamp ? (int) ceil($delayStamp->getDelay() / 1000) : 0;

        $messageGroupId = null;
        $messageDeduplicationId = null;
        $xrayTraceId = null;

//        /** @var AmazonSqsFifoStamp|null $amazonSqsFifoStamp */
//        $amazonSqsFifoStamp = $envelope->last(AmazonSqsFifoStamp::class);
//        if (null !== $amazonSqsFifoStamp) {
//            $messageGroupId = $amazonSqsFifoStamp->getMessageGroupId();
//            $messageDeduplicationId = $amazonSqsFifoStamp->getMessageDeduplicationId();
//        }

//        /** @var AmazonSqsXrayTraceHeaderStamp|null $amazonSqsXrayTraceHeaderStamp */
//        $amazonSqsXrayTraceHeaderStamp = $envelope->last(AmazonSqsXrayTraceHeaderStamp::class);
//        if (null !== $amazonSqsXrayTraceHeaderStamp) {
//            $xrayTraceId = $amazonSqsXrayTraceHeaderStamp->getTraceId();
//        }

        try {
            $this->connection->send(
                $encodedMessage['body'],
                $encodedMessage['headers'] ?? [],
                $delay,
                $messageGroupId,
                $messageDeduplicationId,
                $xrayTraceId
            );
        } catch (HttpException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        return $envelope;
    }
}
