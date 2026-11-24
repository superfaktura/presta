<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use stdClass;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;

final class CreatedInvoiceResponseMapper
{
    public function map(stdClass $response, bool $useSandbox): ?CreatedInvoice
    {
        $error = $response->error ?? null;

        if (0 !== $error && '0' !== $error) {
            return null;
        }

        $data = $response->data ?? null;
        $invoice = $data instanceof stdClass ? ($data->Invoice ?? null) : null;

        if (!$invoice instanceof stdClass) {
            return null;
        }

        $invoiceId = $this->positiveId($invoice->id ?? null);
        $companyId = $this->positiveId($invoice->user_profile_id ?? null);

        if (null === $invoiceId || null === $companyId) {
            return null;
        }

        return new CreatedInvoice(new InvoiceId($invoiceId), $companyId, $useSandbox);
    }

    private function positiveId(mixed $value): ?int
    {
        if (is_string($value) && 1 === preg_match('/\A[1-9][0-9]*\z/', $value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
        }

        return is_int($value) && 0 < $value ? $value : null;
    }
}