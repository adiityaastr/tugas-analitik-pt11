<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mahasiswa;

class ClassificationController extends Controller
{
    public function index()
    {
        $totalTraining = Mahasiswa::count();

        return view('klasifikasi', compact('totalTraining'));
    }

    public function predict(Request $request)
    {
        $request->validate([
            'ipk' => 'required|numeric',
            'kehadiran' => 'required|numeric',
            'sks_lulus' => 'required|numeric',
            'status_kerja' => 'required'
        ]);

        $total = Mahasiswa::count();

        $totalYa = Mahasiswa::where('tepat_waktu', 'Ya')->count();
        $totalTidak = Mahasiswa::where('tepat_waktu', 'Tidak')->count();

        if ($total == 0) {
            return redirect()->back()
                ->with('error', 'Data training tidak ditemukan.');
        }

        $pYa = $totalYa / $total;
        $pTidak = $totalTidak / $total;

        /*
        |--------------------------------------------------------------------------
        | KATEGORISASI DATA TESTING
        |--------------------------------------------------------------------------
        */

        $ipkTinggi = $request->ipk >= 3;
        $hadirTinggi = $request->kehadiran >= 80;
        $sksTinggi = $request->sks_lulus >= 110;

        /*
        |--------------------------------------------------------------------------
        | PROBABILITAS IPK
        |--------------------------------------------------------------------------
        */

        $ipkYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('ipk', $ipkTinggi ? '>=' : '<', 3)
            ->count();

        $ipkTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('ipk', $ipkTinggi ? '>=' : '<', 3)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PROBABILITAS KEHADIRAN
        |--------------------------------------------------------------------------
        */

        $hadirYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('kehadiran', $hadirTinggi ? '>=' : '<', 80)
            ->count();

        $hadirTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('kehadiran', $hadirTinggi ? '>=' : '<', 80)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | PROBABILITAS SKS
        |--------------------------------------------------------------------------
        */

        $sksYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('sks_lulus', $sksTinggi ? '>=' : '<', 110)
            ->count();

        $sksTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('sks_lulus', $sksTinggi ? '>=' : '<', 110)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | STATUS KERJA
        |--------------------------------------------------------------------------
        */

        $kerjaYa = Mahasiswa::where('tepat_waktu', 'Ya')
            ->where('status_kerja', $request->status_kerja)
            ->count();

        $kerjaTidak = Mahasiswa::where('tepat_waktu', 'Tidak')
            ->where('status_kerja', $request->status_kerja)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | LAPLACE SMOOTHING
        |--------------------------------------------------------------------------
        */

        $pIpkYa = ($ipkYa + 1) / ($totalYa + 2);
        $pIpkTidak = ($ipkTidak + 1) / ($totalTidak + 2);

        $pHadirYa = ($hadirYa + 1) / ($totalYa + 2);
        $pHadirTidak = ($hadirTidak + 1) / ($totalTidak + 2);

        $pSksYa = ($sksYa + 1) / ($totalYa + 2);
        $pSksTidak = ($sksTidak + 1) / ($totalTidak + 2);

        $pKerjaYa = ($kerjaYa + 1) / ($totalYa + 2);
        $pKerjaTidak = ($kerjaTidak + 1) / ($totalTidak + 2);

        /*
        |--------------------------------------------------------------------------
        | NAIVE BAYES
        |--------------------------------------------------------------------------
        */

        $probYa =
            $pYa *
            $pIpkYa *
            $pHadirYa *
            $pSksYa *
            $pKerjaYa;

        $probTidak =
            $pTidak *
            $pIpkTidak *
            $pHadirTidak *
            $pSksTidak *
            $pKerjaTidak;

        $hasil = $probYa > $probTidak
            ? 'Ya'
            : 'Tidak';

        return redirect('/')
            ->with('prediction', $hasil)
            ->with('prob_ya', $probYa)
            ->with('prob_tidak', $probTidak);
    }
}