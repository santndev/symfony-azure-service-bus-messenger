<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace SanTran\Component\Messenger\Bridge\AzureServiceBus\Tests\Transport;

use PHPUnit\Framework\TestCase;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Tests\Fixtures\DummyMessage;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\AzureServiceBusReceiver;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\Connection;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\Models\BrokeredMessage;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Serializer as SerializerComponent;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AmazonSqsReceiverTest extends TestCase
{
    public function testItReturnsTheDecodedMessageToTheHandler()
    {
        $serializer = $this->createSerializer();

        $sqsEnvelop = $this->createSqsEnvelope();
        $connection = $this->createMock(Connection::class);
        $connection->method('get')->willReturn($sqsEnvelop);

        $receiver        = new AzureServiceBusReceiver($connection, $serializer);
        $actualEnvelopes = iterator_to_array($receiver->get());
        $this->assertCount(1, $actualEnvelopes);
        $this->assertEquals(new DummyMessage('Hi'), $actualEnvelopes[0]->getMessage());
    }

    private function createSqsEnvelope()
    {
        $message = new BrokeredMessage();
        $message->setBody('{"message": "Hi"}');
        $message->setLockLocation('locked');
        return [
            'id'      => 1,
            'body'    => $message,
            'headers' => [
                'type' => DummyMessage::class,
            ],
            'lock_location'=>'locked'
        ];
    }

    private function createSerializer(): Serializer
    {
        $serializer = new Serializer(
            new SerializerComponent\Serializer([new ObjectNormalizer()], ['json' => new JsonEncoder()])
        );

        return $serializer;
    }
}
