<?php
/**
 * The first point to handler the connection to azure service bus
 *
 * @project      symfony-azure-service-bus-messenger
 * @date         12/27/2021
 * @copyright    2021 San Tran
 * @author       San.Tran <solesantn@gmail.com>
 */

namespace Symfony\Component\Messenger\Bridge\AzureServiceBus\Transport;

use GuzzleHttp\Psr7\Stream;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\ServiceBus\Internal\IServiceBus;
use WindowsAzure\ServiceBus\Models\BrokeredMessage;
use WindowsAzure\ServiceBus\Models\BrokerProperties;
use WindowsAzure\ServiceBus\Models\QueueInfo;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;

class Connection
{
    private const DEFAULT_OPTIONS        = [
        'buffer_size'            => 9,
        'wait_time'              => 20,
        'poll_timeout'           => 0.1,
        'visibility_timeout'     => null,
        'auto_setup'             => true,
        'name_space'             => '',
        'queue_url'              => 'https://servicebus.windows.net',
        'shared_access_key_name' => null,
        'shared_access_key'      => null,
        'entity_path'            => 'messages',
        'sslmode'                => null,
        'debug'                  => null,
    ];
    private const MESSAGE_ATTRIBUTE_NAME = 'X-Symfony-Messenger';
    /**
     * @var array
     */
    private $configuration;
    /**
     * @var IServiceBus
     */
    private $serviceBus;
    /**
     * @var BrokeredMessage
     */
    private $currentResponse;

    /**
     * @var array
     */
    private $buffer;

    public function __construct(array $configuration, IServiceBus $serviceBus = null)
    {
        $this->configuration = array_replace_recursive(self::DEFAULT_OPTIONS, $configuration);
        $this->serviceBus    = $serviceBus ?? ServicesBuilder::getInstance()->createServiceBusService("");
    }

