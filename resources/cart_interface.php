<?php
namespace RAAS\CMS\Shop;
use \RAAS\CMS\Material;
use \RAAS\Redirector;
use \RAAS\CMS\DATETIMEFORMAT;
use \RAAS\CMS\Material_Field;

$convertMeta = function($x)
{
    return $x;
};

$notify = function(Order $Item)
{
    $temp = array_values(array_filter(array_map('trim', preg_split('/(;|,)/', $Item->parent->Form->email))));
    $emails = $userEmails = array();
    $sms = array();
    foreach ($temp as $row) {
        if (($row[0] == '[') && ($row[strlen($row) - 1] == ']')) {
            $sms[] = substr($row, 1, -1);
        } else {
            $emails[] = $row;
        }
    }
    foreach ($Item->fields as $key => $val) {
        if ((($val->datatype == 'email') || ($key == 'email')) && $Item->$key) {
            $userEmails = array_merge($userEmails, array_values(array_filter(array_map('trim', preg_split('/(;|,)/', $Item->$key)))));
        }
    }
    if ($Item->parent->Form->Interface->id) {
        $template = $Item->parent->Form->Interface->description;
    } else {
        $template = $Item->parent->Form->description;
    }

    ob_start();
    $forUser = false;
    eval('?' . '>' . $template);
    $message = ob_get_contents();
    ob_end_clean();

    ob_start();
    $forUser = true;
    eval('?' . '>' . $template);
    $userMessage = ob_get_contents();
    ob_end_clean();

    ob_start();
    $SMS = true;
    eval('?' . '>' . $template);
    $message_sms = ob_get_contents();
    ob_end_clean();


    $subject = date(DATETIMEFORMAT) . ' ' . sprintf(ORDER_STANDARD_HEADER, $Item->parent->name, $Item->page->name);
    $userSubject = date(DATETIMEFORMAT) . ' ' . sprintf(ORDER_STANDARD_HEADER_USER, $Item->id, $_SERVER['HTTP_HOST']);
    if ($emails) {
        \RAAS\Application::i()->sendmail($emails, $subject, $message, 'info@' . $_SERVER['HTTP_HOST'], 'RAAS.CMS');
    }
    if ($userEmails) {
        \RAAS\Application::i()->sendmail($userEmails, $userSubject, $userMessage, 'info@' . $_SERVER['HTTP_HOST'], 'RAAS.CMS');
    }
    if ($sms) {
        \RAAS\Application::i()->sendmail($sms, $subject, $message_sms, 'info@' . $_SERVER['HTTP_HOST'], 'RAAS.CMS', false);
    }
};

