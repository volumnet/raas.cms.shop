<?php
/**
 * Интерфейс взаимодействия с транспортной компанией СДЭК
 */
namespace RAAS\CMS\Shop;

use SimpleXMLElement;
use RAAS\Application;
use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Block_PHP;
use RAAS\CMS\Material;
use RAAS\CMS\Page;

/**
 * Класс интерфейса взаимодействия с транспортной компанией СДЭК
 * @property-write string $authLogin Логин для авторизации
 * @property-write string $secure Ключ для авторизации
 */
class SDEKInterface extends AbstractInterface
{
    /**
     * Тестовый режим
     * @var bool
     */
    public $isTest = false;

    /**
     * Сохраненный файл пунктов выдачи заказов
     * @var string
     */
    public $pvzFile = 'sdek.pvz.json';

    /**
     * Объект сохраненных пунктов выдачи заказов
     * @var array
     */
    public $pvzJSON = [];


    /**
     * ID# города отправителя
     * @var int
     */
    public $senderCityId = 250; // Екатеринбург


    /**
     * ID# тарифа
     * @var int
     */
    public $tariffId = 10; // Экспресс лайт склад-склад

    /**
     * Функция расчета товаров
     * @var callable
     */
    public $cartGoodsCalculationCallback = '$this->cartGoods';

    /**
     * Переменная веса
     * @param string
     */
    public $weightVar = 'weight';

    /**
     * Коэффициент веса из переменной
     * @var int
     */
    public $weightRatio = 1;

    /**
     * Вес по умолчанию (кг)
     * @var float
     */
    public $defaultWeight = 1;

    /**
     * Переменная длины
     * @param string
     */
    public $lengthVar = 'length';

    /**
     * Коэффициент длины из переменной
     * @var int
     */
    public $lengthRatio = 1;

    /**
     * Длина по умолчанию (см)
     * @var int
     */
    public $defaultLength = 10;

    /**
     * Переменная ширины
     * @param string
     */
    public $widthVar = 'width';

    /**
     * Коэффициент ширины из переменной
     * @var int
     */
    public $widthRatio = 1;

    /**
     * Ширина по умолчанию (см)
     * @var int
     */
    public $defaultWidth = 10;

    /**
     * Переменная высоты
     * @param string
     */
    public $heightVar = 'height';

    /**
     * Коэффициент высоты из переменной
     * @var int
     */
    public $heightRatio = 1;

    /**
     * Высота по умолчанию (см)
     * @var int
     */
    public $defaultHeight = 10;

    /**
     * Коэффициент расчета цены
     * @var float
     */
    public $priceRatio = 1;

    /**
     * Логин для авторизации
     * @var string
     */
    protected $authLogin = '';

    /**
     * Ключ для авторизации
     * @var string
     */
    protected $secure = '';

    /**
     * Конструктор класса
     * @param Block_PHP|null $block Блок, для которого применяется
     *                               интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     * @param array $files Поля $_FILES параметров
     */
    public function __construct(
        Block_PHP $block = null,
        Page $page = null,
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $session = [],
        array $server = [],
        array $files = []
    ) {
        parent::__construct(
            $block,
            $page,
            $get,
            $post,
            $cookie,
            $session,
            $server,
            $files
        );
    }


    public function __set($var, $val)
    {
        switch ($var) {
            case 'authLogin':
            case 'secure':
                $this->$var = $val;
                break;
        }
    }


    public function process()
    {
        switch ($this->get['action']) {
            case 'regions':
                $result = $this->regions($this->get, $this->page->lang);
                break;
            case 'cities':
                $result = $this->cities($this->get, $this->page->lang);
                break;
            case 'pvz':
                $result = $this->pvz($this->get, $this->page->lang);
                break;
            case 'regions.pvz':
                $result = $this->regionsPVZ($this->get);
                break;
            case 'cities.pvz':
                $result = $this->citiesPVZ($this->get);
                break;
            case 'pvz.pvz':
                $result = $this->pvzPVZ($this->get);
                break;
            case 'calculator':
                $result = $this->calculator($this->get, $this->page->lang);
                break;

        }
        return $result;
    }


    /**
     * Получает список городов
     * @param array $get GET-параметры
     * @param string $lang Язык запроса <pre>ru|en</pre>
     * @return array
     */
    public function regions(array $get = [], $lang = 'ru')
    {
        unset($get['action']);
        $url = 'v1/location/regions/json';
        if ($lang == 'ru') {
            $lang = 'rus';
        } else {
            $lang = 'eng';
        }
        $get['lang'] = $lang;
        if ($get) {
            $url .= '?' . http_build_query($get);
        }
        $result = $this->methodJSON($url);
        return $result;
    }


