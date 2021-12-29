<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use WindowsAzure\ServiceBus\Models\BrokeredMessage;

class AzureServiceBusReceiver implements ReceiverInterface, MessageCountAwareInterface
{
    private $connection;
    private $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    public function getMessageCount(): int
    {
        // TODO: Implement getMessageCount() method.
    }

    /**
     * @throws \Exception
     */
    public function get(): iterable
    {
        try {
            /** @var array|null $sqsEnvelope */
            $sqsEnvelope = $this->connection->get();
        } catch (\HttpException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
        if (null === $sqsEnvelope) {
            return;
        }

        try {
            $envelope = $this->serializer->decode([
                'body'    => $sqsEnvelope['body'],
                'headers' => $sqsEnvelope['headers'],
            ]);
        } catch (MessageDecodingFailedException $exception) {
            $this->connection->delete($sqsEnvelope['body']);

            throw $exception;
        }

        yield $envelope->with(new AzureServiceBusReceivedStamp($sqsEnvelope['id']));
    }

    public function ack(Envelope $envelope): void
    {
        // TODO: Implement ack() method.
    }

    public function reject(Envelope $envelope): void
    {
        // TODO: Implement reject() method.
    }
}
