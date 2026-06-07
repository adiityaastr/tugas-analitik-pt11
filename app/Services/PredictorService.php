<?php

namespace App\Services;

use App\Models\Mahasiswa;
use Rubix\ML\Classifiers\ClassificationTree;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\Classifiers\RandomForest;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use Rubix\ML\CrossValidation\Reports\ConfusionMatrix;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Transformers\ZScaleStandardizer;

class PredictorService
{
    private string $storagePath;
    private string $metricsPath;

    private array $availableModels = ['naive-bayes', 'random-forest', 'knn'];

    public function __construct()
    {
        $this->storagePath = storage_path('app/models/');
        $this->metricsPath = $this->storagePath . 'metrics.json';
    }

    public function getAvailableModels(): array
    {
        return $this->availableModels;
    }

    public function getModelLabel(string $key): string
    {
        return match ($key) {
            'naive-bayes' => 'Naive Bayes',
            'random-forest' => 'Random Forest',
            'knn' => 'K-Nearest Neighbors',
            default => $key,
        };
    }

    public function getMetrics(): array
    {
        if (file_exists($this->metricsPath)) {
            $data = json_decode(file_get_contents($this->metricsPath), true);
            if (!empty($data)) {
                return $data;
            }
        }

        return $this->trainAll();
    }

    public function forceRetrain(): array
    {
        $files = glob($this->storagePath . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return $this->trainAll();
    }

    public function trainAll(): array
    {
        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }

        $dataset = $this->loadDataset();

        $dataset->randomize();
        [$training, $testing] = $dataset->stratifiedSplit(0.8);

        $standardizer = new ZScaleStandardizer(true);
        $standardizer->fit($training);

        $trainingNumeric = clone $training;
        $testingNumeric = clone $testing;

        $trainingNumeric->apply($standardizer);
        $testingNumeric->apply($standardizer);

        file_put_contents($this->storagePath . 'standardizer.ser', serialize($standardizer));

        $metrics = [];

        $metrics['naive-bayes'] = $this->trainAndEvaluateNaiveBayes($training, $testing);
        $metrics['random-forest'] = $this->trainAndEvaluateRandomForest($trainingNumeric, $testingNumeric);
        $metrics['knn'] = $this->trainAndEvaluateKnn($trainingNumeric, $testingNumeric);

        file_put_contents($this->metricsPath, json_encode($metrics, JSON_PRETTY_PRINT));

        return $metrics;
    }

    public function predict(string $modelKey, array $input): array
    {
        $standardizer = null;
        $stdPath = $this->storagePath . 'standardizer.ser';

        if (file_exists($stdPath)) {
            $standardizer = unserialize(file_get_contents($stdPath));
        }

        $sample = new Unlabeled([$input]);

        if ($standardizer !== null) {
            $sample->apply($standardizer);
        }

        return match ($modelKey) {
            'random-forest' => $this->predictPersistedModel('random_forest.model', $sample),
            'knn' => $this->predictPersistedModel('knn.model', $sample),
            'naive-bayes' => $this->predictNaiveBayes($input),
            default => throw new \InvalidArgumentException("Unknown model: $modelKey"),
        };
    }

    private function loadDataset(): Labeled
    {
        $data = Mahasiswa::all();

        $samples = [];
        $labels = [];

        foreach ($data as $row) {
            $samples[] = [
                (float) $row->ipk,
                (float) $row->kehadiran,
                (float) $row->sks_lulus,
                $row->status_kerja === 'Ya' ? 1.0 : 0.0,
            ];
            $labels[] = $row->tepat_waktu;
        }

        return new Labeled($samples, $labels);
    }

    private function predictPersistedModel(string $filename, Unlabeled $sample): array
    {
        $path = $this->storagePath . $filename;

        if (!file_exists($path)) {
            throw new \RuntimeException("Model file not found: $filename. Please train models first.");
        }

        $persister = new Filesystem($path);
        $model = PersistentModel::load($persister);

        $predictions = $model->predict($sample);
        $probabilities = $model->proba($sample);

        $label = $predictions[0] ?? 'Unknown';

        $confidence = '';
        if ($probabilities && isset($probabilities[0])) {
            $probs = $probabilities[0];
            $confidence = [];
            foreach ($probs as $cls => $prob) {
                $confidence[$cls] = round($prob * 100, 2);
            }
        }

        return [
            'prediction' => $label,
            'confidence' => $confidence,
        ];
    }

