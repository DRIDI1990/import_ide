<?php
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

require_once(_PS_MODULE_DIR_.'ide_import_and_configure/vendor/autoload.php');
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use ModuleIde\Adapter\IdeProductSearchProvider;
class ide_import_and_configureidelistingModuleFrontController extends ProductListingFrontController
{
  public $php_self = 'module-ide_import_and_configure-idelisting';


  public function initContent()
  {
      parent::initContent();

      $this->doProductSearch('catalog/listing/new-products');
  }

  //front labelling for page ide listing
  public function getListingLabel()
  {

      return $this->trans(
          'Produits: %category_name%',
          array('%category_name%' => 'ide'),
          'Shop.Theme.Catalog'
      );
  }
  //Query information
  protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setQueryType('module-ide_import_and_configure-idelisting')
            ->setSortOrder(new SortOrder('product', 'date_add', 'desc'));

        return $query;
    }
  //IDE Data provider 
  protected function getDefaultProductSearchProvider()
  {

    return new IdeProductSearchProvider(
        $this->getTranslator()

    );
  }

}
