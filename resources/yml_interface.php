<?php
namespace RAAS\CMS\Shop;
use \SimpleXMLElement;
use \RAAS\CMS\Material;
use \RAAS\CMS\Material_Field;

ini_set('max_execution_time', 300);

$getValue = function(Material $Item, $key, array $settings = array()) {
    $v = $f = null;
    if ($settings['field']->id) {
        $v = $Item->fields[$settings['field']->urn]->doRich();
    } elseif ($settings['field_id']) {
        $v = $Item->{$settings['field_id']};
    }
    if (($v === null) && $settings['value']) {
        $v = $settings['value'];
    }
    if (isset($settings['callback']) && $settings['callback']) {
        $f = create_function('$x, $Field', $settings['callback']);
    } elseif (isset(Block_YML::$ymlFields[$key]['callback']) && Block_YML::$ymlFields[$key]['callback']) {
        $f = create_function('$x, $Field', Block_YML::$ymlFields[$key]['callback']);
    } elseif (isset(Block_YML::$ymlFields[$key]['type']) && (Block_YML::$ymlFields[$key]['type'] == 'number')) {
        $f = create_function('$x, $Field', 'return str_replace(",", ".", $x);');
    }
    if ($f) {
        $v = $f($v, $settings['field']->id ? $settings['field'] : null);
    }
    $v = preg_replace('/\\t+/umi', ' ', $v);
    $v = preg_replace('/ +/umi', ' ', $v);
    $v = trim($v);
    return $v;
};

$yml = new SimpleXMLElement('<' . '?xml version="1.0" encoding="UTF-8"?' . '><yml_catalog date="' . date('Y-m-d H:i') . '"></yml_catalog>');
$shop = $yml->addChild('shop');
foreach (array('name', 'company', 'url', 'platform', 'version', 'agency', 'email', 'currencies', 'categories', 'local_delivery_cost', 'cpa') as $key) {
    switch ($key) {
        case 'url':
            $shop->addChild($key, 'http://' . $_SERVER['HTTP_HOST']);
            break;
        case 'platform':
            $shop->addChild($key, 'RAAS.CMS');
            break;
        case 'version':
            $shop->addChild('version', '4.2');
            break;
        case 'currencies':
            $currencies = $shop->addChild('currencies');
            $currency = $currencies->addChild('currency');
            $currency->addAttribute('id', $config['default_currency']);
            $currency->addAttribute('rate', '1');

            foreach ((array)$Block->currencies as $key => $row) {
                $currency = $currencies->addChild('currency');
                $currency->addAttribute('id', $key);
                $currency->addAttribute('rate', trim(str_replace(',', '.', $row['rate'])));
                if (isset($row['plus'])) {
                    $currency->addAttribute('plus', trim(number_format((float)$row['plus'], 2, '.', '')));
                }
            }
            break;
        case 'categories':
            $categories = $shop->addChild('categories');
            foreach ((array)$Block->catalog_cats as $row) {
                $category = $categories->addChild('category', trim($row->name));
                $category->addAttribute('id', (int)$row->id);
                if ($row->pid && in_array($row->pid, $Block->catalog_cats_ids)) {
                    $category->addAttribute('parentId', (int)$row->pid);
                }
            }
            break;
        case 'local_delivery_cost':
            if (isset($config[$k])) {
                $v = $shop->addChild($key, str_replace(',', '.', trim($config[$key])));
            }
            break;
        default:
            $k = (($key == 'name') ? 'shop_name' : $key);
            if (isset($config[$k])) {
                $v = $shop->addChild($key, trim($config[$k]));
            }
            break;
    }
}

