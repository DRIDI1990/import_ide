<?php
/**
* 2007-2022 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once('vendor/autoload.php');

class Ide_import_and_configure extends Module
{
    public $_errors=[];
    public $_success=[];



    public function __construct()
    {
        $this->name = 'ide_import_and_configure';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alaa DRIDI';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('import ide');
        $this->description = $this->l('Module of import ide ');
        $this->_errors=[];
        $this->_success=[];

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {


        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('actionObjectAddAfter') &&
            $this->registerHook('displayBackOfficeHeader')&&
              $this->installTab();
    }

    public function uninstall()
    {


        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall() &&  $this->uninstallTab();
    }
    //Add product when ide inserted in database
    public function hookActionObjectAddAfter($params){

       $object = $params['object'];
       $default_home_category = Configuration::get('PS_HOME_CATEGORY');
       if(get_class($object)=='Ide' && empty($object->fk_id_product)){
           $product_object = new Product();
           $product_object->name =$object->name;
           $product_object->id_category_default = (int)$default_home_category;
           if($product_object->add()){
               $product_object->addToCategories($default_home_category);
               $object->fk_id_product =  $product_object->id;
               $object->save();
           }
       }
   }

   //add tab in BO
    private function installTab()
    {
        $tabId = (int) Tab::getIdFromClassName('AdminIde');
        if (!$tabId) {
            $tabId = null;
        }

        $tab = new Tab($tabId);
        $tab->active = 1;
        $tab->class_name = 'AdminIde';

        $tab->name = array();
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->trans('Ide list ', array(), 'Modules.ide_import_and_configure.Admin', $lang['locale']);
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('DEFAULT');
        $tab->module = $this->name;

        return $tab->save();
    }
 //Remove tab From BO
    private function uninstallTab()
    {
        $tabId = (int) Tab::getIdFromClassName('AdminIde');
        if (!$tabId) {
            return true;
        }

        $tab = new Tab($tabId);

        return $tab->delete();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitIde_import_and_configureModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
     protected function getConfigForm()
      {


          return array(
              'form' => array(
                  'legend' => array(
                      'title' => $this->l('Settings'),
                      'icon' => 'icon-cogs',
                  ),
                  'input' => array(

                      array(
                          'col' => 9,
                          'type' => 'file',
                          'desc' => $this->l('List des ide'),
                          'name' => 'IDE_CSV',
                          'label' => $this->l('List csv ide'),

                      ),


                  ),
                  'submit' => array(
                      'title' => $this->l('Uploads'),
                  ),
              ),
          );
      }


      /**
       * Load the configuration form
       */
       public function getContent()
      {
          /**
           * If values have been submitted in the form, process.
           */
          if (((bool)Tools::isSubmit('submitIde_import_and_configureModule')) == true) {
              $this->postProcess();
          }

          $this->context->smarty->assign('module_dir', $this->_path);
          $this->context->smarty->assign('errors', $this->_errors);
          $this->context->smarty->assign('success', $this->_success);

          $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

          return $output.$this->renderForm();
      }



    /**
     * Save form data.
     */
     protected function postProcess()
      {
          $file_attachment=$_FILES['IDE_CSV'];
          $extension = array('.csv');



          if(!empty($file_attachment['name']) && $file_attachment['size']!=0){
              if (!@filemtime($file_attachment['tmp_name']) ||
                  !@move_uploaded_file($file_attachment['tmp_name'], _PS_MODULE_DIR_.'ide_import_and_configure/import/'.$file_attachment['name'])) {
                  $this->_errors[]= 'An error occurred while uploading / copying the file.';
              } else {
                  //File was uploaded
                  $this->importFromCsv( _PS_MODULE_DIR_.'ide_import_and_configure/import/'.$file_attachment['name']);
                  @unlink(_PS_MODULE_DIR_.'ide_import_and_configure/import/'.$file_attachment['name']);
                  $this->_success[]= 'Import was completed .';

              }
          }else{
              $this->_errors[] = Tools::displayError('An error occurred during the file-upload process.');
          }


      }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
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
   // import Data from csv
    public function importFromCsv($file){
        $languages = Language::getLanguages();


        if (($open = fopen($file, "r")) !== FALSE)
        {
            $line = 0;
            while (($data = fgetcsv($open, 1000, ";")) !== FALSE)
            {
                $line+=1;
                if($line>1){
                    //ide table
                    $name = $data[2];
                    $is_free = $data[5];
                    $manifacturer = $data[6];
                    $version = $data[10];
                    //product table
                    $id_ps = (int)$data[1];
                    $id_ide = (int)$data[0];
                    $prix_ttc = $data[7];
                    $tax = $data[8];
                    $rate = str_replace('%','0',str_replace(',','.',$tax));
                    $id_tax = $this->applicableTaxeId($rate);
                    $qty = $data[9];
                    //ide_lan
                    $description = array('fr'=>$data[3],'en'=>$data[4]);


                    //create prestashop product
                    $product_object = new Product($id_ps);
                    foreach ($languages as $lan){

                        $product_object->name[$lan['id_lang']] = $name;
                    }
                    $percent = 1 + ((float)$rate/100);
                    $prix_ht = (float)($prix_ttc / $percent);


                    $product_object->price = is_null($prix_ht)?0:Tools::ps_round($prix_ht,2);

                    $product_object->id_tax_rules_group = $id_tax;


                    if(empty($product_object->id_category_default)){
                        $default_home_category = Configuration::get('PS_HOME_CATEGORY');
                      $product_object->id_category_default = $default_home_category;
                      $product_object->addToCategories($default_home_category);
                    }

                    $product_object->save();
                    StockAvailable::setQuantity($product_object->id, 0, $qty);

                    //create ide object
                    $ide_object = new Ide($id_ide);
                    $ide_object->name = $name;
                    $ide_object->fk_id_product = $product_object->id;
                    $ide_object->is_free = $is_free;
                    $ide_object->version = $version;
                    $ide_object->manifacturer = $manifacturer;

                    foreach ($languages as $lan){
                      if(array_key_exists(strtolower($lan['iso_code']),$description)){
                          $ide_object->description[$lan['id_lang']] = $description[$lan['iso_code']];
                      }

                    }
                    $ide_object->save();

                }
            }

            fclose($open);
        }
    }
// Get ipplicable taxe ID
     public function applicableTaxeId($taxe_rate)
    {
        $taxes = Tax::getTaxes($this->context->language->id,true);

        foreach ($taxes as $key => $taxe) {
          if((float)$taxe['rate']!=$taxe_rate){
            unset($taxes[$key]);
          }
        }
        if(!empty($taxes)){

          usort($taxes, function($a, $b) {
              return $a['id_tax'] <=> $b['id_tax'];
          });
        }
        return $taxes[0]['id_tax'];
    }
}
