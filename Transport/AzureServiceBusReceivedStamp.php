<?php
/**
 * @project      symfony-azure-service-bus-messenger
 * @date         12/28/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AzureServiceBusReceivedStamp implements NonSendableStampInterface
{
    private $lockLocation;

    public function __construct(string $lockLocation)
    {
        $this->lockLocation = $lockLocation;
    }

    public function getLockLocation(): string
    {
        return $this->lockLocation;
    }
}
