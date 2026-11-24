<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use SensitiveParameter;
use SuperFaktura\PrestaShop\Application\Handler\RecordOrderInvoicePaymentHandler;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\CreatedInvoiceResponseMapper;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\InvoiceGateway;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\LegacyApiRequest;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\PeppolInvoiceGatewayFactory;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\SuperFakturaClientFactory;

final readonly class ModuleServiceFactory
{
    public function __construct(
        private string $moduleVersion,
        private string $prestaShopVersion,
        private string $applicationTitle,
    ) {
    }

    /** @param array<string, string|int|bool> $configuration */
    public function createOrderInvoiceService(
        #[SensitiveParameter]
        array $configuration,
    ): CreateOrderInvoiceService {
        $options = (new InvoiceCreationOptionsFactory())
            ->fromConfiguration($configuration);

        $invoices = new DbOrderInvoiceRepository(new PrestaShopDb());

        return new CreateOrderInvoiceService(
            new InvoicePayloadBuilder(
                new InvoiceClientMapper(new LegacyCountryIdMapper()),
                new PaymentMethodMapper(),
            ),
            $this->createInvoiceGateway($configuration),
            $invoices,
            new OrderInvoiceLogger(),
            $options,
            1 == $configuration['send_invoice'],
            new PrestaShopPeppolInvoiceGatewayResolver(
                new PeppolInvoiceGatewayFactory(),
                'PrestaShop ' . $this->moduleVersion,
            ),
            1 == $configuration['send_peppol'],
        );
    }

    /** @param array<string, string|int|bool> $configuration */
    public function createInvoiceGateway(
        #[SensitiveParameter]
        array $configuration,
    ): InvoiceGateway {
        $parser = new JsonParser();
        $useSandbox = 1 == $configuration['use_sandbox'];

        $client = (new SuperFakturaClientFactory())->create(
            email: (string) $configuration['email'],
            apiKey: (string) $configuration['apikey'],
            companyId: (string) $configuration['company_id'],
            moduleName: 'PrestaShop ' . $this->moduleVersion,
            useSandbox: $useSandbox,
        );

        return new InvoiceGateway(
            request: new LegacyApiRequest(
                client: $client,
                parser: $parser,
                applicationTitle: $this->applicationTitle,
                moduleIdentification: 'PrestaShop: ' . $this->prestaShopVersion
                                      . ' module: ' . $this->moduleVersion . ' ',
            ),
            parser: $parser,
            mapper: new CreatedInvoiceResponseMapper(),
            useSandbox: $useSandbox,
        );
    }

    /** @param array<string, string|int|bool> $configuration */
    public function createRecordOrderInvoicePaymentHandler(
        #[SensitiveParameter]
        array $configuration,
    ): RecordOrderInvoicePaymentHandler {
        return new RecordOrderInvoicePaymentHandler(
            new DbOrderPaymentDetailsRepository(new PrestaShopDb()),
            $this->createInvoiceGateway($configuration),
        );
    }
}