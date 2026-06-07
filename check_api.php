<?php
require 'E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/vendor/autoload.php';

use Rubix\ML\CrossValidation\HoldOut;
use Rubix\ML\Datasets\Labeled;

echo "HoldOut methods:\n";
$methods = get_class_methods(HoldOut::class);
print_r($methods);

echo "\nLabeled methods:\n";
$labeled = get_class_methods(Labeled::class);
print_r($labeled);
