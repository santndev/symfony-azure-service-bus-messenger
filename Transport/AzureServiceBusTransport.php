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
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ResetInterface;

class AzureServiceBusTransport implements TransportInterface, SetupableTransportInterface, MessageCountAwareInterface, ResetInterface
{
    private $serializer;
    private $connection;
    private $receiver;
    private $sender;

    public function __construct(Connection $connection, SerializerInterface $serializer = null, ReceiverInterface $receiver = null, SenderInterface $sender = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
        $this->receiver = $receiver;
        $this->sender = $sender;
    }
    /**
     * {@inheritdoc}
     */
    public function get(): iterable
    {
        return ($this->receiver ?? $this->getReceiver())->get();
    }

    /**
     * {@inheritdoc}
     */
    public function ack(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->ack($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function reject(Envelope $envelope): void
    {
        ($this->receiver ?? $this->getReceiver())->reject($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageCount(): int
    {
        return ($this->receiver ?? $this->getReceiver())->getMessageCount();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        return ($this->sender ?? $this->getSender())->send($envelope);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        try {
            $this->connection->setup();
        } catch (HttpException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    public function reset()
    {
        try {
            $this->connection->reset();
        } catch (HttpException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    private function getReceiver(): AzureServiceBusReceiver
    {
        return $this->receiver = new AzureServiceBusReceiver($this->connection, $this->serializer);
    }

    private function getSender(): AzureServiceBusSender
    {
        return $this->sender = new AzureServiceBusSender($this->connection, $this->serializer);
    }
}
