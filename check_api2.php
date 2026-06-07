<?php
require 'E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/vendor/autoload.php';

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use Rubix\ML\CrossValidation\Reports\ConfusionMatrix;
use Rubix\ML\Transformers\ZScaleStandardizer;

// Test basic flow
$samples = [
    [3.5, 90, 120, 1.0],
    [2.1, 60, 80, 0.0],
    [3.8, 95, 130, 0.0],
    [2.3, 55, 70, 1.0],
    [3.2, 85, 110, 1.0],
    [2.8, 70, 100, 0.0],
    [3.0, 88, 115, 1.0],
    [2.5, 65, 85, 0.0],
];
$labels = ['Ya', 'Tidak', 'Ya', 'Tidak', 'Ya', 'Tidak', 'Ya', 'Tidak'];

$dataset = new Labeled($samples, $labels);
$dataset->randomize();
[$train, $test] = $dataset->stratifiedSplit(0.75);

echo "Train size: " . $train->numSamples() . "\n";
echo "Test size: " . $test->numSamples() . "\n";

$std = new ZScaleStandardizer(true);
$std->fit($train);

$train = clone $train;
$test = clone $test;
$train->apply($std);
$test->apply($std);

$knn = new KNearestNeighbors(3, true);
$knn->train($train);

$predictions = $knn->predict($test);
echo "Predictions: ";
print_r($predictions);

$accuracy = (new Accuracy())->score($predictions, $test->labels());
echo "Accuracy: " . ($accuracy * 100) . "%\n";

$cm = new ConfusionMatrix();
$report = $cm->generate($predictions, $test->labels());

echo "CM class: " . get_class($report) . "\n";
echo "CM methods: ";
print_r(get_class_methods($report));

echo "CM toArray exists? " . (method_exists($report, 'toArray') ? 'yes' : 'no') . "\n";

echo "classes: ";
print_r($knn->classes());

echo "\nProba test:\n";
$proba = $knn->proba($test);
print_r($proba);
