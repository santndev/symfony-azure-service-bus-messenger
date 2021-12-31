<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport;

use Exception;
use GuzzleHttp\Psr7\Stream;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\Models\BrokeredMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AzureServiceBusReceiver implements ReceiverInterface, MessageCountAwareInterface
{
    private $connection;
    private $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * @throws Exception
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
        /** @var BrokeredMessage|null $brokeredMessage */
        $brokeredMessage = $sqsEnvelope['body'];
        try {
            $brokeredProperties = null;
            if ($brokeredMessage) {
                $brokeredProperties = $brokeredMessage->getBrokerProperties();
            }
            $lockLocation = $brokeredProperties ? $brokeredProperties->getLockLocation() : null;
            /** @var Stream|string $messageBody */
            $messageBody = $brokeredMessage->getBody();
            $envelope    = $this->serializer->decode([
                'body'          => ($messageBody instanceof Stream) ? $messageBody->getContents() : $messageBody,
                'headers'       => $sqsEnvelope['headers'],
                'lock_location' => $lockLocation
            ]);
        } catch (MessageDecodingFailedException $exception) {
            $this->connection->delete($brokeredMessage);

            throw $exception;
        }

        yield $envelope->with(new AzureServiceBusReceivedStamp($lockLocation));
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function ack(Envelope $envelope): void
    {
        try {
            $this->connection->deleteByLockLocation($this->findSqsReceivedStamp($envelope)->getLockLocation());
        } catch (\HttpException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws Exception
     */
    public function reject(Envelope $envelope): void
    {
        $this->connection->reset();
    }

    private function findSqsReceivedStamp(Envelope $envelope): AzureServiceBusReceivedStamp
    {
        /** @var AzureServiceBusReceivedStamp|null $serviceBusReceivedStamp */
        $serviceBusReceivedStamp = $envelope->last(AzureServiceBusReceivedStamp::class);

        if (null === $serviceBusReceivedStamp) {
            throw new LogicException('No AzureServiceBusReceivedStamp found on the Envelope.');
        }

        return $serviceBusReceivedStamp;
    }

    /**
     * @throws Exception
     */
    public function getMessageCount(): int
    {
        return $this->connection->getMessageCount();
    }
}
