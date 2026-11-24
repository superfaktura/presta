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
        $set_invoice_paid;

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
        $this->version       = '1.7.6';
        $this->author        = "www.superfaktura.sk";
        $this->need_instance = 1;


        $config = Configuration::getMultiple(array('SUPERFAKTURA_EMAIL', 'SUPERFAKTURA_APIKEY', 'SUPERFAKTURA_COMPANY_ID', 'SUPERFAKTURA_ORDER_STATE_REFUND', 'SUPERFAKTURA_ORDER_STATE_INVOICE', 'SUPERFAKTURA_SET_INVOICE_PAID', 'SUPERFAKTURA_VARIABLE_SOURCE', 'SUPERFAKTURA_SEQUENCE_ID', 'SUPERFAKTURA_SEND_INVOICE', 'SUPERFAKTURA_INVOICE_TYPE', 'SUPERFAKTURA_INVOICE_LANGUAGE', 'SUPERFAKTURA_ISSUED_BY', 'SUPERFAKTURA_ISSUED_BY_PHONE', 'SUPERFAKTURA_ISSUED_BY_WEB', 'SUPERFAKTURA_ISSUED_BY_EMAIL', 'SUPERFAKTURA_BY_SQUARE', 'SUPERFAKTURA_LOGO_ID', 'SUPERFAKTURA_BANK_ID', 'SUPERFAKTURA_CANCEL_SEQUENCE_ID', 'SUPERFAKTURA_PRODUCT_SYNTETIC', 'SUPERFAKTURA_PRODUCT_ANALYTIC', 'SUPERFAKTURA_CARRIER_SYNTETIC', 'SUPERFAKTURA_CARRIER_ANALYTIC', 'SUPERFAKTURA_CALLBACK_PAYMENT', 'SUPERFAKTURA_PAYPAL', 'SUPERFAKTURA_ONLINE_PAYMENT', 'SUPERFAKTURA_UPDATE_ADDRESSBOOK', 'SUPERFAKTURA_USE_SANDBOX', 'SUPERFAKTURA_ADD_ROUNDING'));

        $this->email                    = isset($config['SUPERFAKTURA_EMAIL']) ? $config['SUPERFAKTURA_EMAIL'] : "";
        $this->apikey                   = isset($config['SUPERFAKTURA_APIKEY']) ? $config['SUPERFAKTURA_APIKEY'] : "";
        $this->company_id               = isset($config['SUPERFAKTURA_COMPANY_ID']) ? $config['SUPERFAKTURA_COMPANY_ID'] : "";
        $this->id_order_state_invoice   = isset($config['SUPERFAKTURA_ORDER_STATE_INVOICE']) ? $config['SUPERFAKTURA_ORDER_STATE_INVOICE'] : -1;
        $this->id_order_state_refund    = isset($config['SUPERFAKTURA_ORDER_STATE_REFUND']) ? $config['SUPERFAKTURA_ORDER_STATE_REFUND'] : 0;
        $this->set_invoice_paid         = isset($config['SUPERFAKTURA_SET_INVOICE_PAID']) ? $config['SUPERFAKTURA_SET_INVOICE_PAID'] : 0;
        $this->variable_source          = isset($config['SUPERFAKTURA_VARIABLE_SOURCE']) ? $config['SUPERFAKTURA_VARIABLE_SOURCE'] : 'order';
        $this->sequence_id              = isset($config['SUPERFAKTURA_SEQUENCE_ID']) ? $config['SUPERFAKTURA_SEQUENCE_ID'] : "";
        $this->send_invoice             = isset($config['SUPERFAKTURA_SEND_INVOICE']) ? $config['SUPERFAKTURA_SEND_INVOICE'] : 0;
        $this->invoice_type             = isset($config['SUPERFAKTURA_INVOICE_TYPE']) ? $config['SUPERFAKTURA_INVOICE_TYPE'] : 'regular';
        $this->invoice_language         = isset($config['SUPERFAKTURA_INVOICE_LANGUAGE']) ? $config['SUPERFAKTURA_INVOICE_LANGUAGE'] : 'slo';
        $this->issued_by                = isset($config['SUPERFAKTURA_ISSUED_BY']) ? $config['SUPERFAKTURA_ISSUED_BY'] : "";
        $this->issued_by_phone          = isset($config['SUPERFAKTURA_ISSUED_BY_PHONE']) ? $config['SUPERFAKTURA_ISSUED_BY_PHONE'] : "";
        $this->issued_by_web            = isset($config['SUPERFAKTURA_ISSUED_BY_WEB']) ? $config['SUPERFAKTURA_ISSUED_BY_WEB'] : "";
        $this->issued_by_email          = isset($config['SUPERFAKTURA_ISSUED_BY_EMAIL']) ? $config['SUPERFAKTURA_ISSUED_BY_EMAIL'] : "";
        $this->by_square                = isset($config['SUPERFAKTURA_BY_SQUARE']) ? $config['SUPERFAKTURA_BY_SQUARE'] : "";
        $this->logo_id                  = isset($config['SUPERFAKTURA_LOGO_ID']) ? $config['SUPERFAKTURA_LOGO_ID'] : "";
        $this->bank_id                  = isset($config['SUPERFAKTURA_BANK_ID']) ? $config['SUPERFAKTURA_BANK_ID'] : "";
        $this->cancel_sequence_id       = isset($config['SUPERFAKTURA_CANCEL_SEQUENCE_ID']) ? $config['SUPERFAKTURA_CANCEL_SEQUENCE_ID'] : "";
        $this->product_syntetic         = isset($config['SUPERFAKTURA_PRODUCT_SYNTETIC']) ? $config['SUPERFAKTURA_PRODUCT_SYNTETIC'] : "";
        $this->product_analytic         = isset($config['SUPERFAKTURA_PRODUCT_ANALYTIC']) ? $config['SUPERFAKTURA_PRODUCT_ANALYTIC'] : "";
        $this->carrier_syntetic         = isset($config['SUPERFAKTURA_CARRIER_SYNTETIC']) ? $config['SUPERFAKTURA_CARRIER_SYNTETIC'] : "";
        $this->carrier_analytic         = isset($config['SUPERFAKTURA_CARRIER_ANALYTIC']) ? $config['SUPERFAKTURA_CARRIER_ANALYTIC'] : "";
        $this->online_payment           = isset($config['SUPERFAKTURA_ONLINE_PAYMENT']) ? $config['SUPERFAKTURA_ONLINE_PAYMENT'] : "";
        $this->paypal                   = isset($config['SUPERFAKTURA_PAYPAL']) ? $config['SUPERFAKTURA_PAYPAL'] : "";
        $this->callback_payment         = isset($config['SUPERFAKTURA_CALLBACK_PAYMENT']) ? $config['SUPERFAKTURA_CALLBACK_PAYMENT'] : "";
        $this->update_addressbook       = isset($config['SUPERFAKTURA_UPDATE_ADDRESSBOOK']) ? $config['SUPERFAKTURA_UPDATE_ADDRESSBOOK'] : "";
        $this->use_sandbox              = isset($config['SUPERFAKTURA_USE_SANDBOX']) ? $config['SUPERFAKTURA_USE_SANDBOX'] : "";
        $this->add_rounding             = isset($config['SUPERFAKTURA_ADD_ROUNDING']) ? $config['SUPERFAKTURA_ADD_ROUNDING'] : "";

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

    public function uninstall()
    {
        require_once __DIR__ . '/autoload.php';

        if (!parent::uninstall()) {
            return false;
        }

        try {
            return (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationStore())
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

        if (Tools::isSubmit('btnSubmit')) {
            try {
                $values = (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationForm())
                    ->read($currentValues);
            } catch (\InvalidArgumentException) {
                $this->_html .= '<div class="error">'
                                . $this->l('Nastavenia obsahujú neplatné hodnoty.')
                                . '</div>';

                return $this->_html;
            }

            $errors = [];

            if ('' === trim((string) $values['email'])) {
                $errors[] = $this->l('E-mail musí byť vyplnený.');
            }

            if ('' === trim((string) $values['apikey'])) {
                $errors[] = $this->l('API key musí byť vyplnené.');
            }

            if (0 == $values['id_order_state_refund']) {
                $this->warning .= $this->l('Stav objednávky pre vytvorenie dobropisu musí byť vyplnený');
            }

            if ([] === $errors) {
                try {
                    (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleConfigurationStore())
                        ->save($values);

                    $this->loadModuleConfiguration();

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

        $this->_displayForm();

        return $this->_html;
    }

    private function _displayForm()
    {
        global $cookie;

        $states = OrderState::getOrderStates((int)($cookie->id_lang));

        if (!empty($this->use_sandbox)) {
            $this->_html .= '<div style="width: 517px; margin: 10px auto; color: red; font-size; font-size: 20px;">' .  $this->l("Doklady budú vystavované na Sandboxe") . '</div>';            
        }

        $this->_html .= '<div style="width: 517px; margin: 10px auto;">
            <form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">

                <strong>E-mail: <sup>*</sup></strong><br />
                <input type="text" name="email" size="50" value="' . htmlentities(Tools::getValue('email', $this->email), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />

                <strong>API key: <sup>*</sup></strong><br />
                <input type="text" name="apikey" size="50" value="' . htmlentities(Tools::getValue('apikey', $this->apikey), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />

                <strong>Company ID: </strong><br />
                <input type="text" name="company_id" size="50" value="' . htmlentities(Tools::getValue('company_id', $this->company_id), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />

                <input type="checkbox" name="use_sandbox" value="1" style="margin-right: 5px;"' . (1 == Tools::getValue('use_sandbox', $this->use_sandbox) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Vystavovať doklady na Sandboxe") . '</strong><br /><br />

                <strong>' . $this->l("Faktúru vytvárať pri") . ': <sup>*</sup></strong><br />
                <select name="id_order_state_invoice">
                    <option value="0"'.((0 == Tools::getValue('id_order_state_invoice', $this->id_order_state_invoice)) ? ' selected="selected"' : '').'>vytvorení objednávky</option>
        ';

                foreach ($states AS $state)
                    $this->_html .= '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == Tools::getValue('id_order_state_invoice', $this->id_order_state_invoice)) ? ' selected="selected"' : '').'>zmene stavu objednávky na "'.stripslashes($state['name']).'"</option>';

        $this->_html .= '
                </select><br />
                <br />

                <input type="checkbox" name="set_invoice_paid" value="1" style="margin-right: 5px;"' . (1 == Tools::getValue('set_invoice_paid', $this->set_invoice_paid) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Pri vytvorení faktúry ju nastaviť ako uhradenú") . '</strong>
                <br />
                <input type="checkbox" name="add_rounding" value="1" style="margin-right: 5px;"' . (1 == Tools::getValue('add_rounding', $this->add_rounding) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Pridať centové / halierové vyrovnanie pre platbu v hotovosti") . '</strong><br /><br />

                <strong>' . $this->l("Stav objednávky pre vytvorenie dobropisu") . ': <sup>*</sup></strong><br />
                <select name="id_order_state_refund">
        ';

                foreach ($states AS $state)
                    $this->_html .= '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == Tools::getValue('id_order_state_refund', $this->id_order_state_refund)) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';

        $this->_html .= '
                </select><br />
                <br />

                <strong>' . $this->l("Variabilný symbol na faktúre") . ': <sup>*</sup></strong><br />
                <select name="variable_source">
        ';

        $this->_html .= '<option value="order"'.('order' == Tools::getValue('variable_source', $this->variable_source) ? ' selected="selected"' : '').'>'.$this->l("Číslo objednávky").'</option>';
        $this->_html .= '<option value="invoice"'.('invoice' == Tools::getValue('variable_source', $this->variable_source) ? ' selected="selected"' : '').'>'.$this->l("Číslo faktúry").'</option>';

        $this->_html .= '
                </select><br />
                <br />
        <strong>ID číselníku pod ktorým chcete vystavovať doklady: </strong><br />
                <input type="text" name="sequence_id" size="50" value="' . htmlentities(Tools::getValue('sequence_id', $this->sequence_id), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
         <strong>ID číselníku pod ktorým chcete vystavovať dobropisy: </strong><br />
                <input type="text" name="cancel_sequence_id" size="50" value="' . htmlentities(Tools::getValue('cancel_sequence_id', $this->cancel_sequence_id), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>ID loga ktoré chcete zobrazovať na doklade: </strong><br />
                <input type="text" name="logo_id" size="50" value="' . htmlentities(Tools::getValue('logo_id', $this->logo_id), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>ID bank. účtu, ktoré chcete zobrazovať na doklade: </strong><br />
                <input type="text" name="bank_id" size="50" value="' . htmlentities(Tools::getValue('bank_id', $this->bank_id), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        ';

        $this->_html .= '       
                <input type="checkbox" name="send_invoice" value="1" style="margin-right: 5px;"' . (1 == Tools::getValue('send_invoice', $this->send_invoice) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Po vytvorení dokladu odoslať faktúru klientovi") . '</strong><br /><br />
        ';
        
        $this->_html .= '       
                <input type="checkbox" name="update_addressbook" value="1" style="margin-right: 5px;"' . (1 == Tools::getValue('update_addressbook', $this->update_addressbook) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Po vytvorení dokladu zaktualizuje údaje klienta") . '</strong><br /><br />
        ';

        $this->_html .= '
                </select>
                <strong>' . $this->l("Vyberte jazyk v ktorom chcete doklady vystavovať") . ':</strong><br />
                <select name="invoice_language">
        ';

        $this->_html .= '<option value="slo"'.('slo' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Slovenčina").'</option>';
        $this->_html .= '<option value="cze"'.('cze' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Čeština").'</option>';
        $this->_html .= '<option value="eng"'.('eng' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Angličtina").'</option>';
        $this->_html .= '<option value="deu"'.('deu' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Nemčina").'</option>';
        $this->_html .= '<option value="rus"'.('rus' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Ruština").'</option>';
        $this->_html .= '<option value="ukr"'.('ukr' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Ukrajinčina").'</option>';
        $this->_html .= '<option value="hun"'.('hun' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Maďarčina").'</option>';
        $this->_html .= '<option value="pol"'.('pol' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Poľština").'</option>';
        $this->_html .= '<option value="rom"'.('rom' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Rumunčina").'</option>';
        $this->_html .= '<option value="hrv"'.('hrv' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Chorvátčina").'</option>';
        $this->_html .= '<option value="slv"'.('slv' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Slovinčina").'</option>';
        $this->_html .= '<option value="spa"'.('spa' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Španielčina").'</option>';
        $this->_html .= '<option value="ita"'.('ita' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Taliančina").'</option>';
        $this->_html .= '<option value="nld"'.('nld' == Tools::getValue('invoice_language', $this->invoice_language) ? ' selected="selected"' : '').'>'.$this->l("Holandčina").'</option>';
        $this->_html .= '
                </select><br /><br />';
        
        $this->_html .= '
                </select>
                <strong>' . $this->l("Typ dokladu, ktorý chcete vystavovať") . ': <sup>*</sup></strong><br />
                <select name="invoice_type">
        ';

        $this->_html .= '<option value="regular"'.('regular' == Tools::getValue('invoice_type', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Ostrá faktúra").'</option>';
        $this->_html .= '<option value="proforma"'.('proforma' == Tools::getValue('invoice_type', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Zálohová faktúra").'</option>';
        $this->_html .= '<option value="order"'.('order' == Tools::getValue('invoice_type', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Objednávka").'</option>';

        $this->_html .= '
                </select><br /><br />';

        $this->_html .= '
        <strong>Faktúru vystavil: </strong><br />
                <input type="text" name="issued_by" size="50" value="' . htmlentities(Tools::getValue('issued_by', $this->issued_by), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>Telefón: </strong><br />
                <input type="text" name="issued_by_phone" size="50" value="' . htmlentities(Tools::getValue('issued_by_phone', $this->issued_by_phone), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>Web: </strong><br />
                <input type="text" name="issued_by_web" size="50" value="' . htmlentities(Tools::getValue('issued_by_web', $this->issued_by_web), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>Email: </strong><br />
                <input type="text" name="issued_by_email" size="50" value="' . htmlentities(Tools::getValue('issued_by_email', $this->issued_by_email), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        ';

        $this->_html .= '       
                <input type="checkbox" name="by_square" value="1" style="margin-right: 5px;" ' . (1 == Tools::getValue('by_square', $this->by_square) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Zobraziť Pay by square na faktúre") .     ': </strong><br />
                <input type="checkbox" name="online_payment" value="1" style="margin-right: 5px;" ' . (1 == Tools::getValue('online_payment', $this->online_payment) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Zobraziť Online platby na faktúre") . ': </strong><br />
                <input type="checkbox" name="paypal" value="1" style="margin-right: 5px;" ' . (1 == Tools::getValue('paypal', $this->paypal) ? ' checked="checked"' : '') . ' />
                <strong>' . $this->l("Zobraziť Paypal na faktúre") . ': </strong> <br /><br />
        ';

        $this->_html .= '
            <strong>Payment callback. URL, <br /> ktorá sa automaticky zavolá po pridaní úhrady k faktúre </strong><br />
            <input type="text" name="callback_payment" size="50" value="' . htmlentities(Tools::getValue('callback_payment', $this->callback_payment), ENT_COMPAT, 'UTF-8') . '" /><br />';

        $this->_html .= '<br /><strong>' . $this->l("Účtovníctvo") . ': </strong><br />';
        $this->_html .= '
                <br />
        <strong> Syntetický účet pre produkty: </strong><br />
                <input type="text" name="product_syntetic" size="50" value="' . htmlentities(Tools::getValue('product_syntetic', $this->product_syntetic), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
         <strong>Analytický účet pre produkty: </strong><br />
                <input type="text" name="product_analytic" size="50" value="' . htmlentities(Tools::getValue('product_analytic', $this->product_analytic), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>Syntetický účet pre dopravu: </strong><br />
                <input type="text" name="carrier_syntetic" size="50" value="' . htmlentities(Tools::getValue('carrier_syntetic', $this->carrier_syntetic), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        <strong>Analytický účet pre dopravu: </strong><br />
                <input type="text" name="carrier_analytic" size="50" value="' . htmlentities(Tools::getValue('carrier_analytic', $this->carrier_analytic), ENT_COMPAT, 'UTF-8') . '" /><br />
                <br />
        ';

        $this->_html .= '        <input type="submit" name="btnSubmit" value="'.$this->l('Uložiť').'" class="button" />
            </form>
            </div>   
        ';
    }


    private function createPeppolInvoiceGateway(): \SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface
    {
        require_once __DIR__ . '/autoload.php';

        return (new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\PeppolInvoiceGatewayFactory())->create(
            (string) $this->email,
            (string) $this->apikey,
            (string) $this->company_id,
            'PrestaShop ' . $this->version,
            1 == $this->use_sandbox,
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

    private function loadOrderConfiguration(Order $order): bool
    {
        $shopId = (int) $order->id_shop;
        $orderId = (int) $order->id;

        if (0 >= $shopId || 0 >= $orderId) {
            return false;
        }

        try {
            $this->loadModuleConfiguration($order);

            return true;
        } catch (\Throwable $exception) {
            $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference($shopId, $orderId);

            (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger())->report(
                $reference,
                'load_configuration_failed',
                exception: $exception,
            );

            return false;
        }
    }

    private function createInvoiceGateway(): \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\InvoiceGateway
    {
        require_once __DIR__ . '/autoload.php';

        $parser = new \SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser();
        $useSandbox = 1 == $this->use_sandbox;

        $client = (new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\SuperFakturaClientFactory())->create(
            email: (string) $this->email,
            apiKey: (string) $this->apikey,
            companyId: (string) $this->company_id,
            moduleName: 'PrestaShop ' . $this->version,
            useSandbox: $useSandbox,
        );

        $request = new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\LegacyApiRequest(
            client: $client,
            parser: $parser,
            applicationTitle: (string) ($_SERVER['SERVER_NAME'] ?? ''),
            moduleIdentification: 'PrestaShop: ' . _PS_VERSION_ . ' module: ' . $this->version . ' ',
        );

        return new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\InvoiceGateway(
            request: $request,
            parser: $parser,
            mapper: new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\CreatedInvoiceResponseMapper(),
            useSandbox: $useSandbox,
        );
    }

    private function _createInvoice($order, $cart)
    {
        require_once __DIR__ . '/autoload.php';

        if (!$this->loadOrderConfiguration($order)) {
            return;
        }

        (new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ShopContextRunner())->runForShop(
            (int) $order->id_shop,
            function () use ($order, $cart): void {
                $this->_createInvoiceInShop($order, $cart);
            },
        );
    }



    private function _createInvoiceInShop($order, $cart)
    {
        require_once __DIR__ . '/autoload.php';

        $orderReference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
            (int) $order->id_shop,
            (int) $order->id,
        );

        $logger = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger();

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_SHOP, $order->id_shop);
        }

        $options = new \SuperFaktura\PrestaShop\Application\Dto\InvoiceCreationOptions(
            invoiceType: (string) $this->invoice_type,
            useOrderIdAsVariable: 'order' === $this->variable_source,
            alreadyPaid: 1 == $this->set_invoice_paid,
            updateAddressBook: (bool) $this->update_addressbook,
            addCashOnDeliveryRounding: (bool) $this->add_rounding,
            language: (string) $this->invoice_language,
            sequenceId: $this->sequence_id ?: '',
            issuedBy: (string) ($this->issued_by ?: ''),
            issuedByPhone: (string) ($this->issued_by_phone ?: ''),
            issuedByWeb: (string) ($this->issued_by_web ?: ''),
            issuedByEmail: (string) ($this->issued_by_email ?: ''),
            logoId: $this->logo_id ?: '',
            bankId: $this->bank_id ?: null,
            productSyntheticAccount: $this->product_syntetic ?: '',
            productAnalyticAccount: $this->product_analytic ?: '',
            carrierSyntheticAccount: $this->carrier_syntetic ?: '',
            carrierAnalyticAccount: $this->carrier_analytic ?: '',
            bySquare: $this->by_square,
            onlinePayment: $this->online_payment,
            paypal: $this->paypal,
            callbackPayment: $this->callback_payment,
        );

        $builder = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\InvoicePayloadBuilder(
            new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\InvoiceClientMapper(
                new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\LegacyCountryIdMapper(),
            ),
            new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PaymentMethodMapper(),
        );

        try {
            $data = $builder->build($order, $cart, $options);
            $payloadJson = json_encode($data, JSON_THROW_ON_ERROR);

            $invoices = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\DbOrderInvoiceRepository(
                new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PrestaShopDb(),
            );

            $gateway = $this->createInvoiceGateway();
        } catch (\Throwable $exception) {
            $logger->report(
                $orderReference,
                'prepare_invoice_failed',
                exception: $exception,
            );

            return;
        }

        try {
            $created = $gateway->create($payloadJson);
        } catch (\Throwable $exception) {
            $logger->report(
                $orderReference,
                'invoice_creation_unconfirmed',
                exception: $exception,
            );

            return;
        }

        if (null === $created) {
            $logger->report($orderReference, 'invoice_creation_unconfirmed');

            return;
        }

        try {
            $invoices->save($orderReference, $created->invoice);
        } catch (\Throwable $exception) {
            $logger->report(
                $orderReference,
                'save_invoice_reference_failed',
                $created->invoice,
                $exception,
            );
        }

        if (1 == $this->send_invoice) {
            try {
                $sent = $gateway->sendEmail($created);
            } catch (\Throwable $exception) {
                $logger->report(
                    $orderReference,
                    'invoice_email_unconfirmed',
                    $created->invoice,
                    $exception,
                );

                return;
            }

            if (!$sent) {
                $logger->report(
                    $orderReference,
                    'invoice_email_unconfirmed',
                    $created->invoice,
                );
            }
        }
    }


    public function hookNewOrder($params)
    {
        $order = $params['order'] ?? null;

        if (!$order instanceof Order || !$this->loadOrderConfiguration($order)) {
            return;
        }

        if (0 != $this->id_order_state_invoice) {
            return;
        }

        $this->_createInvoice($order, $params['cart']);
    }

    public function hookActionPaymentConfirmation($params)
    {
        require_once __DIR__ . '/autoload.php';

        $order = $this->orderFromHook($params);

        if (null === $order || !$this->loadOrderConfiguration($order)) {
            return;
        }

        if (1 == $this->set_invoice_paid) {
            return;
        }

        $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
            (int) $order->id_shop,
            (int) $order->id,
        );
        $logger = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger();

        try {
            $recordPayment = $this->createRecordOrderInvoicePaymentHandler();
            $confirmed = $recordPayment($reference, new \DateTimeImmutable('today'));
        } catch (\Throwable $exception) {
            $logger->report(
                $reference,
                'invoice_payment_unconfirmed',
                exception: $exception,
            );

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

        if (null === $order || !$this->loadOrderConfiguration($order)) {
            return;
        }

        $isRefund = (int) $this->id_order_state_refund === $statusId;
        $isInvoice = (int) $this->id_order_state_invoice === $statusId;

        if (!$isRefund && !$isInvoice) {
            return;
        }

        if ($isRefund) {
            require_once __DIR__ . '/autoload.php';

            $reference = new \SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference(
                (int) $order->id_shop,
                (int) $order->id,
            );
            $logger = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\OrderInvoiceLogger();

            try {
                $sequenceId = $this->cancel_sequence_id
                    ? (string) $this->cancel_sequence_id
                    : null;

                $confirmed = $this->createInvoiceGateway()->createCreditNote(
                    $reference,
                    $sequenceId,
                );
            } catch (\Throwable $exception) {
                $logger->report(
                    $reference,
                    'credit_note_creation_unconfirmed',
                    exception: $exception,
                );

                return;
            }

            if (!$confirmed) {
                $logger->report($reference, 'credit_note_creation_unconfirmed');
            }

            return;
        }

        $cart = new Cart($order->id_cart);
        $this->_createInvoice($order, $cart);
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


    private function createRecordOrderInvoicePaymentHandler(): \SuperFaktura\PrestaShop\Application\Handler\RecordOrderInvoicePaymentHandler
    {
        require_once __DIR__ . '/autoload.php';

        return new \SuperFaktura\PrestaShop\Application\Handler\RecordOrderInvoicePaymentHandler(
            new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\DbOrderPaymentDetailsRepository(
                new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\PrestaShopDb(),
            ),
            $this->createInvoiceGateway(),
        );
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
