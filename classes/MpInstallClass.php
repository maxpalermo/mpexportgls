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

class MpInstallClass
{
    protected $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }
    
    /**
     * Install Main Menu
     * @return int Main menu id
     */
    public function installMainMenu()
    {
        $id_mp_menu = (int) TabCore::getIdFromClassName('MpModules');
        if ($id_mp_menu == 0) {
            $tab = new TabCore();
            $tab->active = 1;
            $tab->class_name = 'MpModules';
            $tab->id_parent = 0;
            $tab->module = null;
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $this->module->l('MP Modules');
            }
            $id_mp_menu = $tab->add();
            if ($id_mp_menu) {
                PrestaShopLoggerCore::addLog('id main menu: '.(int)$id_mp_menu);
                return (int)$tab->id;
            } else {
                PrestaShopLoggerCore::addLog('id main menu error');
                return false;
            }
        }
    }
    
    /**
     * Get id of main menu
     * @return int Main menu id
     */
    public function getMainMenuId()
    {
        $id_menu = (int)Tab::getIdFromClassName('MpModules');
        return $id_menu;
    }
    
    /**
     * Install New tab
     * @param string $parent Parent tab name
     * @param type $class_name Class name of the module
     * @param type $name Display name of the module
     * @param type $active If true, Tab menu will be shown
     * @return boolean True if successfull, False otherwise
     */
    public function installTab($parent, $class_name, $name, $active = 1)
    {
        // Create new admin tab
        $tab = new Tab();
        $id_parent = (int)Tab::getIdFromClassName($parent);
        
        if (!$id_parent) {
            $id_parent = $this->installMainMenu();
            if (!$id_parent) {
                $this->module->setError($this->module->l('Unable to install main module menu tab.'));
                return false;
            }
            PrestaShopLoggerCore::addLog('Created main menu: id=' . (int)$id_parent);
        }
        $tab->id_parent = (int)$id_parent;
        $tab->name      = array();
        
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        
        $tab->class_name = $class_name;
        $tab->module     = $this->module->name;
        $tab->active     = $active;
        
        if (!$tab->add()) {
            $this->module->_errors[] = $this->module->l('Error during Tab install.');
            return false;
        }
        return true;
    }
    
    /**
     * Uninstall tab
     * @param string pe $class_name Class name of the module
     * @return boolean True if successfull, False otherwise
     */
    public function uninstallTab($class_name)
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        if ($id_tab) {
            $tab = new Tab((int)$id_tab);
            $result = $tab->delete();
            if (!$result) {
                $this->module->_errors[] = $this->module->l('Unable to remove module menu tab.');
            }
            return $result;
        }
    }
}
