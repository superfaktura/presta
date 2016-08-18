<?php

if ( !defined( '_PS_VERSION_' ) )
    exit;

/**
*   Version 1.6.1
*   Last modified 2016-08-18
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
        $invoice_type;

    const API_AUTH_KEYWORD          = 'SFAPI';
    const SF_URL_CREATE_INVOICE     = 'https://moja.superfaktura.sk/invoices/create';
    const SF_URL_CREATE_CLIENT      = 'https://moja.superfaktura.sk/clients/create';
    const SF_URL_PAY_INVOICE        = 'https://moja.superfaktura.sk/invoice_payments/add/ajax:1/api:1/import_type:prestashop/import_id:';
    const SF_URL_CREATE_CANCEL      = 'https://moja.superfaktura.sk/invoices/cancelFromRegular/0/import_type:prestashop/import_id:';
    const SF_URL_GET_PDF_INVOICE    = 'https://moja.superfaktura.sk/invoices/pdf/0/import_type:prestashop/import_id:';
    const SF_URL_SEND_INVOICE       = 'https://moja.superfaktura.sk/invoices/send';

    public function __construct()
    {
        $this->name          = "superfaktura";
        $this->tab           = "billing_invoicing";
        $this->version       = 1.6;
        $this->author        = "www.superfaktura.sk";
        $this->need_instance = 1;


        $config = Configuration::getMultiple(array('SUPERFAKTURA_EMAIL', 'SUPERFAKTURA_APIKEY', 'SUPERFAKTURA_COMPANY_ID', 'SUPERFAKTURA_ORDER_STATE_REFUND', 'SUPERFAKTURA_ORDER_STATE_INVOICE', 'SUPERFAKTURA_SET_INVOICE_PAID', 'SUPERFAKTURA_VARIABLE_SOURCE', 'SUPERFAKTURA_SEQUENCE_ID', 'SUPERFAKTURA_SEND_INVOICE', 'SUPERFAKTURA_INVOICE_TYPE' ));

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

                <strong>' . $this->l("Faktúru vytvárať pri") . ': <sup>*</sup></strong><br />
                <select name="id_order_state_invoice">
                    <option value="0"'.((0 == Tools::getValue('id_order_state_invoice', $this->id_order_state_invoice)) ? ' selected="selected"' : '').'>vytvorení objednávky</option>
        ';

                foreach ($states AS $state)
                    $this->_html .= '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == Tools::getValue('id_order_state_invoice', $this->id_order_state_invoice)) ? ' selected="selected"' : '').'>zmene stavu objednávky na "'.stripslashes($state['name']).'"</option>';

        $this->_html .= '
                </select><br />
                <br />

                <strong>' . $this->l("Pri vytvorení faktúry ju nastaviť ako uhradenú") . ': </strong><br />
                <input type="checkbox" name="set_invoice_paid" value="1"' . (1 == Tools::getValue('set_invoice_paid', $this->set_invoice_paid) ? ' checked="checked"' : '') . ' /><br />
                <br />

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
        ';


        $this->_html .= '       <strong>' . $this->l("Po vytvorení odoslať faktúru klientovi") . ': </strong><br />
                <input type="checkbox" name="send_invoice" value="1"' . (1 == Tools::getValue('send_invoice', $this->send_invoice) ? ' checked="checked"' : '') . ' /><br />
                <br />
        ';
        
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
        $this->_html .= '        <input type="submit" name="btnSubmit" value="'.$this->l('Uložiť').'" class="button" />
            </form>
            </div>   
        ';
    }


    private function _request($url, $data = "")
    {
        $c = curl_init();

        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_REFERER        => $url,
            CURLOPT_HEADER         => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => array("Authorization: " . self::API_AUTH_KEYWORD . " email=" . $this->email . "&apikey=" . $this->apikey . "&company_id=" . $this->company_id),
            CURLOPT_ENCODING       => '',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
        );

        curl_setopt_array($c, $options);

        $result = curl_exec($c);

        $http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);

        curl_close($c);

        if ((false === $result) || (200 != $http_code))
        {
            return false;
        }


        if (false === strpos($result, "\r\n\r\n"))
        {
            return false;
        }

        $result = explode("\r\n\r\n", $result);


        return $result[count($result) - 1];
    }



    private function _createInvoice($order, $cart)
    {

        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_SHOP, $order->id_shop);

        $data = array(
            'InvoiceItem' => array()
        );

        $customer         = new Customer($cart->id_customer);
        $address          = new Address($cart->id_address_invoice);
        $delivery_address = new Address($cart->id_address_delivery);
        $carrier          = new Carrier($order->id_carrier);
        $currency         = new Currency($order->id_currency);
        $products = $order->getCartProducts();


        $name = $address->company;
        if (empty($name) && (("" != $address->firstname) || ("" != $address->lastname)))
        {
            $name .= ("" != $name ? ", " : "") . $address->firstname;
            $name .= ("" != $address->firstname ? " " : "") . $address->lastname;
        }

        $delivery_name = $delivery_address->company;
        if (empty($delivery_name) && (("" != $delivery_address->firstname) || ("" != $delivery_address->lastname)))
        {
            $delivery_name .= ("" != $delivery_name ? ", " : "") . $delivery_address->firstname;
            $delivery_name .= ("" != $delivery_address->firstname ? " " : "") . $delivery_address->lastname;
        }

        $phone = ("" != $address->phone_mobile ? $address->phone_mobile : $address->phone);

        $dic    = "";
        $ic_dph = "";
        $start  = isset($address->vat_number) ? substr($address->vat_number, 0, 2) : '';

        if(!empty($address->vat_number)){
            if(!is_numeric($start)){
                $ic_dph = $address->vat_number;
                $dic    = substr($address->vat_number, 2);
            } else {
                $dic = $address->vat_number;
            }
        }

        if(strlen($ic_dph) < 5){
            $ic_dph = "";
        }

        //prekladanie ID krajin v preste do SF
        $countries_translate = array(
            231 => 1,
            230 => 2,
            38 => 3,
            39 => 4,
            40 => 5,
            41 => 6,
            42 => 7,
            232 => 8,
            43 => 9,
            44 => 10,
            45 => 11,
            46 => 12,
            24 => 13,
            2 => 14,
            47 => 15,
            48 => 16,
            49 => 17,
            50 => 18,
            51 => 19,
            52 => 20,
            3 => 21,
            53 => 22,
            54 => 23,
            55 => 24,
            56 => 25,
            34 => 26,
            233 => 27,
            57 => 28,
            234 => 29,
            58 => 30,
            235 => 31,
            59 => 32,
            236 => 33,
            60 => 34,
            62 => 35,
            63 => 36,
            64 => 37,
            4 => 38,
            65 => 39,
            237 => 40,
            66 => 41,
            67 => 42,
            68 => 43,
            5 => 44,
            238 => 45,
            239 => 46,
            69 => 47,
            70 => 48,
            72 => 49,
            71 => 50,
            240 => 51,
            73 => 52,
            32 => 53,
            74 => 54,
            75 => 55,
            76 => 56,
            16 => 57,
            20 => 58,
            77 => 59,
            78 => 60,
            79 => 61,
            80 => 62,
            81 => 63,
            82 => 64,
            83 => 65,
            84 => 66,
            85 => 67,
            86 => 68,
            87 => 69,
            88 => 70,
            89 => 71,
            90 => 72,
            7 => 73,
            8 => 74,
            241 => 76,
            242 => 77,
            243 => 78,
            91 => 79,
            92 => 80,
            93 => 81,
            1 => 82,
            94 => 83,
            97 => 84,
            9 => 85,
            96 => 86,
            95 => 87,
            98 => 88,
            99 => 89,
            100 => 90,
            102 => 91,
            103 => 92,
            104 => 93,
            105 => 94,
            106 => 95,
            108 => 97,
            22 => 98,
            143 => 99,
            109 => 100,
            110 => 101,
            111 => 102,
            112 => 103,
            113 => 104,
            26 => 105,
            29 => 106,
            10 => 107,
            115 => 108,
            11 => 109,
            117 => 110,
            118 => 111,
            119 => 112,
            120 => 113,
            121 => 114,
            122 => 116,
            123 => 117,
            124 => 118,
            125 => 119,
            126 => 120,
            127 => 121,
            128 => 122,
            129 => 123,
            130 => 124,
            131 => 125,
            12 => 126,
            132 => 127,
            133 => 128,
            134 => 129,
            135 => 130,
            136 => 131,
            137 => 132,
            138 => 133,
            139 => 134,
            140 => 135,
            141 => 136,
            142 => 137,
            35 => 138,
            144 => 139,
            145 => 140,
            146 => 141,
            147 => 142,
            148 => 143,
            149 => 144,
            151 => 145,
            152 => 146,
            153 => 147,
            61 => 148,
            154 => 149,
            154 => 150,
            156 => 151,
            13 => 152,
            157 => 153,
            158 => 154,
            27 => 155,
            159 => 156,
            160 => 157,
            31 => 158,
            161 => 159,
            162 => 160,
            163 => 161,
            23 => 162,
            164 => 163,
            165 => 164,
            166 => 165,
            168 => 166,
            169 => 167,
            170 => 168,
            171 => 169,
            172 => 170,
            173 => 171,
            14 => 172,
            15 => 173,
            174 => 174,
            175 => 175,
            176 => 176,
            36 => 177,
            177 => 178,
            178 => 179,
            180 => 180,
            181 => 181,
            184 => 182,
            185 => 183,
            186 => 184,
            187 => 185,
            188 => 186,
            189 => 187,
            191 => 188,
            192 => 189,
            25 => 190,
            37 => 191,
            193 => 192,
            194 => 193,
            195 => 194,
            30 => 195,
            196 => 196,
            6 => 197,
            197 => 198,
            183 => 200,
            198 => 201,
            199 => 202,
            200 => 203,
            201 => 204,
            18 => 205,
            19 => 206,
            202 => 207,
            203 => 208,
            204 => 209,
            205 => 210,
            206 => 211,
            33 => 212,
            207 => 213,
            208 => 214,
            209 => 215,
            210 => 216,
            211 => 217,
            212 => 218,
            213 => 219,
            214 => 220,
            215 => 221,
            216 => 222,
            217 => 223,
            17 => 224,
            21 => 225,
            218 => 227,
            219 => 228,
            220 => 229,
            221 => 230,
            222 => 231,
            223 => 232,
            224 => 233,
            225 => 234,
            226 => 235,
            227 => 236,
            190 => 237,
            238 => 238,
            239 => 239,
            150 => 240,
            184 => 241,
            116 => 243,
            101 => 244,
        );


        $data['Client'] = array(
            'ico'                 => isset($address->dni) ? $address->dni : '',
            'name'                => $name,
            'address'             => $address->address1 . ("" != $address->address2 ? ", " : "") . $address->address2,
            'city'                => $address->city,
            'zip'                 => $address->postcode,
            'country'             => $address->country,
            'country_id'          => $countries_translate[$address->id_country],
            'delivery_name'       => $delivery_name,
            'delivery_address'    => $delivery_address->address1 . ("" != $delivery_address->address2 ? ", " : "") . $delivery_address->address2,
            'delivery_city'       => $delivery_address->city,
            'delivery_zip'        => $delivery_address->postcode,
            'delivery_country'    => $delivery_address->country,
            'delivery_country_id' => $countries_translate[$delivery_address->id_country],
            'phone'               => ("" != $address->phone_mobile ? $address->phone_mobile : $address->phone),
            'email'               => $customer->email,
            'ic_dph'              => $ic_dph,
            'dic'                 => $dic
        );

        $data['Invoice'] = array(
            'import_type'   => "prestashop",
            'type'          => $this->invoice_type,
            'import_id'     => $order->id,
            'variable'      => ($this->variable_source == 'order') ? $order->id : null,
            'order_no'      => $order->id,
            'already_paid'  => (1 == $this->set_invoice_paid),
            'delivery_type' => isset($carrier->name) ? $carrier->name : '',
            'payment_type'  => isset($order->payment) ? ($order->payment == 'Bank wire') ? 'transfer' : $order->payment : '',
            'rounding'      => 'item_ext', // defultne nastavime na maloobchod predchadzame problemom so zaokruhlovanim
            'sequence_id'   => (!empty($this->sequence_id)) ? $this->sequence_id : ''
        );

        $data['InvoiceSetting']['settings'] = json_encode(array(
        	'signature' => true,
        	'payment_info' => true,
        	'bysquare'     => true
        ));

        if (isset($currency->iso_code))
            $data['Invoice']['invoice_currency'] = $currency->iso_code;

        //invoice items
        echo '<pre>';
        print_r($products);
        echo '</pre>';
        exit();
        
        foreach ($products as $product)
        {
            $sku = $product['product_reference'];
            if(empty($sku)){ $sku = $product['product_upc']; }
            if(empty($sku)){ $sku = $product['product_ean13']; }
            if(empty($sku)){ $sku = null; }

            $data['InvoiceItem'][] = array(
                'name'        => $product['product_name'],
                'description' => trim((isset($product['attributes_small']) ? strip_tags($product['attributes_small']) : "").PHP_EOL.$sku),
                'quantity'    => $product['product_quantity'],
                'unit'        => 'ks',
                'unit_price'  => $product['product_price'],
                'tax'         => $product['tax_rate'],
                'sku'         => $sku
            );
        }

        //shipping
        if (isset($order->total_shipping) && (0 < $order->total_shipping))
        {
            $shipping_tax = isset($order->carrier_tax_rate) ? $order->carrier_tax_rate : 0;
            $shipping     = $order->total_shipping / (1 + ($shipping_tax / 100));

            $data['InvoiceItem'][] = array(
                'name'        => $carrier->name,
                'quantity'    => 1,
                'unit'        => 'ks',
                'unit_price'  => $shipping,
                'tax'         => $shipping_tax
            );
        }

        //discount
        if(isset($order->total_discounts) && $order->total_discounts > 0){
            $data['InvoiceItem'][] = array(
                'name'        => 'Zľava',
                'unit_price'  => ( $order->total_discounts / (1 + ($order->carrier_tax_rate / 100)))  * -1,
                'tax'         => $order->carrier_tax_rate,

            );
        }
        $response = $this->_request(self::SF_URL_CREATE_INVOICE, array('data' => json_encode($data)));

        
        //poslat fakturu emailom 
        if($response->error == 0 && $this->send_invoice == 1){
            $invoice = json_decode($response);    
            
            $request_data['Email'] =array(
                'invoice_id' => $invoice->data->Invoice->id,
                'to'  => $invoice->data->Client->email,
            );

            $send = $this->_request(self::SF_URL_SEND_INVOICE, array('data' => json_encode($request_data)));

        }
        if (false == $response)
        {
             return;
        }

        $response = json_decode($response);

        if ((isset($response->error) && (0 != $response->error)) || ! isset($response->data->Invoice->variable))
        {
            return;
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

            $response = $this->_request(self::SF_URL_PAY_INVOICE . $params['id_order'], array('data' => json_encode($data)));
        }
    }


    public function hookActionOrderStatusUpdate($params)
    {
        if (intval($this->id_order_state_refund) == intval($params['newOrderStatus']->id))
        {      
            $response = $this->_request(self::SF_URL_CREATE_CANCEL . $params['id_order']);
        }
        elseif ($this->id_order_state_invoice == $params['newOrderStatus']->id)
        {
            $order = new Order($params['id_order']);
            $cart = new Cart($order->id_cart);

            $this->_createInvoice($order, $cart);
        }
    }


    /*public function hookDisplayPDFInvoice($params)
    {
        if (isset($params['object']->id_order))
        {
            $response = $this->_request(self::SF_URL_GET_PDF_INVOICE . $params['object']->id_order);

            if ("" != $response)
            {

                header("Pragma: public"); // required
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private",false); // required for certain browsers

                header("Content-Type: application/force-download");
                header("Content-Disposition: attachment; filename=\"invoice.pdf\";" );

                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".strlen($response));

                echo $response;

                exit();
            }
        }
    }*/


}
