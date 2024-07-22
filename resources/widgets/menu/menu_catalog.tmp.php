<?php
/**
 * Основное меню каталога
 * @param Page $Page Текущая страница
 * @param Block_Menu $Block Текущий блок
 * @param array<[
 *            'name' => string Наименование пункта,
 *            'url' => string URL пункта,
 *            'children' =>? array рекурсивно такой же массив
 *        ]> $menuArr Меню данных массива
 * @param Menu $Item Текущее меню
 */
namespace RAAS\CMS\Shop;

use SOME\HTTP;
use RAAS\AssetManager;
use RAAS\CMS\Field;
use RAAS\CMS\Menu;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

$ajax = (bool)stristr($Page->url, '/ajax/') || (($_GET['AJAX'] ?? null) == $Block->id);

/**
 * Получает код списка меню
 * @param array<[
 *            'name' => string Наименование пункта,
 *            'url' => string URL пункта,
 *            'children' =>? array рекурсивно такой же массив
 *        ]>|Menu $node Текущий узел для получения кода
 * @param Page $current Текущая страница
 * @return string
 */
$showMenu = function($node, Page $current) use (&$showMenu, $ajax) {
    static $level = 0;
    if ($node instanceof Menu) {
        $children = $node->visSubMenu;
        $rootName = $node->name;
        $rootUrl = $node->url;
        $rootId = $node->page_id;
    } else {
        $children = (isset($node['children']) && is_array($node['children'])) ? $node['children'] : [];
        $rootName = $node['name'];
        $rootUrl = $node['url'];
        $rootId = $node['page_id'];
    }
    $childrenText = '';
    for ($i = 0; $i < count($children); $i++) {
        $row = $children[$i];
        if ($node instanceof Menu) {
            $url = $row->url;
            $name = $row->name;
            if (!$level) {
                $page = $row->page;
            }
            // $itemId = (int)$page->id;
            $itemId = (int)$row->page_id;
        } else {
            $url = $row['url'];
            $name = $row['name'];
            if (!$level) {
                $page = new Page($row['page_id']);
            }
            $itemId = (int)$row['page_id'];
        }
        if (!$level) {
            $image = $page->icon->id ? $page->icon : ($page->image->id ? $page->image : null);
        }
        $active = $semiactive = false;
        // 2021-02-23, AVS: заменил HTTP::queryString('', true) на $current->url,
        // чтобы была возможность использовать через AJAX
        // 2021-06-16, AVS: Заменил ($url == $current->url) на (!$ajax && ($url == $_SERVER['REQUEST_URI'])),
        // чтобы при активном материале ссылка не была активной
        if (!$ajax && ($url == $_SERVER['REQUEST_URI'])) {
            $active = true;
        } elseif (preg_match('/^' . preg_quote($url, '/') . '/umi', $current->url) &&
            ($url != '/')
        ) {
            $semiactive = true;
        }
        $ch = '';
        if (/*$active || $semiactive ||*/ $ajax || !stristr($url, '/catalog/')) { // Для подгрузки AJAX'ом
            $level++;
            $ch = $showMenu($row, $current);
            $level--;
        }
        if (!$level) {
            $childrenText .= $ch;
        }
        if (preg_match('/class="[\\w\\- ]*?active[\\w\\- ]*?"/umi', $ch)) {
            $semiactive = true;
        }
        $liClasses = array(
            'menu-catalog__item',
            'menu-catalog__item_' . (!$level ? 'main' : 'inner'),
            'menu-catalog__item_level_' . $level
        );
        $aClasses = array(
            'menu-catalog__link',
            'menu-catalog__link_' . (!$level ? 'main' : 'inner'),
            'menu-catalog__link_level_' . $level
        );
        $innerClasses = [
            'menu-catalog__inner',
            'menu-catalog__inner_' . (!$level ? 'main' : 'inner'),
            'menu-catalog__inner_level_' . $level
        ];
        // if ($active) {
        //     $liClasses[] = 'menu-catalog__item_active';
        //     $liClasses[] = 'menu-catalog__item_focused';
        //     $aClasses[] = 'menu-catalog__link_active';
        // } elseif ($semiactive) {
        //     $liClasses[] = 'menu-catalog__item_semiactive';
        //     $liClasses[] = 'menu-catalog__item_focused';
        //     $aClasses[] = 'menu-catalog__link_semiactive';
        // }
        if ($ch) {
            $liClasses[] = 'menu-catalog__item_has-children';
            $aClasses[] = 'menu-catalog__link_has-children';
        }
        $text .= '<div class="' . implode(' ', $liClasses) . '" data-id="' . $itemId . '">'
              .  '  <a class="' . implode(' ', $aClasses) . '" ' . (0 && $active ? '' : ' href="' . htmlspecialchars($url) . '"') . ((!$level && $image) ? ' style="background-image: url(\'/' . addslashes($image->fileURL) . '\')"' : '') . '>'
              .       htmlspecialchars($name)
              .       ($ch ? '<span class="menu-catalog__children-trigger"></span>' : '')
              .  '  </a>'
              .     ($level ? $ch : '')
              .  '</div>';
    }
    $ulClasses = array(
        'menu-catalog__list',
        'menu-catalog__list_' . (!$level ? 'main' : 'inner'),
        'menu-catalog__list_level_' . $level
    );
    if ($text) {
        if (!$level) {
            $text = '<div class="' . implode(' ', $ulClasses) . '">' . $text . '</div>' . $childrenText;
        } else {
            $text = '<div data-id="' . $rootId . '" class="' . implode(' ', $ulClasses) . '">' . $text . '</div>';
        }
    }
    return $text;
};

$usePage = $ajax ? new Page($_GET['id'] ?: 1) : $Page;
$menuText = $showMenu($menuArr ?: $Item, $usePage);
?>

<nav class="menu-catalog" data-vue-role="menu-catalog" data-v-bind_page-id="<?php echo (int)$usePage->id?>" data-v-bind_block-id="<?php echo (int)$Block->id?>" data-v-slot="vm">
  <a href="/catalog/" class="menu-catalog__trigger" data-v-on_click.prevent.stop="vm.toggle($event)">
    <?php echo htmlspecialchars($Block->name)?>
  </a>
  <div class="menu-catalog__outer">
    <?php echo $menuText?>
  </div>
</nav>
<?php AssetManager::requestJS('/js/menu-catalog.js');
