<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/27/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace SanTran\Component\Messenger\Bridge\AzureServiceBus\Tests\Transport;

use PHPUnit\Framework\TestCase;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\Connection;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\ServiceException;

class ConnectionTest extends TestCase
{
    public function testExtraOptions()
    {
        $this->expectException(\InvalidArgumentException::class);
        Connection::fromDsn('sqs://default/queue', [
            'extra_key',
        ]);
    }

    public function testExtraParamsInQuery()
    {
        $this->expectException(\InvalidArgumentException::class);
        Connection::fromDsn('sqs://default/queue?extra_param=some_value');
    }

    public function testConfigureWithCredentials()
    {
        $awsKey     = 'test';
        $awsSecret  = 'secret';
        $entityPath = 'messenger';
        $connection = Connection::fromDsn('https://namespace.servicebus.windows.net', [
            'shared_access_key_name' => $awsKey,
            'shared_access_key'      => $awsSecret,
            'entity_path'            => $entityPath,
        ]);
        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testConfigureWithAutoSetUpNoPermission()
    {
        $awsKey     = 'test';
        $awsSecret  = 'secret';

        $connection = Connection::fromDsn('https://namespace.servicebus.windows.net/messenger', [
            'shared_access_key_name' => $awsKey,
            'shared_access_key'      => $awsSecret,
            'auto_setup'             => true
        ]);

        $this->expectException(ServiceException::class);
        $connection->get();
    }

    public function testConfigureWithAutoSetUp()
    {
        $awsKey     = 'test';
        $awsSecret  = 'secret';

        $connection = Connection::fromDsn('https://namespace.servicebus.windows.net/emag-api', [
            'shared_access_key_name' => $awsKey,
            'shared_access_key'      => $awsSecret,
            'auto_setup'             => true
        ]);

        $this->expectException(ServiceException::class);
        $connection->get();
    }
}
