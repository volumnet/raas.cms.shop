<?php
namespace RAAS\CMS\Shop;
use \RAAS\Field as RAASField;
use \RAAS\CMS\Form as CMSForm;
use \RAAS\CMS\EditBlockForm;

class EditBlockCartForm extends EditBlockForm
{
    protected function getInterfaceField()
    {
        $field = parent::getInterfaceField();
        $snippet = Snippet::importByURN('__RAAS_shop_cart_interface');
        $field->default = $snippet->id;
        return $field;
    }


    protected function getInterfaceCodeField()
    {
        $field = parent::getInterfaceCodeField();
        $snippet = Snippet::importByURN('__RAAS_shop_cart_interface');
        $field->default = $snippet->description;
        return $field;
    }


    protected function getWidgetCodeField()
    {
        $field = parent::getWidgetCodeField();
        $field->default = Module::i()->stdCartView;
        return $field;
    }


    protected function getCommonTab()
    {
        $tab = parent::getCommonTab();
        $tab->children[] = new RAASField(array(
            'type' => 'select', 'name' => 'form', 'caption' => $this->_view->_('CART'), 'children' => array('Set' => Cart_Type::getSet())
        ));
        $tab->children[] = $this->getWidgetField();
        $tab->children[] = $this->getWidgetCodeField();
        return $tab;
    }


    protected function getServiceTab()
    {
        $tab = parent::getServiceTab();
        $tab->children[] = $this->getInterfaceField();
        $tab->children[] = $this->getInterfaceCodeField();
        return $tab;
    }
}