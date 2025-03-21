<?php
/**
 * Отображение дополнительных полей заказа
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Application;
use RAAS\Field as RAASField;
use RAAS\CMS\Sub_Dev as CMSSubDev;

/**
 * Отображает поле
 * @param RAASField $field Поле для отображения
 */
$_RAASForm_Control = function (RAASField $field) {
    $Item = $field->Form->Item;
    switch ($field->name) {
        case 'status_id':
            echo '<strong>' .
                    htmlspecialchars($Item->status->id ? $Item->status->name : Module::i()->view->_('ORDER_STATUS_NEW')) .
                 '</strong>';
            break;
        case 'paid':
            if ($Item->paid) {
                echo '<strong class="text-success">' . Module::i()->view->_('PAYMENT_PAID');
            } else {
                echo '<strong class="text-error">' . Module::i()->view->_('PAYMENT_NOT_PAID');
            }
            echo '</strong>';
            break;
        case 'pid':
            if (Application::i()->user->root) {
                echo '<a href="' . Sub_Dev::i()->url . '&action=edit_cart_type&id=' . (int)$Item->pid . '" target="_blank">' .
                        htmlspecialchars($Item->parent->name) .
                     '</a>';
            } else {
                echo '<a href="' . Sub_Orders::i()->url . '&id=' . (int)$Item->pid . '" target="_blank">' .
                        htmlspecialchars($Item->parent->name) .
                     '</a>';
            }
            break;
        case 'payment_interface_id':
            if ($Item->payment_interface_classname) {
                echo Text::getClassCaption($Item->payment_interface_classname);
            } elseif ($Item->paymentInterface->id) {
                echo '<a href="' . CMSSubDev::i()->url . '&action=edit_snippet&id=' . (int)$Item->payment_interface_id . '" target="_blank">' .
                        htmlspecialchars($Item->paymentInterface->name) .
                     '</a>';
            }
            break;
        case 'payment_id':
            if ($Item->paymentInterface->id) {
                echo $Item->payment_id;
            }
            break;
    }
};
