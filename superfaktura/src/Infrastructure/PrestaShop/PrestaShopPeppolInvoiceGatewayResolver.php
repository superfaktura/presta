<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use RuntimeException;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayResolverInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\PeppolInvoiceGatewayFactory;

final readonly class PrestaShopPeppolInvoiceGatewayResolver implements PeppolInvoiceGatewayResolverInterface
{
    public function __construct(
        private PeppolInvoiceGatewayFactory $factory,
        private string $moduleName,
    ) {
    }

    public function forInvoice(
        OrderReference $order,
        CreatedInvoice $invoice,
    ): PeppolInvoiceGatewayInterface {
        $configuration = (new ModuleConfigurationStore())->loadForShop($order->shopId);
        $useSandbox = 1 == $configuration['use_sandbox'];

        if ($invoice->useSandbox !== $useSandbox) {
            throw new RuntimeException(
                'The configured SuperFaktura environment differs from the invoice environment.',
            );
        }

        return $this->factory->create(
            email: (string) $configuration['email'],
            apiKey: (string) $configuration['apikey'],
            companyId: (string) $invoice->companyId,
            moduleName: $this->moduleName,
            useSandbox: $useSandbox,
        );
    }
}