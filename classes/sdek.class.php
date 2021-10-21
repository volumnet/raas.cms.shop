<?php
/**
 * Класс интеграции со СДЭК (версия API 2)
 */
namespace RAAS\CMS\Shop\SDEK;

/**
 * Класс интеграции со СДЭК (версия API 2)
 */
class SDEK
{
    /**
     * Тестовый режим
     * @var bool
     */
    public $isTest = false;

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
     * Токен авторизации
     */
    protected $authToken = '';

    /**
     * Конструктор класса
     * @param string $authLogin Логин
     * @param string $authToken Ключ авторизации
     */
    public function __construct($authLogin, $secure)
    {
        $this->authLogin = $authLogin;
        $this->secure = $secure;
    }


    /**
     * Авторизация
     */
    public function auth()
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->authLogin,
            'client_secret' => $this->secure,
        ];
        $response = $this->method('oauth/token', $data, null, false);
        if ($response['access_token']) {
            $this->authToken = $response['access_token'];
        }
    }


    /**
     * Выполнение метода
     * @param string $methodName Внутренний адрес метода
     * @param mixed $data Запрос
     * @param string|null $domain Имя домена (по умолчанию стандартное)
     * @param bool $isJSON отправлять данные в JSON-формате
     * @return array
     */
    public function method($methodName, $data = null, $domain = null, $isJSON = true)
    {
        if (!$domain) {
            if ($this->isTest) {
                $domain = 'https://api.edu.cdek.ru/v2';
            } else {
                $domain = 'https://api.cdek.ru/v2';
            }
        }
        $url = $domain . '/' . $methodName;

        $headers = [];
        if ($isJSON) {
            $headers[] = 'Content-Type: application/json';
            if ($data) {
                $data = json_encode($data);
            }
        }
        $ch = curl_init($url);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_VERBOSE, 1);
        if ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $text = curl_exec($ch);
        $json = json_decode($text, true);
        return $json;
    }
}
