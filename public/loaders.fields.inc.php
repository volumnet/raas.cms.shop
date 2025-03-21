<?php
/**
 * Поля загрузки загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\Field as RAASField;
use RAAS\FieldSet;

/**
 * Отображает группу полей
 * @param fieldSet $fieldSet
 */
$_RAASForm_FieldSet = function(FieldSet $fieldSet) {
    $form = $fieldSet->Form;
    ?>
    <div class="control-group">
      <label class="control-label"><?php echo htmlspecialchars($fieldSet->caption)?>:</label>
      <div class="controls">
        <?php
        $childrenArr = array_map(function ($f) {
            ob_start();
            ?>
            <label style="display: inline" for="<?php echo htmlspecialchars($f->name)?>">
              <?php echo $f->render() . ' ' . htmlspecialchars($f->caption)?>
            </label>
            <?php
            $result = ob_get_clean();
            return $result;
        }, (array)$fieldSet->children);
        switch ($fieldSet->name) {
            case 'offset':
                echo implode(', ', $childrenArr);
                break;
            case 'show':
                echo implode(' &nbsp; &nbsp; &nbsp; ', $childrenArr);
                break;
        }
        ?>
      </div>
    </div>
    <?php
};

/**
 * Отображает поле
 * @param RAASField $field Поле
 */
$_RAASForm_Control = function (RAASField $field) {
    $form = $field->Form;
    $loader = $form->meta['loader'];
    switch ($field->name) {
        case 'material_type':
            echo htmlspecialchars($loader->Material_Type->name);
            break;
        case 'image_field':
            echo htmlspecialchars((string)$loader->Image_Field->name);
            break;
        case 'filename_format':
            $uniqueFieldName = '';
            if (is_numeric($loader->ufid)) {
                $uniqueFieldName = $loader->Unique_Field->name;
            } elseif ($column->fid) {
                $uniqueFieldName = View_Web::i()->context->_(mb_strtoupper($column->fid));
            }
            $fileFormat = '';
            if ($uniqueFieldName) {
                $fileFormat .= '[' . $uniqueFieldName . ']' . $loader->sep_string;
            }
            $fileFormat .= '[' . View_Web::i()->context->_('FILENAME') . '].(jpg|gif|png)';
            echo htmlspecialchars($fileFormat);
            break;
        case 'columns':
            ?>
            <table class="table cms-shop-headers-table">
              <thead>
                <tr>
                  <?php for ($i = 0; $i < count($loader->columns); $i++) {
                      $column = $loader->columns[$i]; ?>
                      <th<?php echo ($loader->ufid == $column->fid) ? ' class="unique"' : ''?>></th>
                  <?php } ?>
                </tr>
                <tr>
                  <?php
                  for ($i = 0; $i < count($loader->columns); $i++) {
                      $column = $loader->columns[$i]; ?>
                      <th<?php echo ($loader->ufid == $column->fid) ? ' class="unique"' : ''?>>
                        <?php
                        if (is_numeric($column->fid)) {
                            echo htmlspecialchars($column->Field->name);
                        } elseif ($column->fid) {
                            echo htmlspecialchars(View_Web::i()->_(mb_strtoupper($column->fid)));
                        }
                        ?>
                      </th>
                  <?php } ?>
                </tr>
              </thead>
            </table>
            <?php
            break;
        case 'file':
            ?>
            <input<?php echo $field->getAttrsString()?> />
            <?php
            break;
        default:
            echo $field->render();
            break;
    }
};
