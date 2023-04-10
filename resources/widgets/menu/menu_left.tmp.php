<?php
/**
 * Левое меню
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
use RAAS\CMS\Menu;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

$useAjax = true;

$ajax = (bool)stristr($Page->url, '/ajax/') || (($_GET['AJAX'] ?? '') == $Block->id);

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
$showMenu = function($node, Page $current) use (&$showMenu, $ajax, $useAjax) {
    static $level = 0;
    if ($node instanceof Menu) {
        $children = $node->visSubMenu;
    } else {
        $children = (isset($node['children']) && is_array($node['children'])) ? $node['children'] : array();
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
        // 2021-06-16, AVS: Заменил ($url == $current->url) на (!$ajax && ($url == $_SERVER['REQUEST_URI'])),
        // чтобы при активном материале ссылка не была активной
        if (!$ajax && ($url == $_SERVER['REQUEST_URI'])) {
            $active = true;
        } elseif (preg_match('/^' . preg_quote($url, '/') . '/umi', $current->url) &&
            ($url != '/')
        ) {
            $semiactive = true;
        }
        // 2021-02-23, AVS: заменил HTTP::queryString('', true) на $current->url,
        // чтобы была возможность использовать через AJAX
        $ch = '';
        if (!$useAjax || $active || $semiactive || $ajax || !stristr($url, '/catalog/')) { // Для подгрузки AJAX'ом
            $level++;
            $ch = $showMenu($row, $current);
            $level--;
        }
        if (preg_match('/class="[\\w\\- ]*?active[\\w\\- ]*?"/umi', $ch)) {
            $semiactive = true;
        }
        $liClasses = array(
            'menu-left__item',
            'menu-left__item_' . (!$level ? 'main' : 'inner'),
            'menu-left__item_level_' . $level
        );
        $aClasses = array(
            'menu-left__link',
            'menu-left__link_' . (!$level ? 'main' : 'inner'),
            'menu-left__link_level_' . $level
        );
        if ($active) {
            $liClasses[] = 'menu-left__item_active';
            $liClasses[] = 'menu-left__item_focused';
            $aClasses[] = 'menu-left__link_active';
        } elseif ($semiactive) {
            $liClasses[] = 'menu-left__item_semiactive';
            $liClasses[] = 'menu-left__item_focused';
            $aClasses[] = 'menu-left__link_semiactive';
        }
        if ($ch) {
            $liClasses[] = 'menu-left__item_has-children';
            $aClasses[] = 'menu-left__link_has-children';
        }
        $text .= '<li class="' . implode(' ', $liClasses) . '">'
              .  '  <a class="' . implode(' ', $aClasses) . '" ' . ($active ? '' : ' href="' . htmlspecialchars($url) . '"') . '>'
              .       htmlspecialchars($name)
              .       ($ch ? '<span class="menu-left__children-trigger"></span>' : '')
              .  '  </a>'
              .     $ch
              .  '</li>';
    }
    $ulClasses = array(
        'menu-left__list',
        'menu-left__list_' . (!$level ? 'main' : 'inner'),
        'menu-left__list_level_' . $level
    );
    return $text ? '<ul class="' . implode(' ', $ulClasses) . '">' . $text . '</ul>' : $text;
};

$current = $ajax ? new Page($_GET['id']) : $Page;
?>
<nav
  class="menu-left"
  data-vue-role="menu-left"
  data-v-bind_page-id="<?php echo (int)$Page->id?>"
  data-v-bind_block-id="<?php echo (int)$Block->id?>"
  data-v-bind_use-ajax="<?php echo htmlspecialchars(json_encode($useAjax))?>"
  data-v-slot="vm"
>
  <div class="menu-left__title">
    <a href="/catalog/">
      <?php echo htmlspecialchars($Block->name)?>
    </a>
  </div>
  <?php echo $showMenu($menuArr ?: $Item, $current)?>
</nav>
<?php
AssetManager::requestCSS('/css/menu-left.css');
AssetManager::requestJS('/js/menu-left.js');
