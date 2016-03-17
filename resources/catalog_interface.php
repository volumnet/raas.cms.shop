<?php
namespace RAAS\CMS;

$IN = (array)$_GET;
parse_str(trim($Block->params), $temp);
$IN = array_merge($IN, (array)$temp);

$OUT = array();
if ($Page->Material && $Block->nat) {
    $Item = $Page->Material;
    if ($Page->initialURL != $Item->url) {
        // Адреса не совпадают
        if ((int)$Block->legacy && ($Item->pid == $Block->material_type)) {
            // Установлена переадресация
            header("HTTP/1.1 301 Moved Permanently");
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $Item->url); 
            exit;
        } else {
            return;
        }
    }
    $OUT['Item'] = $Item;
    foreach (array('name', 'meta_title', 'meta_keywords', 'meta_description') as $key) {
        if ($Item->$key) {
            $Page->{'old' . ucfirst($key)} = $Page->$key;
            $Page->$key = $Item->$key;
        }
    }
    $Item->proceed = true;
    $_SESSION['visited'] = (array)$_SESSION['visited'];
    $_SESSION['visited'][] = (int)$Item->id;
    $_SESSION['visited'] = array_unique($_SESSION['visited']);
    $_SESSION['visited'] = array_filter($_SESSION['visited']);
    $_SESSION['visited'] = array_values($_SESSION['visited']);
} else {
    $MType = new Material_Type($Block->material_type);
    $cc = new Catalog_Cache($MType);
    if (!$cc->load()) {
        $cc->getCache();
        $cc->save();
    }
    $Set = $cc->data;

    if (!$MType->global_type) {
        $pages_ids = array_merge(array((int)$Page->id), (array)$Page->all_children_ids);
        $Set = array_filter($Set, function($row) use ($pages_ids) { return (bool)array_intersect((array)$row['pages_ids'], $pages_ids); });
    }

    $isSearch = false;

    if ($Page->visChildren && !$isSearch) {
        $cats = $Page->visChildren;
        $temp = array();
        foreach ($cats as $cat) {
            $ids = array_merge(array($cat->id), (array)$cat->all_children_ids);
            foreach ($Set as $row) {
                if ((bool)array_intersect((array)$row['pages_ids'], $ids)) {
                    $temp[$cat->id]++;
                }
            }
            $cat->rollback();
            unset($cat);
        }
        $cats = array();
        foreach ($temp as $key => $val) {
            $cats[$key] = new Page($key);
            $cats[$key]->counter = $val;
        }
        $OUT['Set'] = array_values($cats);
        $OUT['showCatalog'] = true;
    } else {
        /*** SORTING ***/
        // @todo доделать для будущих проектов
        $sortFunction = array();
        if (isset($Block->sort_var_name, $IN[(string)$Block->sort_var_name])) {
            $sortKey = $IN[(string)$Block->sort_var_name];
            if ($f = $Material_Type->fields[$sortKey]->id) {
                $sortFunction[] = 'strnatcmp($a["' . addslashes($sortKey) . '"], $b["' . addslashes($sortKey) . '"])';
            }
        }
        $sortFunction[] = '((float)$a["price"] - (float)$b["price"])';
        $sortFunction = 'return (' . implode(' || ', $sortFunction) . ');';

        $sortFunction = create_function('$a, $b', $sortFunction);
        usort($Set, $sortFunction);
        
        if (isset($Block->pages_var_name, $Block->rows_per_page) && (int)$Block->rows_per_page) {
            $Pages = new \SOME\Pages(isset($IN[$Block->pages_var_name]) ? (int)$IN[$Block->pages_var_name] : 1, (int)$Block->rows_per_page);
        }

        $Set = \SOME\SOME::getArraySet($Set, $Pages);
        $nativeFields = Material::_classes();
        $nativeFields = $nativeFields['RAAS\\CMS\\Material']['fields'];
        $Set = array_map(function($row) use ($nativeFields) { 
            $native = array_intersect_key($row, array_flip($nativeFields));
            $row2 = new Material($native); 
            $row2->metacache = $row; 
            return $row2; 
        }, $Set);
        
        $OUT['Set'] = $Set;
        $OUT['MType'] = $MType;
        if ($Pages !== null) {
            $OUT['Pages'] = $Pages;
        }
        $OUT['showItems'] = true;
    }
}
return $OUT;