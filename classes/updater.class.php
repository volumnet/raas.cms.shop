<?php
namespace RAAS\CMS\Shop;
use \RAAS\IContext;
use \RAAS\CMS\Snippet;
use \RAAS\CMS\Snippet_Folder;

class Updater extends \RAAS\Updater
{
    public function preInstall()
    {
    }


    public function postInstall()
    {
        $w = new Webmaster();
        $s = Snippet::importByURN('__RAAS_shop_cart_interface');
        $w->checkStdSnippets();
        if (!$s || !$s->id) {
            $w->createWidgets();
        }
    }
}