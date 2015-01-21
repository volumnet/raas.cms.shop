<?php
namespace RAAS\CMS\Shop;

class ImageLoader extends \SOME\SOME
{
    protected static $tablename = 'cms_shop_imageloaders';
    protected static $defaultOrderBy = "name";
    protected static $references = array(
        'Material_Type' => array('FK' => 'mtype', 'classname' => 'RAAS\\CMS\\Material_Type', 'cascade' => true),
        'Unique_Field' => array('FK' => 'ufid', 'classname' => 'RAAS\\CMS\\Material_Field', 'cascade' => false),
        'Image_Field' => array('FK' => 'ifid', 'classname' => 'RAAS\\CMS\\Material_Field', 'cascade' => false),
        'Interface' => array('FK' => 'interface_id', 'classname' => 'RAAS\\CMS\\Snippet', 'cascade' => true),
    );
    
    public function commit()
    {
        if (!trim($this->name) && trim($this->Material_Type->name)) {
            $this->name = $this->Material_Type->name;
        }
        parent::commit();
    }


    public function upload(array $files = null, $test = false, $clear = false)
    {
        $Loader = $this;
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }


    public function download()
    {
        $Loader = $this;
        $OUT = eval('?' . '>' . $this->Interface->description);
        return $OUT;
    }
}