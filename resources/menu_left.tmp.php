<?php
$showMenu = function($node, \RAAS\CMS\Page $current) use (&$showMenu) {
    static $level = 0;
    if ($node instanceof \RAAS\CMS\Menu) {
        $children = $node->visSubMenu;
    } else {
        $children = (isset($node['children']) && is_array($node['children'])) ? $node['children'] : array();
    }
    for ($i = 0; $i < count($children); $i++) {
        $row = $children[$i];
        $level++;
        $ch = $showMenu($row, $current);
        $level--;
        if ($node instanceof \RAAS\CMS\Menu) {
            $url = $row->url;
            $name = $row->name;
        } else {
            $url = $row['url'];
            $name = $row['name'];
        }
        $active = ($url == \SOME\HTTP::queryString('', true));
        $semiactive = stristr(\SOME\HTTP::queryString('', true), $url) && ($url != '/');
        if (stristr($ch, 'class="active"')) {
            $semiactive = true;
        }
        $text .= '<li' . ($active || $semiactive ? ' class="active"' : '') . '>'
              .  '  <a' . ($active ? '' : ' href="' . htmlspecialchars($url) . '"') . '>' . htmlspecialchars($name) . '</a>'
              .     $ch
              .  '</li>';
    }
    return $text ? '<ul>' . $text . '</ul>' : $text;
};
?>
<div class="menu_left__outer block_left">
  <div class="menu_left__title block_left__title">
    <a href="<?php echo htmlspecialchars($Item->url)?>">
      <?php echo htmlspecialchars($Item->page->name)?>
    </a>
  </div>
  <nav class="menu_left"><?php echo $showMenu($menuArr ?: $Item, $Page)?></nav>
</div>