$OUT = array();
$Cart_Type = new Cart_Type((int)$config['cart_type']);
$Cart = new Cart($Cart_Type, \RAAS\Controller_Frontend::i()->user);
switch (isset($_GET['action']) ? $_GET['action'] : '') {
    case 'set':
        $Item = new Material((int)(isset($_GET['id']) ? $_GET['id'] : ''));
        $amount = isset($_GET['amount']) ? $_GET['amount'] : 0;
        $meta = isset($_GET['meta']) ? $_GET['meta'] : '';
        if ($Item->id) {
            $Cart->set($Item, $amount, $meta);
        }
        new Redirector($_GET['back'] ? 'history:back' : \SOME\HTTP::queryString('action=&id=&meta=&amount='));
        break;
    case 'add':
        $Item = new Material((int)(isset($_GET['id']) ? $_GET['id'] : ''));
        $amount = isset($_GET['amount']) ? $_GET['amount'] : 1;
        $meta = isset($_GET['meta']) ? $_GET['meta'] : '';
        if ($Item->id && $amount) {
            $Cart->add($Item, $amount, $meta);
        }
        new Redirector($_GET['back'] ? 'history:back' : \SOME\HTTP::queryString('action=&id=&meta=&amount='));
        break;
    case 'reduce':
        $Item = new Material((int)(isset($_GET['id']) ? $_GET['id'] : ''));
        $amount = isset($_GET['amount']) ? $_GET['amount'] : 1;
        $meta = isset($_GET['meta']) ? $_GET['meta'] : '';
        if ($Item->id && $amount) {
            $Cart->reduce($Item, $amount, $meta);
        }
        new Redirector($_GET['back'] ? 'history:back' : \SOME\HTTP::queryString('action=&id=&meta=&amount='));
        break;
    case 'delete':
        $Item = new Material((int)(isset($_GET['id']) ? $_GET['id'] : ''));
        $meta = isset($_GET['meta']) ? $_GET['meta'] : '';
        if ($Item->id) {
            $Cart->set($Item, 0, $meta);
        }
        new Redirector($_GET['back'] ? 'history:back' : \SOME\HTTP::queryString('action=&id=&meta=&amount='));
        break;
    case 'clear':
        $Cart->clear();
        new Redirector($_GET['back'] ? 'history:back' : \SOME\HTTP::queryString('action=&id=&meta=&amount='));
        break;
    default:
        $Form = $Cart_Type->Form;
        if (isset($_POST['amount'])) {
            foreach ($_POST['amount'] as $key => $val) {
                list($id, $meta) = explode('_', $key);
                $Item = new Material($id);
                $Cart->set($Item, (int)$val, $meta);
            }
        }
        if ($Form->id && $Cart->items) {
            $localError = array();
            if (($Form->signature && isset($_POST['form_signature']) && $_POST['form_signature'] == md5('form' . (int)$Form->id . (int)$Block->id)) || (!$Form->signature && ($_SERVER['REQUEST_METHOD'] == 'POST'))) {
                $Item = new Order();
                $Item->pid = (int)$Cart_Type->id;
                foreach ($Form->fields as $row) {
                    switch ($row->datatype) {
                        case 'file': case 'image':
                            if (!isset($_FILES[$row->urn]['tmp_name']) || !$row->isFilled($_FILES[$row->urn]['tmp_name'])) {
                                if ($row->required && !$row->countValues()) {
                                    $localError[$row->urn] = sprintf(ERR_CUSTOM_FIELD_REQUIRED, $row->name);
                                }
                            } elseif (isset($_FILES[$row->urn]['tmp_name']) && $row->isFilled($_FILES[$row->urn]['tmp_name'])) {
                                if (!$row->validate($_FILES[$row->urn]['tmp_name'])) {
                                    $localError[$row->urn] = sprintf(ERR_CUSTOM_FIELD_INVALID, $row->name);
                                }
                            }
                            break;
                        default:
                            if (!isset($_POST[$row->urn]) || !$row->isFilled($_POST[$row->urn])) {
                                if ($row->required) {
                                    $localError[$row->urn] = sprintf(ERR_CUSTOM_FIELD_REQUIRED, $row->name);
                                }
                            } elseif (isset($_POST[$row->urn]) && $row->isFilled($_POST[$row->urn])) {
                                if (($row->datatype == 'password') && ($_POST[$row->urn] != $_POST[$row->urn . '@confirm'])) {
                                    $localError[$row->urn] = sprintf(ERR_CUSTOM_PASSWORD_DOESNT_MATCH_CONFIRM, $row->name);
                                } elseif (!$row->validate($_POST[$row->urn])) {
                                    $localError[$row->urn] = sprintf(ERR_CUSTOM_FIELD_INVALID, $row->name);
                                }
                            }
                            break;
                    }
                }
                if ($Form->antispam && $Form->antispam_field_name) {
                    switch ($Form->antispam) {
                        case 'captcha':
                            if (!isset($_POST[$Form->antispam_field_name], $_SESSION['captcha_keystring']) || ($_POST[$Form->antispam_field_name] != $_SESSION['captcha_keystring'])) {
                                $localError[$row->urn] = ERR_CAPTCHA_FIELD_INVALID;
                            }
                            break;
                        case 'hidden':
                            if (isset($_POST[$Form->antispam_field_name]) && $_POST[$Form->antispam_field_name]) {
                                $localError[$row->urn] = ERR_CAPTCHA_FIELD_INVALID;
                            }
                            break;
                    }
                }
                if (!$localError) {
                    if ((\RAAS\Controller_Frontend::i()->user instanceof \RAAS\CMS\User) && \RAAS\Controller_Frontend::i()->user->id) {
                        $Item->uid = (int)\RAAS\Controller_Frontend::i()->user->id;
                    } else {
                        $Item->uid = 0;
                    }
                    // Для AJAX'а
                    //$Referer = \RAAS\CMS\Page::importByURL($_SERVER['HTTP_REFERER']);
                    //$Item->page_id = (int)$Referer->id;
                    $Item->page_id = (int)$Page->id;
                    $Item->ip = (string)$_SERVER['REMOTE_ADDR'];
                    $Item->user_agent = (string)$_SERVER['HTTP_USER_AGENT'];

                    if ($Item instanceof Order) {
                        $temp = array();
                        foreach ($Cart->items as $row) {
                            if ($row->amount) {
                                $m = new Material($row->id);
                                $priceURN = $Cart->getPriceURN($m->material_type);
                                $price = number_format($row->{$priceURN}, 2, '.', '');
                                $temp[] = array(
                                    'material_id' => $row->id,
                                    'name' => $row->name,
                                    'meta' => $convertMeta($row->meta),
                                    'realprice' => number_format($row->realprice, 2, '.', ''),
                                    'amount' => (int)$row->amount
                                );
                            }
                        }
                        $Item->meta_items = $temp;
                    }
                    $Item->commit();
                    foreach ($Item->fields as $fname => $temp) {
                        if (isset($Item->fields[$fname])) {
                            $row = $Item->fields[$fname];
                            switch ($row->datatype) {
                                case 'file': case 'image':
                                    $row->deleteValues();
                                    if ($row->multiple) {
                                        foreach ($_FILES[$row->urn]['tmp_name'] as $key => $val) {
                                            $row2 = array(
                                                'vis' => (int)$_POST[$row->urn . '@vis'][$key],
                                                'name' => (string)$_POST[$row->urn . '@name'][$key],
                                                'description' => (string)$_POST[$row->urn . '@description'][$key],
                                                'attachment' => (int)$_POST[$row->urn . '@attachment'][$key]
                                            );
                                            if (is_uploaded_file($_FILES[$row->urn]['tmp_name'][$key]) && $row->validate($_FILES[$row->urn]['tmp_name'][$key])) {
                                                $att = new Attachment((int)$row2['attachment']);
                                                $att->upload = $_FILES[$row->urn]['tmp_name'][$key];
                                                $att->filename = $_FILES[$row->urn]['name'][$key];
                                                $att->mime = $_FILES[$row->urn]['type'][$key];
                                                $att->parent = $Material;
                                                if ($row->datatype == 'image') {
                                                    $att->image = 1;
                                                    if ($temp = (int)$this->package->registryGet('maxsize')) {
                                                        $att->maxWidth = $att->maxHeight = $temp;
                                                    }
                                                    if ($temp = (int)$this->package->registryGet('tnsize')) {
                                                        $att->tnsize = $temp;
                                                    }
                                                }
                                                $att->commit();
                                                $row2['attachment'] = (int)$att->id;
                                                $row->addValue(json_encode($row2));
                                            } elseif ($row2['attachment']) {
                                                $row->addValue(json_encode($row2));
                                            }
                                            unset($att, $row2);
                                        }
                                    } else {
                                        $row2 = array(
                                            'vis' => (int)$_POST[$row->urn . '@vis'],
                                            'name' => (string)$_POST[$row->urn . '@name'],
                                            'description' => (string)$_POST[$row->urn . '@description'],
                                            'attachment' => (int)$_POST[$row->urn . '@attachment']
                                        );
                                        if (is_uploaded_file($_FILES[$row->urn]['tmp_name']) && $row->validate($_FILES[$row->urn]['tmp_name'])) {
                                            $att = new Attachment((int)$row2['attachment']);
                                            $att->upload = $_FILES[$row->urn]['tmp_name'];
                                            $att->filename = $_FILES[$row->urn]['name'];
                                            $att->mime = $_FILES[$row->urn]['type'];
                                            $att->parent = $Material;
                                            if ($row->datatype == 'image') {
                                                $att->image = 1;
                                                if ($temp = (int)$this->package->registryGet('maxsize')) {
                                                    $att->maxWidth = $att->maxHeight = $temp;
                                                }
                                                if ($temp = (int)$this->package->registryGet('tnsize')) {
                                                    $att->tnsize = $temp;
                                                }
                                            }
                                            $att->commit();
                                            $row2['attachment'] = (int)$att->id;
                                            $row->addValue(json_encode($row2));
                                        } elseif ($_POST[$row->urn . '@attachment']) {
                                            $row2['attachment'] = (int)$_POST[$row->urn . '@attachment'];
                                            $row->addValue(json_encode($row2));
                                        }
                                        unset($att, $row2);
                                    }
                                    break;
                                default:
                                    $row->deleteValues();
                                    if (isset($_POST[$row->urn])) {
                                        foreach ((array)$_POST[$row->urn] as $val) {
                                            $row->addValue($val);
                                        }
                                    }
                                    break;
                            }
                            if (in_array($row->datatype, array('file', 'image'))) {
                                $row->clearLostAttachments();
                            }
                        }
                    }
                    $Cart->clear();
                    $notify($Item);
                    if ($_POST['epay'] != 1) {
                        $OUT['success'][(int)$Block->id] = true;
                    }
                }
            }
            $OUT['localError'] = $localError;
            $OUT['DATA'] = $_POST;
            $OUT['Item'] = $Item;
        }
        $OUT['Form'] = $Form;
        break;
}
if (isset($_GET['back'])) {
    new Redirector('history:back');
}

$OUT['Cart'] = $Cart;
$OUT['Cart_Type'] = $Cart_Type;
$OUT['convertMeta'] = $convertMeta;
if ($Block->EPay_Interface->id) {
    eval('?' . '>' . $Block->EPay_Interface->description);
}
return $OUT;
