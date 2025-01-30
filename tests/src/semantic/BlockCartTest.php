<?php
/**
 * Тест класса Block_Cart
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\Application;
use RAAS\Controller_Frontend as ControllerFrontend;
use RAAS\CMS\Package;

/**
 * Тест класса Block_Cart
 */
#[CoversClass(Block_Cart::class)]
class BlockCartTest extends BaseTest
{
    public static $tables = [
    ];

    public static function setUpBeforeClass(): void
    {
        ControllerFrontend::i()->exportLang(Application::i(), 'ru');
        ControllerFrontend::i()->exportLang(Package::i(), 'ru');
        ControllerFrontend::i()->exportLang(Module::i(), 'ru');
    }

    /**
     * Тест метода commit() - случай с установленным виджетом, без интерфейса
     */
    public function testCommit()
    {
        $block = new Block_Cart(['location' => 'content', 'cats' => [1]]);
        $block->commit();

        $this->assertEquals('Корзина', $block->name);

        Block_Cart::delete($block);
    }


    /**
     * Тест метода getAddData
     */
    public function testGetAddData()
    {
        $block = new Block_Cart([
            'cart_type' => 1,
            'epay_interface_id' => 2,
            'epay_login' => 'login',
            'epay_pass1' => 'pass1',
            'epay_pass2' => 'pass2',
            'epay_test' => true,
            'epay_currency' => 'RUB',
            'location' => 'content',
            'cats' => [1]
        ]);
        $block->commit();
        $blockId = $block->id;

        $result = $block->getAddData();

        $this->assertEquals($blockId, $result['id']);
        $this->assertEquals(1, $result['cart_type']);
        $this->assertEquals(2, $result['epay_interface_id']);
        $this->assertEquals('', $result['epay_interface_classname']);
        $this->assertEquals('login', $result['epay_login']);
        $this->assertEquals('pass1', $result['epay_pass1']);
        $this->assertEquals('pass2', $result['epay_pass2']);
        $this->assertEquals(1, $result['epay_test']);
        $this->assertEquals('RUB', $result['epay_currency']);

        Block_Cart::delete($block);
    }


    /**
     * Тест метода getAddData() - случай с переданным классом платежного интерфейса
     */
    public function testGetAddDataWithEpayInterfaceClassname()
    {
        $block = new Block_Cart([
            'cart_type' => 1,
            'epay_interface_id' => 2,
            'epay_interface_classname' => SberbankInterface::class,
            'epay_login' => 'login',
            'epay_pass1' => 'pass1',
            'epay_pass2' => 'pass2',
            'epay_test' => true,
            'epay_currency' => 'RUB',
            'location' => 'content',
            'cats' => [1]
        ]);
        $block->commit();
        $blockId = $block->id;

        $result = $block->getAddData();

        $this->assertEquals($blockId, $result['id']);
        $this->assertEquals(1, $result['cart_type']);
        $this->assertEquals(0, $result['epay_interface_id']);
        $this->assertEquals(SberbankInterface::class, $result['epay_interface_classname']);
        $this->assertEquals('login', $result['epay_login']);
        $this->assertEquals('pass1', $result['epay_pass1']);
        $this->assertEquals('pass2', $result['epay_pass2']);
        $this->assertEquals(1, $result['epay_test']);
        $this->assertEquals('RUB', $result['epay_currency']);

        Block_Cart::delete($block);
    }
}
