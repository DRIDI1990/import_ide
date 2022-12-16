<?php
/**
 *  Prestashop Module
 *
 * Module declaration
 *
 * @author Alaa DRIDI - https://www.sixtrone.com
 *
 */

require_once('./../vendor/autoload.php');

class AdminIdeController extends ModuleAdminController
{


    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = Ide::$definition['table'];
        $this->identifier = Ide::$definition['primary'];
        $this->className = Ide::class;
        $this->lang = false;
        $this->toolbar_title = 'IDE';


        parent::__construct();

        $this->fields_list = [
            'id_ide' => [
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->module->l('name'),
                'align' => 'left',
            ],

            'is_free' => [
                'title' => $this->module->l('Free'),
                'align' => 'left',
            ]
            ,
            'manifacturer' => [
                'title' => $this->module->l('manifacturer'),
                'align' => 'left',
            ]

        ];


        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    // render form iDE add & update
    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return;
        }
        $image = _PS_MODULE_DIR_ . '/ide_import_and_configure/views/img/' . $obj->image_path;

        $image_url = ImageManager::thumbnail(
            $image,
            $obj->image_path,
            350
        );
        $image_size = file_exists($image) ? filesize($image) / 1000 : false;
        $this->fields_form = [

            'legend' => [
                'title' => $this->module->l('Edit Ide'),
                'icon' => 'icon-cog'
            ],

            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('name'),
                    'name' => 'name',
                    'class' => 'input fixed-width-lg',
                    'size' => 600,
                    'required' => true,
                    'empty_message' => $this->l('Please fill the name'),
                    'hint' => $this->module->l('Enter IDE name')
                ],
                [
                    'type' => 'file',
                    'label' => $this->module->l('Ide picture '),
                    'name' => 'image_path',
                    'display_image' => true,
                    'image' => !is_null($obj->image_path) ? $image_url : false,
                    'size' => $image_size,
                    'hint' => $this->module->l('Ide picture '),
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Manifacturer'),
                    'name' => 'manifacturer',
                    'class' => 'input fixed-width-lg',
                    'size' => 500,
                    'empty_message' => $this->module->l('Fill the manifacturer '),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Is free'),
                    'name' => 'is_free',
                    'active' => 'is_free', 'class' => 'fixed-width-xs',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 0,
                            'label' => $this->l('Paid')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 1,
                            'label' => $this->l('Free')
                        )
                    )
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('Version'),
                    'name' => 'version',
                    'class' => 'input fixed-width-lg',
                    'size' => 500,
                    'empty_message' => $this->module->l('Fill the version '),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description'),
                    'name' => 'description',
                    'size' => 500,
                    'empty_message' => $this->module->l('Fill the version '),
                    'lang' => true,
                    'cols' => 60,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ],

            ],

            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];

        return parent::renderForm();
    }


    /**
     * Gestion de la toolbar
     */
    public function initPageHeaderToolbar()
    {

        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Add IDE'),
            'icon' => 'process-icon-new'
        );

        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitAddide')) {
            $uploaded_image = $_FILES['image_path'];
            if(!empty($uploaded_image['name']) && $uploaded_image['size']!=0){

                $file_object = array();
                $file_object['name'] = $_FILES['image_path']['name'];
                $file_object['type'] = $_FILES['image_path']['type'];
                $file_object['tmp_name'] = $_FILES['image_path']['tmp_name'];
                $file_object['error'] = $_FILES['image_path']['error'];
                $file_object['size'] = $_FILES['image_path']['size'];

                if ($error = ImageManager::validateUpload($file_object)) {
                    return $error;
                } else {
                    $ext = substr($file_object['name'], strrpos($file_object['name'], '.') + 1);
                    $file_name = md5($file_object['name']) . '.' . $ext;
                    if (!move_uploaded_file($file_object['tmp_name'], _PS_MODULE_DIR_ . 'ide_import_and_configure/views/img/' . $file_name)) {
                        return $this->displayError($this->trans('An error occurred while attempting to upload the file.', array(), 'Admin.Notifications.Error'));
                    } else {
                        $image = $file_name;
                        $_POST['image_path'] = $image;
                    }
                }

            } else {
                $_POST['image_path'] = '';
            }
           
            parent::postProcess();

        }
        if (Tools::getIsset('deleteide')) {
            $ide_id = Tools::getValue('id_ide');
            $ide = new Ide((int)$ide_id);
            $image_to_unlink = $ide->image_path;

            if ($ide->delete()) {

                if (file_exists(_PS_MODULE_DIR_ . 'ide_import_and_configure/views/img/' . $image_to_unlink)) {
                    @unlink(_PS_MODULE_DIR_ . 'ide_import_and_configure/views/img/' . $image_to_unlink);
                }
                $this->confirmations[] = $this->_conf[1];
            }
        }


    }


}
