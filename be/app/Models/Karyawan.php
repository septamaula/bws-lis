<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'karyawan';
    protected $primaryKey = 'karyawan_id';

    protected $fillable = [
        'nik',
        'nama',
        'nama_panggilan',
        'jk',
        'email',
        'hp',
        'departemen',
        'jabatan',
        'kantor_cabang',
        'golongan',
        'st_kerja',
        'tgl_masuk',
        'foto',
        'role_name',
        'target_jam_pelatihan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target_jam_pelatihan' => 'integer',
        'tgl_masuk' => 'date',
    ];

    /**
     * Relasi ke User Akun Login (jika menggunakan NIK/Email yang sama)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Relasi ke pendaftaran kelas / pelatihan
     */
    public function enrollments()
    {
        return $this->hasMany(ClassEnrollment::class, 'user_id', 'karyawan_id');
    }
}
