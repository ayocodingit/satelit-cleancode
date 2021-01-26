<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInputHasil;
use App\Imports\HasilPemeriksaanImport;
use App\Models\LabPCR;
use App\Models\PemeriksaanSampel;
use App\Models\RegisterPerujuk;
use App\Models\Sampel;
use App\Traits\PemeriksaanTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Storage;
use Validator;

class PCRController extends Controller
{
    use PemeriksaanTrait;

    public function getData(Request $request)
    {
        $user = Auth::user();
        $models = Sampel::leftJoin('register', 'sampel.register_id', 'register.id')
            ->leftJoin('pasien_register', 'pasien_register.register_id', 'register.id')
            ->leftJoin('pasien', 'pasien_register.pasien_id', 'pasien.id')
            ->leftJoin('kota', 'kota.id', 'pasien.kota_id')
            ->where('sampel.sampel_status', 'sample_taken')
            ->whereNull('register.deleted_at')
            ->where('register.lab_satelit_id', $user->lab_satelit_id)
            ->where('sampel.lab_satelit_id', $user->lab_satelit_id)
            ->where('pasien.lab_satelit_id', $user->lab_satelit_id);
        $params = $request->get('params', false);
        $search = $request->get('search', false);
        $order = $request->get('order', 'name');
        if ($search != '') {
            $models = $models->where(function ($q) use ($search) {
                $q->where('nomor_sampel', 'ilike', '%' . $search . '%')
                    ->orWhere('register.nama_rs', 'ilike', '%' . $search . '%')
                    ->orWhere('nama_lengkap', 'ilike', '%' . $search . '%')
                    ->orWhere('nik', 'ilike', '%' . $search . '%');
            });
        }
        if ($params) {
            foreach (json_decode($params) as $key => $val) {
                if ($val == '') {
                    continue;
                }

                switch ($key) {
                    case "start_date":
                        $models = $models->whereDate('sampel.waktu_sample_taken', '>=', date('Y-m-d', strtotime($val)));
                        break;
                    case "end_date":
                        $models = $models->whereDate('sampel.waktu_sample_taken', '<=', date('Y-m-d', strtotime($val)));
                        break;
                    case "fasyankes_id":
                        $models = $models->where('register.fasyankes_id', $val);
                        break;
                    default:
                        break;
                }
            }
        }

        $count = $models->count();

        $page = $request->get('page', 1);
        $perpage = $request->get('perpage', 500);

        if ($order) {
            $order_direction = $request->get('order_direction', 'asc');
            if (empty($order_direction)) {
                $order_direction = 'asc';
            }

            switch ($order) {
                case 'nomor_sampel':
                    $models = $models->orderBy('nomor_sampel', $order_direction);
                    break;
                case 'nama_lengkap':
                    $models = $models->orderBy('pasien.nama_lengkap', $order_direction);
                    break;
                case 'nik':
                    $models = $models->orderBy('pasien.nik', $order_direction);
                    break;
                case 'instansi_pengirim':
                    $models = $models->orderBy('register.instansi_pengirim_nama', $order_direction);
                    break;
                case 'waktu_sample_taken':
                    $models = $models->orderBy($order, $order_direction);
                    break;
                default:
                    break;
            }
        }
        $models = $models->select('*', 'sampel.id as sampel_id');
        $models = $models->skip(($page - 1) * $perpage)->take($perpage)->get();

        $result = [
            'data' => $models,
            'count' => $count,
        ];

        return response()->json($result);
    }

    public function detail(Request $request, $id)
    {
        $model = Sampel::with(['pcr', 'status', 'ekstraksi', 'register'])
            ->find($id);
        $model->log_pcr = $model->logs()
            ->whereIn('sampel_status', ['pcr_sample_received', 'pcr_sample_analyzed', 'extraction_sample_reextract'])
            ->orderByDesc('created_at')
            ->get();
        $model->sampel = Auth::user()->lab_satelit_id;
        $model->pasien = $model->register ? optional($model->register)->pasiens()->with(['kota'])->first() : null;
        return response()->json(['status' => 200, 'message' => 'success', 'data' => $model]);
    }

    public function input(StoreInputHasil $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['pcr'])->find($id);
        $pcr = $sampel->pcr;
        if (!$pcr) {
            $pcr = new PemeriksaanSampel();
            $pcr->sampel_id = $sampel->id;
            $pcr->user_id = $user->id;
        }
        $pcr->tanggal_input_hasil = date('Y-m-d', strtotime($request->tanggal_input_hasil));
        $pcr->jam_input_hasil = date('H:s');
        $pcr->catatan_pemeriksaan = $request->catatan_pemeriksaan != '' ? $request->catatan_pemeriksaan : null;
        $pcr->grafik = $request->grafik;
        $pcr->hasil_deteksi = $this->parseHasilDeteksi($request->hasil_deteksi);
        $pcr->kesimpulan_pemeriksaan = $request->kesimpulan_pemeriksaan;
        $pcr->nama_kit_pemeriksaan = $request->nama_kit_pemeriksaan;
        $pcr->save();

