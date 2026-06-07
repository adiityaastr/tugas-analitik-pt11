<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Prediksi Kelulusan Mahasiswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card shadow-lg border-0">

                <div class="card-header bg-primary text-white text-center py-4">

                    <h2 class="mb-2">
                        Sistem Prediksi Kelulusan Mahasiswa
                    </h2>

                    <p class="mb-0">
                        Implementasi Metode Klasifikasi Naive Bayes
                    </p>

                </div>

                <div class="card-body">

                    <!-- Informasi Sistem -->

                    <div class="row mb-4">

                        <div class="col-md-6">

                            <div class="card border-success h-100">
                                <div class="card-body text-center">

                                    <h6 class="text-success">
                                        Data Training
                                    </h6>

                                    <h3>
                                        {{ $totalTraining ?? 500 }}
                                    </h3>

                                    <small>
                                        Data Historis Mahasiswa
                                    </small>

                                    <hr class="my-2">

                                    <div id="accuracy-container">
                                        <h6 class="text-muted mb-1">Akurasi</h6>
                                        <h4 class="text-success mb-0" id="accuracy-score">
                                            {{ $metrics[$defaultModel]['accuracy'] ?? 0 }}%
                                        </h4>
                                        <small class="text-muted" id="accuracy-label">
                                            {{ match($defaultModel) {
                                                'naive-bayes' => 'Naive Bayes',
                                                'random-forest' => 'Random Forest',
                                                'knn' => 'K-Nearest Neighbors',
                                                default => ucfirst(str_replace('-', ' ', $defaultModel)),
                                            } }}
                                        </small>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="card border-info h-100">
                                <div class="card-body text-center">

                                    <h6 class="text-info">
                                        Algoritma
                                    </h6>

                                    <select
                                        id="model-select"
                                        class="form-select form-select-lg mt-2"
                                        onchange="updateAccuracy()"
                                    >

                                        @foreach($models as $key)
                                            <option value="{{ $key }}"
                                                {{ $key === $defaultModel ? 'selected' : '' }}>
                                                {{ match($key) {
                                                    'naive-bayes' => 'Naive Bayes',
                                                    'random-forest' => 'Random Forest',
                                                    'knn' => 'K-Nearest Neighbors',
                                                    default => $key,
                                                } }}
                                            </option>
                                        @endforeach

                                    </select>

                                    <small class="text-muted mt-2 d-block">
                                        Classification
                                    </small>

                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Tombol Validasi -->

                    <div class="d-grid mb-4">
                        <button
                            id="retrain-btn"
                            class="btn btn-warning btn-lg"
                            onclick="validateModels()"
                        >
                            <span id="retrain-spinner" class="d-none spinner-border spinner-border-sm me-2"></span>
                            Validasi Ulang Akurasi
                        </button>
                    </div>

                    <!-- Tabel Detail Metrik -->

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Algoritma</th>
                                    <th>Akurasi</th>
                                    <th>Precision</th>
                                    <th>Recall</th>
                                    <th>F1-Score</th>
                                </tr>
                            </thead>
                            <tbody id="metrics-table-body">
                                @foreach($models as $key)
                                <tr id="row-{{ $key }}">
                                    <td class="fw-bold">{{ match($key) {
                                        'naive-bayes' => 'Naive Bayes',
                                        'random-forest' => 'Random Forest',
                                        'knn' => 'K-Nearest Neighbors',
                                        default => $key,
                                    } }}</td>
                                    <td class="acc-cell" id="acc-{{ $key }}">
                                        {{ $metrics[$key]['accuracy'] ?? 0 }}%
                                    </td>
                                    <td class="prec-cell" id="prec-{{ $key }}">
                                        {{ $metrics[$key]['precision'] ?? 0 }}%
                                    </td>
                                    <td class="rec-cell" id="rec-{{ $key }}">
                                        {{ $metrics[$key]['recall'] ?? 0 }}%
                                    </td>
                                    <td class="f1-cell" id="f1-{{ $key }}">
                                        {{ $metrics[$key]['f1_score'] ?? 0 }}%
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <h4 class="mb-3">
                        Data Testing
                    </h4>

                    <form action="{{ url('/predict') }}" method="POST" id="predict-form">

                        @csrf

                        <input type="hidden" name="model" id="model-input" value="{{ $defaultModel }}">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    IPK
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="4"
                                    name="ipk"
                                    class="form-control"
                                    placeholder="Contoh: 3.50"
                                    value="{{ old('ipk') }}"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Kehadiran (%)
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    name="kehadiran"
                                    class="form-control"
                                    placeholder="Contoh: 90"
                                    value="{{ old('kehadiran') }}"
                                    required>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    SKS Lulus
                                </label>

                                <input
                                    type="number"
                                    name="sks_lulus"
                                    class="form-control"
                                    placeholder="Contoh: 120"
                                    value="{{ old('sks_lulus') }}"
                                    required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label fw-bold">
                                    Status Kerja
                                </label>

                                <select
                                    class="form-select"
                                    name="status_kerja"
                                    required>

                                    <option value="">
                                        -- Pilih Status --
                                    </option>

                                    <option value="Ya"
                                        {{ old('status_kerja') === 'Ya' ? 'selected' : '' }}>
                                        Ya
                                    </option>

                                    <option value="Tidak"
                                        {{ old('status_kerja') === 'Tidak' ? 'selected' : '' }}>
                                        Tidak
                                    </option>

                                </select>

                            </div>

                        </div>

                        <div class="d-grid mt-4">

                            <button
                                type="submit"
                                class="btn btn-success btn-lg">

                                Prediksi Kelulusan

                            </button>

                        </div>

                    </form>

                </div>

                <div class="card-footer text-center text-muted">

                    Data Mining - Classification

                </div>

            </div>

        </div>

    </div>

