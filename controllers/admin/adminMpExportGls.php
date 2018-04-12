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
    public $link;
    public $id_lang;
    public $id_shop;
    protected $admin_page;
    
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'orders';
        $this->context = Context::getContext();
        $this->smarty = Context::getContext()->smarty;
        $this->debug = false;
        $this->id_lang = ContextCore::getContext()->language->id;
        $this->states = OrderStateCore::getOrderStates($this->id_lang);
        $this->name = 'AdminMpExportGls';
        
        parent::__construct();
        
        $this->link = Context::getContext()->link;
        $this->id_lang = (int) Context::getContext()->language->id;
        $this->id_shop = (int) Context::getContext()->shop->id;
        
        require_once $this->module->getPath().'classes/MpExportGlsAdminPage.php';
        $this->admin_page = new MpExportGlsAdminPage($this->module);
        
        $this->addRowAction('export');
    }
    
    public function initContent()
    {
        /**
         * CHECK EXPORT FLAG
         */
        if (Tools::isSubmit('export') || Tools::isSubmit('configurationBox')) {
            require_once $this->module->getPath().'vendor/PHPExcel.php';
            require_once $this->module->getPath().'classes/MpExcel.php';
            require_once $this->module->getPath().'classes/MpExport.php';
            $this->content = $this->module->displayConfirmation('EXPORT!!!');
            $export = new MpExport($this->module);
            $content = $export->export();
            $excel = new MpExcel($this->module);
            $excel->write($content);
        }
        $this->content .= $this->admin_page->getContent();
        parent::initContent();
    }
    
    public function displayExportLink($token = null, $id = 0, $name = null)
	{
		if (!array_key_exists('Export', self::$cache_lang)) {
			self::$cache_lang['Export'] = $this->l('Export');
        }
        
        if (!$token) {
            $token = Tools::getAdminTokenLite($this->name);
        }
        
        $currentIndex = $this->link->getAdminLink($this->name, false)
            .'&id_order='.$id
            .'&export'
            .'&table=orders'
            .'&token='.$token;
	
		$this->smarty->assign(array(
				'href' => $currentIndex,
				'action' => self::$cache_lang['Export'],
				'id' => $id
		));
        
        //print "<pre>BUTTON -> token: ".$token.', id: '.$id.', name: '.$name.', href: '.$currentIndex."</pre>";
        
		return $this->smarty->fetch($this->module->getPath().'views/templates/admin/list_action_export.tpl');
	}
}
