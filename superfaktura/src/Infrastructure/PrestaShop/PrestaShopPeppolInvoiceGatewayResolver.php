<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use LogicException;
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

    public function forInvoice(OrderReference $order, CreatedInvoice $invoice): PeppolInvoiceGatewayInterface
    {
        $shopClass = 'Shop';
        $readConfiguration = ['Configuration', 'getMultiple'];

        if (!class_exists($shopClass) || !is_callable($readConfiguration)) {
            throw new LogicException('PrestaShop shop configuration is unavailable.');
        }

        $shop = new $shopClass($order->shopId);
        $groupId = (int) ($shop->id_shop_group ?? 0);

        if ($order->shopId !== (int) ($shop->id ?? 0) || 0 >= $groupId) {
            throw new RuntimeException('The shop for this order could not be loaded.');
        }

        $configuration = $readConfiguration(
            ['SUPERFAKTURA_EMAIL', 'SUPERFAKTURA_APIKEY', 'SUPERFAKTURA_USE_SANDBOX'],
            null,
            $groupId,
            $order->shopId,
        );

        $useSandbox = 1 == ($configuration['SUPERFAKTURA_USE_SANDBOX'] ?? false);

        if ($invoice->useSandbox !== $useSandbox) {
            throw new RuntimeException('The configured SuperFaktura environment differs from the invoice environment.');
        }

        return $this->factory->create(
            email: (string) ($configuration['SUPERFAKTURA_EMAIL'] ?? ''),
            apiKey: (string) ($configuration['SUPERFAKTURA_APIKEY'] ?? ''),
            companyId: (string) $invoice->companyId,
            moduleName: $this->moduleName,
            useSandbox: $useSandbox,
        );
    }
}