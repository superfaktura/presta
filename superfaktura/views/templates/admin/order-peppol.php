<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/** @var \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference $order */
/** @var list<\SuperFaktura\PrestaShop\Application\Dto\OrderInvoicePeppolState> $states */
/** @var string $action */
/** @var string $token */
/** @var bool $canRefresh */
/** @var array<string, string> $labels */
/** @var \SuperFaktura\PrestaShop\Application\Enum\ManualPeppolStatusRefreshOutcome|null $notice */
/** @var \DateTimeZone $timezone */

$escape = static fn (string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8',
);
$label = static fn (string $key): string => $escape($labels[$key]);
$date = static fn (?\DateTimeImmutable $value): string => null === $value
    ? $label('none')
    : $escape($value->setTimezone($timezone)->format('d.m.Y H:i:s'));
?>

<div id="sf-peppol-status" class="panel card">
    <h3 class="panel-heading card-header"><?= $label('title') ?></h3>

    <div class="panel-body card-body">
        <?php if (null !== $notice): ?>
            <p class="alert <?= \SuperFaktura\PrestaShop\Application\Enum\ManualPeppolStatusRefreshOutcome::UPDATED === $notice ? 'alert-success' : 'alert-warning' ?>">
                <?= $label('notice_' . $notice->value) ?>
            </p>
        <?php endif; ?>

        <?php foreach ($states as $state): ?>
            <section style="margin-bottom: 20px;">
                <h4>
                    <?= $label('invoice') ?> #<?= $escape((string) $state->invoice->invoiceId->value) ?>
                    — <?= $label('company') ?> #<?= $escape((string) $state->invoice->companyId) ?>
                    (<?= $label($state->invoice->useSandbox ? 'sandbox' : 'production') ?>)
                </h4>

                <dl>
                    <dt><?= $label('send_status') ?></dt>
                    <dd><?= null === $state->status ? $label('unknown') : $label('status_' . $state->status->status->value) ?></dd>

                    <dt><?= $label('provider_status') ?></dt>
                    <dd><?php
                        $provider = $state->status?->providerStatus;

                        echo match ($provider) {
                            null, '' => $label('none'),
                            'accepted', 'rejected', 'failed_delivery' => $label('provider_' . $provider),
                            default => $escape($provider),
                        };
                        ?></dd>

                    <dt><?= $label('checked_at') ?></dt>
                    <dd><?= $date($state->checkedAt) ?></dd>

                    <dt><?= $label('monitoring') ?></dt>
                    <dd><?= null === $state->pollOutcome ? $label('poll_none') : $label('poll_' . strtolower($state->pollOutcome->name)) ?></dd>

                    <dt><?= $label('next_check') ?></dt>
                    <dd><?= $date($state->nextPollAt) ?></dd>
                </dl>

                <?php if (null !== $state->lastError): ?>
                    <p class="alert alert-warning">
                        <?= $label('status_unavailable' === $state->lastError ? 'status_unavailable' : 'last_check_failed') ?>
                    </p>
                <?php endif; ?>

                <?php if (null !== $state->status?->failureCode): ?>
                    <p>
                        <?= $label('failure_code') ?>:
                        <code><?= $escape($state->status->failureCode) ?></code>
                    </p>
                <?php endif; ?>

                <?php if ($canRefresh): ?>
                    <form method="post" action="<?= $escape($action) ?>">
                        <input type="hidden" name="sf_peppol_token" value="<?= $escape($token) ?>" />
                        <input type="hidden" name="id_order" value="<?= $escape((string) $order->orderId) ?>" />
                        <input type="hidden" name="sf_invoice_id" value="<?= $escape((string) $state->invoice->invoiceId->value) ?>" />
                        <input type="hidden" name="sf_company_id" value="<?= $escape((string) $state->invoice->companyId) ?>" />
                        <input type="hidden" name="use_sandbox" value="<?= (int) $state->invoice->useSandbox ?>" />

                        <button type="submit" class="btn btn-default btn-outline-primary">
                            <?= $label('refresh') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>