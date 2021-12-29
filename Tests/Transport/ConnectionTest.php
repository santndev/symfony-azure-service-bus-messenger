<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/27/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace Symfony\Component\Messenger\Bridge\AzureServiceBus\Tests\Transport;

use GuzzleHttp\ClientInterface;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport\Connection;
use WindowsAzure\Common\ServiceException;

class ConnectionTest extends TestCase
{
//    public function testExtraOptions()
//    {
//        $this->expectException(\InvalidArgumentException::class);
//        Connection::fromDsn('sqs://default/queue', [
//            'extra_key',
//        ]);
//    }
//
//    public function testExtraParamsInQuery()
//    {
//        $this->expectException(\InvalidArgumentException::class);
//        Connection::fromDsn('sqs://default/queue?extra_param=some_value');
//    }

//    public function testConfigureWithCredentials()
//    {
//        $awsKey     = 'emag-sb-sas';
//        $awsSecret  = 'W0hC8o98RSPzanKeWI78ar2sm04fsMJw7xdzkQRWBko=';
//        $entityPath = 'emag';
//        $connection = Connection::fromDsn('https://dreamrobot-apps.servicebus.windows.net', [
//            'shared_access_key_name' => $awsKey,
//            'shared_access_key'      => $awsSecret,
//            'entity_path'            => $entityPath,
//        ]);
//        $this->assertInstanceOf(Connection::class, $connection);
//    }

//    public function testConfigureWithAutoSetUpNoPermission()
//    {
//        $awsKey     = 'emag-sb-sas';
//        $awsSecret  = 'W0hC8o98RSPzanKeWI78ar2sm04fsMJw7xdzkQRWBko=';
//        $entityPath = 'emag';
//
//        $connection = Connection::fromDsn('https://dreamrobot-apps.servicebus.windows.net/emag', [
//            'shared_access_key_name' => $awsKey,
//            'shared_access_key'      => $awsSecret,
//            'entity_path'            => $entityPath,
//            'auto_setup'             => true
//        ]);
//
//        $this->expectException(ServiceException::class);
//        $connection->get();
//    }

//    public function testConfigureWithAutoSetUp()
//    {
//        $awsKey     = 'RootManageSharedAccessKey';
//        $awsSecret  = 'N5SBHuQNzQ3rbbhzFxSL+BN5aX+mHzjc06RxM9/3CCo=';
//
//        $connection = Connection::fromDsn('https://dreamrobot-apps.servicebus.windows.net/emag-api', [
//            'shared_access_key_name' => $awsKey,
//            'shared_access_key'      => $awsSecret,
//            'auto_setup'             => true
//        ]);
//
//        $this->expectException(ServiceException::class);
//        $connection->get();
//    }

    public function testConfigureWithAutoSetUp()
    {
        $awsKey     = 'RootManageSharedAccessKey';
        $awsSecret  = 'N5SBHuQNzQ3rbbhzFxSL+BN5aX+mHzjc06RxM9/3CCo=';

        $connection = Connection::fromDsn('https://dreamrobot-apps.servicebus.windows.net/emag', [
            'shared_access_key_name' => $awsKey,
            'shared_access_key'      => $awsSecret,
            'auto_setup'             => true
        ]);

//        $connection->send("santn test 123", [
//            "foor" => "bar",
//            "id"   => 3
//        ]);
//        $connection->send("santn test 4", [
//            "foor" => "bar",
//            "id"   => 4
//        ]);
//        $connection->send("santn test 2", [
//            "foor" => "bar",
//            "id"   => 2
//        ]);
        $m = $connection->get();
        var_dump($m->getMessageId(), $m->getBody());
        $connection->delete($m);
    }

}
