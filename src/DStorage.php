<?php
namespace didphp\Core;

/**
 * Class DStorage
 * 处理存储
 * @package did\Core
 */
class DStorage {
    public $path;

    public function __construct($path='') {
        $this->path = $path;
    }

    public static function disk($path='') {
        return new self($path);
    }

    public function exists($path='') {
        $this->path .= $path;
        return $this->call('file_exists');
    }

    public function mkdir($dir_path='') {
        $this->path .= $dir_path;
        return $this->call('mkdir');
//        return call_user_func("mkdir", 'asd');
    }

    private function call($function='') {
        if ($function == '' || $this->path == '') {
            return false;
        }
        $allowFunction = ['mkdir'];
        if (!in_array($function, $allowFunction)) {
            return false;
        }
        $this->path = str_replace("\\", "/", str_replace("\\\\", "/", $this->path));
        return call_user_func($function, $this->path);
    }
}
