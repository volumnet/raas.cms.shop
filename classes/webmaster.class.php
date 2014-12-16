<?php
namespace RAAS\CMS\Shop;
use \RAAS\Application;
use \RAAS\CMS\Snippet;
use \RAAS\CMS\Snippet_Folder;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Material_Field;
use \RAAS\CMS\Form_Field;
use \RAAS\CMS\Page;

class Webmaster extends \RAAS\CMS\Webmaster
{
    protected static $instance;

    public function __get($var)
    {
        switch ($var) {
            default:
                return \RAAS\CMS\Shop\Module::i()->__get($var);
                break;
        }
    }


    /**
     * Создаем стандартные сниппеты
     */
    public function checkStdSnippets()
    {
        $Item = Snippet::importByURN('__RAAS_shop_cart_interface');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_cart_interface', 'locked' => 1));
        }
        $Item->name = $this->view->_('CART_STANDARD_INTERFACE');
        $Item->description = $this->stdCartInterface;
        $Item->commit();

        $Item = Snippet::importByURN('__RAAS_shop_order_notify');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_order_notify', 'locked' => 1));
        }
        $Item->name = $this->view->_('ORDER_STANDARD_NOTIFICATION');
        $Item->description = $this->stdFormTemplate;
        $Item->commit();

        $Item = Snippet::importByURN('__RAAS_shop_imageloader_interface');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_imageloader_interface', 'locked' => 1));
        }
        $Item->name = $this->view->_('IMAGELOADER_STANDARD_INTERFACE');
        $Item->description = $this->stdImageLoaderInterfaceFile;
        $Item->commit();

        $Item = Snippet::importByURN('__RAAS_shop_priceloader_interface');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_priceloader_interface', 'locked' => 1));
        }
        $Item->name = $this->view->_('PRICELOADER_STANDARD_INTERFACE');
        $Item->description = $this->stdPriceLoaderInterfaceFile;
        $Item->commit();

        $Item = Snippet::importByURN('__RAAS_shop_yml_interface');
        if (!$Item->id) {
            $Item = new Snippet(array('pid' => Snippet_Folder::importByURN('__RAAS_interfaces')->id, 'urn' => '__RAAS_shop_yml_interface', 'locked' => 1));
        }
        $Item->name = $this->view->_('YML_STANDARD_INTERFACE');
        $Item->description = file_get_contents($this->resourcesDir . '/yml_interface.php');
        $Item->commit();
    }


    public function createWidgets() 
    {
        // Добавим виджеты
        $snippets = array(
            'cart' => $this->view->_('CART'), 
            // 'yml' => $this->view->_('YANDEX_MARKET'),
        );
        $VF = Snippet_Folder::importByURN('__RAAS_views');
        foreach ($snippets as $urn => $name) {
            $temp = Snippet::importByURN($urn);
            if (!$temp->id) {
                $S = new Snippet();
                $S->name = $this->view->_($name);
                $S->urn = $urn;
                $S->pid = $VF->id;
                $f = $this->resourcesDir . '/' . $urn . '.tmp.php';
                $S->description = file_get_contents($f);
                $S->commit();
            }
        }

        $temp = Material_Type::importByURN('catalog');
        if (!$temp->id) {
            $MT = new Material_Type();
            $MT->name = $this->view->_('CATALOG');
            $MT->urn = 'catalog';
            $MT->global_type = 1;
            $MT->commit();

            $F = new Material_Field();
            $F->pid = $MT->id;
            $F->name = $this->view->_('ARTICLE');
            $F->urn = 'article';
            $F->datatype = 'text';
            $F->show_in_table = 1;
            $F->commit();
            $articleCol = $F;

            $F = new Material_Field();
            $F->pid = $MT->id;
            $F->name = $this->view->_('PRICE');
            $F->urn = 'price';
            $F->datatype = 'number';
            $F->show_in_table = 1;
            $F->commit();
            $priceCol = $F;

            $F = new Material_Field();
            $F->pid = $MT->id;
            $F->name = $this->view->_('IMAGE');
            $F->multiple = 1;
            $F->urn = 'images';
            $F->datatype = 'image';
            $F->commit();
        }


        $S = Snippet::importByURN('__RAAS_shop_order_notify');
        $FRM = new \RAAS\CMS\Form();
        $FRM->name = $this->view->_('ORDER_FORM');
        $FRM->signature = 0;
        $FRM->antispam = 'hidden';
        $FRM->antispam_field_name = '_name';
        $FRM->interface_id = (int)$S->id;
        $FRM->commit();

        $F = new Form_Field();
        $F->pid = $FRM->id;
        $F->name = $this->view->_('YOUR_NAME');
        $F->urn = 'full_name';
        $F->required = 1;
        $F->datatype = 'text';
        $F->show_in_table = 1;
        $F->commit();

        $F = new Form_Field();
        $F->pid = $FRM->id;
        $F->name = $this->view->_('PHONE');
        $F->urn = 'phone';
        $F->datatype = 'text';
        $F->show_in_table = 1;
        $F->commit();

        $F = new Form_Field();
        $F->pid = $FRM->id;
        $F->name = $this->view->_('EMAIL');
        $F->urn = 'email';
        $F->datatype = 'text';
        $F->show_in_table = 1;
        $F->commit();

        $F = new Form_Field();
        $F->pid = $FRM->id;
        $F->name = $this->view->_('ORDER_COMMENT');
        $F->urn = 'description';
        $F->required = 1;
        $F->datatype = 'textarea';
        $F->show_in_table = 0;
        $F->commit();


        $OS = new Order_Status();
        $OS->name = $this->view->_('IN_PROGRESS');
        $OS->urn = 'progress';
        $OS->commit();

        $OS = new Order_Status();
        $OS->name = $this->view->_('COMPLETED');
        $OS->urn = 'completed';
        $OS->commit();

        $OS = new Order_Status();
        $OS->name = $this->view->_('CANCELED');
        $OS->urn = 'canceled';
        $OS->commit();

        $CT = new Cart_Type();
        $CT->name = $this->view->_('CART');
        $CT->urn = 'cart';
        $CT->form_id = $FRM->id;
        $CT->no_amount = 0;
        $CT->mtypes = array(array('id' => $MT->id, 'price_id' => $priceCol->id));
        $CT->commit();

        $CT2 = new Cart_Type();
        $CT2->name = $this->view->_('FAVORITES');
        $CT2->urn = 'favorites';
        $CT2->form_id = 0;
        $CT2->no_amount = 1;
        $CT2->mtypes = array(array('id' => $MT->id, 'price_id' => $priceCol->id));
        $CT2->commit();
        
        $Site = array_shift(Page::getSet(array('where' => "NOT pid")));
        if (!Page::getSet(array('where' => array("pid = " . (int)$Site->id, "urn = 'catalog'")))) {
            $catalog = $this->createPage(array('name' => $this->view->_('CATALOG'), 'urn' => 'catalog', 'cache' => 0, 'response_code' => 200), $Site);
        }

        if (!Page::getSet(array('where' => array("pid = " . (int)$Site->id, "urn = 'cart'")))) {
            $cart = $this->createPage(array('name' => $this->view->_('CART'), 'urn' => 'cart', 'cache' => 0, 'response_code' => 200), $Site);
            $I = Snippet::importByURN('__RAAS_shop_cart_interface');
            $S = Snippet::importByURN('cart');
            $B = new Block_Cart();
            $B->location = 'content';
            $B->vis = 1;
            $B->author_id = $B->editor_id = Application::i()->user->id;
            $B->cats = array($cart->id);
            $B->cart_type = $CT ? $CT->id : 0;
            $B->name = $this->view->_('CART');
            $B->widget_id = $S->id;
            $B->interface_id = $I->id;
            $B->commit();
        }

        if (!Page::getSet(array('where' => array("pid = " . (int)$Site->id, "urn = 'favorites'")))) {
            $favorites = $this->createPage(array('name' => $this->view->_('FAVORITES'), 'urn' => 'favorites', 'cache' => 0, 'response_code' => 200), $Site);
            $I = Snippet::importByURN('__RAAS_shop_cart_interface');
            $S = Snippet::importByURN('cart');
            $B = new Block_Cart();
            $B->location = 'content';
            $B->vis = 1;
            $B->author_id = $B->editor_id = Application::i()->user->id;
            $B->cats = array($favorites->id);
            $B->cart_type = $CT2 ? $CT2->id : 0;
            $B->name = $this->view->_('FAVORITES');
            $B->widget_id = $S->id;
            $B->interface_id = $I->id;
            $B->commit();
        }

        $IL = new ImageLoader();
        $IL->mtype = $MT->id;
        $IL->ufid = $articleCol->id;
        $IL->name = $this->view->_('DEFAULT_IMAGELOADER');
        $IL->sep_string = '_';
        $IL->interface_id = (int)Snippet::importByURN('__RAAS_shop_imageloader_interface')->id;
        $IL->commit();

        $PL = new PriceLoader();
        $PL->mtype = $MT->id;
        $PL->ufid = $articleCol->id;
        $PL->name = $this->view->_('DEFAULT_PRICELOADER');
        $PL->interface_id = (int)Snippet::importByURN('__RAAS_shop_priceloader_interface')->id;
        $PL->commit();
    }
}