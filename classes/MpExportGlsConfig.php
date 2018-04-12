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

class MpExportGlsConfig
{
    protected $module;
    protected $link;
    protected $context;
    protected $forms;
    protected $values;
    protected $id_lang;
    
    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
        $this->link = new LinkCore();
        $this->forms = array();
        $this->values = array();
        $this->id_lang = (int)$this->context->language->id;
    }
    
    public function initForm()
    {
        $form_fields = $this->getFields();
        $message = '';
        
        $form = new HelperFormCore();
        $form->table = 'orders';
        $form->default_form_language = (int) ConfigurationCore::get('PS_LANG_DEFAULT');
        $form->allow_employee_form_lang = (int) ConfigurationCore::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $form->submit_action = 'submit_form';
        $form->currentIndex = $this->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->module->name
            .'&tab_module='.$this->module->tab
            .'&module_name='.$this->module->name;
        $form->token = Tools::getAdminTokenLite('AdminModules');
        $form->tpl_vars = array(
            'fields_value' => $this->getFieldsValue(),
            'languages' => $this->context->controller->getLanguages(),
        );
        if (Tools::isSubmit('submit_form')) {
            $message = $this->module->displayConfirmation('Configuration updated.');
        }
        return $message.$form->generateForm(array($form_fields));
    }
    
    protected function getFields()
    {
        return $this->getForm();
    }
    
    protected function getForm()
    {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Configuration page: choose fields you want to export.'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(

            ),
            'buttons' => array(
                'export' => array(
                    'title' => $this->module->l('Go to export page'),
                    'href' => $this->module->getAdminModuleController(),
                    'icon' => 'process-icon-upload',
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Save'),
            ),
        );
        
        $this->addSwitch(
            $form,
            $this->module->l('Postcode'),
            $this->module->l('If set, exports postcode'),
            'input_switch_postcode'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Order reference'),
            $this->module->l('If set, exports order reference'),
            'input_switch_order_reference'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Order date'),
            $this->module->l('If set, exports order date'),
            'input_switch_order_date'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Cash on delivery'),
            $this->module->l('If set, exports order amount'),
            'input_switch_cash_delivery'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Order notes'),
            $this->module->l('If set, exports order notes'),
            'input_switch_order_notes'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Customer id'),
            $this->module->l('If set, exports cutomer id'),
            'input_switch_customer_id'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Customer email'),
            $this->module->l('If set, exports customer email'),
            'input_switch_customer_email'
        );
        $this->addSwitch(
            $form,
            $this->module->l('Customer mobile phone'),
            $this->module->l('If set, exports customer mobile phone'),
            'input_switch_mobile_phone'
        );
        $this->addSelectCarriers(
            $form,
            $this->module->l('Carrier'),
            $this->module->l('Select GLS carriers'),
            'input_select_carriers'
        );
        $this->addSelectPaymentModules(
            $form,
            $this->module->l('Cash on delivery'),
            $this->module->l('Select cash on delivery payment module'),
            'input_select_payment_modules'
        );
        
        $this->forms = array(
            'form' => $form,
        );
        
        return $this->forms;
    }
    
    protected function addSwitch(&$form, $label, $desc, $name)
    {
        $switch = array(
            'type' => 'switch',
            'label' => $label,
            'desc' => $desc,
            'name' => $name,
            'values' => array(
                array(
                    'id' => $name.'_on',
                    'value' => 1,
                    'label' => $this->module->l('Yes'),
                ),
                array(
                    'id' => $name.'_off',
                    'value' => 0,
                    'label' => $this->module->l('No'),
                ),

            ),
        );
        $form['input'][] = $switch;
        
        /**
         * UPDATE CONFIGURATION
         */
        $key = Tools::strtoupper('MP_GLS_'.$name);
        if (Tools::isSubmit('submit_form')) {
            $value = (int)Tools::getValue($name, 0);
            ConfigurationCore::updateValue($key, $value);
        } else {
            $value = (int)ConfigurationCore::get($key);
        }
        /**
         * GET CONFIGURATION VALUE
         */
        $this->values[$name] = $value;
    }
    
    protected function addSelectCarriers(&$form, $label, $desc, $name)
    {
        $select = array(
            'type' => 'select',
            'label' => $label,
            'desc' => $desc,
            'name' => $name,
            'multiple' => true,
            'class' => 'chosen fixed-width-xxl',
            'options' => array(
                'query' => CarrierCore::getCarriers($this->id_lang),
                'id' => 'id_carrier',
                'name' => 'name',
            ),
        );
        $form['input'][] = $select;
        
        /**
         * UPDATE CONFIGURATION
         */
        $key = Tools::strtoupper('MP_GLS_'.$name);
        if (Tools::isSubmit('submit_form')) {
            $value = Tools::getValue($name, 0);
            ConfigurationCore::updateValue($key, implode(',',$value));
        } else {
            $value = explode(",", ConfigurationCore::get($key));
        }
        /**
         * GET CONFIGURATION VALUE
         */
        $this->values[$name.'[]'] = $value;
    }
    
    protected function addSelectPaymentModules(&$form, $label, $desc, $name)
    {
        $select = array(
            'type' => 'select',
            'label' => $label,
            'desc' => $desc,
            'name' => $name,
            'multiple' => true,
            'class' => 'chosen fixed-width-xxl',
            'options' => array(
                'query' => PaymentModuleCore::getInstalledPaymentModules(),
                'id' => 'name',
                'name' => 'name',
            ),
        );
        $form['input'][] = $select;
        
        /**
         * UPDATE CONFIGURATION
         */
        $key = Tools::strtoupper('MP_GLS_'.$name);
        if (Tools::isSubmit('submit_form')) {
            $value = Tools::getValue($name, 0);
            ConfigurationCore::updateValue($key, implode(',',$value));
        } else {
            $value = explode(",", ConfigurationCore::get($key));
        }
        /**
         * GET CONFIGURATION VALUE
         */
        $this->values[$name.'[]'] = $value;
    }
    
    protected function getFieldsValue()
    {
        return $this->values;
    }
}