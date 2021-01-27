<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegisterSampelResource;
use App\Models\Register;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Registrasisampel extends Controller
{
    public function getData(Request $request)
    {
        $models = Register::query();
        if ($request->user()->lab_satelit_id) {
            $models->where('lab_satelit_id', $request->user()->lab_satelit_id);
        }
        $params = $request->input('params', false);
        $search = $request->get('search', false);
        $perpage = $request->get('perpage', 20);
        $order = $request->get('order', 'nama');
        $order_direction = $request->get('order_direction', 'desc');

        if ($search) {
            $models = $this->search($search, $models);
        }

        if ($params) {
            $models = $this->filter($params, $models);
        }

        if ($order) {
            $models = $this->orderBy($order, $order_direction, $models);
        }

        return RegisterSampelResource::collection($models->paginate($perpage));
    }

    public function search($search, $models)
    {
        $models->where(function ($query) use ($search) {
            $query->whereHas('pasienRegister.pasien', function ($query) use ($search) {
                $query->where('nama_lengkap', 'ilike', '%' . $search . '%')
                    ->orWhere('nik', 'ilike', '%' . $search . '%')
                    ->orWhere('usia_tahun', 'ilike', '%' . $search . '%')
                    ->orWhere('usia_bulan', 'ilike', '%' . $search . '%');
            })
            ->orWhere('instansi_pengirim_nama', 'ilike', '%' . $search . '%')
            ->orWhere('sumber_pasien', 'ilike', '%' . $search . '%')
            ->orWhere('status', 'ilike', '%' . $search . '%')
            ->orWhereHas('pasienRegister.pasien.kota', function ($query) use ($search) {
                $query->where('nama', 'ilike', '%' . $search . '%');
            })
            ->orWhereHas('sampel', function ($query) use ($search) {
                $query->where('nomor_sampel', 'ilike', '%' . $search . '%');
            });
        });

        return $models;
    }

    public function filter($params, $models)
    {
        foreach (json_decode($params) as $key => $val) {
            if (!$val || $val == '') {
                continue;
            }
            switch ($key) {
                case "nama_pasien":
                    $models->whereHas('pasienRegister.pasien', function ($query) use ($val) {
                        $query->where('nama_lengkap', 'ilike', '%' . $val . '%')
                            ->orWhere('nik', 'ilike', '%' . $val . '%');
                    });
                    break;
                case "start_date":
                    $models->whereHas('sampel', function($query) use ($val) {
                        $query->whereDate('waktu_sample_taken', '>=', Carbon::parse($val));
                    });
                    break;
                case "end_date":
                    $models->whereHas('sampel', function($query) use ($val) {
                        $query->whereDate('waktu_sample_taken', '<=', Carbon::parse($val));
                    });
                    break;
                case "kota":
                    $models->whereHas('pasienRegister.pasien', function($query) use ($val) {
                        $query->where('kota_id', $val);
                    });
                    break;
                case "fasyankes_id":
                case "sumber_pasien":
                case "status":
                    $models->where($key, 'ilike', '%' . $val . '%');
                    break;
                case "nomor_sampel":
                    $models->whereHas('sampel', function($query) use ($val) {
                        $query->whereDate('nomor_sampel', 'ilike', '%' . $val . '%');
                    });
                    break;
            }
        }

        return $models;
    }

    public function orderBy($order, $order_direction, $models)
    {
        switch ($order) {
            case 'nama_pasien':
                $models->whereHas('pasienRegister.pasien', function ($query) use ($order_direction) {
                    $query->orderBy('nama_lengkap', $order_direction);
                });
                break;
            case 'tgl_input':
                $models->whereHas('sampel', function($query) use ($order_direction) {
                    $query->orderBy('waktu_sample_taken', $order_direction);
                });
                break;
            case 'nama_kota':
                $models->whereHas('pasienRegister.pasien', function ($query) use ($order_direction) {
                    $query->orderBy('nama', $order_direction);
                });
                break;
            case 'no_sampel':
                $models->whereHas('sampel', function($query) use ($order_direction) {
                    $query->orderBy('nomor_sampel', $order_direction);
                });
                break;
            case 'instansi_pengirim_nama':
                $models->orderBy('nama_rs', $order_direction);
                break;
            case 'sumber_pasien':
            case 'status':
                $models->orderBy($order, $order_direction);
                break;
        }
        return $models;
    }
}
