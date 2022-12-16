<?php

class Ide extends Product
{

   public $is_free;
   public $version;
   public $name;
   public $manifacturer;
   public $image_path;
   public $description;
   public $fk_id_product;


   public static $definition = [
       'table' => 'ide',
       'primary' => 'id_ide',
       'multilang' => true,
       'fields' => [
           'fk_id_product' => ['type' => self::TYPE_INT],
           'is_free' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
           'version' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
           'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
           'manifacturer' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => false],
           'image_path' => ['type' => self::TYPE_STRING, 'required' => false],

           // Lang fields

           'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml']

       ]
   ];

   //Delete created product on deleting IDE
   public function delete()
   {
     $product = new Product($this->fk_id_product);
     if($product->delete()){
       return parent::delete();
     }

   }

   /**
    * Get Ide products.
    *
    * @param int $id_lang Language id
    * @param int $pageNumber Start from (optional)
    * @param int $nbProducts Number of products to return (optional)
    *
    * @return array IDE products
    */
   public static function getIdeProducts($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, Context $context = null)
   {

       $now = date('Y-m-d') . ' 00:00:00';
       if (!$context) {
           $context = Context::getContext();
       }

       $front = true;
       if (!in_array($context->controller->controller_type, array('front', 'modulefront'))) {
           $front = false;
       }

       if ($page_number < 1) {
           $page_number = 1;
       }
       if ($nb_products < 1) {
           $nb_products = 10;
       }
       if (empty($order_by) || $order_by == 'position') {
           $order_by = 'date_add';
       }
       if (empty($order_way)) {
           $order_way = 'DESC';
       }
       if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
           $order_by_prefix = 'product_shop';
       } elseif ($order_by == 'name') {
           $order_by_prefix = 'pl';
       }
       if (!Validate::isOrderBy($order_by) || !Validate::isOrderWay($order_way)) {
           die(Tools::displayError());
       }

       $sql_groups = '';
       if (Group::isFeatureActive()) {
           $groups = FrontController::getCurrentCustomerGroups();
           $sql_groups = ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
               JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP')) . ')
               WHERE cp.`id_product` = p.`id_product`)';
       }

       if (strpos($order_by, '.') > 0) {
           $order_by = explode('.', $order_by);
           $order_by_prefix = $order_by[0];
           $order_by = $order_by[1];
       }

       $nb_days_new_product = (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT');

       $sql_ide = new DbQuery();
       $sql_ide->select('i.`fk_id_product`');
       $sql_ide->from('ide', 'i');
       $result_ide = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_ide);
       $id_ide = array();
       if(!empty( $result_ide)){
         foreach ($result_ide as $key => $value) {
          array_push($id_ide,$value['fk_id_product']);
         }

         if ($count) {

             return count($id_ide);
         }
         $sql = new DbQuery();
         $sql->select(
             'p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
             pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
             (DATEDIFF(product_shop.`date_add`,
                 DATE_SUB(
                     "' . $now . '",
                     INTERVAL ' . $nb_days_new_product . ' DAY
                 )
             ) > 0) as new'
         );

         $sql->from('product', 'p');
         $sql->join(Shop::addSqlAssociation('product', 'p'));
         $sql->leftJoin(
             'product_lang',
             'pl',
             '
             p.`id_product` = pl.`id_product`
             AND pl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl')
         );
         $sql->leftJoin('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id);
         $sql->leftJoin('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang);
         $sql->leftJoin('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');

         $sql->where('product_shop.`active` = 1');
         if ($front) {
             $sql->where('product_shop.`visibility` IN ("both", "catalog")');
         }
         $lis_id_ide = implode(',',$id_ide);
         $sql->where('p.`id_product` IN ('.$lis_id_ide.')');


         $sql->orderBy((isset($order_by_prefix) ? pSQL($order_by_prefix) . '.' : '') . '`' . pSQL($order_by) . '` ' . pSQL($order_way));
         $sql->limit($nb_products, (int) (($page_number - 1) * $nb_products));

         if (Combination::isFeatureActive()) {
             $sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
             $sql->leftJoin('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id);
         }
         $sql->join(Product::sqlStock('p', 0));

         $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

         if (!$result) {
             return false;
         }

         if ($order_by == 'price') {
             Tools::orderbyPrice($result, $order_way);
         }
         $products_ids = array();
         foreach ($result as $row) {
             $products_ids[] = $row['id_product'];
         }
         // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
         Product::cacheFrontFeatures($products_ids, $id_lang);

        return Product::getProductsProperties((int) $id_lang, $result);

       }

   }




}
