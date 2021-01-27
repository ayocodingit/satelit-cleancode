<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RegisterSampelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $pasien = $this->pasienRegister->pasien;
        $sampel = $this->sampel;
        return [
            'nik' => $pasien->nik,
            'nama_lengkap' => $pasien->nama_lengkap,
            'tanggal_lahir' => $pasien->tanggal_lahir,
            "usia_tahun" => $pasien->usia_tahun,
            "nomor_sampel" => $sampel->nomor_sampel,
            "nama_kota" => optional($pasien->kota)->nama,
            "register_id" => $this->id,
            "pasien_id" => $pasien->id,
            "sumber_pasien" => $this->sumber_pasien,
            "status" => $this->status,
            "waktu_sample_taken" => $sampel->waktu_sample_taken,
            "nama_rs" => $this->nama_rs,
            "sampel_status" => $sampel->sampel_status,
            "register_perujuk_id" => $this->register_perujuk_id,
        ];
    }
}
