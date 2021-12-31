<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @category  Microsoft
 *
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 *
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */

namespace SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common;

use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\Internal\Http\IHttpClient;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\Internal\Serialization\ISerializer;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\Internal\Http\HttpClient;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\Internal\Filters\HeadersFilter;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\Internal\Serialization\XmlSerializer;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\Common\Internal\ServiceBusSettings;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\Internal\IServiceBus;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\Internal\IWrap;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\ServiceBusRestProxy;
use SanTran\Component\Messenger\Bridge\AzureServiceBus\WindowsAzure\ServiceBus\Internal\WrapRestProxy;

/**
 * Builds azure service objects.
 *
 * @category  Microsoft
 *
 * @author    Azure PHP SDK <azurephpsdk@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 *
 * @version   Release: 0.5.0_2016-11
 *
 * @link      https://github.com/windowsazure/azure-sdk-for-php
 */
class ServicesBuilder
{
    /**
     * @var ServicesBuilder
     */
    private static $_instance = null;

    /**
     * Gets the HTTP client used in the REST services construction.
     *
     * @return IHttpClient
     */
    protected function httpClient()
    {
        return new HttpClient();
    }

    /**
     * Gets the serializer used in the REST services construction.
     *
     * @return ISerializer
     */
    protected function serializer()
    {
        return new XmlSerializer();
    }

    /**
     * Builds a WRAP client.
     *
     * @param string $wrapEndpointUri The WRAP endpoint uri
     *
     * @return IWrap
     */
    protected function createWrapService(string $wrapEndpointUri)
    {
        $httpClient = $this->httpClient();
        return new WrapRestProxy($httpClient, $wrapEndpointUri);
    }

    /**
     * Builds a Service Bus object.
     *
     * @param string $connectionString The configuration connection string
     *
     * @return IServiceBus
     */
    public function createServiceBusService(string $connectionString): IServiceBus
    {
        $settings = ServiceBusSettings::createFromConnectionString(
            $connectionString
        );

        $httpClient = $this->httpClient();
        $serializer = $this->serializer();
        $serviceBusWrapper = new ServiceBusRestProxy(
            $httpClient,
            $settings->getServiceBusEndpointUri(),
            $serializer
        );

        // Adding headers filter
        $headers = [];

        $headersFilter = new HeadersFilter($headers);
        $serviceBusWrapper = $serviceBusWrapper->withFilter($headersFilter);

        $filter = $settings->getFilter();

        return $serviceBusWrapper->withFilter($filter);
    }

    /**
     * Gets the static instance of this class.
     *
     * @return ServicesBuilder
     */
    public static function getInstance(): ?ServicesBuilder
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}