    public function __sleep(): array
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
    }

    public function __destruct()
    {
        $this->reset();
    }

    /**
     * @throws \Exception
     */
    public static function fromDsn(string $dsn, array $options = [], LoggerInterface $logger = null): self
    {
        if (false === $parsedUrl = parse_url($dsn)) {
            throw new InvalidArgumentException(sprintf('The given Azure Service Bus DSN "%s" is invalid.', $dsn));
        }
        $query = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }
        // check for extra keys in options
        $optionsExtraKeys = array_diff(array_keys($options), array_keys(self::DEFAULT_OPTIONS));
        if (0 < \count($optionsExtraKeys)) {
            throw new InvalidArgumentException(sprintf('Unknown option found: [%s]. Allowed options are [%s].',
                implode(', ', $optionsExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
        }
        // check for extra keys in options
        $queryExtraKeys = array_diff(array_keys($query), array_keys(self::DEFAULT_OPTIONS));
        if (0 < \count($queryExtraKeys)) {
            throw new InvalidArgumentException(sprintf('Unknown option found in DSN: [%s]. Allowed options are [%s].',
                implode(', ', $queryExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
        }

        $options       = $options + self::DEFAULT_OPTIONS;
        $configuration = [
            'buffer_size'        => (int)$options['buffer_size'],
            'wait_time'          => (int)$options['wait_time'],
            'poll_timeout'       => $options['poll_timeout'],
            'visibility_timeout' => $options['visibility_timeout'],
            'auto_setup'         => filter_var($options['auto_setup'], \FILTER_VALIDATE_BOOLEAN),
            'entity_path'        => (string)$options['entity_path'],
        ];

        $parsedPath = explode('/', ltrim($parsedUrl['path'] ?? '/', '/'));
        if (\count($parsedPath) > 0 && !empty($queueName = end($parsedPath))) {
            $configuration['entity_path'] = $queueName;
        }

        $sharedAccessKeyName = $options['shared_access_key_name'] ?? self::DEFAULT_OPTIONS['shared_access_key_name'];
        $sharedAccessKey     = $options['shared_access_key'] ?? self::DEFAULT_OPTIONS['shared_access_key'];
        $clientConfiguration = [
            'shared_access_key_name' => $sharedAccessKeyName,
            'shared_access_key'      => $sharedAccessKey
        ];

        if ('default' !== ($parsedUrl['host'] ?? 'default')) {
            $clientConfiguration['queue_url'] =
                sprintf('%s://%s%s', ($query['sslmode'] ?? null) === 'disable' ? 'http' : 'https', $parsedUrl['host'],
                    ($parsedUrl['port'] ?? null) ? ':'.$parsedUrl['port'] : '');
        } elseif (self::DEFAULT_OPTIONS['queue_url'] !== $options['queue_url'] ?? self::DEFAULT_OPTIONS['queue_url']) {
            $clientConfiguration['queue_url'] = $options['queue_url'];
        }
        $clientConfigurationString = "
            Endpoint={$clientConfiguration['queue_url']};
            SharedAccessKeyName={$clientConfiguration['shared_access_key_name']};
            SharedAccessKey={$clientConfiguration['shared_access_key']}
        ";
        $serviceBusRestProxy       =
            ServicesBuilder::getInstance()->createServiceBusService($clientConfigurationString);
        return new self($configuration, $serviceBusRestProxy);
    }

    /**
     * @throws \Exception
     */
    public function get(): ?array
    {
        if ($this->configuration['auto_setup']) {
            $this->setup();
        }

        foreach ($this->getNextMessages() as $message) {
            return $message;
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function setup(): void
    {
        // Set to be false to disable setup more than once
        $this->configuration['auto_setup'] = false;
        $queueInfo                         = $this->serviceBus->getQueue($this->configuration['entity_path']);
        if ($queueInfo->getTitle() === $this->configuration['entity_path']) {
            return;
        }

        $queueInfo = new QueueInfo($this->configuration['entity_path']);

        // Create queue.
        $this->serviceBus->createQueue($queueInfo);
    }

    /**
     * @return \Generator<int, array>
     * @throws \Exception
     */
    private function getNextMessages(): \Generator
    {
        yield from $this->getPendingMessages();
        yield from $this->getNewMessages();
    }


    /**
     * @return \Generator<int, array>
     */
    private function getPendingMessages(): \Generator
    {
        while (!empty($this->buffer)) {
            yield array_shift($this->buffer);
        }
    }

    /**
     * @return \Generator<int, array>
     * @throws \Exception
     */
    private function getNewMessages(): \Generator
    {
        if (null === $this->currentResponse) {
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();
            $this->currentResponse = $this->serviceBus->receiveQueueMessage($this->configuration['entity_path'], $options);
        }

        if (!$this->fetchMessage()) {
            return;
        }

        yield from $this->getPendingMessages();
    }

    private function fetchMessage(): bool
    {
        if($this->currentResponse){
            $headers = [];
            if($this->currentResponse->getContentType()){
                $headers['type'] = $this->currentResponse->getContentType();
            }

            $this->buffer[] = [
                'id' => $this->currentResponse->getMessageId(),
                'body' => $this->currentResponse->getBody(),
                'headers' => $headers,
            ];
        }

        $this->currentResponse = null;

        return true;
    }

    /**
     * @throws \Exception
     */
    public function send(string $body, array $headers, int $delay = 0, string $messageGroupId = null, string $messageDeduplicationId = null, string $xrayTraceId = null): void
    {
        if ($this->configuration['auto_setup']) {
            $this->setup();
        }

        // Create message.
        $message = new BrokeredMessage();
        $message->setBody($body);
        if(isset($headers['id'])){
            $message->setMessageId($headers['id']);
        }

        // Send message.
        $this->serviceBus->sendQueueMessage($this->configuration['entity_path'], $message);
    }

    /**
     * @throws \Exception
     */
    public function delete(BrokeredMessage $message): void
    {
        $this->serviceBus->deleteMessage($message);
    }

    public function reset()
    {

    }
}
