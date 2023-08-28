<?php
/**
 * Команда получения точек выдачи
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use SOME\ZipArchive;
use RAAS\Application;
use RAAS\Command;

/**
 * Команда получения точек выдачи
 */
class UpdatePickupPointsCommand extends Command
{
    /**
     * Количество метров в градусе широты
     */
    const M_IN_LAT_DEG = 111133;

    /**
     * Количество метров в градусе долготы на экваторе
     */
    const M_IN_LON_DEG = 111319;

    /**
     * Выполнение команды
     */
    public function process()
    {
        $pointsByServices = [];
        if (in_array('cdek', (array)$GLOBALS['argv'])) {
            $pointsByServices['cdek'] = $this->processCDEK();
            $this->controller->doLog('Обработаны точки выдачи СДЭК');
        }
        if (in_array('russianpost', (array)$GLOBALS['argv'])) {
            $pointsByServices['russianpost'] = $this->processRussianPost();
            $this->controller->doLog('Обработаны точки выдачи Почты России');
        }
        $result = [];
        foreach ($pointsByServices as $serviceURN => $serviceData) {
            foreach ($serviceData as $cityURN => $cityData) {
                if (!isset($result[$cityURN])) {
                    $result[$cityURN] = $cityData;
                } else {
                    $result[$cityURN]['points'] = array_merge($result[$cityURN]['points'], $cityData['points']);
                }
            }
        }
        $this->controller->doLog('Точки выдачи объединены');

        foreach ($result as $cityURN => $cityData) {
            $result[$cityURN]['postalCode'] = $this->getCityPostalCode($cityData);
        }
        $this->controller->doLog('Найдены почтовые индексы городов');
        uasort($result, function ($a, $b) {
            $aCounter = count($a['points']);
            $bCounter = count($b['points']);
            if ($aCounter != $bCounter) {
                return $bCounter - $aCounter;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });
        $this->controller->doLog('Города отсортированы по количеству точек выдачи');

        $citiesFilename = Application::i()->baseDir . '/cities.php';
        $citiesBriefFilename = Application::i()->baseDir . '/cities.brief.php';
        $cacheId = 'RAASCACHE' . date('YmdHis') . md5(rand());
        $cacheText = '<' . '?php return unserialize(<<' . "<'" . $cacheId . "'\n"
              . serialize($result) . "\n" . $cacheId . "\n);\n";
        $tmpName = tempnam(sys_get_temp_dir(), 'raas_');
        file_put_contents($tmpName, $cacheText);
        file_put_contents(
            Application::i()->baseDir . '/cities.json',
            json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        if (is_file($citiesFilename)) {
            unlink($citiesFilename);
        }
        rename($tmpName, $citiesFilename);

        $brief = array_map(function ($x) {
            unset($x['points']);
            return $x;
        }, $result);
        $cacheText = '<' . '?php return unserialize(<<' . "<'" . $cacheId . "'\n"
              . serialize($brief) . "\n" . $cacheId . "\n);\n";
        $tmpName = tempnam(sys_get_temp_dir(), 'raas_');
        file_put_contents($tmpName, $cacheText);
        file_put_contents(
            Application::i()->baseDir . '/cities.brief.json',
            json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        if (is_file($citiesBriefFilename)) {
            unlink($citiesBriefFilename);
        }
        rename($tmpName, $citiesBriefFilename);
        $this->controller->doLog('Точки выдачи сохранены');
    }


    /**
     * Получает список точек СДЭК по городам
     * @return array
     */
    public function processCDEK(): array
    {
        $cdekJSON = $this->getRawCDEKPoints();
        $this->controller->doLog('Получены точки выдачи СДЭК');
        $result = [];
        foreach ($cdekJSON as $item) {
            $cityData = $this->formatCDEKCity($item);
            $cityData['urn'] = Text::beautify($cityData['name']);
            if (!$cityData['urn']) {
                continue;
            }
            if (!isset($result[$cityData['urn']])) {
                $result[$cityData['urn']] = $cityData;
            }
            $resultItem = $this->formatCDEKPoint($item);
            $result[$cityData['urn']]['points'][] = $resultItem;
        }
        return $result;
    }


    /**
     * Получает список исходных точек выдачи СДЭК
     * @return array
     */
    public function getRawCDEKPoints(): array
    {
        $url = 'https://integration.cdek.ru/pvzlist/v1/json';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $cdekJSON = json_decode($response, true);
        $cdekJSON = (array)($cdekJSON['pvz'] ?? []);
        return $cdekJSON;
    }


    /**
     * Форматирует город СДЭК
     * @param array $item Входные данные точки
     * @return array
     */
    public function formatCDEKCity(array $item): array
    {
        $result = [
            'value' => trim($item['city']),
            'name' => trim($item['city']),
            'region' => trim($item['regionName']),
            'cdekCityId' => $item['cityCode'],
        ];
        return $result;
    }


    /**
     * Форматирует точку СДЭК
     * @param array $item Входные данные точки
     * @return array
     */
    public function formatCDEKPoint(array $item): array
    {
        $resultItem = [
            'id' => trim($item['code']),
            'name' => trim($item['name']),
            'address' => trim($item['address']),
            'postalCode' => trim($item['postalCode']),
            'description' => trim($item['addressComment']),
            'lat' => (float)$item['coordY'],
            'lon' => (float)$item['coordX'],
            'serviceURN' => 'cdek',
            'weight' => null,
            'sizes' => [],
        ];
        if ($item['workTime']) {
            $resultItem['schedule'] = $item['workTime'];
        }
        if ($item['phoneDetailList']) {
            $resultItem['phones'] = array_map(function ($x) {
                return Text::beautifyPhone($x['number'], 10);
            }, $item['phoneDetailList']);
        }
        if ($item['officeImageList']) {
            $resultItem['images'] = array_map(function ($x) {
                return $x['url'];
            }, $item['officeImageList']);
        }
        $pvzSizes = array_values($item['dimensions'] ?? []);
        if ($pvzSizes && count($pvzSizes) >= 3) {
            sort($pvzSizes);
            $resultItem['sizes'] = $pvzSizes;
        }
        return $resultItem;
    }


    public function processRussianPost()
    {
        $pochtaJSON = $this->getRawRussianPostPoints();
        $this->controller->doLog('Получены точки выдачи Почты России');
        $result = [];
        foreach ($pochtaJSON as $item) {
            $cityData = $this->formatRussianPostCity($item);
            $cityData['urn'] = Text::beautify($cityData['name']);
            if (!$cityData['urn']) {
                continue;
            }
            if (!isset($result[$cityData['urn']])) {
                $result[$cityData['urn']] = $cityData;
            }
            $result[$cityData['urn']]['points'][] = $this->formatRussianPostPoint($item);
        }
        return $result;
    }

    /**
     * Получает список исходных точек выдачи Почты России
     * @return array
     */
    public function getRawRussianPostPoints(): array
    {
        $login = ($GLOBALS['russianpost']['login'] ?? null);
        $password = ($GLOBALS['russianpost']['password'] ?? null);
        $token = ($GLOBALS['russianpost']['token'] ?? null);
        if (!$login || !$password || !$token) {
            return [];
        }
        $jsonTmpFilename = tempnam(sys_get_temp_dir(), 'raas_');
        $jsonFilename = Application::i()->baseDir . '/pochta.ops.json';

        $headers = [
            'Accept: application/octet-stream',
            'Authorization: AccessToken ' . $token,
            'X-User-Authorization: Basic ' . base64_encode($login . ':' . $password)
        ];
        $url = 'https://otpravka-api.pochta.ru//1.0/unloading-passport/zip?type=ALL';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $zipText = curl_exec($ch);
        if (!$zipText) {
            $this->controller->doLog('Не удалось получить список точек выдачи');
            return [];
        }
        $zipTmpFilename = tempnam(sys_get_temp_dir(), 'raas_');
        file_put_contents($zipTmpFilename, $zipText);
        $zip = new ZipArchive;
        $zip->open($zipTmpFilename);
        $jsonText = $zip->getFromIndex(0);
        $json = json_decode($jsonText, true);
        $pochtaJSON = (array)($json['passportElements'] ?? []);
        return $pochtaJSON;
    }


    /**
     * Форматирует город Почты России
     * @param array $item Входные данные точки
     * @return array
     */
    public function formatRussianPostCity(array $item): array
    {
        $regionName = $item['address']['region'] ?? '';
        $regionName = preg_replace('/^обл (.*?)$/umis', '$1 обл.', $regionName);
        $regionName = preg_replace('/^край (.*?)$/umis', '$1 край', $regionName);
        $regionName = preg_replace('/^респ (.*?)$/umis', 'респ. $1', $regionName);
        $regionName = preg_replace('/^АО (.*?)$/umis', '$1', $regionName);

        $cityName = $item['address']['place'] ?? '';
        $cityName = preg_replace('/^г (.*?)$/umis', '$1', $cityName);
        $cityName = preg_replace('/^д (.*?)$/umis', '$1', $cityName);
        $cityName = preg_replace('/^дер (.*?)$/umis', '$1', $cityName);
        $cityName = preg_replace('/^с (.*?)$/umis', '$1', $cityName);
        $cityName = preg_replace('/^п (.*?)$/umis', '$1', $cityName);

        $result = [
            'value' => trim($cityName),
            'name' => trim($cityName),
            'region' => trim($regionName)
        ];
        return $result;
    }


    /**
     * Форматирует точку Почты России
     * @param array $item Входные данные точки
     * @return array
     */
    public function formatRussianPostPoint(array $item): array
    {
        $addressArr = [];
        if ($item['address']['street'] ?? null) {
            $addressArr[] = $item['address']['street'];
        }
        if ($item['address']['house'] ?? null) {
            $addressChunk = $item['address']['house'];
            if ($item['address']['building'] ?? null) {
                if (is_numeric($item['address']['building'])) {
                    $addressChunk .= '/' . $item['address']['building'];
                } else {
                    $addressChunk .= ((mb_strlen($item['address']['building']) > 1) ? ' ' : '') . $item['address']['building'];
                }
            }
            $addressArr[] = $addressChunk;
        } elseif ($item['address']['building'] ?? null) {
            $addressArr[] = $item['address']['building'];
        }
        if (!$addressArr && ($item['addressFias']['ads'] ?? null)) {
            $addressArr[] = $item['addressFias']['ads'];
        }
        $resultItem = [
            'id' => trim($item['address']['index'] ?? ''),
            // 'name' => trim($item['name']),
            'address' => trim(implode(', ', $addressArr)),
            'postalCode' => trim($item['address']['index'] ?? ''),
            'lat' => (float)$item['latitude'],
            'lon' => (float)$item['longitude'],
            'serviceURN' => 'russianpost',
            // 'inner' => $item,
            'weight' => null,
            'sizes' => [],
        ];
        if ($weightLimit = ($item['ecomOptions']['weightLimit'] ?? 0)) {
            $resultItem['weight'] = $weightLimit;
        }
        $resultItem['name'] = $item['type'];
        if ($item['ecomOptions']['getto']) {
            $resultItem['description'] = $item['ecomOptions']['getto'];
        }

        if ($item['workTime']) {
            $resultItem['schedule'] = implode("\n", $item['workTime']);
        }
        // if ($item['phoneDetailList']) {
        //     $resultItem['phones'] = array_map(function ($x) {
        //         return Text::beautifyPhone($x['number'], 10);
        //     }, $item['phoneDetailList']);
        // }
        // if ($item['officeImageList']) {
        //     $resultItem['images'] = array_map(function ($x) {
        //         return $x['url'];
        //     }, $item['officeImageList']);
        // }
        return $resultItem;
    }


    /**
     * Получает индекс города
     * @param array $cityData Индекс города
     * @return string|null
     */
    public function getCityPostalCode(array $cityData): string
    {
        // Найдем индекс города
        $cityPoints = $cityData['points'];

        $cityPostalCode = '';
        $postalCodesPoints = array_filter($cityPoints, function ($x) {
            return (($x['serviceURN'] != 'russianpost') || mb_stristr($x['name'], 'ОПС'));
        });
        $postalCodes = array_values(array_filter(array_unique(array_map(function ($x) {
            return $x['postalCode'];
        }, $postalCodesPoints))));
        if (count($postalCodes) == 1) {
            $cityPostalCode = $postalCodes[0];
        }

        if (!$cityPostalCode && $cityPoints) {
            // Индексы абсолютно разные, найдем по среднему
            $latSum = $lonSum = 0;
            foreach ($cityPoints as $cityPoint) {
                $latSum += $cityPoint['lat'];
                $lonSum += $cityPoint['lon'];
            }
            $latAvg = $latSum / count($cityPoints);
            $lonAvg = $lonSum / count($cityPoints);
            $avgPoint = [
                'lat' => $latAvg,
                'lon' => $lonAvg,
            ];
            usort($postalCodesPoints, function ($a, $b) use ($avgPoint) {
                $aDistance = static::getDistance($a, $avgPoint);
                $bDistance = static::getDistance($b, $avgPoint);
                return $aDistance - $bDistance;
            });
            $cityPostalCode = $postalCodesPoints[0]['postalCode'] ?? '';
        }
        return $cityPostalCode;
    }


    /**
     * Получает расстояние между точками
     * @param array $a <pre><code>['lat' => float Широта, 'lon' => float Долгота]</code></pre> Точка A
     * @param array $b <pre><code>['lat' => float Широта, 'lon' => float Долгота]</code></pre> Точка B
     * @return int Расстояние в метрах
     */
    public static function getDistance(array $a, array $b): float
    {
        $dLat = (float)$b['lat'] - (float)$a['lat'];
        $dLon = (float)$b['lon'] - (float)$a['lon'];
        $avgLat = (((float)$b['lat'] + (float)$a['lat']) / 2) * M_PI / 180;
        $dy = $dLat * static::M_IN_LAT_DEG;
        $dx = $dLon * static::M_IN_LON_DEG * cos($avgLat);
        $dl = (int)sqrt(pow($dx, 2) + pow($dy, 2));
        return $dl;
    }
}
