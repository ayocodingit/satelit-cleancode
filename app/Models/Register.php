<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Register extends Model
{
    use SoftDeletes;

    protected $table = 'register';

    protected $fillable = [
        'nomor_register',
        'fasyankes_id',
        'nomor_rekam_medis',
        'nama_dokter',
        'no_telp',
        'register_uuid',
        'creator_user_id',
        'sumber_pasien',
        'jenis_registrasi',
        'tanggal_kunjungan',
        'kunjungan_ke',
        'rs_kunjungan',
        'dinkes_pengirim',
        'other_dinas_pengirim',
        'nama_rs',
        'other_nama_rs',
        'fasyankes_pengirim',
        'hasil_rdt',
        'status',
        'swab_ke',
        'tanggal_swab',
    ];

    protected $hidden = ['fasyankes_id'];

    protected $dates = [
        'tanggal_kunjungan'
    ];

    public function fasyankes()
    {
        return $this->belongsTo(Fasyankes::class);
    }

    public function pasienRegister()
    {
        return $this->hasOne(PasienRegister::class, 'register_id', 'id');
    }

    public function sampel()
    {
        return $this->hasOne(Sampel::class);
    }

    public function logs()
    {
        return $this->hasMany(RegisterLog::class, 'register_id', 'id');
    }
}
