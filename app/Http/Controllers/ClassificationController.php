<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mahasiswa;
use App\Services\PredictorService;

class ClassificationController extends Controller
{
    private PredictorService $predictor;

    public function __construct(PredictorService $predictor)
    {
        $this->predictor = $predictor;
    }

    public function index()
    {
        $totalTraining = Mahasiswa::count();
        $metrics = $this->predictor->getMetrics();
        $models = $this->predictor->getAvailableModels();
        $defaultModel = 'random-forest';

        return view('klasifikasi', compact(
            'totalTraining',
            'metrics',
            'models',
            'defaultModel',
        ));
    }

    public function predict(Request $request)
    {
        $request->validate([
            'ipk' => 'required|numeric',
            'kehadiran' => 'required|numeric',
            'sks_lulus' => 'required|numeric',
            'status_kerja' => 'required',
            'model' => 'sometimes|string',
        ]);

        $input = [
            (float) $request->ipk,
            (float) $request->kehadiran,
            (float) $request->sks_lulus,
            $request->status_kerja === 'Ya' ? 1.0 : 0.0,
        ];

        $modelKey = $request->input('model', 'random-forest');

        try {
            $result = $this->predictor->predict($modelKey, $input);
        } catch (\Exception $e) {
            return redirect('/')->with('error', $e->getMessage());
        }

        return redirect('/')
            ->with('prediction', $result['prediction'])
            ->with('confidence', $result['confidence'])
            ->with('selected_model', $modelKey)
            ->with('model_label', $this->predictor->getModelLabel($modelKey))
            ->withInput();
    }

    public function accuracy(string $model)
    {
        $metrics = $this->predictor->getMetrics();

        if (!isset($metrics[$model])) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        $data = $metrics[$model];

        return response()->json([
            'model' => $model,
            'label' => $this->predictor->getModelLabel($model),
            'accuracy' => $data['accuracy'] ?? 0,
            'precision' => $data['precision'] ?? 0,
            'recall' => $data['recall'] ?? 0,
            'f1_score' => $data['f1_score'] ?? 0,
        ]);
    }

    public function retrain()
    {
        $metrics = $this->predictor->forceRetrain();

        $result = [];
        foreach ($metrics as $key => $data) {
            $result[$key] = [
                'label' => $this->predictor->getModelLabel($key),
                'accuracy' => $data['accuracy'] ?? 0,
                'precision' => $data['precision'] ?? 0,
                'recall' => $data['recall'] ?? 0,
                'f1_score' => $data['f1_score'] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'models' => $result,
        ]);
    }
}
