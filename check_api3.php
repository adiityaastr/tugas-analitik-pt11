<?php
require 'E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/vendor/autoload.php';

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\CrossValidation\Reports\ConfusionMatrix;

$samples = [
    [3.5, 90, 120, 1.0], [2.1, 60, 80, 0.0],
    [3.8, 95, 130, 0.0], [2.3, 55, 70, 1.0],
];
$labels = ['Ya', 'Tidak', 'Ya', 'Tidak'];
$dataset = new Labeled($samples, $labels);

echo "possibleOutcomes: ";
print_r($dataset->possibleOutcomes());

$knn = new KNearestNeighbors(3, true);
$knn->train($dataset);

$proba = $knn->proba($dataset);
echo "\nproba:\n";
print_r($proba);

$predictions = $knn->predict($dataset);
$cmRep = new ConfusionMatrix();
$report = $cmRep->generate($predictions, $labels);
echo "\nReport toArray:\n";
print_r($report->toArray());
