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
    foreach (array('name', 'meta_title', 'meta_keywords', 'meta_description', 'h1') as $key) {
        $Page->{'old' . ucfirst($key)} = $Page->$key;
        $Page->$key = $Item->$key;
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
        if ($Page->pid) {
            $pages_ids = array_merge(array((int)$Page->id), (array)$Page->all_children_ids);
        } else {
            $pages_ids = array($Page->id);
        }
        $Set = array_filter($Set, function($row) use ($pages_ids) { return (bool)array_intersect((array)$row['pages_ids'], $pages_ids); });
    }

    $doSearch = false;

    if ($Page->pid) {
        // Точное соответствие
        foreach (array(/*...*/) as $key) {
            if ($IN[$key]) {
                $doSearch = true;
                $Set = array_filter(
                    $Set,
                    function ($x) use ($IN, $key) {
                        return (bool)array_intersect((array)$x[$key], (array)$IN[$key]);
                    }
                );
            }
        }

        // Вхождение подстроки
        $getValueSubstringFilterFunction = function ($IN, $key) {
            return function ($y) use ($IN, $key) {
                return (bool)stristr($y, $IN[$key]);
            };
        };
        foreach (array('article') as $key) {
            if ($IN[$key]) {
                $doSearch = true;
                $Set = array_filter(
                    $Set,
                    function ($x) use ($IN, $key, $getValueSubstringFilterFunction) {
                        return (bool)array_filter((array)$x[$key], $getValueSubstringFilterFunction($IN, $key));
                    }
                );
            }
        }

        // От .. до
        $getValueFromToFilterFunction = function ($IN, $key, $from = true) {
            if ($from) {
                return function ($y) use ($IN, $key) {
                    return $y >= $IN[$key . '_from'];
                };
            } else {
                return function ($y) use ($IN, $key) {
                    return $y <= $IN[$key . '_to'];
                };
            }
        };

        $getRowFromToFilterFunction = function ($IN, $key, $from = true) use ($getValueFromToFilterFunction) {
            $f = $getValueFromToFilterFunction($IN, $key, $from);
            return function ($x) use ($key, $f) {
                return (bool)array_filter((array)$x[$key], $f);
            };
        };
        foreach (array('price') as $key) {
            if ($IN[$key . '_from']) {
                $doSearch = true;
                $Set = array_filter($Set, $getRowFromToFilterFunction($IN, $key, true));
            }
            if ($IN[$key . '_to']) {
                $doSearch = true;
                $Set = array_filter($Set, $getRowFromToFilterFunction($IN, $key, false));
            }
        }
    }

    if ($Page->visChildren && !$doSearch && $Page->pid) {
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
        $OUT['subCats'] = array_values($cats);
    }

    if ($Set) {
        $Set = array_filter($Set, function($row) use ($Page) { return in_array($Page->id, (array)$row['pages_ids']); });
        $sortFunction = array();
        if (in_array($IN['sort'], array('price'))) {
            // Вариант для сортировки из фильтра
            $sortFunction[] = '(' . ($IN['order'] == 'desc' ? '-1 * ' : '') . '((int)$a["' . $IN['sort'] . '"] - (int)$b["' . $IN['sort'] . '"]))';
        } else {
            $sortFunction[] = '((int)(bool)$b["priority"] - (int)(bool)$a["priority"])';
            $sortFunction[] = '((int)$a["priority"] - (int)$b["priority"])';
            // Вариант для статической сортировки
            if (isset($Block->sort_var_name, $IN[(string)$Block->sort_var_name])) {
                $sortKey = $IN[(string)$Block->sort_var_name];
                if ($f = $MType->fields[$sortKey]->id) {
                    $sortFunction[] = 'strnatcmp($a["' . addslashes($sortKey) . '"], $b["' . addslashes($sortKey) . '"])';
                }
            }
            $sortFunction[] = '((int)$a["price"] - (int)$b["price"])';
        }
        $sortFunction = 'return (' . implode(' ?: ', $sortFunction) . ');';
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
        if ($Pages !== null) {
            $OUT['Pages'] = $Pages;
        }
    }

    $OUT['MType'] = $MType;
    $OUT['doSearch'] = $doSearch;
}
return $OUT;
