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

class MpExportGlsAdminPage
{
    protected $module;
    protected $link;
    protected $context;
    protected $forms;
    protected $values;
    protected $id_lang;
    protected $cookie;
    
    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
        $this->link = new LinkCore();
        $this->forms = array();
        $this->values = array();
        $this->id_lang = (int)$this->context->language->id;
        $this->cookie = Context::getContext()->cookie;
    }
    
    public function getContent()
    {
        if (Tools::isSubmit('submit_form') || Tools::isSubmit('page')) {
            return $this->initForm().$this->initList();
        } else {
            $this->cookie->__unset('input_date_start');
            $this->cookie->__unset('input_date_end');
            $this->cookie->__unset('input_select_order_states');
            return $this->initForm();
        }
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
        $form->currentIndex = $this->link->getAdminLink($this->module->getAdminClassName(), false);
        $form->token = Tools::getAdminTokenLite($this->module->getAdminClassName());
        $form->tpl_vars = array(
            'fields_value' => $this->getFieldsValue(),
            'languages' => $this->context->controller->getLanguages(),
        );
        
        return $message.$form->generateForm(array($form_fields));
    }
    
    public function initList()
    {
        require_once $this->module->getPath().'classes/MpExportGlsHelperList.php';
        $list = new MpExportGlsHelperList($this->module);
        return $list->initList();
    }
    
    protected function getFields()
    {
        return $this->getForm();
    }
    
    protected function getForm()
    {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Export GLS: choose orders you want to export.', get_class($this)),
                'icon' => 'icon-list',
            ),
            'input' => array(

            ),
            'buttons' => array(
                'back' => array(
                    'title' => $this->module->l('Go to control panel', get_class($this)),
                    'href' => $this->link->getAdminLink('AdminDashboard', get_class($this)),
                    'icon' => 'process-icon-back',
                ),
                'config' => array(
                    'title' => $this->module->l('Go to config panel', get_class($this)),
                    'href' => $this->link->getAdminLink('AdminModules', get_class($this))
                        .'&configure='.$this->module->name
                        .'&tab_module=administration'
                        .'&module_name='.$this->module->name,
                    'icon' => 'process-icon-cogs',
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Find', get_class($this)),
                'icon' => 'process-icon-ok',
            ),
        );
        
        
        $this->addSelectOrderStates(
            $form,
            $this->module->l('Order state', get_class($this)),
            $this->module->l('Select order state to export', get_class($this)),
            'input_select_order_states'
        );
        
        $this->addDate(
            $form,
            $this->module->l('Date start', get_class($this)),
            $this->module->l('Select start date to find orders', get_class($this)),
            'input_date_start'
        );
        
        $this->addDate(
            $form,
            $this->module->l('Date end', get_class($this)),
            $this->module->l('Select end date to find orders', get_class($this)),
            'input_date_end'
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
                    'label' => $this->module->l('Yes', get_class($this)),
                ),
                array(
                    'id' => $name.'_off',
                    'value' => 0,
                    'label' => $this->module->l('No', get_class($this)),
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
            'class' => 'chosen',
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
            ConfigurationCore::updateValue($key, implode(',', $value));
        } else {
            $value = explode(",", ConfigurationCore::get($key));
        }
        /**
         * GET CONFIGURATION VALUE
         */
        $this->values[$name.'[]'] = $value;
    }
    
    protected function addSelectOrderStates(&$form, $label, $desc, $name)
    {
        $select = array(
            'type' => 'select',
            'label' => $label,
            'desc' => $desc,
            'name' => $name,
            'multiple' => true,
            'class' => 'chosen',
            'options' => array(
                'query' => OrderStateCore::getOrderStates($this->id_lang),
                'id' => 'id_order_state',
                'name' => 'name',
            ),
        );
        $form['input'][] = $select;
        
        /**
         * GET CONFIGURATION VALUE
         */
        $value = Tools::getValue($name, array());
        if (Tools::isSubmit('page')) {
            $value = explode(',', $this->cookie->$name);
        }
        $this->cookie->$name = implode(',', $value);
        $this->values[$name.'[]'] = $value;
    }
    
    protected function addDate(&$form, $label, $desc, $name)
    {
        $date = array(
            'type' => 'date',
            'label' => $label,
            'desc' => $desc,
            'name' => $name,
            'class' => 'date datepicker',
        );
        $form['input'][] = $date;
        
        /**
         * GET CONFIGURATION VALUE
         */
        $value = Tools::getValue($name, '');
        if (Tools::isSubmit('page')) {
            $value = $this->cookie->$name;
        }
        $this->cookie->$name = $value;
        $this->values[$name] = $value;
    }
    
    protected function getFieldsValue()
    {
        return $this->values;
    }
}
