<?php
namespace RAAS\CMS;

use RAAS\CMS\Shop\Video;
use RAAS\Attachment;

eval('?' . '>' . Snippet::importByURN('category_inc')->description);
eval('?' . '>' . Snippet::importByURN('item_inc')->description);
eval('?' . '>' . Snippet::importByURN('file_inc')->description);
$formatPrice = function($price) {
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
};


if ($Item) {
    ?>
    <div class="catalog">
      <div class="article_opened">
        <div class="article__article">
          <?php echo ARTICLE_SHORT?> <span><?php echo htmlspecialchars($Item->article)?></span>
        </div>
        <div class="row">
          <?php if ($Item->visImages) { ?>
              <div class="col-sm-6 col-lg-5">
                <div class="article__images__container">
                  <div class="article__image">
                    <?php for ($i = 0; $i < count($Item->visImages); $i++) { ?>
                        <a href="/<?php echo $Item->visImages[$i]->fileURL?>" <?php echo $i ? 'style="display: none"' : ''?> data-image-num="<?php echo (int)$i?>" data-lightbox-gallery="g">
                          <img src="/<?php echo htmlspecialchars($Item->visImages[$i]->tnURL)?>" alt="<?php echo htmlspecialchars($Item->visImages[$i]->name ?: $row->name)?>" /></a>
                    <?php } ?>
                  </div>
                  <?php if (count($Item->visImages) > 1) { ?>
                      <div class="article__images hidden-xs">
                        <?php for ($i = 0; $i < count($Item->visImages); $i++) { $row = $Item->visImages[$i]; ?>
                            <div data-href="/<?php echo htmlspecialchars(addslashes($row->fileURL))?>" class="article__images__image" data-image-num="<?php echo (int)$i?>">
                              <img src="/<?php echo htmlspecialchars($row->tnURL)?>" alt="<?php echo htmlspecialchars($row->name)?>" /></div>
                        <?php } ?>
                      </div>
                  <?php } ?>
                </div>
              </div>
          <?php } ?>
          <div class="col-sm-6 col-lg-7">
            <div class="article_opened__details">
              <div class="article__text">
                <div class="article__price<?php echo ($Item->price_old && ($Item->price_old != $Item->price)) ? ' article__price_new' : ''?>" data-price="<?php echo (float)$Item->price?>">
                  <?php if ($Item->price_old && ($Item->price_old != $Item->price)) { ?>
                      <span class="article__price__old"><?php echo $formatPrice((float)$Item->price_old)?></span>
                  <?php } ?>
                  <span data-role="price-container">
                    <?php echo $formatPrice((float)$Item->price)?>
                  </span>
                  <i class="fa fa-rub"></i>
                </div>
              </div>
              <div class="article__available"><?php echo $Item->available ? '<span class="text-success">' . AVAILABLE . '</span>' : '<span class="text-danger">' . AVAILABLE_CUSTOM . '</span>'?></div>
              <form action="/cart/" class="article__controls" data-role="add-to-cart-form" data-id="<?php echo (int)$Item->id?>" data-price="<?php echo (int)$Item->price?>">
                <?php if ($Item->available) { ?>
                    <input type="hidden" name="action" value="add" />
                    <input type="hidden" name="id" value="<?php echo (int)$Item->id?>" />
                    <input type="hidden" name="back" value="1" />
                    <input type="number" class="form-control" autocomplete="off" name="amount" min="<?php echo (int)$Item->min ?: 1?>" step="<?php echo (int)$Item->step ?: 1?>" value="<?php echo (int)$Item->min ?: 1?>" />
                    <button type="submit" class="btn btn-danger"><?php echo TO_CART?></button>
                    <?php /* <a href="/cart/?action=add&id=<?php echo (int)$Item->id?>" class="btn btn-danger" data-role="add-to-cart-trigger" data-id="<?php echo (int)$Item->id?>" data-price="<?php echo (int)$Item->price?>" data-active-html="<?php echo DELETE_FROM_CART?>"><?php echo TO_CART?></button> */ ?>
                <?php } ?>
                <a href="/favorites/?action=add&id=<?php echo (int)$Item->id?>" class="btn btn-info" data-role="add-to-favorites-trigger" data-id="<?php echo (int)$Item->id?>" data-active-html="<?php echo DELETE_FROM_FAVORITES?>"><?php echo TO_FAVORITES?></a>
              </form>
              <div class="share">
                <script type="text/javascript" src="//yastatic.net/share/share.js" charset="utf-8"></script>
                <?php echo SHARE?>: <div class="yashare-auto-init" style="display: inline-block; vertical-align: middle" data-yashareL10n="ru" data-yashareQuickServices="vkontakte,facebook,twitter,odnoklassniki,moimir" data-yashareTheme="counter"></div>
              </div>
              <?php
              $propsText = '';
              $brands = $models = array();
              foreach ((array)$Item->model as $val) {
                  $brands[$val->brand->id] = $val->brand->name;
                  $models[$val->id] = $val->name;
              }
              unset($temp);
              foreach ($Item->fields as $key => $val) {
                  if (
                      !in_array(
                          $val->urn,
                          array('images', 'brief', 'videos', 'videos_url', 'files', 'onmain', 'article', 'price', 'price_old', 'available', 'min', 'step')
                      ) &&
                      !in_array($val->datatype, array('image', 'file', 'material', 'checkbox'))
                  ) {
                      if ($val->doRich()) {
                          $propsText .= '<tr><th>' . htmlspecialchars($val->name) . ': </th><td>' . implode(', ', array_map(function($x) use ($val) { return $val->doRich($x); }, $val->getValues(true))) . '</td></tr>';
                      }
                  }
              }
              if ($propsText) {
                  echo '<div class="article__props"><table class="table table-striped"><tbody>' . $propsText . '</tbody></table></div><div class="clearfix"></div>';
              }
              ?>
            </div>
          </div>
        </div>
        <?php
        $tabs = array();
        foreach (array('description', 'files', 'videos', 'reviews', 'related') as $key) {
            $text = '';
            $name = $Item->fields[$key]->name;
            switch ($key) {
                case 'description':
                    $name = DESCRIPTION;
                    $text = trim($Item->description);
                    break;
                case 'files':
                    if ($Item->files) {
                        $text = '<div class="article__files">';
                        foreach ($Item->files as $file) {
                            $text .= '<div class="article__file">
                                        <a href="' . htmlspecialchars($file->fileURL) . '">'
                                  .  '    <span class="fa ' . $getFileIcon($file) . '"></span> '
                                  .       htmlspecialchars($file->name ?: basename($file->fileURL))
                                  . '   </a>
                                      </div>';
                        }
                        $text .= '</div>';
                    }
                    break;
                case 'videos':
                    if ($Item->videos) {
                        $text .= '<div class="article__videos">';
                        for ($i = 0; $i < (count($Item->videos) / 4); $i++) {
                            $text .= '<div class="row">';
                            for ($j = $i * 4; $j < ($i + 1) * 4; $j++) {
                                if ($val = $Item->videos[$j]) {
                                    $ytid = $ytname = '';
                                    if (preg_match('/^(.*?)((http(s?):\\/\\/.*?(((\\?|&)v=)|(embed\\/)|(youtu\\.be\\/)))([\\w\\-\\_]+).*?)$/', $val, $regs)) {
                                        $ytname = trim($regs[1]);
                                        $ytid = trim($regs[10]);
                                    }
                                    if (!$ytname) {
                                        $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $ytid . '&key=AIzaSyCJgMFQqq6Ax9WlGhuslTz4viyG3RbPEic';
                                        $json = file_get_contents($url);
                                        $json = json_decode($json, true);
                                        if (isset($json['items'][0]['snippet']['title'])) {
                                            $ytname = trim($json['items'][0]['snippet']['title']);
                                        }
                                    }
                                    $text .= '<div class="col-sm-3">
                                                <div class="article__video">
                                                  <a href="http://youtube.com/watch?v=' . $ytid . '" data-lightbox-gallery="v" title="' . htmlspecialchars($ytname) . '">
                                                    <img src="http://i.ytimg.com/vi/' . htmlspecialchars($ytid) . '/hqdefault.jpg" alt="' . htmlspecialchars($ytname) . '">
                                                  </a>
                                                </div>
                                              </div>';

                                }
                            }
                            $text .= '</div>';
                        }
                        $text .= '</div>';
                    }
                    break;
                case 'reviews':
                    if ($hasComments) {
                        if ($comments) {
                            $name .= ' (' . count($comments) . ')';
                            if ($comments) {
                                ob_start();
                                eval('?' . '>' . Snippet::importByURN('goods_comments')->description);
                                $commentFormBlock->process($Page);
                                $text .= ob_get_contents();
                                ob_end_clean();
                            }
                        }
                    }
                    break;
                case 'related':
                    if ($Item->related) {
                        $text .= '<div class="catalog__inner">
                                    <div class="row">';
                        foreach ($Item->related as $row) {
                            $text .= '<div class="col-sm-4">';
                            ob_start();
                            $showItem($row);
                            $text .= ob_get_contents();
                            ob_end_clean();
                            $text .= '</div>';
                        }
                        $text .= '  </div>
                                  </div>';
                    }
                    break;
            }
            if ($text) {
                $tabs[$key] = array('name' => $name, 'description' => $text);
            }
        }
        if ($tabs) {
            ?>
            <ul class="nav nav-tabs" role="tablist">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <li<?php echo !$i ? ' class="active"' : ''?>>
                    <a href="#<?php echo $key?>" aria-controls="<?php echo $key?>" role="tab" data-toggle="tab">
                      <?php echo htmlspecialchars($row['name'])?>
                    </a>
                  </li>
              <?php $i++; } ?>
            </ul>
            <div class="tab-content" style="padding: 15px 0;">
              <?php $i = 0; foreach ($tabs as $key => $row) { ?>
                  <div class="tab-pane<?php echo !$i ? ' active' : ''?>" id="<?php echo $key?>"><?php echo $row['description']?></div>
              <?php $i++; } ?>
            </div>
        <?php } ?>
      </div>
    </div>