    /**
     * Получает список регионов из сохраненных пунктов выдачи заказов
     * @param array $get GET-параметры
     * @return array <pre>array<[
     *     'country': string Наименование страны,
     *     'countryCode': string Код страны,
     *     'region': string Наименование региона,
     *     'regionCode': string Код региона,
     * ]></pre>
     */
    public function regionsPVZ(array $get = [])
    {
        if (!$this->loadPVZ()) {
            return [];
        }
        $result = [];
        foreach ($this->pvzJSON as $pvz) {
            $regionCode = trim($pvz['regionCode']);
            if ($result[$regionCode]) {
                continue;
            }
            $countryCode = trim($pvz['countryCodeIso']);
            if ($get['countryCode'] && ($get['countryCode'] != $countryCode)) {
                continue;
            }
            $resultArr = [
                'country' => trim($pvz['countryName']),
                'countryCode' => $countryCode,
                'region' => trim($pvz['regionName']),
                'regionCode' => $regionCode,
            ];
            $result[$regionCode] = $resultArr;
        }
        usort($result, function ($a, $b) {
            return strnatcasecmp($a['cityName'], $b['cityName']);
        });
        return $result;
    }


    /**
     * Получает список городов
     * @param array $get GET-параметры
     * @param string $lang Язык запроса <pre>ru|en</pre>
     * @return array
     */
    public function cities(array $get = [], $lang = 'ru')
    {
        unset($get['action']);
        $url = 'v1/location/cities/json';
        if ($lang == 'ru') {
            $lang = 'rus';
        } else {
            $lang = 'eng';
        }
        $get['lang'] = $lang;
        if ($get) {
            $url .= '?' . http_build_query($get);
        }
        $result = $this->methodJSON($url);
        return $result;
    }


    /**
     * Получает список городов из сохраненных пунктов выдачи заказов
     * @param array $get GET-параметры
     * @return array <pre>array<[
     *     'cityName' => string Наименование города
     *     'cityCode': string Код города,
     *     'country': string Наименование страны,
     *     'countryCode': string Код страны,
     *     'region': string Наименование региона,
     *     'regionCode': string Код региона,
     * ]></pre>
     */
    public function citiesPVZ(array $get = [])
    {
        if (!$this->loadPVZ()) {
            return [];
        }
        $result = [];
        foreach ($this->pvzJSON as $pvz) {
            $cityCode = trim($pvz['cityCode']);
            if ($result[$cityCode]) {
                continue;
            }
            $regionCode = trim($pvz['regionCode']);
            if ($get['regionCode'] && ($get['regionCode'] != $regionCode)) {
                continue;
            }
            $countryCode = trim($pvz['countryCodeIso']);
            if ($get['countryCode'] && ($get['countryCode'] != $countryCode)) {
                continue;
            }
            $resultArr = [
                'cityName' => trim($pvz['city']),
                'cityCode' => $cityCode,
                'country' => trim($pvz['countryName']),
                'countryCode' => $countryCode,
                'region' => trim($pvz['regionName']),
                'regionCode' => $regionCode,
            ];
            $result[$cityCode] = $resultArr;
        }
        usort($result, function ($a, $b) {
            return strnatcasecmp($a['cityName'], $b['cityName']);
        });
        return $result;
    }


    /**
     * Получает список пунктов выдачи заказа
     * @param array $get GET-параметры
     * @param string $lang Язык запроса <pre>ru|en</pre>
     * @return array
     */
    public function pvz(array $get = [], $lang = 'ru')
    {
        unset($get['action']);
        $url = 'pvzlist/v1/json';
        if ($lang == 'ru') {
            $lang = 'rus';
        } else {
            $lang = 'eng';
        }
        $get['lang'] = $lang;
        if ($get) {
            $url .= '?' . http_build_query($get);
        }
        $result = $this->methodJSON($url);
        return $result;
    }


    /**
     * Получает список пунктов выдачи заказа по сохраненному списку
     * @param array $get GET-параметры
     * @return array
     */
    public function pvzPVZ(array $get = [])
    {
        if (!$this->loadPVZ()) {
            return [];
        }
        $result = [];
        foreach ($this->pvzJSON as $pvz) {
            foreach ([
                'cityid' => 'cityCode',
                'regionid' => 'regionCode',
                'countryid' => 'countryCode',
                'countryiso' => 'countryCodeIso',
                'type' => 'type',
                'havecashless' => 'haveCashless',
                'havecash' => 'haveCash',
                'allowedcod' => 'allowedCod',
                'isdressingroom' => 'isDressingRoom',
                'takeonly' => 'takeOnly',
            ] as $getParam => $pvzParam) {
                if (isset($get[$getParam]) && ($pvz[$pvzParam] != $get[$getParam])) {
                    continue 2;
                }
            }
            $result[] = $pvz;
        }
        return $result;
    }


