<?php
/**
 * ${CLASS_DESCRIPTION}
 *
 * @project      symfony-azure-service-bus-messenger
 * @date         12/30/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace SanTran\Component\Messenger\Bridge\AzureServiceBus\Tests\Transport;

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Tests\Fixtures\DummyMessage;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\AzureServiceBusReceiver;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\AzureServiceBusTransport;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\Connection;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\Models\BrokeredMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AzureServiceBusTransportTest extends TestCase
{
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        // Mocking the concrete receiver class because mocking multiple interfaces is deprecated
        $this->receiver = $this->createMock(AzureServiceBusReceiver::class);
        $this->sender   = $this->createMock(SenderInterface::class);

        $this->transport = new AzureServiceBusTransport($this->connection, null, $this->receiver, $this->sender);
    }

    public function testItIsATransport()
    {
        $transport = $this->getTransport();

        $this->assertInstanceOf(TransportInterface::class, $transport);
    }

    public function testReceivesMessages()
    {
        $transport = $this->getTransport(
            $serializer = $this->createMock(SerializerInterface::class),
            $connection = $this->createMock(Connection::class)
        );

        $decodedMessage = new DummyMessage('Decoded.');

        $message = new BrokeredMessage();
        $message->setBody('body');
        $message->setLockLocation('locked');
        $sqsEnvelope = [
            'id'      => '5',
            'body'    => $message,
            'headers' => ['my' => 'header'],
            'lock_location'=>'locked'
        ];

        $serializer->method('decode')
                   ->with(['body' => $message->getBody(), 'headers' => ['my' => 'header'], 'lock_location'=>'locked'])
                   ->willReturn(new Envelope($decodedMessage));
        $connection->method('get')->willReturn($sqsEnvelope);

        $envelopes = iterator_to_array($transport->get());
        $this->assertSame($decodedMessage, $envelopes[0]->getMessage());
    }

    private function getTransport(SerializerInterface $serializer = null, Connection $connection = null)
    {
        $serializer = $serializer ?? $this->createMock(SerializerInterface::class);
        $connection = $connection ?? $this->createMock(Connection::class);

        return new AzureServiceBusTransport($connection, $serializer);
    }
}
