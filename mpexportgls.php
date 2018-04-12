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

if (!class_exists('MpInstallClass')) {
    require_once dirname(__file__).'/classes/MpInstallClass.php';
}
if (!class_exists('MpExportGlsConfig')) {
    require_once dirname(__file__).'/classes/MpExportGlsConfig.php';
}

class MpExportGls extends Module
{
    protected $install;
    protected $config;
    protected $adminClassName = 'AdminMpExportGls';
    
    public function getAdminClassName()
    {
        return $this->adminClassName;
    }
    
    public function getAdminModuleController()
    {
        $link = new LinkCore();
        $path = $link->getAdminLink($this->adminClassName);
        return $path;
    }
    
    public function getPath()
    {
        return $this->local_path;
    }
    
    public function getUrl()
    {
        return $this->path;
    }
    
    public function setError($error)
    {
        $this->_errors[] = $error;
    }
    
    public function setConfirmation($message)
    {
        $this->_confirmations[] = $message;
    }
    
    public function setWarning($warning)
    {
        $this->_warnings[] = $warning;
    }
    
    public function __construct()
    {
        $this->name = 'mpexportgls';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Digital Solutions®';
        $this->need_instance = 0;
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MP Export GLS');
        $this->description = $this->l('With this module you can export an excel sheet with orders delivered by GLS');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->install = new MpInstallClass($this);
        $this->config = new MpExportGlsConfig($this);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAdminOrderContentShip') &&
            $this->install->installTab('MpModules', $this->adminClassName, $this->l('MP Export GLS'));
    }

    public function uninstall()
    {
        Configuration::deleteByName('MPEXPORTGLS_LIVE_MODE');

        return parent::uninstall() &&
            $this->install->uninstallTab($this->adminClassName);
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        return $this->config->initForm();
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayAdminOrderContentShip()
    {
        /* Place your code here. */
    }
}
