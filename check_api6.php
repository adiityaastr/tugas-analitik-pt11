<?php
require 'E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/vendor/autoload.php';

use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Encoding;

$std = new ZScaleStandardizer(true);
echo "Encoding methods:\n";
$methods = get_class_methods(Encoding::class);
print_r($methods);

echo "\nCan we serialize Persistable? ";
$ref = new ReflectionClass(ZScaleStandardizer::class);
echo $ref->implementsInterface('Rubix\ML\Persistable') ? "YES" : "NO";