    /**
     * Вычисляет стоимость товаров
     * @param array $get GET-параметры
     * @param string $lang Язык запроса <pre>ru|en</pre>
     * @return array
     */
    public function calculator(array $get = [], $lang = 'ru')
    {
        $fromCart = $get['cart'];
        unset($get['action'], $get['cart']);
        $url = 'calculator/calculate_price_by_json_request.php';
        if ($lang == 'ru') {
            $lang = 'rus';
        } else {
            $lang = 'eng';
        }
        $data = $get;
        if ($fromCart) {
            $cartGoodsCalculationCallback = $this->cartGoodsCalculationCallback;
            if (preg_match('/^\\$this-\\>(\\w+)$/umis', $cartGoodsCalculationCallback, $regs)) {
                $cartGoodsCalculationCallback = [$this, $regs[1]];
            }
            $data['goods'] = $cartGoodsCalculationCallback();
        }
        if (!$data['senderCityId']) {
            $data['senderCityId'] = $this->senderCityId;
        }
        if (!$data['tariffId']) {
            $data['tariffId'] = $this->tariffId;
        }
        if ($this->authLogin) {
            $data['authLogin'] = $this->authLogin;
        }
        if ($this->secure) {
            $data['secure'] = $this->secure;
        }
        $data['tariffId'] = (int)$data['tariffId'];
        $data['senderCityId'] = (int)$data['senderCityId'];
        $data['receiverCityId'] = (int)$data['receiverCityId'];
        $data['version'] = '1.0';
        $data['lang'] = $lang;
        $formData = ['json' => json_encode($data)];
        $result = $this->methodJSON($url, $formData, 'http://api.cdek.ru');
        $result['result']['price'] = ceil($result['result']['price'] * $this->priceRatio);
        $result['result']['priceByCurrency'] = ceil($result['result']['priceByCurrency'] * $this->priceRatio);
        return $result;
    }


    /**
     * Функция расчета товаров из корзины
     * @return array <pre>array<[
     *     'weight' => float Вес в кг,
     *     'length' => int Длина в см,
     *     'width' => int Ширина в см,
     *     'height' => int Высота в см,
     * ]></pre>
     */
    public function cartGoods()
    {
        $cartType = Cart_Type::importByURN('cart');
        $cart = new Cart($cartType);
        $result = [];
        foreach ($cart->items as $cartItem) {
            $material = new Material($cartItem->id);
            $row = [
                'weight' => (float)(($material->{$this->weightVar} * $this->weightRatio) ?: $this->defaultWeight),
                'length' => round(($material->{$this->lengthVar} * $this->lengthRatio) ?: $this->defaultLength),
                'width' => round(($material->{$this->widthVar} * $this->widthRatio) ?: $this->defaultWidth),
                'height' => round(($material->{$this->heightVar} * $this->heightRatio) ?: $this->defaultHeight),
            ];
            for ($i = 0; $i < $cartItem->amount; $i++) {
                $result[] = $row;
            }
        }
        if (!$result) {
            $result[] = [
                'weight' => (float)$this->defaultWeight,
                'length' => round($this->defaultLength),
                'width' => round($this->defaultWidth),
                'height' => round($this->defaultHeight),
            ];
        }
        return $result;
    }


    /**
     * Загружает сохраненные пункты выдачи заказов
     * @param bool $force Перезагрузить в любом случае
     */
    public function loadPVZ($force = false)
    {
        try {
            if (!$this->pvzJSON || $force) {
                $filepath = Application::i()->baseDir . '/' . $this->pvzFile;
                if (!is_file($filepath)) {
                    return false;
                }
                $text = file_get_contents($filepath);
                $json = json_decode($text, true);
                $this->pvzJSON = $json['pvz'];
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * "Сырое" выполнение метода
     * @param string $url Абсолютный URL метода
     * @param string|null $xml XML-запрос
     * @return string
     */
    public function rawMethod($url, $data = null)
    {
        $ch = curl_init($url);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_VERBOSE, 1);
        $result = curl_exec($ch);
        return $result;
    }


    /**
     * Выполнение Метода
     * @param string $methodURL Внутренний адрес метода
     * @param mixed $data Запрос
     * @param string|null $domain Имя домена (по умолчанию стандартное)
     * @return string
     */
    public function method($methodURL, $data = null, $domain = null)
    {
        if (!$domain) {
            $domain = $this->getDomain();
        }
        $url = $domain . '/' . $methodURL;
        $text = $this->rawMethod($url, $data);
        return $text;
    }


    /**
     * Выполнение XML-метода
     * @param string $methodURL Внутренний адрес метода
     * @param mixed $data Запрос
     * @param string|null $domain Имя домена (по умолчанию стандартное)
     * @return SimpleXMLElement
     */
    public function methodXML($methodURL, $data = null, $domain = null)
    {
        $text = $this->method($methodURL, $data, $domain);
        $sxe = new SimpleXMLElement($text);
        return $sxe;
    }


    /**
     * Выполнение JSON-метода
     * @param string $methodURL Внутренний адрес метода
     * @param mixed $data Запрос
     * @param string|null $domain Имя домена (по умолчанию стандартное)
     * @return array
     */
    public function methodJSON($methodURL, $data = null, $domain = null)
    {
        $text = $this->method($methodURL, $data, $domain);
        $json = json_decode($text, true);
        return $json;
    }


    public function getDomain()
    {
        if ($this->isTest) {
            $url = 'https://integration.edu.cdek.ru';
        } else {
            $url = 'https://integration.cdek.ru';
        }
        return $url;
    }
}
