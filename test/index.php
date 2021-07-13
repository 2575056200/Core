<?php
require '../src/DStorage.php';

$path = '../data';
$a = \didphp\Core\DStorage::disk($path)->mkdir();
var_dump($a);