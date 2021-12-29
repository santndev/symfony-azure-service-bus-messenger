<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace Symfony\Component\Messenger\Bridge\AzureServiceBus\Tests\Transport;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\AzureServiceBus\Tests\Fixtures\DummyMessage;
use Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport\AzureServiceBusReceiver;
use Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport\Connection;
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

        $receiver = new AzureServiceBusReceiver($connection, $serializer);
        $actualEnvelopes = iterator_to_array($receiver->get());
        $this->assertCount(1, $actualEnvelopes);
        $this->assertEquals(new DummyMessage('Hi'), $actualEnvelopes[0]->getMessage());
    }

    private function createSqsEnvelope()
    {
        return [
            'id' => 1,
            'body' => '{"message": "Hi"}',
            'headers' => [
                'type' => DummyMessage::class,
            ],
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
