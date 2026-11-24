<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/../../autoload.php';

use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Enum\ManualPeppolStatusRefreshOutcome;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Infrastructure\PrestaShop\AdminPeppolAccess;
use SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleServiceFactory;
use SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger;

class AdminSuperFakturaPeppolController extends ModuleAdminController
{
    public function postProcess()
    {
        if (!$this->module instanceof SuperFaktura || 0 >= (int) $this->module->id) {
            $this->reject(403, 'Access denied.');
        }

        $labels = $this->module->peppolAdminLabels();

        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
            header('Allow: POST');
            $this->reject(405, $labels['post_required']);
        }

        $given = $_POST['sf_peppol_token'] ?? null;
        $expected = Tools::getAdminTokenLite(AdminPeppolAccess::CONTROLLER);

        if (
            !is_string($given)
            || !is_string($expected)
            || '' === $expected
            || !hash_equals($expected, $given)
        ) {
            $this->reject(403, $labels['request_invalid']);
        }

        $orderId = $this->positivePostInt('id_order', $labels['request_invalid']);
        $invoiceId = $this->positivePostInt('sf_invoice_id', $labels['request_invalid']);
        $companyId = $this->positivePostInt('sf_company_id', $labels['request_invalid']);
        $sandbox = $_POST['use_sandbox'] ?? null;

        if (!in_array($sandbox, ['0', '1'], true)) {
            $this->reject(400, $labels['request_invalid']);
        }

        $factory = new ModuleServiceFactory(
            (string) $this->module->version,
            (string) _PS_VERSION_,
            '',
        );

        $order = $factory
            ->createAdminPeppolAccess((int) $this->module->id)
            ->loadOrder($orderId, true);

        if (null === $order) {
            $this->reject(403, $labels['access_denied']);
        }

        $reference = new OrderReference((int) $order->id_shop, $orderId);
        $invoice = new CreatedInvoice(
            new InvoiceId($invoiceId),
            $companyId,
            '1' === $sandbox,
        );

        try {
            $outcome = $factory
                ->createManualPeppolStatusRefreshService()
                ->refresh($reference, $invoice);
        } catch (OutOfBoundsException) {
            $this->reject(404, $labels['not_linked']);
        } catch (Throwable $exception) {
            (new OrderInvoiceLogger())->report(
                $reference,
                'manual_peppol_status_failed',
                $invoice,
                $exception,
            );

            $outcome = ManualPeppolStatusRefreshOutcome::FAILED;
        }

        $this->context->cookie->{'sf_peppol_result_' . $orderId} = $outcome->value;
        $this->context->cookie->write();

        $url = $this->context->link->getAdminLink('AdminOrders', true, [], [
            'id_order' => $orderId,
            'vieworder' => 1,
        ]);

        Tools::redirectAdmin($url . '#sf-peppol-status');
    }

    private function positivePostInt(string $field, string $message): int
    {
        $value = $_POST[$field] ?? null;

        if (!is_string($value) && !is_int($value)) {
            $this->reject(400, $message);
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (false === $id) {
            $this->reject(400, $message);
        }

        return $id;
    }

    protected function reject(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');

        exit($message);
    }
}