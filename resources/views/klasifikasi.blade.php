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

        <div class="col-lg-8">

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

                            <div class="card border-success">
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

                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="card border-info">
                                <div class="card-body text-center">

                                    <h6 class="text-info">
                                        Algoritma
                                    </h6>

                                    <h3>
                                        Naive Bayes
                                    </h3>

                                    <small>
                                        Classification
                                    </small>

                                </div>
                            </div>

                        </div>

                    </div>

                    <hr>

                    <h4 class="mb-3">
                        Data Testing
                    </h4>

                    <form action="{{ url('/predict') }}" method="POST">

                        @csrf

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

                                    <option value="Ya">
                                        Ya
                                    </option>

                                    <option value="Tidak">
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

                    Data Mining - Classification using Naive Bayes

                </div>

            </div>

        </div>

    </div>

</div>

@if(session('prediction'))

<script>

document.addEventListener('DOMContentLoaded', function() {

    Swal.fire({

        icon: '{{ session("prediction") == "Ya" ? "success" : "warning" }}',

        title: 'Hasil Prediksi',

        html: `
            <div style="text-align:left;font-size:15px;">

                <p>
                    <strong>Status Kelulusan :</strong>
                </p>

                <h4 style="color:
                {{ session('prediction') == 'Ya'
                    ? '#198754'
                    : '#dc3545' }}">
                    {{ session('prediction') == 'Ya'
                        ? '✅ Lulus Tepat Waktu'
                        : '❌ Tidak Lulus Tepat Waktu' }}
                </h4>

                <hr>

                <p>
                    Probabilitas Ya :
                    <strong>
                        {{ round(session('prob_ya',0) * 100,2) }}%
                    </strong>
                </p>

                <p>
                    Probabilitas Tidak :
                    <strong>
                        {{ round(session('prob_tidak',0) * 100,2) }}%
                    </strong>
                </p>

            </div>
        `,

        width: 650,
        confirmButtonText: 'Tutup'

    });

});

</script>

@endif

</body>

</html>