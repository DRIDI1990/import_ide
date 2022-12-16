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

//add table of module
$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ide` (
    `id_ide` int(11) NOT NULL AUTO_INCREMENT,
    `fk_id_product` int(11) NOT NULL,
    `is_free` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
    `version` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `manifacturer` varchar(255),
    `image_path` varchar(255),
    PRIMARY KEY  (`id_ide`),
    KEY `fk_product` (`fk_id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ide_lang` (
    `id_ide` int(11) unsigned NOT NULL,
    `id_lang` int(10) unsigned NOT NULL,
    `description` mediumtext ,
     PRIMARY KEY (`id_ide`,`id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Add meta page for listing IDE
foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
$sql_meta=Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'meta (`page`, `configurable`) VALUES ("module-ide_import_and_configure-idelisting", 1)');

$id_meta_listing_page = Db::getInstance()->Insert_ID();
$languages = Language::getLanguages(false, $this->context->shop->id);
foreach ($languages as $lang) {
		        Db::getInstance()->Execute(
                   'INSERT INTO `'._DB_PREFIX_.'meta_lang` (`id_meta`, `id_shop`, `id_lang`, `title`, `url_rewrite`)
                    VALUES ('.$id_meta_listing_page.','.$this->context->shop->id.','.$lang['id_lang'].',"List ide","ide-list")'
                   );
}
