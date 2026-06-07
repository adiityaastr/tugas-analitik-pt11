<?php
require 'E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/vendor/autoload.php';

use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\PersistentModel;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\Encoding;

$std = new ZScaleStandardizer(true);
echo "ZScaleStandardizer interfaces: ";
print_r(class_implements($std));

echo "\nPersistentModel check: ";
$rf = new ReflectionClass(PersistentModel::class);
$methods = $rf->getMethods();
foreach ($methods as $m) echo $m->getName() . " ";

echo "\n\nEncoding class: " . Encoding::class . "\n";

// Try saving estimator via PersistentModel
$knn = new KNearestNeighbors(3, true);
$dataset = new Rubix\ML\Datasets\Labeled([[1,2,3,4],[5,6,7,8]], ['a','b']);
$knn->train($dataset);

$persistent = new PersistentModel($knn, new Filesystem('E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/storage/app/models/test.model'));
$persistent->save();
echo "Saved KNN via PersistentModel OK\n";

$loaded = PersistentModel::load(new Filesystem('E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/storage/app/models/test.model'));
echo "Loaded model: " . get_class($loaded) . "\n";
