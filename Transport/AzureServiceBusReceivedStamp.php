<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AzureServiceBusReceivedStamp implements NonSendableStampInterface
{
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
