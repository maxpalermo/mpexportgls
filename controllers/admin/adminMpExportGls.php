<?php
/**
 * 2017 mpSOFT
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
 *  @author    mpSOFT by Massimiliano Palermo<info@mpsoft.it>
 *  @copyright 2017 mpSOFT by Massimiliano Palermo
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of mpSOFT
 */

class AdminMpExportGlsController extends ModuleAdminController
{
    public $id_customer_prefix;
    private $date_start;
    private $date_end;
    public $link;
    public $id_lang;
    public $id_shop;
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'orders';
        $this->context = Context::getContext();
        $this->smarty = Context::getContext()->smarty;
        $this->debug = false;
        $this->id_lang = ContextCore::getContext()->language->id;
        $this->states = OrderStateCore::getOrderStates($this->id_lang);
        $this->name = 'AdminMpExportDocuments';
        
        parent::__construct();
        
        $this->link = Context::getContext()->link;
        $this->id_lang = (int) Context::getContext()->language->id;
        $this->id_shop = (int) Context::getContext()->shop->id;
    }
    
    public function ajaxProcessGetTranslation()
    {
        $translate = Tools::getValue('translate');
        $title = Tools::getValue('title');
        
        $translations = array(
            'Export selected documents?' => $this->l('Export selected documents?'),
        );
        
        $titles = array(
            'Confirm' => $this->l('Confirm'),
        );
        
        foreach ($translations as $key=>$value) {
            if ($key == $translate) {
                $translate = $value;
                break;
            }
        }
        
        foreach ($titles as $key=>$value) {
            if ($key == $title) {
                $title = $value;
                break;
            }
        }
        
        return Tools::jsonEncode(
            array(
                'result' => true,
                'translation' => $translate,
                'title' => $title,
            )
        );
    }
    
    public function ajaxProcessExportSelected()
    {
        require_once 'Export.php';
        $export = new ExportToXML(Tools::getValue('list_of_ids', array()), Tools::getValue('type', ''));
        $content = $export->export();
        print $content;
        exit();
    }
    
    public function getCustomController($name, $folder='admin')
    {
        //Include filename
        require_once $this->module->getPath().'controllers/'.$folder.'/'.$name.'.php';
        //Build controller name
        $controller_name = get_class($this->module).$name.'Controller';
        //Instantiate controller
        $controller = new $controller_name($this);
        //Return controller
        return $controller;
    }
    
    public function initContent()
    {
        $this->helperlistContent = '';
        $this->messages = array();
        $this->date_start = '';
        $this->date_end = '';
        $this->total_document = 0;
        
        /**
         * CHECK AJAX CALLS
         */
        if (Tools::isSubmit('ajax') && !empty(Tools::getValue('action'))) {
            $action = 'ajaxProcess' . Tools::getValue('action');
            print $this->$action();
            exit();
        }
        
        /**
         * GET LIST
         */
        if (Tools::isSubmit('submitForm')) {
            /**
             * GET DATA CONTENT
             */
            $this->date_start = Tools::getValue('input_text_date_start');
            $this->date_end = Tools::getValue('input_text_date_end');
            $type = Tools::getValue('input_select_type_document');
            $contentController = $this->getCustomController($type);
            $params = array(
                'date_start' => $this->date_start,
                'date_end' => $this->date_end,
                'action' => 'Display',
                'pagination' => 30,
                'current_page' => 1,
                'controller_name' => $this->name,
            );
            $result = $contentController->run($params);
            $this->helperlistContent = $result['helperlist'];
            $this->total_document = $result['total'];
        }
        /**
         * INITIALIZE CONTENT
         */
        $this->helperformContent = $this->initHelperForm();
        $this->content = implode('<br>', $this->messages) 
            . $this->helperformContent 
            . $this->helperlistContent 
            . $this->scriptContent();
        
        parent::initContent();
    }
    
    private function scriptContent()
    {
        Context::getContext()->controller->addJS($this->module->getUrl().'views/js/adminController.js');
        return '';
    }
    
    protected function initHelperForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Export configuration'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'required' => true,
                        'type' => 'date',
                        'name' => 'input_text_date_start',
                        'label' => $this->l('Start date'),
                        'desc' => $this->l('Please insert the start date'),
                        'prefix' => '<i class="icon-chevron-right"></i>',
                        'suffix' => '<i class="icon-calendar"></i>',
                        'class' => 'datepicker'
                    ),
                    array(
                        'required' => true,
                        'type' => 'date',
                        'name' => 'input_text_date_end',
                        'label' => $this->l('End date'),
                        'desc' => $this->l('Please insert the end date'),
                        'prefix' => '<i class="icon-chevron-right"></i>',
                        'suffix' => '<i class="icon-calendar"></i>',
                        'class' => 'datepicker',
                    ),
                    array(
                        'required' => true,
                        'type' => 'select',
                        'name' => 'input_select_type_document',
                        'label' => $this->l('Type document'),
                        'desc' => $this->l('Select the type document from the list above.'),
                        'prefix' => '<i class="icon-chevron-right"></i>',
                        'suffix' => '<i class="icon-list-ul"></i>',
                        'class' => 'input fixed-width-sm',
                        'options' => array(
                            'query' => $this->getDocumentsType(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'input_hidden_total_documents',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Get'),
                    'icon' => 'process-icon-next'
                ),
            ),
        );
        
        $helper = new HelperFormCore();
        $helper->table = '';
        $helper->default_form_language = (int)$this->id_lang;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANGUAGE');
        $helper->submit_action = 'submitForm';
        $helper->currentIndex = $this->link->getAdminLink($this->name); 
        $helper->token = Tools::getAdminTokenLite($this->name);
        if (Tools::isSubmit('submitForm')) {
            $submit_values = Tools::getAllValues();
            $output = array();
            foreach($submit_values as $key=>$value) {
                if(is_array($value)) {
                    $output[$key.'[]'] = $value;
                } else {
                    $output[$key] = $value;
                }
            }
            $output['input_hidden_total_documents'] = sprintf("%s: %s",$this->l('Total'),Tools::displayPrice($this->total_document));
            $helper->tpl_vars = array(
                'fields_value' => $output,
                'languages' => $this->context->controller->getLanguages(),
            );
        } else {
            $helper->tpl_vars = array(
                'fields_value' => array(
                    'input_text_date_start' => '',
                    'input_text_date_end' => '',
                    'input_select_type_document' => 0,
                    'input_hidden_total_documents' => sprintf("%s: %s",$this->l('Total'),Tools::displayPrice($this->total_document)),
                ),
                'languages' => $this->context->controller->getLanguages(),
            );
        }
        return $helper->generateForm(array($fields_form));
    }
    
    private function getDocumentsType()
    {
        return array(
            array(
                'id' => '0',
                'name' => $this->l('Please select a type document'),
            ),
            array(
                'id' => 'Orders',
                'name' => $this->l('Orders'),
            ),
            array(
                'id' => 'Invoices',
                'name' => $this->l('Invoices'),
            ),
            array(
                'id' => 'Returns',
                'name' => $this->l('Returns'),
            ),
            array(
                'id' => 'Slips',
                'name' => $this->l('Delivery slip'),
            ),
            array(
                'id' => 'Deliveries',
                'name' => $this->l('Delivery'),
            ),
        );
    }
}
