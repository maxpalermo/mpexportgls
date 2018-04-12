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

class MpExportGlsHelperList extends HelperListCore
{
    public $context;
    public $values;
    public $id_lang;
    public $module;
    public $link;
    protected $cookie;
    
    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
        $this->link = new LinkCore();
        $this->values = array();
        $this->id_lang = (int)$this->context->language->id;
        parent::__construct();
        $this->cookie = Context::getContext()->cookie;
    }
    
    public function initList()
    {
        $this->bootstrap = true;
        $this->actions = array('export');
        $this->bulk_actions = array(
            'export' => array(
                'text' => $this->l('Export selected'),
                'confirm' => $this->l('Export selected orders?'),
                'icon' => 'icon-upload',
            ),
        );
        $this->currentIndex = $this->link->getAdminLink($this->module->getAdminClassName(), false);
        $this->identifier = 'id_order';
        $this->no_link = true;
        $this->page = Tools::getValue('submitFilterconfiguration', 1);
        $this->_default_pagination = Tools::getValue('configuration_pagination', 50);
        $this->show_toolbar = true;
        $this->toolbar_btn = array(
            'export' => array(
                'desc' => $this->l('Export all'),
                'href' => $this->context->link->getAdminLink($this->module->getAdminClassName()).'&exportAll',
            )
        );
        $this->shopLinkType='';
        $this->simple_header = false;
        $this->token = Tools::getAdminTokenLite($this->module->getAdminClassName());
        $this->title = $this->module->l('Orders found', get_class($this));
        $list = $this->getList();
        $fields_display = $this->getFields();
        
        return $this->generateList($list, $fields_display);
    }
    
    protected function getFields()
    {
        $list = array();
        $this->addText($list, $this->l('Id order'), 'id_order', 32, 'text-right');
        $this->addDate($list, $this->l('Order date'), 'order_date', 'auto', 'text-center');
        $this->addDate($list, $this->l('Order state'), 'order_state', 'auto', 'text-left');
        $this->addPrice($list, $this->l('Order amount'), 'order_amount', 'auto', 'text-right');
        $this->addHtml($list, $this->l('Cash on delivery'), 'cash_on_delivery', 'auto', 'text-center');
        $this->addText($list, $this->l('Customer'), 'customer', 'auto', 'text-left');
        
        return $list;
    }
    
    protected function addText(&$list, $title, $key, $width, $alignment, $search = false)
    {
        $item = array(
            'title' => $title,
            'width' => $width,
            'align' => $alignment,
            'type' => 'text',
            'search' => $search,
        );
        
        $list[$key] = $item;
    }
    
    protected function addDate(&$list, $title, $key, $width, $alignment, $search = false)
    {
        $item = array(
            'title' => $title,
            'width' => $width,
            'align' => $alignment,
            'type' => 'date',
            'search' => $search,
        );
        
        $list[$key] = $item;
    }
    
    protected function addPrice(&$list, $title, $key, $width, $alignment, $search = false)
    {
        $item = array(
            'title' => $title,
            'width' => $width,
            'align' => $alignment,
            'type' => 'price',
            'search' => $search,
        );
        
        $list[$key] = $item;
    }
    
    protected function addHtml(&$list, $title, $key, $width, $alignment, $search = false)
    {
        $item = array(
            'title' => $title,
            'width' => $width,
            'align' => $alignment,
            'type' => 'bool',
            'float' => true,
            'search' => $search,
        );
        
        $list[$key] = $item;
    }

    protected function addIcon($icon, $color)
    {
        return "<i class='icon $icon' style='color: $color;'></i>"; 
    }
    
    protected function getList()
    {
        if (Tools::isSubmit('page')) {
            $date_start = $this->cookie->input_date_start;
            $date_end = $this->cookie->input_date_end;
            $order_states = $this->cookie->input_select_order_states;
        } else {
            $date_start = Tools::getValue('date_start', '');
            $date_end = Tools::getValue('date_end', '');
            $order_states = Tools::getValue('input_select_order_states', array());
        }
        
        if (!$order_states) {
            $order_states = '';
        } else {
            $order_states = implode(',', $order_states);
        }
        $carriers = ConfigurationCore::get('MP_GLS_INPUT_SELECT_CARRIERS');
        if (!$carriers) {
            $carriers = '';
        }
        $cash_module = ConfigurationCore::get('MP_GLS_INPUT_SELECT_PAYMENT_MODULES');
        if (!$cash_module) {
            $cash_module = '';
        }
        
        $db = Db::getInstance();
        
        $sql = new DbQueryCore();
        $sql->select('o.id_order')
            ->select('o.date_add as order_date')
            ->select('osl.name as order_state')
            ->select('o.total_paid as order_amount')
            ->select('o.module')
            ->select('CONCAT(c.firstname, \' \', c.lastname) as customer')
            ->from('orders', 'o')
            ->innerJoin('order_state_lang', 'osl', 'o.current_state=osl.id_order_state')
            ->innerJoin('customer', 'c', 'c.id_customer=o.id_customer')
            ->orderBy('o.date_add DESC')
            ->orderBy('o.id_order DESC');
        
        $sql_count = new DbQueryCore();
        $sql_count->select('count(*)')
            ->from('orders', 'o')
            ->innerJoin('order_state_lang', 'osl', 'o.current_state=osl.id_order_state')
            ->innerJoin('customer', 'c', 'c.id_customer=o.id_customer')
            ->orderBy('o.date_add DESC')
            ->orderBy('o.id_order DESC');
        
        if ($date_start) {
            $date_start .= ' 00:00:00';
            $sql->where('o.date_add >= \''.pSQL($date_start).'\'');
            $sql_count->where('o.date_add >= \''.pSQL($date_start).'\'');
        }
        if ($date_end) {
            $date_end .= ' 23:59:59';
            $sql->where('o.date_add <= \''.pSQL($date_end).'\'');
            $sql_count->where('o.date_add <= \''.pSQL($date_end).'\'');
        }
        if ($order_states) {
            $sql->where('o.current_state in ('.pSQL($order_states).')');
            $sql_count->where('o.current_state in ('.pSQL($order_states).')');
        }
        if ($carriers) {
            $sql->where('o.id_carrier in ('.pSQL($carriers).')');
            $sql_count->where('o.id_carrier in ('.pSQL($carriers).')');
        }
        
        $this->listTotal = $db->getValue($sql_count);
        
        //Save query in cookies
        Context::getContext()->cookie->export_query = $sql->build();
        
        //Set Pagination
        $sql->limit($this->_default_pagination, ($this->page-1)*$this->_default_pagination);
        
        $result = $db->executeS($sql);
        
        if ($result) {
            foreach ($result as &$row)
            {
                if ($row['module'] == $cash_module) {
                    $row['cash_on_delivery'] = $this->addIcon('icon-check', '#79BB79');
                } else {
                    $row['cash_on_delivery'] = $this->addIcon('icon-times', '#BB7979');
                }
                $row['customer'] = $this->ucFirst($row['customer']);
            }
        }
        
        return $result;
    }
    
    public function ucFirst($str)
    {
        $parts = explode(' ', $str);
        foreach($parts as $part) {
            $part = Tools::ucfirst($part);
        }
        return implode(' ', $parts);
    }
}