<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Karyawan;
use App\Models\User;
use App\Models\UserAccess;
use App\Models\UserLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRbacApiController extends Controller
{
    /**
     * Master definisi seluruh grup modul dan hak akses (RBAC Matrix)
     */
    public static function getPermissionModules(): array
    {
        return [
            [
                'code' => 'dashboard',
                'name' => 'Dashboard & Monitoring',
                'description' => 'Akses dashboard eksekutif dan antrean operasional',
                'permissions' => [
                    ['key' => 'dashboard/view', 'name' => 'Lihat Dashboard', 'desc' => 'Akses ringkasan eksekutif dan KPI operasional'],
                    ['key' => 'dashboard/executive', 'name' => 'Dashboard Eksekutif BoD', 'desc' => 'Monitoring level direksi & serapan pagu anggaran'],
                    ['key' => 'dashboard/checker', 'name' => 'Antrean Dual-Control Checker', 'desc' => 'Notifikasi dan akses review otorisator'],
                ],
            ],
            [
                'code' => 'tna',
                'name' => 'TNA & Perencanaan Anggaran',
                'description' => 'Training Needs Analysis dan pagu anggaran tahunan',
                'permissions' => [
                    ['key' => 'tna/view', 'name' => 'Lihat Daftar TNA', 'desc' => 'Melihat daftar usulan TNA tahunan unit kerja'],
                    ['key' => 'tna/create', 'name' => 'Ajukan Usulan TNA', 'desc' => 'Input kebutuhan diklat dan estimasi biaya unit'],
                    ['key' => 'tna/review', 'name' => 'Review & Validasi HCBP', 'desc' => 'Validasi dan kurasi kebutuhan diklat oleh HCBP'],
                    ['key' => 'tna/approve', 'name' => 'Approval Pagu Anggaran (Checker)', 'desc' => 'Otorisasi akhir alokasi anggaran pelatihan'],
                ],
            ],
            [
                'code' => 'program',
                'name' => 'Program Kerja Pelatihan',
                'description' => 'Katalog dan silabus pelatihan Bank Woori Saudara',
                'permissions' => [
                    ['key' => 'program/view', 'name' => 'Lihat Program', 'desc' => 'Melihat katalog program dan silabus diklat'],
                    ['key' => 'program/create', 'name' => 'Buat Program Baru', 'desc' => 'Menambahkan modul dan kurikulum pelatihan'],
                    ['key' => 'program/edit', 'name' => 'Edit Program', 'desc' => 'Mengubah silabus dan pagu per program'],
                    ['key' => 'program/delete', 'name' => 'Hapus / Nonaktifkan', 'desc' => 'Menonaktifkan program pelatihan'],
                ],
            ],
            [
                'code' => 'class',
                'name' => 'Jadwal & Batch Kelas (Dual-Control)',
                'description' => 'Pelaksanaan kelas diklat dan otorisasi Maker-Checker',
                'permissions' => [
                    ['key' => 'class/view', 'name' => 'Lihat Jadwal Kelas', 'desc' => 'Melihat kalender dan detail batch kelas'],
                    ['key' => 'class/create', 'name' => 'Buat Batch Kelas (Maker)', 'desc' => 'Membuat jadwal, ruangan, dan undangan peserta'],
                    ['key' => 'class/maker_submit', 'name' => 'Submit Otorisasi Checker', 'desc' => 'Mengirim pengajuan batch ke antrean otorisator'],
                    ['key' => 'class/checker_approve', 'name' => 'Otorisasi Kelas (Checker)', 'desc' => 'Menyetujui atau menolak pelaksanaan batch diklat'],
                ],
            ],
            [
                'code' => 'participants',
                'name' => 'Peserta Diklat & ELAPSE',
                'description' => 'Manajemen kehadiran dan tracking jam belajar OJK',
                'permissions' => [
                    ['key' => 'participants/view', 'name' => 'Lihat Data Peserta', 'desc' => 'Melihat daftar peserta dan progres pembelajaran'],
                    ['key' => 'participants/enroll', 'name' => 'Daftarkan Peserta', 'desc' => 'Menambahkan karyawan ke dalam batch diklat'],
                    ['key' => 'participants/certificate', 'name' => 'Penerbitan Sertifikat', 'desc' => 'Generate dan verifikasi sertifikat kelulusan'],
                ],
            ],
            [
                'code' => 'eval',
                'name' => 'Evaluasi, Pre/Post Test',
                'description' => 'Pengujian kompetensi, quiz ELAPSE, dan evaluasi pengajar',
                'permissions' => [
                    ['key' => 'eval/view', 'name' => 'Lihat Hasil Evaluasi', 'desc' => 'Melihat skor pre/post test dan feedback'],
                    ['key' => 'eval/input_score', 'name' => 'Input Nilai Ujian', 'desc' => 'Memasukkan nilai ujian akhir atau sertifikasi'],
                    ['key' => 'eval/review', 'name' => 'Review Evaluasi Pengajar', 'desc' => 'Menganalisis penilaian trainer dan kepuasan peserta'],
                    ['key' => 'eval/pre_post_test', 'name' => 'Akses Ujian (Peserta)', 'desc' => 'Mengerjakan soal pre-test dan post-test'],
                ],
            ],
            [
                'code' => 'task',
                'name' => 'Penugasan & Action Learning',
                'description' => 'Tugas mandiri, studi kasus perbankan, dan approval tugas',
                'permissions' => [
                    ['key' => 'task/view', 'name' => 'Lihat Daftar Tugas', 'desc' => 'Melihat penugasan kelas dan status pengumpulan'],
                    ['key' => 'task/create', 'name' => 'Buat Penugasan Baru', 'desc' => 'Memberikan tugas action project kepada peserta'],
                    ['key' => 'task/submit', 'name' => 'Upload Tugas (Peserta)', 'desc' => 'Mengunggah laporan atau tugas akhir diklat'],
                    ['key' => 'task/review', 'name' => 'Review Tugas PIC/Spv', 'desc' => 'Memeriksa berkas tugas yang diunggah'],
                    ['key' => 'task/grade', 'name' => 'Beri Nilai & Approval Akhir', 'desc' => 'Approval tugas oleh Manager / Kadiv'],
                ],
            ],
            [
                'code' => 'transaction',
                'name' => 'Transaksi & Reimbursement',
                'description' => 'Klaim biaya pelatihan, pembayaran vendor, dan kwitansi',
                'permissions' => [
                    ['key' => 'transaction/view', 'name' => 'Lihat Transaksi Keuangan', 'desc' => 'Melihat voucher klaim dan status pencairan'],
                    ['key' => 'transaction/claim', 'name' => 'Ajukan Klaim / Reimburse', 'desc' => 'Input kwitansi reimbursement ujian/pelatihan'],
                    ['key' => 'transaction/checker_approve', 'name' => 'Otorisasi Pencairan (Checker)', 'desc' => 'Otorisasi dual-control pembayaran finansial'],
                ],
            ],
            [
                'code' => 'certification',
                'name' => 'Sertifikasi Wajib Perbankan',
                'description' => 'Kepatuhan sertifikasi OJK (Manajemen Risiko, SKSP, Tresuri)',
                'permissions' => [
                    ['key' => 'certification/view', 'name' => 'Monitoring Target Sertifikasi', 'desc' => 'Melihat persentase kelulusan sertifikasi wajib'],
                    ['key' => 'certification/input_target', 'name' => 'Update Target & Realisasi', 'desc' => 'Menginput kuota kelulusan per divisi'],
                    ['key' => 'certification/verify', 'name' => 'Verifikasi Sertifikat LSPP/BSMR', 'desc' => 'Validasi keabsahan dokumen sertifikat resmi'],
                    ['key' => 'certification/history', 'name' => 'Riwayat Sertifikat Pribadi', 'desc' => 'Melihat sertifikat kepemilikan karyawan'],
                ],
            ],
            [
                'code' => 'reporting',
                'name' => 'Laporan & Audit Trail',
                'description' => 'Laporan realisasi diklat, sign-off berjenjang, dan audit OJK',
                'permissions' => [
                    ['key' => 'reporting/view', 'name' => 'Lihat Laporan Pelatihan', 'desc' => 'Akses All Training Information (Class & Yearly)'],
                    ['key' => 'reporting/export', 'name' => 'Export Excel & PDF', 'desc' => 'Mengunduh laporan rekapitulasi diklat'],
                    ['key' => 'reporting/signoff_mgr', 'name' => 'Sign-Off Approved (1) Mgr', 'desc' => 'Persetujuan laporan tingkat Manager L&D'],
                    ['key' => 'reporting/signoff_kadiv', 'name' => 'Sign-Off Approved (2) Kadiv', 'desc' => 'Pengesahan final tingkat Kepala Divisi HC'],
                    ['key' => 'reporting/audit_trail', 'name' => 'Log Audit Trail Kepatuhan', 'desc' => 'Inspeksi jejak audit waktu & IP operasional'],
                ],
            ],
            [
                'code' => 'master_rbac',
                'name' => 'Kelola Pengguna & RBAC',
                'description' => 'Manajemen akun login, peran, hak akses, dan master karyawan',
                'permissions' => [
                    ['key' => 'users/manage', 'name' => 'Kelola Akun Pengguna', 'desc' => 'Tambah, ubah data login, dan reset password'],
                    ['key' => 'rbac/manage', 'name' => 'Kelola Peran & Hak Akses', 'desc' => 'Konfigurasi matriks permission RBAC per peran'],
                    ['key' => 'karyawan/manage', 'name' => 'Kelola Master Karyawan', 'desc' => 'Sinkronisasi data master pegawai Bank Woori'],
                ],
            ],
        ];
    }

    /**
     * GET /api/users
     * Mengambil daftar seluruh user terintegrasi dengan Level dan Karyawan
     */
    public function indexUsers(Request $request): JsonResponse
    {
        $users = User::with(['level.accesses', 'karyawan'])
            ->orderBy('id')
            ->get();

        $levels = UserLevel::withCount(['users', 'accesses'])
            ->orderBy('level_id')
            ->get();

        $karyawans = Karyawan::where('is_active', true)
            ->orderBy('nama')
            ->get(['karyawan_id', 'nik', 'nama', 'email', 'departemen', 'jabatan', 'kantor_cabang']);

        $stats = [
            'total_users' => $users->count(),
            'active_users' => $users->where('is_active', true)->count(),
            'total_levels' => $levels->count(),
            'total_permissions' => collect(self::getPermissionModules())->sum(fn ($m) => count($m['permissions'])),
        ];

        return response()->json([
            'users' => $users,
            'levels' => $levels,
            'karyawans' => $karyawans,
            'permissionModules' => self::getPermissionModules(),
            'stats' => $stats,
        ]);
    }

    /**
     * POST /api/users
     * Membuat akun user baru (tersimpan di users & "user")
     */
    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'username' => 'nullable|string|max:100|unique:users,email',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'level_id' => 'required|exists:user_level,level_id',
            'karyawan_id' => 'nullable|exists:karyawan,karyawan_id',
            'department' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
        ]);

        $level = UserLevel::findOrFail($validated['level_id']);
        $username = $validated['username'] ?? explode('@', $validated['email'])[0];
        $hashedPassword = Hash::make($validated['password']);

        $karyawanId = $validated['karyawan_id'] ?? null;
        $nik = $request->nik ?? ($karyawanId ? Karyawan::find($karyawanId)?->nik : null) ?? ('BWS-'.rand(1000, 9999));

        // 1. Simpan di tabel users (Laravel standard)
        $user = User::create([
            'nik' => $nik,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $hashedPassword,
            'role_name' => $level->level,
            'level_id' => $level->level_id,
            'karyawan_id' => $karyawanId,
            'department' => $validated['department'] ?? 'Head Office Sudirman',
            'branch' => $validated['branch'] ?? 'Kantor Pusat Sudirman',
            'phone' => $validated['phone'] ?? '+62 811-0000-000',
            'is_active' => true,
        ]);

        // 2. Simpan di tabel "user" (LDMS schema compatibility)
        DB::table('user')->insert([
            'username' => $username,
            'password' => $hashedPassword,
            'nama' => $validated['name'],
            'telpon' => $validated['phone'] ?? null,
            'email' => $validated['email'],
            'level_id' => $level->level_id,
            'karyawan_id' => $validated['karyawan_id'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'CREATE_USER',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['email' => $user->email, 'level' => $level->level]),
        ]);

        return response()->json([
            'message' => "Pengguna {$user->name} ({$level->level}) berhasil didaftarkan.",
            'user' => $user->load(['level', 'karyawan']),
        ], 201);
    }

    /**
     * PUT /api/users/{id}
     * Memperbarui data pengguna
     */
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:150',
            'level_id' => 'required|exists:user_level,level_id',
            'karyawan_id' => 'nullable|exists:karyawan,karyawan_id',
            'department' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:6';
        }

        $validated = $request->validate($rules);

        $level = UserLevel::findOrFail($validated['level_id']);

        $user->name = $validated['name'];
        $user->level_id = $level->level_id;
        $user->role_name = $level->level;
        $user->karyawan_id = $validated['karyawan_id'] ?? null;
        $user->department = $validated['department'] ?? $user->department;
        $user->branch = $validated['branch'] ?? $user->branch;
        $user->phone = $validated['phone'] ?? $user->phone;
        $user->is_active = $validated['is_active'];

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Update di tabel "user" (LDMS schema compatibility)
        $ldmsUserData = [
            'nama' => $user->name,
            'level_id' => $user->level_id,
            'karyawan_id' => $user->karyawan_id,
            'telpon' => $user->phone,
            'is_active' => $user->is_active,
            'updated_at' => now(),
        ];
        if ($request->filled('password')) {
            $ldmsUserData['password'] = $user->password;
        }

        DB::table('user')->where('email', $user->email)->update($ldmsUserData);

        return response()->json([
            'message' => "Data pengguna {$user->name} berhasil diperbarui.",
            'user' => $user->load(['level', 'karyawan']),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Menghapus akun pengguna dari sistem LIS dan LDMS
     */
    public function destroyUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($request->user() && $request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.',
            ], 422);
        }

        $userName = $user->name;
        $userEmail = $user->email;

        try {
            DB::beginTransaction();

            // 1. Revoke all active tokens (logout force)
            $user->tokens()->delete();

            // 2. Tandai nonaktif di tabel user (LDMS compatibility)
            DB::table('user')->where('email', $userEmail)->update([
                'is_active' => false,
                'deleted_at' => now(),
            ]);

            // 3. Set non-aktif dan soft-delete user (mempertahankan integritas FK audit & proposal perbankan)
            $user->is_active = false;
            $user->save();
            $user->delete();

            // 4. Audit Log
            AuditLog::create([
                'user_id' => $request->user()?->id ?? 1,
                'action' => 'DELETE_USER',
                'entity_type' => 'User',
                'entity_id' => $id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['email' => $userEmail, 'name' => $userName, 'type' => 'soft_delete']),
            ]);

            DB::commit();

            return response()->json([
                'message' => "Pengguna {$userName} ({$userEmail}) berhasil dihapus dari sistem.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus pengguna: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/rbac/levels
     * Mengambil seluruh daftar role/level beserta hak aksesnya
     */
    public function indexLevels(): JsonResponse
    {
        $levels = UserLevel::with(['accesses'])
            ->withCount(['users', 'accesses'])
            ->orderBy('level_id')
            ->get();

        return response()->json([
            'levels' => $levels,
            'permissionModules' => self::getPermissionModules(),
        ]);
    }

    /**
     * GET /api/rbac/levels/{id}/access
     * Mengambil daftar hak akses spesifik per level
     */
    public function getLevelAccess(int $id): JsonResponse
    {
        $level = UserLevel::with('accesses')->findOrFail($id);

        return response()->json([
            'level' => $level,
            'activeKeys' => $level->accesses->pluck('controller_key')->toArray(),
            'permissionModules' => self::getPermissionModules(),
        ]);
    }

    /**
     * POST /api/rbac/levels/{id}/access
     * Menyimpan/sinkronisasi matriks hak akses untuk role tertentu
     */
    public function updateLevelAccess(Request $request, int $id): JsonResponse
    {
        $level = UserLevel::findOrFail($id);

        $validated = $request->validate([
            'controller_keys' => 'required|array',
            'controller_keys.*' => 'string',
        ]);

        $keys = array_unique($validated['controller_keys']);

        // Hapus akses lama untuk level ini dan masukkan yang baru
        DB::transaction(function () use ($level, $keys) {
            UserAccess::where('level_id', $level->level_id)->delete();

            $insertData = [];
            foreach ($keys as $k) {
                $insertData[] = [
                    'level_id' => $level->level_id,
                    'controller_key' => $k,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($insertData)) {
                UserAccess::insert($insertData);
            }
        });

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'UPDATE_RBAC_PERMISSIONS',
            'entity_type' => 'UserLevel',
            'entity_id' => $level->level_id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['role' => $level->role, 'keys_count' => count($keys)]),
        ]);

        return response()->json([
            'message' => "Matriks Hak Akses untuk peran '{$level->level}' berhasil diperbarui (".count($keys).' permissions aktif).',
            'activeKeys' => $keys,
            'keysCount' => count($keys),
        ]);
    }

    /**
     * GET /api/karyawan
     * Mengambil daftar master data karyawan Bank Woori Saudara
     */
    public function indexKaryawan(Request $request): JsonResponse
    {
        $query = Karyawan::with('user');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'ilike', "%{$s}%")
                    ->orWhere('nik', 'ilike', "%{$s}%")
                    ->orWhere('email', 'ilike', "%{$s}%")
                    ->orWhere('departemen', 'ilike', "%{$s}%")
                    ->orWhere('jabatan', 'ilike', "%{$s}%")
                    ->orWhere('kantor_cabang', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('st_kerja') && $request->st_kerja !== 'ALL') {
            $query->where('st_kerja', $request->st_kerja);
        }

        if ($request->filled('departemen') && $request->departemen !== 'ALL') {
            $query->where('departemen', $request->departemen);
        }

        $karyawans = $query->orderBy('karyawan_id')->get();

        $stats = [
            'total_karyawan' => Karyawan::count(),
            'aktif_karyawan' => Karyawan::where('st_kerja', 'AKTIF')->count(),
            'total_cabang' => Karyawan::distinct('kantor_cabang')->count('kantor_cabang'),
            'target_jam_avg' => round(Karyawan::avg('target_jam_pelatihan') ?? 20),
        ];

        return response()->json([
            'karyawans' => $karyawans,
            'stats' => $stats,
        ]);
    }

    /**
     * POST /api/karyawan
     * Menambahkan data karyawan baru
     */
    public function storeKaryawan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:karyawan,nik',
            'nama' => 'required|string|max:150',
            'nama_panggilan' => 'nullable|string|max:50',
            'jk' => 'nullable|string|in:L,P',
            'email' => 'required|email|max:100|unique:karyawan,email',
            'hp' => 'nullable|string|max:30',
            'departemen' => 'required|string|max:100',
            'jabatan' => 'required|string|max:100',
            'kantor_cabang' => 'required|string|max:100',
            'golongan' => 'nullable|string|max:50',
            'st_kerja' => 'required|string|max:30',
            'tgl_masuk' => 'nullable|date',
            'target_jam_pelatihan' => 'nullable|integer|min:1',
        ]);

        $karyawan = Karyawan::create([
            'nik' => $validated['nik'],
            'nama' => $validated['nama'],
            'nama_panggilan' => $validated['nama_panggilan'] ?? explode(' ', $validated['nama'])[0],
            'jk' => $validated['jk'] ?? 'L',
            'email' => $validated['email'],
            'hp' => $validated['hp'] ?? '+62 811-',
            'departemen' => $validated['departemen'],
            'jabatan' => $validated['jabatan'],
            'kantor_cabang' => $validated['kantor_cabang'],
            'golongan' => $validated['golongan'] ?? 'Officer',
            'st_kerja' => $validated['st_kerja'] ?? 'AKTIF',
            'tgl_masuk' => $validated['tgl_masuk'] ?? now()->toDateString(),
            'target_jam_pelatihan' => $validated['target_jam_pelatihan'] ?? 20,
            'is_active' => ($validated['st_kerja'] === 'AKTIF'),
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'CREATE_KARYAWAN',
            'entity_type' => 'Karyawan',
            'entity_id' => $karyawan->karyawan_id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['nik' => $karyawan->nik, 'nama' => $karyawan->nama]),
        ]);

        return response()->json([
            'message' => "Data karyawan {$karyawan->nama} ({$karyawan->nik}) berhasil ditambahkan.",
            'karyawan' => $karyawan->load('user'),
        ], 201);
    }

    /**
     * PUT /api/karyawan/{id}
     * Memperbarui data karyawan
     */
    public function updateKaryawan(Request $request, int $id): JsonResponse
    {
        $karyawan = Karyawan::findOrFail($id);

        $validated = $request->validate([
            'nik' => "required|string|max:50|unique:karyawan,nik,{$id},karyawan_id",
            'nama' => 'required|string|max:150',
            'nama_panggilan' => 'nullable|string|max:50',
            'jk' => 'nullable|string|in:L,P',
            'email' => "required|email|max:100|unique:karyawan,email,{$id},karyawan_id",
            'hp' => 'nullable|string|max:30',
            'departemen' => 'required|string|max:100',
            'jabatan' => 'required|string|max:100',
            'kantor_cabang' => 'required|string|max:100',
            'golongan' => 'nullable|string|max:50',
            'st_kerja' => 'required|string|max:30',
            'tgl_masuk' => 'nullable|date',
            'target_jam_pelatihan' => 'nullable|integer|min:1',
        ]);

        $karyawan->update([
            'nik' => $validated['nik'],
            'nama' => $validated['nama'],
            'nama_panggilan' => $validated['nama_panggilan'] ?? $karyawan->nama_panggilan,
            'jk' => $validated['jk'] ?? $karyawan->jk,
            'email' => $validated['email'],
            'hp' => $validated['hp'] ?? $karyawan->hp,
            'departemen' => $validated['departemen'],
            'jabatan' => $validated['jabatan'],
            'kantor_cabang' => $validated['kantor_cabang'],
            'golongan' => $validated['golongan'] ?? $karyawan->golongan,
            'st_kerja' => $validated['st_kerja'],
            'tgl_masuk' => $validated['tgl_masuk'] ?? $karyawan->tgl_masuk,
            'target_jam_pelatihan' => $validated['target_jam_pelatihan'] ?? $karyawan->target_jam_pelatihan,
            'is_active' => ($validated['st_kerja'] === 'AKTIF'),
        ]);

        // Sinkronisasi data ke user account terkait (jika ada)
        User::where('karyawan_id', $karyawan->karyawan_id)->orWhere('nik', $karyawan->nik)->update([
            'name' => $karyawan->nama,
            'department' => $karyawan->departemen,
            'branch' => $karyawan->kantor_cabang,
            'phone' => $karyawan->hp,
            'is_active' => $karyawan->is_active,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'UPDATE_KARYAWAN',
            'entity_type' => 'Karyawan',
            'entity_id' => $karyawan->karyawan_id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['nik' => $karyawan->nik, 'nama' => $karyawan->nama]),
        ]);

        return response()->json([
            'message' => "Data karyawan {$karyawan->nama} ({$karyawan->nik}) berhasil diperbarui.",
            'karyawan' => $karyawan->fresh(['user']),
        ]);
    }

    /**
     * DELETE /api/karyawan/{id}
     * Menghapus data karyawan
     */
    public function destroyKaryawan(Request $request, int $id): JsonResponse
    {
        $karyawan = Karyawan::findOrFail($id);
        $nama = $karyawan->nama;
        $nik = $karyawan->nik;

        try {
            DB::beginTransaction();

            // Unlink dari user jika ada
            User::where('karyawan_id', $id)->update(['karyawan_id' => null]);
            DB::table('user')->where('karyawan_id', $id)->update(['karyawan_id' => null]);

            // Nonaktifkan dan soft-delete karyawan
            $karyawan->is_active = false;
            $karyawan->st_kerja = 'NONAKTIF';
            $karyawan->save();
            $karyawan->delete();

            AuditLog::create([
                'user_id' => $request->user()?->id ?? 1,
                'action' => 'DELETE_KARYAWAN',
                'entity_type' => 'Karyawan',
                'entity_id' => $id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['nik' => $nik, 'nama' => $nama, 'type' => 'soft_delete']),
            ]);

            DB::commit();

            return response()->json([
                'message' => "Data karyawan {$nama} ({$nik}) berhasil dihapus.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus data karyawan: '.$e->getMessage(),
            ], 500);
        }
    }
}