<?php } elseif ($Set || $subCats) {
    eval('?' . '>' . Snippet::importByURN('category_inc')->description); ?>
    <div class="catalog">
      <?php
      if ($Page->pid) {
          eval('?' . '>' . Snippet::importByURN('catalog_filter')->description);
      }
      ?>
      <div class="catalog__inner">
        <?php if ($subCats) { ?>
            <div class="row">
              <?php foreach ($subCats as $row) { ?>
                  <div class="col-xs-4 col-sm-3">
                    <?php $showCategory($row);?>
                  </div>
              <?php } ?>
            </div>
        <?php } ?>
      </div>
      <?php if ($Set) { ?>
          <div class="catalog__inner">
            <div class="row">
              <?php foreach ($Set as $row) { ?>
                  <div class="col-sm-6 col-md-4">
                    <?php $showItem($row)?>
                  </div>
              <?php } ?>
            </div>
          </div>
          <?php include \RAAS\CMS\Package::i()->resourcesDir . '/pages.inc.php'?>
          <?php if ($Pages->pages > 1) { ?>
              <div data-pages="<?php echo $Pages->pages?>">
                <ul class="pagination pull-right">
                  <?php
                  echo $outputNav(
                      $Pages,
                      array(
                          'pattern' => '<li><a href="' . \SOME\HTTP::queryString('page={link}') . '">{text}</a></li>',
                          'pattern_active' => '<li class="active"><a>{text}</a></li>',
                          'ellipse' => '<li class="disabled"><a>...</a></li>'
                      )
                  );
                  ?>
                </ul>
              </div>
              <div class="clearfix"></div>
          <?php } ?>
      <?php } ?>
    </div>
<?php } else { ?>
    <div class="catalog">
      <div class="catalog__inner">
        <p><?php echo NO_RESULTS_FOUND?></p>
      </div>
    </div>
<?php } ?>