    private function trainAndEvaluateRandomForest(Labeled $training, Labeled $testing): array
    {
        $model = new RandomForest(
            new ClassificationTree(10, 3, 1e-7, 3),
            100,
            0.2,
            false,
        );

        $model->train($training);

        $path = $this->storagePath . 'random_forest.model';
        $persistent = new PersistentModel($model, new Filesystem($path));
        $persistent->save();

        $predictions = $model->predict($testing);
        $report = new ConfusionMatrix();
        $cm = $report->generate($predictions, $testing->labels());

        $accuracy = (new Accuracy())->score($predictions, $testing->labels());

        $possibleOutcomes = $testing->possibleOutcomes();

        $precision = $this->computePrecision($cm);
        $recall = $this->computeRecall($cm);
        $f1 = $this->computeF1($precision, $recall);

        return [
            'accuracy' => round($accuracy * 100, 2),
            'precision' => round($precision * 100, 2),
            'recall' => round($recall * 100, 2),
            'f1_score' => round($f1 * 100, 2),
            'confusion_matrix' => $cm->toArray(),
            'classes' => $possibleOutcomes,
        ];
    }

    private function trainAndEvaluateKnn(Labeled $training, Labeled $testing): array
    {
        $model = new KNearestNeighbors(5, true);

        $model->train($training);

        $path = $this->storagePath . 'knn.model';
        $persistent = new PersistentModel($model, new Filesystem($path));
        $persistent->save();

        $predictions = $model->predict($testing);
        $report = new ConfusionMatrix();
        $cm = $report->generate($predictions, $testing->labels());

        $accuracy = (new Accuracy())->score($predictions, $testing->labels());

        $possibleOutcomes = $testing->possibleOutcomes();

        $precision = $this->computePrecision($cm);
        $recall = $this->computeRecall($cm);
        $f1 = $this->computeF1($precision, $recall);

        return [
            'accuracy' => round($accuracy * 100, 2),
            'precision' => round($precision * 100, 2),
            'recall' => round($recall * 100, 2),
            'f1_score' => round($f1 * 100, 2),
            'confusion_matrix' => $cm->toArray(),
            'classes' => $possibleOutcomes,
        ];
    }

