<?php

if ( !defined( '_PS_VERSION_' ) )
    exit;

/**
*   Version 1.7.6
*   Last modified 2022-08-04
*/

class SuperFaktura extends Module
{
    private $_html = '';

    public 
        $email,
        $apikey,
        $company_id,
        $id_order_state_invoice,
        $id_order_state_refund,
        $sequence_id,
        $send_invoice, 
        $variable_source,
        $invoice_language,
        $invoice_type,
        $issued_by,
        $issued_by_phone,
        $issued_by_web,
        $issued_by_email,
        $by_square,
        $logo_id,
        $bank_id,
        $cancel_sequence_id,
        $product_syntetic,
        $product_analytic,
        $carrier_syntetic,
        $carrier_analytic,
        $callback_payment,
        $paypal,
        $online_payment,
        $update_addressbook,
        $use_sandbox,
        $add_rounding,
        $set_invoice_paid,
        $send_peppol;

    const API_AUTH_KEYWORD          = 'SFAPI';
    const SF_URL_CREATE_INVOICE     = '/invoices/create';
    const SF_URL_CREATE_CLIENT      = '/clients/create';
    const SF_URL_PAY_INVOICE        = '/invoice_payments/add/ajax:1/api:1/import_type:prestashop/import_id:';
    const SF_URL_CREATE_CANCEL      = '/invoices/cancelFromRegular/0/import_type:prestashop/import_id:';
    const SF_URL_GET_PDF_INVOICE    = '/invoices/pdf/0/import_type:prestashop/import_id:';
    const SF_URL_SEND_INVOICE       = '/invoices/send';
    const SF_URL                    = 'https://moja.superfaktura.sk';
    const SANDBOX_URL               = 'https://sandbox.superfaktura.sk';

    public function __construct()
    {
        $this->name          = "superfaktura";
        $this->tab           = "billing_invoicing";
        $this->version       = '1.7.8';
        $this->author        = "www.superfaktura.sk";
        $this->need_instance = 1;


        parent::__construct();
        $this->loadModuleConfiguration();


        $this->displayName = $this->l( 'SuperFaktura' );
        $this->description = $this->l( 'Prepojenie PrestaShop 1.5.x s www.superfaktura.sk.' );

        $this->warning = "";

        if (empty($this->email))
            $this->warning .= $this->l('E-mail musí byť vyplnený.') . ' ';

        if (empty($this->apikey))
            $this->warning .= $this->l('API key musí byť vyplnené.') . ' ';

        if (-1 == $this->id_order_state_invoice)
            $this->warning .= $this->l('Udalosť kedy vytvárať faktúru musí byť vyplnená.') . ' ';

        if (0 == $this->id_order_state_refund)
            $this->warning .= $this->l('Stav objednávky pre vytvorenie dobropisu musí byť vyplnený.') . ' ';

        if ( ! function_exists("curl_init"))
            $this->warning .= $this->l('Váš web hosting musí podporovať cURL PHP funkcie.') . ' ';

        if ( ! function_exists("json_encode"))
            $this->warning .= $this->l('Váš web hosting musí podporovať JSON_ENCODE/DECODE funkcie.') . ' ';
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
               && $this->installOrderInvoiceStorage()
               && $this->installPeppolStatusStorage()
               && $this->installPeppolAdminUi()
               && $this->registerHook('newOrder')
               && $this->registerHook('actionPaymentConfirmation')
               && $this->registerHook('actionOrderStatusUpdate')
               && $this->registerHook('PDFInvoice');
    }

