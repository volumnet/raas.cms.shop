<?php
/**
 * Файл класса интерфейса электронной оплаты
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Page;

/**
 * Класс интерфейса электронной оплаты
 */
abstract class EPayInterface extends AbstractInterface
{
    /**
     * Конструктор класса
     * @param Block_Cart $block Блок, для которого применяется интерфейс
     * @param Page|null $page Страница, для которой применяется интерфейс
     * @param array $get Поля $_GET параметров
     * @param array $post Поля $_POST параметров
     * @param array $cookie Поля $_COOKIE параметров
     * @param array $session Поля $_SESSION параметров
     * @param array $server Поля $_SERVER параметров
     */
    public function __construct(Block_Cart $block, Page $page = null, array $get = array(), array $post = array(), array $cookie = array(), array $session = array(), array $server = array())
    {
        parent::__construct($block, $page, $get, $post, $cookie, $session, $server);
    }
}
