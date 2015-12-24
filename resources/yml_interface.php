<?php
namespace RAAS\CMS\Shop;
use \SimpleXMLElement;
use \RAAS\CMS\Material;
use \RAAS\CMS\Material_Field;

ini_set('max_execution_time', 300);
// 2015-11-23, AVS: убрал, т.к. мешает кэшировать
// while (ob_get_level()) {
//     ob_end_clean();
// }

$getValue = function(Material $Item, $key, array $settings = array()) {
    if ($settings['field']->id) {
        if ($settings['field']->datatype == 'image') {
            if ($settings['field']->multiple) {
                $x = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $Item->{$settings['field']->urn}[0]->fileURL;
            } else {
                $x = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $Item->{$settings['field']->urn}->fileURL;
            }
        } else {
            $x = $Item->fields[$settings['field']->urn]->doRich();
        }
    } elseif ($settings['field_id']) {
        $x = $Item->{$settings['field_id']};
    }
    if (($x === null) && $settings['value']) {
        $x = $settings['value'];
    }
    if (isset($settings['callback']) && $settings['callback']) {
        $f = $settings['callback'];
    } elseif (isset(Block_YML::$ymlFields[$key]['callback']) && Block_YML::$ymlFields[$key]['callback']) {
        $f = Block_YML::$ymlFields[$key]['callback'];
    } elseif (isset(Block_YML::$ymlFields[$key]['type']) && (Block_YML::$ymlFields[$key]['type'] == 'number')) {
        $f = 'return str_replace(",", ".", $x);';
    }
    if ($f) {
        $Field = $settings['field'];
        $x = eval($f);
    }
    $x = preg_replace('/\\t+/umi', ' ', $x);
    $x = preg_replace('/ +/umi', ' ', $x);
    $x = trim($x);
    return $x;
};


header('Content-Type: application/xml');
echo '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' . "\n" . '<yml_catalog date="' . date('Y-m-d H:i') . '">';
echo '<shop>';
foreach (array('name', 'company', 'url', 'platform', 'version', 'agency', 'email', 'currencies', 'categories', 'local_delivery_cost', 'cpa') as $key) {
    switch ($key) {
        case 'url':
            echo '<' . $key . '>http://' . htmlspecialchars($_SERVER['HTTP_HOST']) . '</' . $key . '>';
            break;
        case 'platform':
            echo '<' . $key . '>RAAS.CMS</' . $key . '>';
            break;
        case 'version':
            echo '<' . $key . '>4.2</' . $key . '>';
            break;
        case 'currencies':
            echo '<currencies><currency id="' . htmlspecialchars($config['default_currency']) . '" rate="1" />';
            foreach ((array)$Block->currencies as $key => $row) {
                echo '<currency id="' . htmlspecialchars($key) . '" rate="' . htmlspecialchars(trim(str_replace(',', '.', $row['rate']))) . '"' . (isset($row['plus']) ? ' plus="' . htmlspecialchars(trim(number_format((float)$row['plus'], 2, '.', ''))) . '"' : '') . ' />';
            }
            echo '</currencies>';
            break;
        case 'categories':
            echo '<categories>';
            foreach ((array)$Block->catalog_cats as $row) {
                echo '<category id="' . (int)$row->id . '"' . (($row->pid && in_array($row->pid, $Block->catalog_cats_ids)) ? ' parentId="' . (int)$row->pid . '"' : '') . '>' . 
                        htmlspecialchars(trim($row->name)) . 
                     '</category>';
            }
            echo '</categories>';
            break;
        case 'local_delivery_cost':
            if (isset($config[$k])) {
                echo '<' . $key . '>' .  htmlspecialchars(str_replace(',', '.', trim($config[$key]))) . '</' . $key . '>';
            }
            break;
        default:
            $k = (($key == 'name') ? 'shop_name' : $key);
            if (isset($config[$k])) {
                echo '<' . $key . '>' . htmlspecialchars(trim($config[$k])) . '</' . $key . '>';
            }
            break;
    }
}

echo '<offers>';
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

    foreach ($SQL_result as $row2) {
        $row = new Material($row2);
        $offerTxt = '';
        $offerAttrs = '';
        $temp = array_merge(Block_YML::$defaultFields[0], (array)Block_YML::$ymlTypes[$mtype->settings['type']], Block_YML::$defaultFields[1]);
        foreach ($temp as $key) {
            switch ($key) {
                case 'url':
                    $offerTxt .= '<' . $key . '>http://' . $_SERVER['HTTP_HOST'] . $row->url . '</' . $key . '>';
                    break;
                case 'categoryId':
                    if ($mtype->global_type) {
                        $cats = array_intersect($row->parents_ids, $Block->catalog_cats_ids);
                        $val = array_shift($cats);
                        $offerTxt .= '<' . $key . '>' . (int)$val . '</' . $key . '>';
                    } else {
                        $cats = array_intersect($row->pages_ids, $Block->catalog_cats_ids);
                        foreach ($cats as $val) {
                            $offerTxt .= '<' . $key . '>' . (int)$val . '</' . $key . '>';
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
                                $offerAttrs .= ' ' . $key . '="' . htmlspecialchars(trim($v)) . '"';
                            } else {
                                $offerTxt .= '<' . $key . '>' . htmlspecialchars(trim($v)) . '</' . $key . '>';
                            }
                        }
                        unset($v);
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
                $paramAttrs = $v = '';
                if ($arr) {
                    $v = $getValue($row, $key, $arr);
                    if (trim($v) !== '') {
                        if ($arr['name']) {
                            $paramAttrs .= ' name="' . htmlspecialchars($arr['name']) . '"';
                        } elseif ($arr['field']->id) {
                            $paramAttrs .= ' name="' . htmlspecialchars($arr['field']->name) . '"';
                        } elseif ($arr['field_id'] == 'name') {
                            $paramAttrs .= ' name="' . htmlspecialchars(NAME) . '"';
                        } elseif ($arr['field_id'] == 'description') {
                            $paramAttrs .= ' name="' . htmlspecialchars(DESCRIPTION) . '"';
                        } else {
                            $paramAttrs .= ' name="' . htmlspecialchars($arr['field_id']) . '"';
                        }
                        if ($arr['unit']) {
                            $paramAttrs .= ' unit="' . htmlspecialchars($arr['unit']) . '"';
                        }
                        $offerTxt .= '<param' . $paramAttrs . '>' . htmlspecialchars($v) . '</param>';
                    }
                }
            }
            
        }
        echo '<offer id="' . (int)$row->id . '"' . ($mtype->settings['type'] ? ' type="' . htmlspecialchars($mtype->settings['type']) . '"' : '') . $offerAttrs . '>' . $offerTxt . '</offer>';
        $row->rollback();
        unset($row);
    }
}
echo '</offers>';
echo '</shop>';
echo '</yml_catalog>';
echo '<!-- Memory used: ' . memory_get_usage() . ' -->';