    public function installOrderInvoiceStorage(): bool
    {
        require_once __DIR__ . '/autoload.php';

        try {
            $db = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PrestaShopDb();

            return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceTable($db))
                ->create();
        } catch (\Throwable) {
            return false;
        }
    }

    public function installPeppolStatusStorage(): bool
    {
        require_once __DIR__ . '/autoload.php';

        try {
            return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PeppolStatusTable(
                new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PrestaShopDb(),
            ))->create();
        } catch (\Throwable) {
            return false;
        }
    }

    public function installPeppolAdminUi(): bool
    {
        require_once __DIR__ . '/autoload.php';

        try {
            return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PeppolAdminTab())
                       ->install((string) $this->name)
                   && $this->registerHook('displayAdminOrder');
        } catch (\Throwable) {
            return false;
        }
    }

    public function uninstall()
    {
        require_once __DIR__ . '/autoload.php';

        if (!parent::uninstall()) {
            return false;
        }

        try {
            return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PeppolAdminTab())
                       ->uninstall((string) $this->name)
                   && (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationStore())
                       ->deleteAll();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getContent()
    {
        require_once __DIR__ . '/autoload.php';

        $this->_html = '<h2>' . $this->displayName . '</h2>';
        $currentValues = $this->loadModuleConfiguration();
        $formValues = $currentValues;

        if (Tools::isSubmit('btnSubmit')) {
            try {
                $formValues = (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationForm())
                    ->read($currentValues);
            } catch (\InvalidArgumentException) {
                $this->_html .= '<div class="error">'
                                . $this->l('Nastavenia obsahujú neplatné hodnoty.')
                                . '</div>';

                $this->_displayForm($currentValues);

                return $this->_html;
            }

            $errors = [];

            if ('' === trim((string) $formValues['email'])) {
                $errors[] = $this->l('E-mail musí byť vyplnený.');
            }

            if ('' === trim((string) $formValues['apikey'])) {
                $errors[] = $this->l('API key musí byť vyplnené.');
            }

            if (0 == $formValues['id_order_state_refund']) {
                $this->warning .= $this->l('Stav objednávky pre vytvorenie dobropisu musí byť vyplnený');
            }

            if ([] === $errors) {
                try {
                    (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationStore())
                        ->save($formValues);

                    $formValues = $this->loadModuleConfiguration();

                    $this->_html .= '<div class="conf">'
                                    . $this->l('Nastavenia uložené')
                                    . '</div>';
                } catch (\Throwable) {
                    $errors[] = $this->l('Nastavenia sa nepodarilo uložiť.');
                }
            }

            if ([] !== $errors) {
                $this->_html .= '<div class="error">'
                                . implode('<br />', $errors)
                                . '</div>';
            }
        }

        $this->_displayForm($formValues);

        return $this->_html;
    }

    /** @param array{id_order?: int|string} $params */
    public function hookDisplayAdminOrder($params): string
    {
        require_once __DIR__ . '/autoload.php';

        $value = $params['id_order'] ?? null;

        if (!is_string($value) && !is_int($value)) {
            return '';
        }

        $orderId = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (false === $orderId) {
            return '';
        }

        $labels = $this->peppolAdminLabels();

        try {
            $factory = $this->createServiceFactory();
            $access = $factory->createAdminPeppolAccess((int) $this->id);
            $order = $access->loadOrder($orderId);

            if (null === $order) {
                return '';
            }

            $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
                (int) $order->id_shop,
                $orderId,
            );
            $states = $factory->createPeppolStatusRepository()->findByOrder($reference);

            if ([] === $states) {
                return '';
            }

            $context = \Context::getContext();
            $key = 'sf_peppol_result_' . $orderId;
            $raw = $context->cookie->{$key} ?? null;
            unset($context->cookie->{$key});

            $notice = is_string($raw)
                ? \SuperFaktura\PrestaShop\Application\Enum\ManualPeppolStatusRefreshOutcome::tryFrom($raw)
                : null;

            return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderPeppolPanelRenderer())->render(
                $reference,
                $states,
                $context->link->getAdminLink(
                    \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\AdminPeppolAccess::CONTROLLER,
                    true,
                ),
                \Tools::getAdminTokenLite(
                    \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\AdminPeppolAccess::CONTROLLER,
                ),
                $access->canRefresh(),
                $labels,
                $notice,
                new \DateTimeZone(date_default_timezone_get()),
            );
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'SuperFaktura: render_peppol_panel_failed; order_id=%d; exception=%s.',
                $orderId,
                $exception::class,
            ));

            return '<div class="alert alert-warning">'
                   . htmlspecialchars(
                       $labels['panel_failed'],
                       ENT_QUOTES | ENT_SUBSTITUTE,
                       'UTF-8',
                   )
                   . '</div>';
        }
    }

    /** @return array<string, string> */
    public function peppolAdminLabels(): array
    {
        return [
            'title' => $this->l('Peppol'),
            'invoice' => $this->l('Faktúra'),
            'company' => $this->l('Firma v SuperFaktúre'),
            'production' => $this->l('Produkcia'),
            'sandbox' => $this->l('Sandbox'),
            'send_status' => $this->l('Stav odoslania'),
            'provider_status' => $this->l('Stav poskytovateľa'),
            'checked_at' => $this->l('Posledná kontrola'),
            'next_check' => $this->l('Najbližšia automatická kontrola'),
            'monitoring' => $this->l('Automatické kontroly'),
            'refresh' => $this->l('Obnoviť stav'),
            'unknown' => $this->l('Stav zatiaľ nebol overený'),
            'none' => '—',
            'status_queued' => $this->l('Čaká na spracovanie'),
            'status_processing' => $this->l('Spracúva sa'),
            'status_pending' => $this->l('Čaká na potvrdenie'),
            'status_accepted' => $this->l('Prijaté na odoslanie'),
            'status_failed' => $this->l('Odoslanie zlyhalo'),
            'provider_accepted' => $this->l('Prijaté'),
            'provider_rejected' => $this->l('Odmietnuté'),
            'provider_failed_delivery' => $this->l('Doručenie zlyhalo'),
            'poll_retry' => $this->l('Aktívne'),
            'poll_finished' => $this->l('Ukončené'),
            'poll_exhausted' => $this->l('Limit kontrol vyčerpaný'),
            'poll_none' => $this->l('Nenaplánované'),
            'last_check_failed' => $this->l('Posledná kontrola sa nepodarila. Zobrazený je posledný známy stav.'),
            'status_unavailable' => $this->l('Aktuálny stav momentálne nie je dostupný.'),
            'failure_code' => $this->l('Kód chyby'),
            'notice_updated' => $this->l('Stav bol obnovený.'),
            'notice_not_found' => $this->l('Pre túto faktúru zatiaľ nie je dostupný Peppol stav.'),
            'notice_busy' => $this->l('Prebieha spracovanie. Skúste obnoviť stav neskôr.'),
            'notice_failed' => $this->l('Stav sa nepodarilo obnoviť. Skúste to znova neskôr.'),
            'panel_failed' => $this->l('Peppol stav momentálne nie je dostupný.'),
            'request_invalid' => $this->l('Požiadavka nie je platná.'),
            'access_denied' => $this->l('Na túto operáciu nemáte oprávnenie.'),
            'post_required' => $this->l('Použite tlačidlo na obnovenie stavu pri objednávke.'),
            'not_linked' => $this->l('Faktúra nie je prepojená s touto objednávkou.'),
        ];
    }

    /** @param array<string, string|int|bool> $values */
    private function _displayForm(array $values): void
    {
        require_once __DIR__ . '/autoload.php';

        global $cookie;

        $labels = [
            'sandbox_notice' => $this->l('Doklady budú vystavované na Sandboxe'),
            'sandbox' => $this->l('Vystavovať doklady na Sandboxe'),
            'invoice_trigger' => $this->l('Faktúru vytvárať pri'),
            'paid' => $this->l('Pri vytvorení faktúry ju nastaviť ako uhradenú'),
            'rounding' => $this->l('Pridať centové / halierové vyrovnanie pre platbu v hotovosti'),
            'refund_trigger' => $this->l('Stav objednávky pre vytvorenie dobropisu'),
            'variable_source' => $this->l('Variabilný symbol na faktúre'),
            'variable_order' => $this->l('Číslo objednávky'),
            'variable_invoice' => $this->l('Číslo faktúry'),
            'send_invoice' => $this->l('Po vytvorení dokladu odoslať faktúru klientovi'),
            'send_peppol' => $this->l('Po vytvorení odoslať ostrú faktúru cez Peppol'),
            'update_addressbook' => $this->l('Po vytvorení dokladu zaktualizuje údaje klienta'),
            'invoice_language' => $this->l('Vyberte jazyk v ktorom chcete doklady vystavovať'),
            'language_slo' => $this->l('Slovenčina'),
            'language_cze' => $this->l('Čeština'),
            'language_eng' => $this->l('Angličtina'),
            'language_deu' => $this->l('Nemčina'),
            'language_rus' => $this->l('Ruština'),
            'language_ukr' => $this->l('Ukrajinčina'),
            'language_hun' => $this->l('Maďarčina'),
            'language_pol' => $this->l('Poľština'),
            'language_rom' => $this->l('Rumunčina'),
            'language_hrv' => $this->l('Chorvátčina'),
            'language_slv' => $this->l('Slovinčina'),
            'language_spa' => $this->l('Španielčina'),
            'language_ita' => $this->l('Taliančina'),
            'language_nld' => $this->l('Holandčina'),
            'invoice_type' => $this->l('Typ dokladu, ktorý chcete vystavovať'),
            'type_regular' => $this->l('Ostrá faktúra'),
            'type_proforma' => $this->l('Zálohová faktúra'),
            'type_order' => $this->l('Objednávka'),
            'by_square' => $this->l('Zobraziť Pay by square na faktúre'),
            'online_payment' => $this->l('Zobraziť Online platby na faktúre'),
            'paypal' => $this->l('Zobraziť Paypal na faktúre'),
            'accounting' => $this->l('Účtovníctvo'),
            'save' => $this->l('Uložiť'),
        ];

        $this->_html .= (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationFormRenderer())
            ->render(
                (string) ($_SERVER['REQUEST_URI'] ?? ''),
                $values,
                OrderState::getOrderStates((int) $cookie->id_lang),
                $labels,
            );
    }


    /** @return array<string, string|int|bool> */
    private function loadModuleConfiguration(?Order $order = null): array
    {
        require_once __DIR__ . '/autoload.php';

        $store = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationStore();
        $values = null === $order
            ? $store->load()
            : $store->loadForShop((int) $order->id_shop);

        foreach ($values as $field => $value) {
            $this->$field = $value;
        }

        return $values;
    }

    /** @return array<string, string|int|bool>|null */
    private function loadOrderConfiguration(Order $order): ?array
    {
        $shopId = (int) $order->id_shop;
        $orderId = (int) $order->id;

        if (0 >= $shopId || 0 >= $orderId) {
            return null;
        }

        try {
            return $this->loadModuleConfiguration($order);
        } catch (\Throwable $exception) {
            $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference($shopId, $orderId);
            (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger())->report(
                $reference,
                'load_configuration_failed',
                exception: $exception,
            );

            return null;
        }
    }

    private function createServiceFactory(): \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleServiceFactory
    {
        require_once __DIR__ . '/autoload.php';

        return new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleServiceFactory(
            moduleVersion: (string) $this->version,
            prestaShopVersion: (string) _PS_VERSION_,
            applicationTitle: (string) ($_SERVER['SERVER_NAME'] ?? ''),
        );
    }


    /** @param array<string, string|int|bool> $configuration */
    private function _createInvoice(Order $order, Cart $cart, #[\SensitiveParameter] array $configuration): void
    {
        $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
            (int) $order->id_shop,
            (int) $order->id,
        );

        (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ShopContextRunner())->runForShop(
            $reference->shopId,
            function () use ($order, $cart, $configuration, $reference): void {
                try {
                    $createInvoice = $this->createServiceFactory()->createOrderInvoiceService($configuration);
                } catch (\Throwable $exception) {
                    (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger())->report(
                        $reference,
                        'prepare_invoice_failed',
                        exception: $exception,
                    );

                    return;
                }

                $createInvoice->create($order, $cart);
            },
        );
    }


    public function hookNewOrder($params)
    {
        $order = $params['order'] ?? null;
        $cart = $params['cart'] ?? null;

        if (!$order instanceof Order || !$cart instanceof Cart) {
            return;
        }

        $configuration = $this->loadOrderConfiguration($order);

        if (null === $configuration || 0 != $configuration['id_order_state_invoice']) {
            return;
        }

        $this->_createInvoice($order, $cart, $configuration);
    }

    public function hookActionPaymentConfirmation($params)
    {
        require_once __DIR__ . '/autoload.php';

        $order = $this->orderFromHook($params);

        if (null === $order) {
            return;
        }

        $configuration = $this->loadOrderConfiguration($order);

        if (null === $configuration || 1 == $configuration['set_invoice_paid']) {
            return;
        }

        $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
            (int) $order->id_shop,
            (int) $order->id,
        );
        $logger = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger();

        try {
            $recordPayment = $this->createServiceFactory()->createRecordOrderInvoicePaymentHandler($configuration);
            $confirmed = $recordPayment($reference, new \DateTimeImmutable('today'));
        } catch (\Throwable $exception) {
            $logger->report($reference, 'invoice_payment_unconfirmed', exception: $exception);

            return;
        }

        if (false === $confirmed) {
            $logger->report($reference, 'invoice_payment_unconfirmed');
        }
    }


    public function hookActionOrderStatusUpdate($params)
    {
        $status = $params['newOrderStatus'] ?? null;

        if (!is_object($status)) {
            return;
        }

        $value = $status->id ?? null;

        if (!is_int($value) && !is_string($value)) {
            return;
        }

        $statusId = filter_var($value, FILTER_VALIDATE_INT);

        if (false === $statusId || 0 >= $statusId) {
            return;
        }

        $order = $this->orderFromHook($params);

        if (null === $order) {
            return;
        }

        $configuration = $this->loadOrderConfiguration($order);

        if (null === $configuration) {
            return;
        }

        $isRefund = (int) $configuration['id_order_state_refund'] === $statusId;
        $isInvoice = (int) $configuration['id_order_state_invoice'] === $statusId;

        if (!$isRefund && !$isInvoice) {
            return;
        }

        if ($isRefund) {
            $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
                (int) $order->id_shop,
                (int) $order->id,
            );
            $logger = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger();

            try {
                $sequenceId = $configuration['cancel_sequence_id']
                    ? (string) $configuration['cancel_sequence_id']
                    : null;
                $confirmed = $this->createServiceFactory()
                    ->createInvoiceGateway($configuration)
                    ->createCreditNote($reference, $sequenceId);
            } catch (\Throwable $exception) {
                $logger->report($reference, 'credit_note_creation_unconfirmed', exception: $exception);

                return;
            }

            if (!$confirmed) {
                $logger->report($reference, 'credit_note_creation_unconfirmed');
            }

            return;
        }

        $cart = new Cart($order->id_cart);
        $this->_createInvoice($order, $cart, $configuration);
    }

    /**
     * Get url
     * 
     * @param string $url
     * 
     * @return string
     * */
    public function getSfUrl(string $url): string
    {
        return ($this->use_sandbox == 1 ? self::SANDBOX_URL : self::SF_URL) . $url;
    }

    /**
     * Is COD payment
     * 
     * @param string $payment
     * 
     * @return bool
     * */
    public function isCodPayment(string $payment = ''): bool
    {
        require_once __DIR__ . '/autoload.php';

        return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PaymentMethodMapper())
            ->isCashOnDelivery($payment);
    }


    /** @param array{id_order?: int|string} $params */
    private function orderFromHook(array $params): ?Order
    {
        $value = $params['id_order'] ?? null;

        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        $orderId = filter_var($value, FILTER_VALIDATE_INT);

        if (false === $orderId || 0 >= $orderId) {
            return null;
        }

        try {
            $order = new Order($orderId);

            if ($orderId !== (int) $order->id || 0 >= (int) $order->id_shop) {
                return null;
            }

            return $order;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'SuperFaktura: load_order_failed; order_id=%d; exception=%s.',
                $orderId,
                $exception::class,
            ));

            return null;
        }
    }
}
