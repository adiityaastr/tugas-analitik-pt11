<?php
require 'E:/tuags_analitik_pt11/Analitik-Virtualisasi-Data/laravel-app/vendor/autoload.php';

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\ZScaleStandardizer;

$sample = new Unlabeled([[3.5, 90, 120, 1.0]]);
echo "Unlabeled methods (filtered): ";
$methods = get_class_methods(Unlabeled::class);
foreach ($methods as $m) {
    if (in_array($m, ['apply', 'samples', 'sample'])) echo "$m ";
}
echo "\n";

$std = new ZScaleStandardizer(true);
// fit on dummy data
$dummy = new Rubix\ML\Datasets\Labeled([[1,2,3,4], [5,6,7,8]], ['a', 'b']);
$std->fit($dummy);

echo "Before: ";
print_r($sample->sample(0));

$sample->apply($std);

echo "After: ";
print_r($sample->sample(0));
