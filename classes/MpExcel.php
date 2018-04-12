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

class MpExcel
{
    protected $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }
    
    /**
     * Write a spreadsheet excel file
     * @param array $content Array of rows to write in excel format
     * @return text File content
     */
    public function write($content)
    {
        
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $activeSheet = $objPHPExcel->getActiveSheet();
        $idx_row = 0;
        foreach ($content as $row)
        {
            //Write Headers
            if ($idx_row == 0) {
                $idx_col = 0;
                foreach ($row as $key=>$col) {
                    $activeSheet->setCellValueByColumnAndRow($idx_col, $idx_row, Tools::strtoupper($key));
                    $activeSheet->setCellValueByColumnAndRow($idx_col, $idx_row+1, Tools::strtoupper($key));
                    $idx_col++;
                }
                $idx_row++;
                $idx_row++;
            } else {
                $idx_col = 0;
                foreach ($row as $col) {
                    $activeSheet->setCellValueByColumnAndRow($idx_col, $idx_row, $col);
                    $idx_col++;
                }
                $idx_row++;
            }
        }
        
        // We'll be outputting an excel file
        header('Content-type: application/vnd.ms-excel');
        // It will be called file.xls
        header('Content-Disposition: attachment; filename="export.xls"');
        // New Object writer
        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        // Write file to the browser
        $objWriter->save('php://output');
        exit();
    }
}