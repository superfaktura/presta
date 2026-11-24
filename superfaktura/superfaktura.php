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
        $add_rounding;

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
        $this->version       = '1.7.5';
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
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        return (
            parent::install()
            && $this->registerHook('newOrder')
            && $this->registerHook('actionPaymentConfirmation')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerHook('PDFInvoice')
        );
    }

    public function uninstall()
    {
        return (
            parent::uninstall()
            && Configuration::deleteByName('SUPERFAKTURA_EMAIL')
            && Configuration::deleteByName('SUPERFAKTURA_APIKEY')
            && Configuration::deleteByName('SUPERFAKTURA_COMPANY_ID')
            && Configuration::deleteByName('SUPERFAKTURA_SEQUENCE_ID')
            && Configuration::deleteByName('SUPERFAKTURA_ORDER_STATE_REFUND')
            && Configuration::deleteByName('SUPERFAKTURA_SET_INVOICE_PAID')
            && Configuration::deleteByName('SUPERFAKTURA_VARIABLE_SOURCE')
            && Configuration::deleteByName('SUPERFAKTURA_INVOICE_TYPE')
            && Configuration::deleteByName('SUPERFAKTURA_INVOICE_LANGUAGE')
            && Configuration::deleteByName('SUPERFAKTURA_ISSUED_BY')
            && Configuration::deleteByName('SUPERFAKTURA_ISSUED_BY_PHONE')
            && Configuration::deleteByName('SUPERFAKTURA_ISSUED_BY_WEB')
            && Configuration::deleteByName('SUPERFAKTURA_ISSUED_BY_EMAIL')
            && Configuration::deleteByName('SUPERFAKTURA_BY_SQUARE')
            && Configuration::deleteByName('SUPERFAKTURA_LOGO_ID')
            && Configuration::deleteByName('SUPERFAKTURA_BANK_ID')
            && Configuration::deleteByName('SUPERFAKTURA_CANCEL_SEQUENCE_ID')
            && Configuration::deleteByName('SUPERFAKTURA_PRODUCT_SYNTETIC')
            && Configuration::deleteByName('SUPERFAKTURA_PRODUCT_ANALYTIC')
            && Configuration::deleteByName('SUPERFAKTURA_CARRIER_SYNTETIC')
            && Configuration::deleteByName('SUPERFAKTURA_CARRIER_ANALYTIC')
            && Configuration::deleteByName('SUPERFAKTURA_CALLBACK_PAYMENT')
            && Configuration::deleteByName('SUPERFAKTURA_PAYPAL')
            && Configuration::deleteByName('SUPERFAKTURA_ONLINE_PAYMENT')
            && Configuration::deleteByName('SUPERFAKTURA_UPDATE_ADDRESSBOOK')
            && Configuration::deleteByName('SUPERFAKTURA_USE_SANDBOX')
            && Configuration::deleteByName('SUPERFAKTURA_ADD_ROUNDING')
        );
    }

    public function getContent()
    {
        $this->_html = '<h2>'.$this->displayName.'</h2>';

        if (Tools::isSubmit('btnSubmit'))
        {
            $errors = array();

            if (!Tools::getValue('email'))
                $errors[] = $this->l('E-mail musí byť vyplnený.');

            if (!Tools::getValue('apikey'))
                $errors[] = $this->l('API key musí byť vyplnené.');

            if (0 == Tools::getValue('id_order_state_refund'))
                $this->warning .= $this->l('Stav objednávky pre vytvorenie dobropisu musí byť vyplnený');


            if (empty($errors))
            {
                Configuration::updateValue('SUPERFAKTURA_EMAIL', Tools::getValue('email'));
                Configuration::updateValue('SUPERFAKTURA_APIKEY', Tools::getValue('apikey'));
                Configuration::updateValue('SUPERFAKTURA_COMPANY_ID', Tools::getValue('company_id'));
                Configuration::updateValue('SUPERFAKTURA_ORDER_STATE_INVOICE', Tools::getValue('id_order_state_invoice'));
                Configuration::updateValue('SUPERFAKTURA_ORDER_STATE_REFUND', Tools::getValue('id_order_state_refund'));
                Configuration::updateValue('SUPERFAKTURA_SET_INVOICE_PAID', (int)Tools::getValue('set_invoice_paid'));
                Configuration::updateValue('SUPERFAKTURA_VARIABLE_SOURCE', Tools::getValue('variable_source'));
                Configuration::updateValue('SUPERFAKTURA_SEQUENCE_ID', Tools::getValue('sequence_id'));
                Configuration::updateValue('SUPERFAKTURA_SEND_INVOICE', Tools::getValue('send_invoice'));
                Configuration::updateValue('SUPERFAKTURA_INVOICE_TYPE', Tools::getValue('invoice_type'));
                Configuration::updateValue('SUPERFAKTURA_INVOICE_LANGUAGE', Tools::getValue('invoice_language'));
                Configuration::updateValue('SUPERFAKTURA_ISSUED_BY', Tools::getValue('issued_by'));
                Configuration::updateValue('SUPERFAKTURA_ISSUED_BY_PHONE', Tools::getValue('issued_by_phone'));
                Configuration::updateValue('SUPERFAKTURA_ISSUED_BY_WEB', Tools::getValue('issued_by_web'));
                Configuration::updateValue('SUPERFAKTURA_ISSUED_BY_EMAIL', Tools::getValue('issued_by_email')); 
                Configuration::updateValue('SUPERFAKTURA_BY_SQUARE', Tools::getValue('by_square'));
                Configuration::updateValue('SUPERFAKTURA_LOGO_ID', Tools::getValue('logo_id'));
                Configuration::updateValue('SUPERFAKTURA_BANK_ID', Tools::getValue('bank_id'));
                Configuration::updateValue('SUPERFAKTURA_CANCEL_SEQUENCE_ID', Tools::getValue('cancel_sequence_id'));
                Configuration::updateValue('SUPERFAKTURA_PRODUCT_SYNTETIC', Tools::getValue('product_syntetic'));
                Configuration::updateValue('SUPERFAKTURA_PRODUCT_ANALYTIC', Tools::getValue('product_analytic'));
                Configuration::updateValue('SUPERFAKTURA_CARRIER_SYNTETIC', Tools::getValue('carrier_syntetic'));
                Configuration::updateValue('SUPERFAKTURA_CARRIER_ANALYTIC', Tools::getValue('carrier_analytic'));
                Configuration::updateValue('SUPERFAKTURA_ONLINE_PAYMENT', Tools::getValue('online_payment'));
                Configuration::updateValue('SUPERFAKTURA_PAYPAL', Tools::getValue('paypal'));
                Configuration::updateValue('SUPERFAKTURA_CALLBACK_PAYMENT', Tools::getValue('callback_payment'));
                Configuration::updateValue('SUPERFAKTURA_UPDATE_ADDRESSBOOK', Tools::getValue('update_addressbook'));
                Configuration::updateValue('SUPERFAKTURA_USE_SANDBOX', Tools::getValue('use_sandbox'));
                Configuration::updateValue('SUPERFAKTURA_ADD_ROUNDING', Tools::getValue('add_rounding'));

                $this->_html .= '<div class="conf"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('Nastavenia uložené').'</div>';
            }
            else
            {
                $this->_html .= '<div class="error"><img src="../img/admin/error2.png" alt="'.$this->l('chyba').'" /> '. implode('<br />', $errors) .'</div>';
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
                <input type="checkbox" name="add_rounding" value="1" style="margin-right: 5px;"' . (1 == Tools::getValue('add_rounding', $this->set_invoice_paid) ? ' checked="checked"' : '') . ' />
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

        $this->_html .= '<option value="slo"'.('slo' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Slovenčina").'</option>';
        $this->_html .= '<option value="cze"'.('cze' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Čeština").'</option>';
        $this->_html .= '<option value="eng"'.('eng' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Angličtina").'</option>';
        $this->_html .= '<option value="deu"'.('deu' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Nemčina").'</option>';
        $this->_html .= '<option value="rus"'.('rus' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Ruština").'</option>';
        $this->_html .= '<option value="ukr"'.('ukr' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Ukrajinčina").'</option>';
        $this->_html .= '<option value="hun"'.('hun' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Maďarčina").'</option>';
        $this->_html .= '<option value="pol"'.('pol' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Poľština").'</option>';
        $this->_html .= '<option value="rom"'.('rom' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Rumunčina").'</option>';
        $this->_html .= '<option value="hrv"'.('hrv' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Chorvátčina").'</option>';
        $this->_html .= '<option value="slv"'.('slv' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Slovinčina").'</option>';
        $this->_html .= '<option value="spa"'.('spa' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Španielčina").'</option>';
        $this->_html .= '<option value="ita"'.('ita' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Taliančina").'</option>';
        $this->_html .= '<option value="nld"'.('nld' == Tools::getValue('invoice_language', $this->invoice_type) ? ' selected="selected"' : '').'>'.$this->l("Holandčina").'</option>';
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


    private function _request($url, $data = "")
    {
        require_once __DIR__ . '/autoload.php';

        $baseUrl = $this->getSfUrl('');

        if (!is_string($url) || !str_starts_with($url, $baseUrl . '/')) {
            return false;
        }

        $payloadJson = null;

        if ('' !== $data && [] !== $data) {
            if (!is_array($data) || !isset($data['data']) || !is_string($data['data'])) {
                return false;
            }

            $payloadJson = $data['data'];
        }

        try {
            $client = (new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\SuperFakturaClientFactory())->create(
                email: (string) $this->email,
                apiKey: (string) $this->apikey,
                companyId: (string) $this->company_id,
                moduleName: 'PrestaShop ' . $this->version,
                useSandbox: 1 == $this->use_sandbox,
            );
        } catch (\InvalidArgumentException) {
            return false;
        }

        $request = new \SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\LegacyApiRequest(
            client: $client,
            parser: new \SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser(),
            applicationTitle: (string) ($_SERVER['SERVER_NAME'] ?? ''),
            moduleIdentification: 'PrestaShop: ' . _PS_VERSION_ . ' module: ' . $this->version . ' ',
        );

        return $request->post(substr($url, strlen($baseUrl)), $payloadJson);
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



    private function _createInvoice($order, $cart)
    {
        require_once __DIR__ . '/autoload.php';

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
        } catch (\JsonException) {
            return;
        }

        $responseBody = $this->_request(
            $this->getSfUrl(self::SF_URL_CREATE_INVOICE),
            ['data' => $payloadJson],
        );

        if (false === $responseBody) {
            return;
        }

        $response = json_decode($responseBody);

        if (!$response) {
            return;
        }

        if (isset($response->error) && 0 == $response->error && 1 == $this->send_invoice) {
            $requestData = [
                'Email' => [
                    'invoice_id' => $response->data->Invoice->id,
                    'to' => $response->data->Client->email,
                ],
            ];

            $this->_request(
                $this->getSfUrl(self::SF_URL_SEND_INVOICE),
                ['data' => json_encode($requestData)],
            );
        }
    }


    public function hookNewOrder($params)
    {

        if (0 != $this->id_order_state_invoice)
            return; // faktura sa vytvara pri inom stave

        $this->_createInvoice($params['order'], $params['cart']);
    }

    public function hookActionPaymentConfirmation($params)
    {
        if (1 == $this->set_invoice_paid)
            return; // faktura sa nastavila ako uhradena pri vytvarani


        $order = Db::getInstance()->getRow("SELECT o.total_paid, c.iso_code
                                            FROM " . _DB_PREFIX_ . "orders AS o
                                            LEFT JOIN " . _DB_PREFIX_ . "currency AS c ON c.id_currency=o.id_currency
                                            WHERE o.id_order=" . $params['id_order']);

        if ($order)
        {
            //TODO: Typ platby v superfakture: transfer, cash, paypal, credit, cod

            $data['InvoicePayment'] = array(
                'import_type'  => "prestashop",
                'import_id'    => $params['id_order'],
                'payment_type' => "transfer",
                'amount'       => $order['total_paid'],
                'currency'     => $order['iso_code'],
                'created'      => date('Y-m-d')
            );

            $response = $this->_request($this->getSfUrl(self::SF_URL_PAY_INVOICE . $params['id_order']), array('data' => json_encode($data)));
        }
    }


    public function hookActionOrderStatusUpdate($params)
    {
        if (intval($this->id_order_state_refund) == intval($params['newOrderStatus']->id))
        {      
            $response = $this->_request($this->getSfUrl(self::SF_URL_CREATE_CANCEL . $params['id_order'] . (!empty($this->cancel_sequence_id) ? '/sequence_id:' . $this->cancel_sequence_id : '')));
        }
        elseif ($this->id_order_state_invoice == $params['newOrderStatus']->id)
        {
            $order = new Order($params['id_order']);
            $cart = new Cart($order->id_cart);

            $this->_createInvoice($order, $cart);
        }
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
}