        if ($sampel->sampel_status == 'sample_taken') {
            $sampel->updateState('pcr_sample_analyzed', [
                'user_id' => $user->id,
                'metadata' => $pcr,
                'description' => 'PCR Sample analyzed as [' . strtoupper($pcr->kesimpulan_pemeriksaan) . ']',
            ], $pcr->tanggal_input_hasil);
        } else {
            $sampel->addLog([
                'user_id' => $user->id,
                'metadata' => $pcr,
                'description' => 'PCR Sample analyzed as [' . strtoupper($pcr->kesimpulan_pemeriksaan) . ']',
            ]);
            $sampel->waktu_pcr_sample_analyzed = date('Y-m-d H:i:s');
            $sampel->save();
        }

        if ($sampel->register_perujuk_id) {
            RegisterPerujuk::find($sampel->register_perujuk_id)->updateState('pemeriksaan_selesai');
        }
        return response()->json(['status' => 201, 'message' => 'Hasil analisa berhasil disimpan']);
    }

    public function inputInvalid(Request $request, $id)
    {
        $user = $request->user();
        $sampel = Sampel::with(['pcr'])->find($id);

        $pcr = $sampel->pcr;
        if (!$pcr) {
            $pcr = new PemeriksaanSampel();
            $pcr->sampel_id = $sampel->id;
            $pcr->user_id = $user->id;
        }
        $pcr->kesimpulan_pemeriksaan = 'invalid';
        $pcr->save();

        if ($sampel->sampel_status == 'sample_taken') {
            $sampel->updateState('pcr_sample_analyzed', [
                'user_id' => $user->id,
                'metadata' => $pcr,
                'description' => 'PCR Sample analyzed as [' . strtoupper($pcr->kesimpulan_pemeriksaan) . ']',
            ]);
        } else {
            $sampel->addLog([
                'user_id' => $user->id,
                'metadata' => $pcr,
                'description' => 'PCR Sample analyzed as [' . strtoupper($pcr->kesimpulan_pemeriksaan) . ']',
            ]);
            $sampel->waktu_pcr_sample_analyzed = date('Y-m-d H:i:s');
            $sampel->save();
        }
        if ($sampel->perujuk_id) {
            RegisterPerujuk::find($sampel->perujuk_id)->updateState('pemeriksaan_selesai');
        }
        return response()->json(['status' => 201, 'message' => 'Hasil analisa berhasil disimpan']);
    }

    public function importHasilPemeriksaan(Request $request)
    {
        $this->__importValidator($request)->validate();

        $importer = new HasilPemeriksaanImport();
        Excel::import($importer, $request->file('register_file'));

        return response()->json([
            'status' => 200,
            'message' => 'Sukses membaca file import excel',
            'data' => $importer->data,
            'errors' => $importer->errors,
            'errors_count' => $importer->errors_count,
        ]);
    }

    private function __importValidator(Request $request)
    {
        $extension = '';

        if ($request->hasFile('register_file')) {
            $extension = strtolower($request->file('register_file')->getClientOriginalExtension());
        }

        return Validator::make([
            'register_file' => $request->file('register_file'),
            'extension' => $extension,
        ], [
            'register_file' => 'required|file|max:2048',
            'extension' => 'required|in:csv,xlsx,xls',
        ]);
    }

    public function importDataHasilPemeriksaan(Request $request)
    {

        $user = $request->user();
        $data = $request->data;
        foreach ($data as $row) {
            $sampel = Sampel::with(['pcr'])->find($row['sampel_id']);
            if ($sampel) {
                $pcr = $sampel->pcr;
                if (!$pcr) {
                    $pcr = new PemeriksaanSampel();
                    $pcr->sampel_id = $sampel->id;
                    $pcr->user_id = $user->id;
                }
                $pcr->tanggal_input_hasil = date('Y-m-d', strtotime($row['tanggal_input_hasil']));
                $pcr->nama_kit_pemeriksaan = $row['nama_kit_pemeriksaan'];
                $pcr->jam_input_hasil = date('H:i');
                $pcr->catatan_pemeriksaan = $row['catatan_pemeriksaan'] != '' ? $row['catatan_pemeriksaan'] : null;
                $pcr->grafik = [];
                $pcr->hasil_deteksi = $row['target_gen'];
                $pcr->kesimpulan_pemeriksaan = $row['kesimpulan_pemeriksaan'];
                $pcr->save();

                if ($sampel->sampel_status == 'sample_taken' || $sampel->sampel_status == 'extraction_sample_sent') {
                    $sampel->updateState('pcr_sample_analyzed', [
                        'user_id' => $user->id,
                        'metadata' => $pcr,
                        'description' => 'PCR Sample analyzed as [' . strtoupper($pcr->kesimpulan_pemeriksaan) . ']',
                    ], $row['tanggal_input_hasil']);
                } else {
                    $sampel->addLog([
                        'user_id' => $user->id,
                        'metadata' => $pcr,
                        'description' => 'PCR Sample analyzed as [' . strtoupper($pcr->kesimpulan_pemeriksaan) . ']',
                    ]);
                    $sampel->waktu_pcr_sample_analyzed = date('Y-m-d H:i:s', strtotime($row['tanggal_input_hasil']));
                    $sampel->save();
                }
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Sukses import data.',
        ]);
    }
}
