<?php
namespace RAAS\CMS;

use SOME\HTTP;

$showMenu = function($node, Page $current) use (&$showMenu) {
    static $level = 0;
    if ($node instanceof Menu) {
        $children = $node->visSubMenu;
    } else {
        $children = (isset($node['children']) && is_array($node['children'])) ? $node['children'] : array();
    }
    for ($i = 0; $i < count($children); $i++) {
        $row = $children[$i];
        $level++;
        $ch = $showMenu($row, $current);
        $level--;
        if ($node instanceof Menu) {
            $url = $row->url;
            $name = $row->name;
        } else {
            $url = $row['url'];
            $name = $row['name'];
        }
        $active = ($url == HTTP::queryString('', true));
        $semiactive = preg_match('/^' . preg_quote($url, '/') . '/umi', HTTP::queryString('', true)) && ($url != '/');
        if (preg_match('/class="[\\w\\- ]*?active[\\w\\- ]*?"/umi', $ch)) {
            $semiactive = true;
        }
        $ulClasses = array(
            'menu-left__list',
            'menu-left__list_' . (!$level ? 'main' : 'inner'),
            'menu-left__list_level_' . $level
        );
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
        if ($active || $semiactive) {
            $liClasses[] = 'menu-left__item_active';
            $aClasses[] = 'menu-left__link_active';
            if ($semiactive) {
                $liClasses[] = 'menu-left__item_semiactive';
                $aClasses[] = 'menu-left__link_semiactive';
            }
        }
        $text .= '<li class="' . implode(' ', $liClasses) . '">'
              .  '  <a class="' . implode(' ', $aClasses) . '" ' . ($active ? '' : ' href="' . htmlspecialchars($url) . '"') . '>' . htmlspecialchars($name) . '</a>'
              .     $ch
              .  '</li>';
    }
    return $text ? '<ul class="' . implode(' ', $ulClasses) . '">' . $text . '</ul>' : $text;
};
?>
<div class="menu-left__outer left-block">
  <div class="menu-left__title left-block__title">
    <a href="/catalog/"><?php echo CATALOG?></a>
  </div>
  <nav class="menu-left"><?php echo $showMenu($menuArr ?: $Item, $Page)?></nav>
</div>