$offers = $shop->addChild('offers');
foreach ($Block->types as $mtype) {
    $SQL_query = "SELECT tM.* FROM " . Material::_tablename() . " AS tM ";
    if (!$mtype->global_type) {
        $SQL_query .= " JOIN " . Material::_dbprefix() . "cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id";
    }
    $SQL_query .= " WHERE tM.vis AND tM.pid IN (" . implode(", ", array_merge(array((int)$mtype->id), (array)$mtype->all_children_ids)) . ") ";
    if (!$mtype->global_type) {
        $SQL_query .= " AND tMPA.pid IN (" . implode(", ", $Block->catalog_cats_ids) . ")";
    }
    $SQL_result = Material::_SQL()->get($SQL_query);
    $ignoredFields = array();
    foreach ($mtype->settings['fields'] as $arr) {
        $ignoredFields[] = ($arr['field']->id ?: $arr['field_id']);
    }
    if ($mtype->settings['params'] || $mtype->settings['param_exceptions']) {
        foreach ($mtype->settings['params'] as $arr) {
            $ignoredFields[] = ($arr['field']->id ?: $arr['field_id']);
        }
        if ($mtype->settings['param_exceptions']) {
            foreach ((array)$mtype->settings['ignored'] as $row) {
                if ($row instanceof Material_Field) {
                    $ignoredFields[] = $row->id;
                } else {
                    $ignoredFields[] = $row;
                }
            }
        }
    }
    $ignoredFields = array_unique($ignoredFields);
    $ignoredFields = array_filter($ignoredFields);
    $ignoredFields = array_values($ignoredFields);

    foreach ($SQL_result as $row) {
        $row = new Material($row);
        $offer = $offers->addChild('offer');
        $offer->addAttribute('id', (int)$row->id);
        $offer->addAttribute('type', $mtype->settings['type']);
        $temp = array_merge(Block_YML::$defaultFields[0], (array)Block_YML::$ymlTypes[$mtype->settings['type']], Block_YML::$defaultFields[1]);
        foreach ($temp as $key) {
            switch ($key) {
                case 'url':
                    $offer->addChild('url', 'http://' . $_SERVER['HTTP_HOST'] . $row->url);
                    break;
                case 'categoryId':
                    if ($mtype->global_type) {
                        $cats = array_intersect($row->parents_ids, $Block->catalog_cats_ids);
                        $val = array_shift($cats);
                        $offer->addChild('categoryId', $val);
                    } else {
                        $cats = array_intersect($row->pages_ids, $Block->catalog_cats_ids);
                        foreach ($cats as $val) {
                            $offer->addChild('categoryId', $val);
                        }
                    }
                    break;
                default:
                    if (isset($mtype->settings['fields'][$key]) && ($arr = $mtype->settings['fields'][$key])) {
                        $v = $getValue($row, $key, $arr);
                        if ($key == 'description') {
                            $v = strip_tags($v);
                            $v = html_entity_decode($v, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                            $v = preg_replace('/(\\r|\\n)+/umi', ' ', $v);
                            $v = \SOME\Text::cuttext($v, 512, '...');
                        }
                        if (trim($v) !== '') {
                            if (in_array($key, array('available', 'bid', 'cbid'))) {
                                $offer->addAttribute($key, trim($v));
                            } else {
                                $offer->addChild($key, trim($v));
                            }
                        }
                    }
                    break;
            }
        }
        
        if ($mtype->settings['params'] || $mtype->settings['param_exceptions']) {
            $temp = $mtype->settings['params'];
            if ($mtype->settings['param_exceptions']) {
                foreach (array('name', 'description') as $key) {
                    if (!in_array($key, $ignoredFields)) {
                        $arr = array();
                        $arr['field_id'] = $key;
                        if ($mtype->settings['params_callback']) {
                            $arr['params_callback'] = $mtype->settings['params_callback'];
                        }
                        $arr['auto'] = true;
                        $temp[] = $arr;
                    }
                }
                foreach ($mtype->fields as $f) {
                    if (!in_array($f->id, $ignoredFields)) {
                        $arr = array();
                        $arr['field'] = $f;
                        if ($mtype->settings['params_callback']) {
                            $arr['params_callback'] = $mtype->settings['params_callback'];
                        }
                        $arr['auto'] = true;
                        $temp[] = $arr;
                    }
                }
            }
            foreach ($temp as $arr) {
                if ($arr) {
                    $v = $getValue($row, $key, $arr);
                    if (trim($v) !== '') {
                        $param = $offer->addChild('param', $v);
                        if ($arr['name']) {
                            $param->addAttribute('name', $arr['name']);
                        } elseif ($arr['field']->id) {
                            $param->addAttribute('name', $arr['field']->name);
                        } elseif ($arr['field_id'] == 'name') {
                            $param->addAttribute('name', NAME);
                        } elseif ($arr['field_id'] == 'description') {
                            $param->addAttribute('name', DESCRIPTION);
                        } else {
                            $param->addAttribute('name', $arr['field_id']);
                        }
                        if ($arr['unit']) {
                            $param->addAttribute('unit', $arr['unit']);
                        }
                    }
                }
            }
            
        }
    }
    
}

$OUT = array('yml' => $yml);
return $OUT;