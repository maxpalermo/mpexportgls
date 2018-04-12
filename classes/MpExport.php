<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Massimiliano Palermo <info@mpsoft.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class MpExport
{
    protected $module;
    protected $postcode = false;
    protected $order_reference = false;
    protected $order_date = false;
    protected $cash_on_delivery = false;
    protected $order_notes = false;
    protected $customer_id = false;
    protected $customer_email = false;
    protected $customer_mobile_phone = false;
    protected $module_payment = '';
    protected $result = array();
    
    public function __construct($module)
    {
        $this->module = $module;
        $prefix = 'MP_GLS_';
        $fields = array(
            'postcode' => 'input_switch_postcode',
            'order_reference' => 'input_switch_reference',
            'order_date' => 'input_switch_order_date',
            'cash_on_delivery' => 'input_switch_cash_delivery',
            'order_notes' => 'input_switch_order_notes',
            'customer_id' => 'input_switch_customer_id',
            'customer_email' => 'input_switch_customer_email',
            'customer_mobile_phone' => 'input_switch_customer_mobile_phone',
        );
        foreach ($fields as $key=>$field) {
            $field = Tools::strtoupper($field);
            $this->$key = (int)ConfigurationCore::get($prefix.$field);
        }
        
        $this->module_payment = ConfigurationCore::get($prefix.'INPUT_SELECT_PAYMENT_MODULES');
    }
    
    public function export()
    {
        if (Tools::isSubmit('export')) {
            return $this->exportSingle();
        } else {
            return $this->exportBulk();
        }
    }
    
    protected function exportSingle()
    {
        print "<pre> SINGLE:".print_r(Tools::getAllValues(),1)."</pre>";
    }
    
    protected function exportBulk()
    {
        $id_orders = Tools::getValue('configurationBox', array());
        foreach ($id_orders as $id_order) {
            $this->createRow($id_order);
        }
        return $this->result;
    }
    
    protected function createRow($id_order)
    {
        $order = new OrderCore($id_order);
        $address = new AddressCore($order->id_address_delivery);
        $state = new StateCore($address->id_state);
        $customer = new CustomerCore($order->id_customer);

        if ($address->company) {
            $company = $address->company;
        } else {
            $company = $address->firstname.' '.$address->lastname;
        }


        $row = array(
            'company' => $this->ucFirst($company),
            'address' => $address->address1 . ' ' . $address->address2,
            'city' => $address->city,
            'state' => Tools::strtoupper($state->iso_code),
            'qty' => 1,
            'weight' => 1,
        );

        if ($this->postcode) {
            $row['postcode'] = $address->postcode;
        }

        if ($this->order_reference) {
            $row['order_reference'] = $order->reference;
        }

        if ($this->order_date) {
            $row['order_date'] = Tools::displayDate($order->date_add);
        }

        if ($this->cash_on_delivery) {
            if ($this->module_payment == $order->module) {
                $row['order_amount'] = $order->total_paid;
            } else {
                $row['order_amount'] = 0;
            }
        }
        
        if ($this->order_notes) {
            $row['order_notes'] = $address->other;
        }
        
        if ($this->customer_id) {
            $row['customer_id'] = (int)$order->id_customer;
        }
        
        if ($this->customer_email) {
            $row['customer_email'] = $customer->email;
        }
        
        if ($this->customer_mobile_phone) {
            $row['customer_mobile_phone'] = $address->phone_mobile;
        }
        
        $this->result[] = $row;
    }
    
    protected function ucFirst($str)
    {
        $parts = explode(' ', $str);
        foreach($parts as &$part) {
            $part = Tools::ucfirst($part);
        }
        return implode(' ', $parts);
    }
}