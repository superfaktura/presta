<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

/** @var string $action */
/** @var array<string, string|int|bool> $values */
/** @var array<array{id_order_state: int|string, name: string}> $states */
/** @var array<string, string> $labels */

$escape = static fn (string $text): string => htmlspecialchars(
    $text,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8',
);

$value = static fn (string $field): string => $escape((string) ($values[$field] ?? ''));
$label = static fn (string $key): string => $escape($labels[$key]);

$checked = static fn (string $field): string => 1 == ($values[$field] ?? 0)
    ? ' checked="checked"'
    : '';

$selected = static fn (string $field, int|string $option): string => $option == ($values[$field] ?? null)
    ? ' selected="selected"'
    : '';

$languages = [
    'slo', 'cze', 'eng', 'deu', 'rus', 'ukr', 'hun',
    'pol', 'rom', 'hrv', 'slv', 'spa', 'ita', 'nld',
];
?>

<?php if (1 == ($values['use_sandbox'] ?? 0)): ?>
    <div style="width: 517px; margin: 10px auto; color: red; font-size: 20px;">
        <?= $label('sandbox_notice') ?>
    </div>
<?php endif; ?>

<div style="width: 517px; margin: 10px auto;">
    <form action="<?= $escape($action) ?>" method="post">
        <strong>E-mail: <sup>*</sup></strong><br />
        <input type="text" name="email" size="50" value="<?= $value('email') ?>" /><br /><br />

        <strong>API key: <sup>*</sup></strong><br />
        <input type="text" name="apikey" size="50" value="<?= $value('apikey') ?>" /><br /><br />

        <strong>Company ID: </strong><br />
        <input type="text" name="company_id" size="50" value="<?= $value('company_id') ?>" /><br /><br />

        <input type="checkbox" name="use_sandbox" value="1" style="margin-right: 5px;"<?= $checked('use_sandbox') ?> />
        <strong><?= $label('sandbox') ?></strong><br /><br />

        <strong><?= $label('invoice_trigger') ?>: <sup>*</sup></strong><br />
        <select name="id_order_state_invoice">
            <option value="0"<?= $selected('id_order_state_invoice', 0) ?>>vytvorení objednávky</option>

            <?php foreach ($states as $state): ?>
                <option value="<?= $escape((string) $state['id_order_state']) ?>"<?= $selected('id_order_state_invoice', $state['id_order_state']) ?>>zmene stavu objednávky na "<?= $escape(stripslashes($state['name'])) ?>"</option>
            <?php endforeach; ?>
        </select><br /><br />

        <input type="checkbox" name="set_invoice_paid" value="1" style="margin-right: 5px;"<?= $checked('set_invoice_paid') ?> />
        <strong><?= $label('paid') ?></strong><br />

        <input type="checkbox" name="add_rounding" value="1" style="margin-right: 5px;"<?= $checked('add_rounding') ?> />
        <strong><?= $label('rounding') ?></strong><br /><br />

        <strong><?= $label('refund_trigger') ?>: <sup>*</sup></strong><br />
        <select name="id_order_state_refund">
            <?php foreach ($states as $state): ?>
                <option value="<?= $escape((string) $state['id_order_state']) ?>"<?= $selected('id_order_state_refund', $state['id_order_state']) ?>><?= $escape(stripslashes($state['name'])) ?></option>
            <?php endforeach; ?>
        </select><br /><br />

        <strong><?= $label('variable_source') ?>: <sup>*</sup></strong><br />
        <select name="variable_source">
            <option value="order"<?= $selected('variable_source', 'order') ?>><?= $label('variable_order') ?></option>
            <option value="invoice"<?= $selected('variable_source', 'invoice') ?>><?= $label('variable_invoice') ?></option>
        </select><br /><br />

        <strong>ID číselníku pod ktorým chcete vystavovať doklady: </strong><br />
        <input type="text" name="sequence_id" size="50" value="<?= $value('sequence_id') ?>" /><br /><br />

        <strong>ID číselníku pod ktorým chcete vystavovať dobropisy: </strong><br />
        <input type="text" name="cancel_sequence_id" size="50" value="<?= $value('cancel_sequence_id') ?>" /><br /><br />

        <strong>ID loga ktoré chcete zobrazovať na doklade: </strong><br />
        <input type="text" name="logo_id" size="50" value="<?= $value('logo_id') ?>" /><br /><br />

        <strong>ID bank. účtu, ktoré chcete zobrazovať na doklade: </strong><br />
        <input type="text" name="bank_id" size="50" value="<?= $value('bank_id') ?>" /><br /><br />

        <input type="checkbox" name="send_invoice" value="1" style="margin-right: 5px;"<?= $checked('send_invoice') ?> />
        <strong><?= $label('send_invoice') ?></strong><br /><br />

		<input type="checkbox" name="send_peppol" value="1" style="margin-right: 5px;"<?= $checked('send_peppol') ?> />
		<strong><?= $label('send_peppol') ?></strong><br /><br />

        <input type="checkbox" name="update_addressbook" value="1" style="margin-right: 5px;"<?= $checked('update_addressbook') ?> />
        <strong><?= $label('update_addressbook') ?></strong><br /><br />

        <strong><?= $label('invoice_language') ?>:</strong><br />
        <select name="invoice_language">
            <?php foreach ($languages as $language): ?>
                <option value="<?= $escape($language) ?>"<?= $selected('invoice_language', $language) ?>><?= $label('language_' . $language) ?></option>
            <?php endforeach; ?>
        </select><br /><br />

        <strong><?= $label('invoice_type') ?>: <sup>*</sup></strong><br />
        <select name="invoice_type">
            <?php foreach (['regular', 'proforma', 'order'] as $type): ?>
                <option value="<?= $escape($type) ?>"<?= $selected('invoice_type', $type) ?>><?= $label('type_' . $type) ?></option>
            <?php endforeach; ?>
        </select><br /><br />

        <strong>Faktúru vystavil: </strong><br />
        <input type="text" name="issued_by" size="50" value="<?= $value('issued_by') ?>" /><br /><br />

        <strong>Telefón: </strong><br />
        <input type="text" name="issued_by_phone" size="50" value="<?= $value('issued_by_phone') ?>" /><br /><br />

        <strong>Web: </strong><br />
        <input type="text" name="issued_by_web" size="50" value="<?= $value('issued_by_web') ?>" /><br /><br />

        <strong>Email: </strong><br />
        <input type="text" name="issued_by_email" size="50" value="<?= $value('issued_by_email') ?>" /><br /><br />

        <input type="checkbox" name="by_square" value="1" style="margin-right: 5px;"<?= $checked('by_square') ?> />
        <strong><?= $label('by_square') ?>: </strong><br />

        <input type="checkbox" name="online_payment" value="1" style="margin-right: 5px;"<?= $checked('online_payment') ?> />
        <strong><?= $label('online_payment') ?>: </strong><br />

        <input type="checkbox" name="paypal" value="1" style="margin-right: 5px;"<?= $checked('paypal') ?> />
        <strong><?= $label('paypal') ?>: </strong><br /><br />

        <strong>Payment callback. URL, <br /> ktorá sa automaticky zavolá po pridaní úhrady k faktúre </strong><br />
        <input type="text" name="callback_payment" size="50" value="<?= $value('callback_payment') ?>" /><br /><br />

        <strong><?= $label('accounting') ?>: </strong><br /><br />

        <strong>Syntetický účet pre produkty: </strong><br />
        <input type="text" name="product_syntetic" size="50" value="<?= $value('product_syntetic') ?>" /><br /><br />

        <strong>Analytický účet pre produkty: </strong><br />
        <input type="text" name="product_analytic" size="50" value="<?= $value('product_analytic') ?>" /><br /><br />

        <strong>Syntetický účet pre dopravu: </strong><br />
        <input type="text" name="carrier_syntetic" size="50" value="<?= $value('carrier_syntetic') ?>" /><br /><br />

        <strong>Analytický účet pre dopravu: </strong><br />
        <input type="text" name="carrier_analytic" size="50" value="<?= $value('carrier_analytic') ?>" /><br /><br />

        <input type="submit" name="btnSubmit" value="<?= $label('save') ?>" class="button" />
    </form>
</div>