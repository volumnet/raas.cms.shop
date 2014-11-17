<?php
namespace RAAS\CMS\Shop;

class EditOrderStatusForm extends \RAAS\Form
{
    public function __get($var)
    {
        switch ($var) {
            case 'view':
                return ViewSub_Dev::i();
                break;
            default:
                return parent::__get($var);
                break;
        }
    }


    public function __construct(array $params = array())
    {
        $view = $this->view;
        $t = Module::i();
        $Item = isset($params['Item']) ? $params['Item'] : null;
        
        $defaultParams = array(
            'caption' => $Item->id ? $Item->name : $this->view->_('EDIT_ORDER_STATUS'),
            'parentUrl' => Sub_Dev::i()->url . '&action=order_statuses',
            'children' => array(
                array('name' => 'name', 'caption' => $this->view->_('NAME'), 'required' => 'required'), 
                array('name' => 'urn', 'caption' => $this->view->_('URN')),
            )
        );
        $arr = array_merge($defaultParams, $params);
        parent::__construct($arr);
    }
}