    private function trainAndEvaluateNaiveBayes(Labeled $training, Labeled $testing): array
    {
        $trainingSamples = $training->samples();
        $trainingLabels = $training->labels();

        $totalYa = 0;
        $totalTidak = 0;

        $ipkYaHigh = 0;
        $ipkTidakHigh = 0;
        $hadirYaHigh = 0;
        $hadirTidakHigh = 0;
        $sksYaHigh = 0;
        $sksTidakHigh = 0;
        $kerjaYaYa = 0;
        $kerjaYaTidak = 0;
        $kerjaTidakYa = 0;
        $kerjaTidakTidak = 0;

        foreach ($trainingSamples as $i => $sample) {
            $label = $trainingLabels[$i];
            $isYa = $label === 'Ya';

            if ($isYa) {
                $totalYa++;
            } else {
                $totalTidak++;
            }

            $ipkHigh = $sample[0] >= 3.0;
            $hadirHigh = $sample[1] >= 80;
            $sksHigh = $sample[2] >= 110;
            $statusKerja = $sample[3] >= 0.5 ? 'Ya' : 'Tidak';

            if ($isYa) {
                if ($ipkHigh) $ipkYaHigh++;
                if ($hadirHigh) $hadirYaHigh++;
                if ($sksHigh) $sksYaHigh++;
                if ($statusKerja === 'Ya') $kerjaYaYa++;
                else $kerjaTidakYa++;
            } else {
                if ($ipkHigh) $ipkTidakHigh++;
                if ($hadirHigh) $hadirTidakHigh++;
                if ($sksHigh) $sksTidakHigh++;
                if ($statusKerja === 'Ya') $kerjaYaTidak++;
                else $kerjaTidakTidak++;
            }
        }

        $total = $totalYa + $totalTidak;
        $pYa = $totalYa / $total;
        $pTidak = $totalTidak / $total;

        $pIpkYaHigh = ($ipkYaHigh + 1) / ($totalYa + 2);
        $pIpkYaLow = ($totalYa - $ipkYaHigh + 1) / ($totalYa + 2);
        $pIpkTidakHigh = ($ipkTidakHigh + 1) / ($totalTidak + 2);
        $pIpkTidakLow = ($totalTidak - $ipkTidakHigh + 1) / ($totalTidak + 2);

        $pHadirYaHigh = ($hadirYaHigh + 1) / ($totalYa + 2);
        $pHadirYaLow = ($totalYa - $hadirYaHigh + 1) / ($totalYa + 2);
        $pHadirTidakHigh = ($hadirTidakHigh + 1) / ($totalTidak + 2);
        $pHadirTidakLow = ($totalTidak - $hadirTidakHigh + 1) / ($totalTidak + 2);

        $pSksYaHigh = ($sksYaHigh + 1) / ($totalYa + 2);
        $pSksYaLow = ($totalYa - $sksYaHigh + 1) / ($totalYa + 2);
        $pSksTidakHigh = ($sksTidakHigh + 1) / ($totalTidak + 2);
        $pSksTidakLow = ($totalTidak - $sksTidakHigh + 1) / ($totalTidak + 2);

        $pKerjaYaYa = ($kerjaYaYa + 1) / ($totalYa + 2);
        $pKerjaTidakYa = ($kerjaTidakYa + 1) / ($totalYa + 2);
        $pKerjaYaTidak = ($kerjaYaTidak + 1) / ($totalTidak + 2);
        $pKerjaTidakTidak = ($kerjaTidakTidak + 1) / ($totalTidak + 2);

        $testingSamples = $testing->samples();
        $testingLabels = $testing->labels();

        $predictions = [];
        foreach ($testingSamples as $sample) {
            $ipkHigh = $sample[0] >= 3.0;
            $hadirHigh = $sample[1] >= 80;
            $sksHigh = $sample[2] >= 110;
            $statusKerja = $sample[3] >= 0.5 ? 'Ya' : 'Tidak';

            $probYa = $pYa
                * ($ipkHigh ? $pIpkYaHigh : $pIpkYaLow)
                * ($hadirHigh ? $pHadirYaHigh : $pHadirYaLow)
                * ($sksHigh ? $pSksYaHigh : $pSksYaLow)
                * ($statusKerja === 'Ya' ? $pKerjaYaYa : $pKerjaTidakYa);

            $probTidak = $pTidak
                * ($ipkHigh ? $pIpkTidakHigh : $pIpkTidakLow)
                * ($hadirHigh ? $pHadirTidakHigh : $pHadirTidakLow)
                * ($sksHigh ? $pSksTidakHigh : $pSksTidakLow)
                * ($statusKerja === 'Ya' ? $pKerjaYaTidak : $pKerjaTidakTidak);

            $predictions[] = $probYa > $probTidak ? 'Ya' : 'Tidak';
        }

        $report = new ConfusionMatrix();
        $cm = $report->generate($predictions, $testingLabels);

        $accuracy = (new Accuracy())->score($predictions, $testingLabels);

        $precision = $this->computePrecision($cm);
        $recall = $this->computeRecall($cm);
        $f1 = $this->computeF1($precision, $recall);

        return [
            'accuracy' => round($accuracy * 100, 2),
            'precision' => round($precision * 100, 2),
            'recall' => round($recall * 100, 2),
            'f1_score' => round($f1 * 100, 2),
            'confusion_matrix' => $cm->toArray(),
            'classes' => ['Tidak', 'Ya'],
        ];
    }

