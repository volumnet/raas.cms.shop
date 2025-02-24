<?php
/**
 * Виджет загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

/**
 * Отображает лог
 * @param array $log Лог
 */
$showLog = function (array $log) {
    for ($i = 0; $i < count($log); $i++) {
        $logEntry = $log[$i]; ?>
        <p>
          <span class="muted">
            <?php echo number_format($logEntry['time'], 3, '.', ' ')?>
          </span>
          <?php echo $logEntry['text']?>
          <?php if (($logEntry['realrow'] ?? null) !== null) { ?>
              <span class="muted">:<?php echo (int)($logEntry['realrow'] + 1)?></span>
          <?php } ?>
        </p>
    <?php }
};


/**
 * Отображает таблицу данных
 * @param PriceLoader $loader Загрузчик прайсов
 * @param array $data Данные
 * @param array $log Лог
 * @param int $rows Смещение по строкам
 */
$showData = function (PriceLoader $loader, array $data, ?array $log = null, int $rows = 0) {
    ?>
    <table class="table table-striped cms-shop-table-raw-data">
      <thead>
        <tr>
          <th>#</th>
          <?php if ($log ?? null) { ?>
              <th>
                <?php echo View_Web::i()->context->_('TIME_SEC')?>
              </th>
          <?php }
          for ($i = 0; $i < count($loader->columns); $i++) {
              $column = $loader->columns[$i]; ?>
              <th<?php echo ($loader->ufid == $column->fid) ? ' class="unique"' : ''?>>
                <?php
                if (is_numeric($column->fid)) {
                    echo htmlspecialchars($column->Field->name);
                } else {
                    echo htmlspecialchars(View_Web::i()->context->_(mb_strtoupper($column->fid)));
                }
                ?>
              </th>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $hrefRx = '/href="(.*?)"/umis';
        for ($i = 0; $i < count($data); $i++) {
            $realrow = $i + (int)($_POST['rows'] ?? 0);
            $dataEntry = $data[$i];
            ?>
            <tr>
              <th>
                <?php echo $realrow + 1?>
              </th>
              <?php
              $matchingLogEntries = array_filter($log ?? [], function ($x) use ($realrow) {
                  return $x['realrow'] == $realrow;
              });
              if ($matchingLogEntries) { ?>
                  <th>
                    <?php foreach ($matchingLogEntries as $logEntry) {
                        if (preg_match($hrefRx, $logEntry['text'], $regs)) {
                            $logEntry['href'] = $regs[1];
                        }
                        $logEntry['rawText'] = html_entity_decode(strip_tags($logEntry['text']));
                        ?>
                        <a
                          class="btn small-btn"
                          <?php echo ($logEntry['href'] ?? null) ? (' href="' . htmlspecialchars($logEntry['href']) . '"') : ''?>
                          target="_blank"
                          title="<?php echo htmlspecialchars($logEntry['rawText'])?>"
                        >
                          <?php echo number_format($logEntry['time'], 3, '.', ' ')?>
                        </a>
                    <?php } ?>
                  </th>
              <?php }
              for ($j = 0; $j < count($loader->columns); $j++) {
                  $column = $loader->columns[$j]; ?>
                  <td<?php echo ($loader->ufid == $column->fid) ? ' class="unique"' : ''?>><?php
                      echo htmlspecialchars($dataEntry[$j] ?? '')
                  ?></td>
              <?php } ?>
            </tr>
        <?php } ?>
      </tbody>
    </table>
    <?php
};

if ($localSuccess ?? null) { ?>
    <div class="alert alert-success alert-block">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <p><?php echo htmlspecialchars($localSuccess['description'])?></p>
    </div>
<?php }

include $VIEW->tmp('/form.tmp.php');

if (($raw_data ?? null) || ($log ?? null)) { ?>
    <h2><?php echo $VIEW->context->_('LOADER_REPORT')?></h2>
    <?php if ($Form instanceof ProcessPriceLoaderForm) { ?>
        <div role="tabpanel">
          <ul class="nav nav-tabs" role="tablist">
            <?php $i = 0; if ($log ?? null) { ?>
                <li role="presentation"<?php echo !$i ? ' class="active"' : ''?>>
                  <a href="#tab_log" role="tab" data-toggle="tab">
                    <?php echo $VIEW->context->_('LOG')?>
                  </a>
                </li>
            <?php $i++; } ?>
            <?php if ($raw_data ?? null) { ?>
                <li role="presentation"<?php echo !$i ? ' class="active"' : ''?>>
                  <a href="#tab_data" role="tab" data-toggle="tab">
                    <?php echo $VIEW->context->_('DATA')?>
                  </a>
                </li>
            <?php $i++; } ?>
          </ul>
          <div class="tab-content">
            <?php $i = 0; if ($log ?? null) { ?>
                <div role="tabpanel" class="tab-pane<?php echo !$i ? ' active' : ''?> cms-shop-log-container" id="tab_log">
                  <?php $showLog($log)?>
                </div>
            <?php $i++; } ?>
            <?php if ($raw_data ?? null) { ?>
                <div role="tabpanel" class="tab-pane<?php echo !$i ? ' active' : ''?> cms-shop-log-container" id="tab_data">
                  <?php $showData($loader, $raw_data, $log ?? null, $_POST['rows'] ?? 0)?>
                </div>
            <?php $i++; } ?>
          </div>
        </div>
    <?php } else { ?>
        <div class="cms-shop-log-container">
          <?php $showLog($log)?>
        </div>
    <?php } ?>
<?php } ?>
