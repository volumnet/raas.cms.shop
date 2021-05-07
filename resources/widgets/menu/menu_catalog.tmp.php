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
use RAAS\CMS\Menu;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

$ajax = (bool)stristr($Page->url, '/ajax/');

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
    } else {
        $children = (isset($node['children']) && is_array($node['children'])) ? $node['children'] : [];
    }
    for ($i = 0; $i < count($children); $i++) {
        $row = $children[$i];
        if ($node instanceof Menu) {
            $url = $row->url;
            $name = $row->name;
        } else {
            $url = $row['url'];
            $name = $row['name'];
        }
        $active = $semiactive = false;
        if ($url == $current->url) {
            $active = true;
        } elseif (preg_match('/^' . preg_quote($url, '/') . '/umi', $current->url) &&
            ($url != '/')
        ) {
            $semiactive = true;
        }
        // 2021-02-23, AVS: заменил HTTP::queryString('', true) на $current->url,
        // чтобы была возможность использовать через AJAX
        $ch = '';
        if (1 || $active || $semiactive || $ajax || !stristr($url, '/catalog/')) { // Для подгрузки AJAX'ом
            $level++;
            $ch = $showMenu($row, $current);
            $level--;
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
        if ($active) {
            $liClasses[] = 'menu-catalog__item_active';
            $liClasses[] = 'menu-catalog__item_focused';
            $aClasses[] = 'menu-catalog__link_active';
        } elseif ($semiactive) {
            $liClasses[] = 'menu-catalog__item_semiactive';
            $liClasses[] = 'menu-catalog__item_focused';
            $aClasses[] = 'menu-catalog__link_semiactive';
        }
        if ($ch) {
            $liClasses[] = 'menu-catalog__item_has-children';
            $aClasses[] = 'menu-catalog__link_has-children';
        }
        $text .= '<li class="' . implode(' ', $liClasses) . '">'
              .  '  <a class="' . implode(' ', $aClasses) . '" ' . ($active ? '' : ' href="' . htmlspecialchars($url) . '"') . '>'
              .       htmlspecialchars($name)
              .       ($ch ? '<span class="menu-catalog__children-trigger"></span>' : '')
              .  '  </a>'
              .     $ch
              .  '</li>';
    }
    $ulClasses = array(
        'menu-catalog__list',
        'menu-catalog__list_' . (!$level ? 'main' : 'inner'),
        'menu-catalog__list_level_' . $level
    );
    return $text ? '<ul class="' . implode(' ', $ulClasses) . '">' . $text . '</ul>' : $text;
};
?>

<nav class="menu-catalog" data-vue-role="menu-catalog" data-v-slot="vm">
  <a href="/catalog/" class="menu-catalog__trigger" data-v-on_click.prevent.stop="vm.toggle()">
    <?php echo htmlspecialchars($Block->name)?>
  </a>
  <?php echo $showMenu($menuArr ?: $Item, $Page)?>
</nav>
<?php echo Package::i()->asset('/js/menu-catalog.js')?>
