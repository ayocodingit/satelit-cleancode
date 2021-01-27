<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $fillable = [
        'nama_lengkap',
        'nik',
        'tanggal_lahir',
        'tempat_lahir',
        'kewarganegaraan',
        'no_hp',
        'no_telp',
        'pekerjaan',
        'jenis_kelamin',
        'kota_id',
        'kecamatan',
        'kelurahan',
        'no_rw',
        'no_rt',
        'alamat_lengkap',
        'keterangan_lain',
        'suhu',
        'sumber_pasien'
    ];

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }
}
