<?php
/**
 * Команда получения точек выдачи
 */
namespace RAAS\CMS\Shop;

use Exception;
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
     * Обозначать URN города через ФИАС (новый режим), если false - то через бьютифицированное название города
     */
    public $cityURNByFias = false;

    /**
     * Количество метров в градусе широты
     */
    const M_IN_LAT_DEG = 111133;

    /**
     * Количество метров в градусе долготы на экваторе
     */
    const M_IN_LON_DEG = 111319;

    /**
     * Кэш названий регионов по их URN
     * @var array <pre><code>array<string[] URN региона => string Название региона></code></pre>
     */
    public $regionNamesByURNs = [];

    /**
     * Выполнение команды
     */
    public function process()
    {
        $pointsByServices = [];
        $args = (array)func_get_args();
        $this->cityURNByFias = (bool)array_intersect(['fias', 'withfias', 'newurn'], $args);
        if (in_array('cdek', $args)) {
            $pointsByServices['cdek'] = $this->processCDEK();
            $this->controller->doLog('Обработаны точки выдачи СДЭК');
        }
        if (in_array('russianpost', $args)) {
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
            $x['counter'] = count($x['points']);
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
     * Нормализует название города
     * @param string $cityName Название города
     * @return string
     */
    public function normalizeCityName(string $cityName): string
    {
        $rx = '/^((г(ор(од)?)?)|(д(ер(евня)?)?)|(с(ело)?)|(п(ос(елок)?)?)|пгт)\.? /umis';
        $cityName = preg_replace($rx, '', $cityName);
        return trim((string)$cityName);
    }


    /**
     * Нормализует название региона
     * @param string $regionName Название региона
     * @param bool $strict Строгая нормализация + бьютификация
     * @return string
     */
    public function normalizeRegionName(string $regionName, bool $strict = false): string
    {
        if (stristr($regionName, 'Алтай')) {
            $regionName = 'Алтайский край';
        }
        if (stristr($regionName, 'Кабардино-Балкар')) {
            $regionName = 'Кабардино-Балкария';
        }
        if (stristr($regionName, 'Карачаево-Черкес')) {
            $regionName = 'Карачаево-Черкесия';
        }
        if (stristr($regionName, 'Якутия')) {
            $regionName = 'Саха (Якутия)';
        }
        if (stristr($regionName, 'Удмурт')) {
            $regionName = 'Удмуртия';
        }
        if (stristr($regionName, 'Чуваш')) {
            $regionName = 'Чувашия';
        }
        $regionName = trim((string)$regionName);
        if ($strict) {
            $rx = '((г(ор(од)?)?)|((а(вт(ономная)?)?)? ?(о(бл(асть)?)?))|(кр(ай)?)|(р(есп(ублика)?)?))\.?';
            $regionName = trim(preg_replace('/^' . $rx . ' /umis', '', (string)$regionName));
            $regionName = trim(preg_replace('/ ' . $rx . '$/umis', '', (string)$regionName));
            $regionName = Text::beautify((string)$regionName);
        } else {
            $rx = '/^г(ор(од)?)?\.?/umis';
            $regionName = trim(preg_replace($rx, '', (string)$regionName));

            $rx = '/^а(вт(ономная)?)? ?о(бл(асть)?)?\.? (.*?ая)$/umis';
            $regionName = trim(preg_replace($rx, '$5 автономная область', (string)$regionName));

            $rx = '/^а(вт(ономная)?)? ?о(бл(асть)?)?\.? (.*?й)$/umis';
            $regionName = trim(preg_replace($rx, '$5 автономный округ', (string)$regionName));

            $rx = '/^о(бл(асть)?)?\.? (.*?ая)$/umis';
            $regionName = trim(preg_replace($rx, '$3 область', (string)$regionName));

            $rx = '/^р(есп(ублика)?)?\.? (.*?ая)$/umis';
            $regionName = trim(preg_replace($rx, '$3 республика', (string)$regionName));

            $rx = '/^р(есп(ублика)?)?\.? /umis';
            $regionName = trim(preg_replace($rx, '', (string)$regionName));

            $rx = '/^кр(ай)?\.? (.*?ий)$/umis';
            $regionName = trim(preg_replace($rx, '$2 край', (string)$regionName));
        }
        return trim((string)$regionName);
    }


    /**
     * Получает список точек СДЭК по городам
     * @return array
     */
    public function processCDEK(): array
    {
        $token = null;
        for ($i = 0; ($i < 3) && !$token; $i++) {
            try {
                $token = $this->getCDEKAccessToken();
            } catch (Exception $e) {
            }
        }
        if (!$token) {
            $token = $this->getCDEKAccessToken();
        }
        $cdekJSON = $this->getRawCDEKPoints($token);
        $this->controller->doLog('Получены точки выдачи СДЭК');
        $result = [];
        foreach ($cdekJSON as $item) {
            $cityData = $this->formatCDEKCity($item);
            if ($this->cityURNByFias) {
                $cityData['urn'] = $cityData['fias'] ?: Text::beautify($cityData['name']);
            } else {
                $cityData['urn'] = Text::beautify($cityData['name']);
            }
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
     * Получает токен доступа СДЭК
     * @return string
     * @throws Exception В случае, если не удалось получить токен
     */
    public function getCDEKAccessToken(): string
    {
        $login = ($GLOBALS['cdek']['login'] ?? null);
        $password = ($GLOBALS['cdek']['password'] ?? null);
        $url = 'https://api.cdek.ru/v2/oauth/token';
        $ch = curl_init($url);
        $postData = [
            'grant_type' => 'client_credentials',
            'client_id' => $login,
            'client_secret' => $password,
        ];
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        $cdekJSON = json_decode($response, true);
        $result = $cdekJSON['access_token'] ?? null;
        if (!$result) {
            $errMsg = 'Cannot get CDEK access token';
            if ($cdekJSON['error'] ?? null) {
                $errMsg .= ': ' . $cdekJSON['error'];
                if ($cdekJSON['error_description'] ?? null) {
                    $errMsg .= '; ' . $cdekJSON['error_description'];
                }
            }
            throw new Exception($errMsg);
        }
        return $result;
    }


    /**
     * Получает список исходных точек выдачи СДЭК
     * @param string $token Токен доступа
     * @return array
     */
    public function getRawCDEKPoints($token): array
    {
        $headers = [
            'Authorization: Bearer ' . $token,
        ];
        $url = 'https://api.cdek.ru/v2/deliverypoints';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        $cdekJSON = (array)json_decode($response, true);
        return $cdekJSON;
    }


    /**
     * Форматирует город СДЭК
     * @param array $item Входные данные точки
     * @return array
     */
    public function formatCDEKCity(array $item): array
    {
        $localRegionName = $item['location']['region'] ?? '';
        $regionURN = $this->normalizeRegionName($localRegionName, true);
        if (!isset($this->regionNamesByURNs[$regionURN])) {
            $this->regionNamesByURNs[$regionURN] = $this->normalizeRegionName($localRegionName);
        }
        $regionName = $this->regionNamesByURNs[$regionURN];
        $cityName = $this->normalizeCityName($item['location']['city'] ?? '');

        $result = [
            'value' => $cityName,
            'name' => $cityName,
            'fias' => trim((string)($item['location']['fias_guid'] ?? '')),
            'region' => $regionName,
            // 'regionURN' => $regionURN,
            'country' => trim((string)($item['location']['country_code'] ?? '')),
            'cdekCityId' => $item['location']['city_code'],
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
            'id' => trim((string)($item['code'] ?? '')),
            'name' => trim((string)($item['name'] ?? '')),
            'address' => trim((string)($item['location']['address'] ?? '')),
            'postalCode' => trim((string)($item['location']['postal_code'] ?? '')),
            'canPay' => ($item['have_cash'] || $item['have_cashless']) && ($item['type'] != 'POSTAMAT'),
            'description' => trim((string)($item['address_comment'] ?? '')),
            'lat' => (float)$item['location']['latitude'],
            'lon' => (float)$item['location']['longitude'],
            'serviceURN' => 'cdek',
            'weight' => null,
            'sizes' => [],
        ];
        if ($item['work_time'] ?? null) {
            $resultItem['schedule'] = $item['work_time'];
        }
        if ($item['phones'] ?? null) {
            $resultItem['phones'] = array_map(function ($x) {
                return Text::beautifyPhone($x['number'], 10);
            }, $item['phones']);
        }
        if ($item['office_image_list'] ?? null) {
            $resultItem['images'] = array_map(function ($x) {
                return $x['url'];
            }, $item['office_image_list']);
        }
        $pvzSizes = array_values($item['dimensions'] ?? []);
        usort($pvzSizes, function ($a, $b) {
            $aPerimeter = array_sum(array_values($a));
            $bPerimeter = array_sum(array_values($b));
            return $bPerimeter - $aPerimeter;
        });
        $pvzSizes = $pvzSizes[0] ?? [];
        if ($pvzSizes) {
            sort($pvzSizes);
            $resultItem['sizes'] = $pvzSizes;
        }
        if ($item['weight_max'] ?? null) {
            $resultItem['weight'] = $item['weight_max'];
        }
        return $resultItem;
    }


    /**
     * Обрабатывает точки Почты России
     * @return array
     */
    public function processRussianPost(): array
    {
        $pochtaJSON = $this->getRawRussianPostPoints();
        $this->controller->doLog('Получены точки выдачи Почты России');
        $result = [];
        foreach ($pochtaJSON as $item) {
            $cityData = $this->formatRussianPostCity($item);
            if ($this->cityURNByFias) {
                $cityData['urn'] = $cityData['fias'] ?: Text::beautify($cityData['name']);
            } else {
                $cityData['urn'] = Text::beautify($cityData['name']);
            }
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

        $headers = [
            'Accept: application/octet-stream',
            'Authorization: AccessToken ' . $token,
            'X-User-Authorization: Basic ' . base64_encode($login . ':' . $password)
        ];
        $url = 'https://otpravka-api.pochta.ru/1.0/unloading-passport/zip?type=ALL';
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
        // file_put_contents('poctha.json', $jsonText);
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
        $localRegionName = $item['address']['region'] ?? '';
        $regionURN = $this->normalizeRegionName($localRegionName, true);
        if (!isset($this->regionNamesByURNs[$regionURN])) {
            $this->regionNamesByURNs[$regionURN] = $this->normalizeRegionName($localRegionName);
        }
        $regionName = $this->regionNamesByURNs[$regionURN];
        $cityName = $this->normalizeCityName($item['address']['place'] ?? '');

        $result = [
            'value' => trim((string)$cityName),
            'name' => trim((string)$cityName),
            'fias' => trim((string)($item['addressFias']['locationGarCode'] ?? '')),
            'region' => trim((string)$regionName),
            // 'regionURN' => $regionURN,
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
                    $addressChunk .= ((mb_strlen($item['address']['building']) > 1) ? ' ' : '')
                        . $item['address']['building'];
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
            'id' => trim((string)($item['address']['index'] ?? '')),
            // 'name' => trim((string)($item['name'] ?? '')),
            'address' => trim(implode(', ', $addressArr)),
            'postalCode' => trim((string)($item['address']['index'] ?? '')),
            // 2023-11-21, AVS: $item['ecomOptions']['cardPayment'] и $item['ecomOptions']['cashPayment']
            // почти всегда false, поэтому пока расчитываем как тип - "...ОПС"
            'canPay' => stristr(($item['type'] ?? ''), 'ОПС'),
            'lat' => (float)($item['latitude'] ?? 0),
            'lon' => (float)($item['longitude'] ?? 0),
            'serviceURN' => 'russianpost',
            // 'inner' => $item,
            'weight' => null,
            'sizes' => [],
        ];
        if ($weightLimit = ($item['ecomOptions']['weightLimit'] ?? 0)) {
            $resultItem['weight'] = $weightLimit;
        }
        $resultItem['name'] = $item['type'];
        if ($item['ecomOptions']['getto'] ?? null) {
            $resultItem['description'] = $item['ecomOptions']['getto'];
        }

        if ($item['workTime'] ?? null) {
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