    private function predictNaiveBayes(array $sample): array
    {
        $metrics = $this->getMetrics();

        $total = Mahasiswa::count();
        $totalYa = Mahasiswa::where('tepat_waktu', 'Ya')->count();
        $totalTidak = Mahasiswa::where('tepat_waktu', 'Tidak')->count();

        if ($total == 0) {
            throw new \RuntimeException('Data training tidak ditemukan.');
        }

        $pYa = $totalYa / $total;
        $pTidak = $totalTidak / $total;

        $ipkTinggi = $sample[0] >= 3.0;
        $hadirTinggi = $sample[1] >= 80;
        $sksTinggi = $sample[2] >= 110;

        $ipkYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('ipk', $ipkTinggi ? '>=' : '<', 3.0)
            ->count();
        $ipkTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('ipk', $ipkTinggi ? '>=' : '<', 3.0)
            ->count();

        $hadirYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('kehadiran', $hadirTinggi ? '>=' : '<', 80)
            ->count();
        $hadirTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('kehadiran', $hadirTinggi ? '>=' : '<', 80)
            ->count();

        $sksYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('sks_lulus', $sksTinggi ? '>=' : '<', 110)
            ->count();
        $sksTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('sks_lulus', $sksTinggi ? '>=' : '<', 110)
            ->count();

        $statusKerja = $sample[3] >= 0.5 ? 'Ya' : 'Tidak';
        $kerjaYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('status_kerja', $statusKerja)
            ->count();
        $kerjaTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('status_kerja', $statusKerja)
            ->count();

        $pIpkYa = ($ipkYa + 1) / ($totalYa + 2);
        $pIpkTidak = ($ipkTidak + 1) / ($totalTidak + 2);
        $pHadirYa = ($hadirYa + 1) / ($totalYa + 2);
        $pHadirTidak = ($hadirTidak + 1) / ($totalTidak + 2);
        $pSksYa = ($sksYa + 1) / ($totalYa + 2);
        $pSksTidak = ($sksTidak + 1) / ($totalTidak + 2);
        $pKerjaYa = ($kerjaYa + 1) / ($totalYa + 2);
        $pKerjaTidak = ($kerjaTidak + 1) / ($totalTidak + 2);

        $probYa = $pYa * $pIpkYa * $pHadirYa * $pSksYa * $pKerjaYa;
        $probTidak = $pTidak * $pIpkTidak * $pHadirTidak * $pSksTidak * $pKerjaTidak;

        $label = $probYa > $probTidak ? 'Ya' : 'Tidak';

        $totalProb = $probYa + $probTidak;
        $confidence = [];
        if ($totalProb > 0) {
            $confidence['Ya'] = round(($probYa / $totalProb) * 100, 2);
            $confidence['Tidak'] = round(($probTidak / $totalProb) * 100, 2);
        }

        return [
            'prediction' => $label,
            'confidence' => $confidence,
        ];
    }

    private function computePrecision($cm): float
    {
        $matrix = $cm->toArray();
        $classes = array_keys($matrix);
        $precisions = [];

        foreach ($classes as $class) {
            $tp = $matrix[$class][$class] ?? 0;
            $fp = 0;
            foreach ($classes as $c) {
                if ($c !== $class) {
                    $fp += $matrix[$c][$class] ?? 0;
                }
            }
            $precisions[$class] = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0;
        }

        return count($precisions) > 0 ? array_sum($precisions) / count($precisions) : 0;
    }

    private function computeRecall($cm): float
    {
        $matrix = $cm->toArray();
        $classes = array_keys($matrix);
        $recalls = [];

        foreach ($classes as $class) {
            $tp = $matrix[$class][$class] ?? 0;
            $fn = 0;
            foreach ($classes as $c) {
                if ($c !== $class) {
                    $fn += $matrix[$class][$c] ?? 0;
                }
            }
            $recalls[$class] = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0;
        }

        return count($recalls) > 0 ? array_sum($recalls) / count($recalls) : 0;
    }

    private function computeF1(float $precision, float $recall): float
    {
        return ($precision + $recall) > 0
            ? 2 * ($precision * $recall) / ($precision + $recall)
            : 0;
    }
}