</div>

@if(session('error'))

<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session("error") }}',
        width: 500,
        confirmButtonText: 'Tutup'
    });
});
</script>

@endif

@if(session('prediction'))

<script>

document.addEventListener('DOMContentLoaded', function() {

    const prediction = '{{ session("prediction") }}';
    const confidence = @json(session('confidence', []));
    const selectedModel = '{{ session("selected_model", "") }}';
    const modelLabel = '{{ session("model_label", "") }}';

    let confidenceHtml = '';
    if (typeof confidence === 'object' && confidence !== null) {
        for (const [key, value] of Object.entries(confidence)) {
            confidenceHtml += `
                <p>
                    Probabilitas ${key} :
                    <strong>${value}%</strong>
                </p>
            `;
        }
    } else {
        confidenceHtml = '<p>Confidence tidak tersedia</p>';
    }

    Swal.fire({

        icon: prediction === 'Ya' ? 'success' : 'warning',

        title: 'Hasil Prediksi',

        html: `
            <div style="text-align:left;font-size:15px;">

                <p>
                    <strong>Status Kelulusan :</strong>
                </p>

                <h4 style="color:
                ${prediction === 'Ya'
                    ? '#198754'
                    : '#dc3545'}">
                    ${prediction === 'Ya'
                        ? '&#9989; Lulus Tepat Waktu'
                        : '&#10060; Tidak Lulus Tepat Waktu'}
                </h4>

                <hr>

                <p>
                    <strong>Model :</strong> ${modelLabel}
                </p>

                ${confidenceHtml}

            </div>
        `,

        width: 650,
        confirmButtonText: 'Tutup'

    });

});

</script>

@endif

<script>
const modelSelect = document.getElementById('model-select');
const modelInput = document.getElementById('model-input');
const accuracyScore = document.getElementById('accuracy-score');
const accuracyLabel = document.getElementById('accuracy-label');
const retrainBtn = document.getElementById('retrain-btn');
const retrainSpinner = document.getElementById('retrain-spinner');

async function updateAccuracy() {
    const model = modelSelect.value;
    if (modelInput) modelInput.value = model;

    try {
        const response = await fetch(`/accuracy/${model}`);
        const data = await response.json();

        if (data.accuracy !== undefined) {
            accuracyScore.textContent = data.accuracy + '%';
            accuracyLabel.textContent = data.label;

            document.getElementById('acc-' + model).textContent = data.accuracy + '%';
            document.getElementById('prec-' + model).textContent = data.precision + '%';
            document.getElementById('rec-' + model).textContent = data.recall + '%';
            document.getElementById('f1-' + model).textContent = data.f1_score + '%';
        }
    } catch (err) {
        console.error('Failed to fetch accuracy:', err);
    }
}

async function validateModels() {
    retrainBtn.disabled = true;
    retrainSpinner.classList.remove('d-none');
    retrainBtn.textContent = '';
    retrainBtn.appendChild(retrainSpinner);
    retrainBtn.append(' Melatih ulang...');

    try {
        const response = await fetch('/retrain', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success && result.models) {
            for (const [key, data] of Object.entries(result.models)) {
                document.getElementById('acc-' + key).textContent = data.accuracy + '%';
                document.getElementById('prec-' + key).textContent = data.precision + '%';
                document.getElementById('rec-' + key).textContent = data.recall + '%';
                document.getElementById('f1-' + key).textContent = data.f1_score + '%';
            }

            const currentModel = modelSelect.value;
            if (result.models[currentModel]) {
                accuracyScore.textContent = result.models[currentModel].accuracy + '%';
                accuracyLabel.textContent = result.models[currentModel].label;
            }

            Swal.fire({
                icon: 'success',
                title: 'Validasi Selesai',
                text: 'Model berhasil dilatih ulang dengan split data baru.',
                timer: 3000,
                showConfirmButton: false,
            });
        }
    } catch (err) {
        console.error('Retrain failed:', err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Validasi gagal. Periksa koneksi database.',
        });
    } finally {
        retrainBtn.disabled = false;
        retrainSpinner.classList.add('d-none');
        retrainBtn.textContent = 'Validasi Ulang Akurasi';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (modelInput) modelInput.value = modelSelect.value;
});
</script>

</body>

</html>
