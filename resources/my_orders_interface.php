<?php
namespace RAAS\CMS\Shop;
use \RAAS\Redirector;

if (!\RAAS\Controller_Frontend::i()->user->id) {
    new Redirector('/');
    exit;
}

$OUT = array();
$Item = null;
if ($_GET['id']) {
    $temp = new Order((int)$_GET['id']);
    if ($temp->uid = (int)\RAAS\Controller_Frontend::i()->user->id) {
        $Item = $temp;
    }
}

if ($Item) {
    switch ($_GET['action']) {
        case 'delete':
            if (!$Order->status_id && !$Order->paid && !$Order->vis) {
                Order::delete($Item);
            }
            new Redirector($_GET['back'] ? 'history:back' : \SOME\HTTP::queryString('id=&action='));
            break;
        default:
            $Page->oldName = $Page->name;
            $Page->Item = $Item;
            $Page->name = ORDER_NUMBER . ' ' $Item->id . ' ' . FROM . ' ' . date(DATETIME_FORMAT, strtotime($Item->post_date));
            $OUT['Item'] = $Item;
            break;
    }
} else {
    $Set = Order::getSet(array('where' => "uid = " . (int)\RAAS\Controller_Frontend::i()->user->id, 'orderBy' => 'id DESC'));
    $OUT['Set'] = $Set;
}
return $OUT;
