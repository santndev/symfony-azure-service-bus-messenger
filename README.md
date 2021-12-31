#Usage
##Simple config for the first version:

File: config/packages/messenger.yaml

```
framework:
  messenger:
    transports:
      azure-sb:
        dsn: '%env(AZURE_SERVICE_BUS_DSN)%'
        options:
          shared_access_key_name: 'it-not-real'
          shared_access_key: 'it-not-real'
          auto_setup: true
```
File: .env

```
AZURE_SERVICE_BUS_DSN=https://[NAMESPACE].servicebus.windows.net/[QUEUE_NAME]
```
File: config/services.yaml
```
services:
...
  SanTran\Component\Messenger\Bridge\AzureServiceBus\Transport\AzureServiceBusTransportFactory:
    tags: [messenger.transport_factory]
```
