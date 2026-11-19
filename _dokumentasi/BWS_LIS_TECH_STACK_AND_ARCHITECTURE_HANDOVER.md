# Bank Woori Saudara (BWS) — Learning Integrated System (LIS)
## Enterprise Banking Decoupled Architecture & Tech Stack Handover

Dokumen ini merupakan panduan resmi arsitektur teknis dan serah terima sistem (*Handover Document*) untuk **Bank Woori Saudara — Learning Integrated System (BWS - LIS)**. Sistem ini dibangun dengan arsitektur decoupled *Multi-Repository* yang memisahkan **Backend REST API (`be/`)**, **Portal Operasional & Admin (`fe/`)**, **Portal Mandiri Peserta Mobile PWA (`pwa-portal/`)**, dan **Database Relasional PostgreSQL**.

---

### 1. Visualisasi Arsitektur & Topologi Sistem

```
+---------------------------------------------------------------------------------------------------------+
|                                     BANK WOORI SAUDARA (BWS) - LIS                                      |
+---------------------------------------------------------------------------------------------------------+
|                                                                                                         |
|     [ PORTAL ADMIN & OPERASIONAL ]                      [ PORTAL MANDIRI PESERTA (PWA) ]                |
|           Vue 3.5 LTS + Vite 6                                React 19 + TypeScript                     |
|           Tailwind CSS (BWS Blue)                           TanStack Query v5 + Vite PWA                |
|          http://localhost:5173                                  http://localhost:5174                   |
|                    │                                                      │                             |
|                    └───────────────────────┬──────────────────────────────┘                             |
|                                            │                                                            |
|                                            ▼ (HTTP / JSON REST API with Sanctum Bearer Token)           |
|                       +------------------------------------------+                                      |
|                       |            CORE BACKEND API              |                                      |
|                       |         PHP 8.4.27 + Laravel 13.42       |                                      |
|                       |           http://localhost:8001          |                                      |
|                       +--------------------+---------------------+                                      |
|                                            │                                                            |
|                                            ▼ (PDO pgsql connection)                                     |
|                       +------------------------------------------+                                      |
|                       |      POSTGRESQL 17 RELATIONAL DB         |                                      |
|                       |      Port 5432: db_lis (Laragon)         |                                      |
|                       |      Port 5433: bws_lis_db (Docker)      |                                      |
|                       +------------------------------------------+                                      |
|                                                                                                         |
+---------------------------------------------------------------------------------------------------------+
```

---

### 2. Rincian Teknologi Paling Mutakhir (*Latest Tech Stack*)

| Layer Sistem | Komponen | Versi Terbaru | Peran & Fungsionalitas |
| :--- | :--- | :--- | :--- |
| **Backend Runtime** | **PHP** | **8.4.27 (Latest Stable)** | Runtime performa tinggi dengan fitur JIT terbaru, strict typing, dan kompatibilitas modul perbankan. |
| **Backend Framework** | **Laravel Framework** | **13.42.0 (Latest Release)** | Core enterprise REST API, routing modern `bootstrap/app.php`, CORS, throttling anti-brute force, dan *Dual-Control Maker-Checker*. |
| **API Security** | **Laravel Sanctum** | **4.4.0** | Otentikasi berbasis Stateful API & Bearer Token terenkripsi untuk session perbankan. |
| **Database** | **PostgreSQL** | **17.2 Alpine** | Penyimpanan data relasional dengan integritas referensial ACID, foreign key cascading, dan audit logging. |
| **Admin Frontend** | **Vue.js** | **3.5.x LTS + Vite 6** | Portal operasional L&D (11 modul), Composition API, Pinia store, Tailwind CSS v4, dan Lucide icons. |
| **Mobile PWA** | **React** | **19.0.x + TypeScript** | Portal belajar mandiri karyawan (ELAPSE), TanStack Query v5, offline app shell Workbox, mobile-first design. |
| **Orkestrasi** | **Docker Compose** | **Docker Engine 27+** | Menjalankan 4 container microservices yang terisolasi dalam satu jaringan privat (`bws-network`). |

---

### 3. Struktur Direktori Proyek

```
bws-lis/
├── be/                  # Backend REST API (Laravel 13.42 + PHP 8.4 + Sanctum)
│   ├── app/Models/      # Eloquent Models (User, UserLevel, UserAccess, LdmsUser, Karyawan, dsb)
│   ├── database/        # Migrations & Seeders
│   ├── Dockerfile       # Container definition (PHP 8.4-cli-alpine)
│   └── routes/api.php   # REST API Endpoints
├── fe/                  # Portal Operasional & Admin (Vue 3.5 LTS + Vite 6 + Tailwind CSS)
│   ├── src/components/  # Modular Views & Modals (UserRbacModule, OverviewModule, dsb)
│   ├── src/views/       # DashboardView.vue, LoginView.vue
│   └── src/style.css    # BWS Corporate Blue Theme Tokens
├── pwa-portal/          # Portal Belajar Mandiri Pegawai (React 19 + TypeScript + Vite PWA)
│   ├── src/pages/       # LearnerDashboard.tsx, LoginPage.tsx
│   └── vite.config.ts   # PWA Manifest & Workbox Service Worker
├── _dokumentasi/        # Dokumentasi Teknis, Panduan Handover, dan Infografis
├── docker-compose.yml   # Konfigurasi multi-container Docker (4 services)
└── index.php            # Laragon Hub Launcher terpadu
```

---

### 4. Arsitektur RBAC (Mengadopsi Pola LDMS)

Sistem otorisasi mengadopsi struktur perbankan dari aplikasi LDMS (`C:\laragon\www\ldms`):

1. **`user_level` (Master Peran / Level)**:
   - `level_id` (PK), `level` (Nama Peran), `role` (slug), `keterangan`, `is_admin`, `is_active`.
   - **8 Peran Resmi BWS**:
     - Level 1: `Super Admin` (`super_admin`, `is_admin = true`)
     - Level 2: `Dept Head` (`dept_head`)
     - Level 3: `HCBP` (`hcbp`)
     - Level 4: `HC Training (Maker)` (`hc_maker`)
     - Level 5: `HC Learning Head (Checker)` (`hc_checker`)
     - Level 6: `Facilitator / Trainer` (`facilitator`)
     - Level 7: `Peserta Diklat` (`participant`)
     - Level 8: `Executive / BoD` (`executive`)

2. **`user_access` (Matriks Hak Akses / Permissions)**:
   - `id` (PK), `level_id` (FK), `controller_key` (permission string).
   - Menampung **108 permissions aktif** yang dikelompokkan ke dalam **11 Grup Modul**:
     1. *Dashboard & Monitoring*
     2. *TNA & Perencanaan Anggaran*
     3. *Program Kerja Pelatihan*
     4. *Jadwal & Batch Kelas (Dual-Control)*
     5. *Peserta Diklat & ELAPSE*
     6. *Evaluasi, Pre/Post Test*
     7. *Penugasan & Action Learning*
     8. *Transaksi & Reimbursement*
     9. *Sertifikasi Wajib Perbankan*
     10. *Laporan & Audit Trail*
     11. *Kelola Pengguna & RBAC*

3. **`"user"` & `users` (Pemetaan Akun Pengguna)**:
   - Terhubung dengan `level_id` &rarr; `user_level` dan `karyawan_id` &rarr; `karyawan`.
   - Terintegrasi dengan master data karyawan (NIK, Divisi, Kantor Cabang).

---

### 5. Panduan Menjalankan Sistem

#### Opsi A: Menggunakan Docker Compose (Direkomendasikan)
Jalankan satu perintah dari direktori root proyek:
```bash
cd C:\laragon\www\bws-lis
docker compose up -d
```
Docker akan menjalankan 4 container:
- **Frontend Admin**: `http://localhost:5173`
- **PWA Portal**: `http://localhost:5174`
- **Backend API**: `http://localhost:8001`
- **PostgreSQL**: `localhost:5433` (user: `bws_admin`, pass: `bws_secret_pass`, db: `bws_lis_db`)

#### Opsi B: Menggunakan Laragon Lokal
Jika dijalankan melalui Laragon:
1. Pastikan PostgreSQL di Laragon aktif pada port `5432` dengan database `db_lis`.
2. Buka terminal untuk masing-masing service:
   - **Backend**: `cd be && php artisan serve --port=8000`
   - **Frontend**: `cd fe && npm run dev`
   - **PWA**: `cd pwa-portal && npm run dev`
3. Buka browser pada `http://localhost/bws-lis` untuk mengakses Laragon Hub Launcher.

---

### 6. Kredensial Login Akun Pengguna

Semua akun default menggunakan password: **`password123`**

| ID | Username | Nama Pegawai | Email Login | Peran / Level | NIK Karyawan |
| :---: | :--- | :--- | :--- | :--- | :--- |
| **1** | `admin` | Budi Pratama | `admin@bws.co.id` | Super Admin | BWS-ADM-001 |
| **2** | `hendro` | Hendro Santoso | `depthead.it@bws.co.id` | Dept Head | BWS-DHD-042 |
| **3** | `siti` | Siti Rahmawati | `hcbp@bws.co.id` | HCBP | BWS-HCP-019 |
| **4** | `aditya` | Aditya Nugraha | `maker.training@bws.co.id` | HC Training (Maker) | BWS-TRN-101 |
| **5** | `maya` | Dr. Maya Wulandari | `checker.head@bws.co.id` | HC Learning Head (Checker) | BWS-TRN-002 |
| **6** | `bambang` | Bambang Soedjarwo | `facilitator@bws.co.id` | Facilitator / Trainer | BWS-FAC-014 |
| **7** | `yazid` | Yazid Bustomi | `participant@bws.co.id` | Peserta Diklat | BWS-EMP-9821 |
| **8** | `kwon` | Kwon Jin-Woo | `bod.executive@bws.co.id` | Executive / BoD | BWS-EXEC-003 |

---

### 7. Standar Identitas Visual Bank Woori Saudara (BWS)

- **Logo Resmi**: Vektor BWS Luminous Globe + Glowing Horizon Arc + Tipografi Bold Italic BWS BANK WOORI SAUDARA.
- **Warna Korporat Utama**:
  - `Woori Navy`: `#002855`
  - `Woori Deep Blue`: `#003876`
  - `Woori Royal Blue`: `#0067AC`
  - `Horizon Sky Cyan`: `#00A0E9`
  - `Light Surface Tint`: `#EBF4FC` / `#F0F4F8`
  - *Catatan Kepatuhan*: Warna hijau (`#157f4f`) telah sepenuhnya dihilangkan dari antarmuka utama.

---

### 8. Standar Tata Letak & Arsitektur UI Modul BWS LIS (Official Design Standard)

Seluruh modul operasional di dalam aplikasi BWS LIS (TNA, Program Kerja, Jadwal Kelas, Peserta ELAPSE, Evaluasi & Nilai, Penugasan, Transaksi & Reimburse, Sertifikasi Wajib, Reporting & Audit, dan Kelola User & RBAC) — **kecuali modul Executive Dashboard/Analytics** — wajib mengadopsi standar tata letak berikut:

#### 1. Header Halaman Tunggal Tanpa Kontainer Rounded (`#page-subheader`)
- **Judul Utama (`h1`)**: Font tegas (`text-2xl md:text-3xl font-bold tracking-tight text-gray-900`) langsung berada di atas canvas abu-abu lembut tanpa kartu putih (`rounded-3xl`) pembungkus ganda.
- **Sub-Judul (`p`)**: Keterangan kontekstual berukuran `text-xs text-gray-400 mt-0.5` yang menjelaskan secara tepat fungsi operasional modul aktif.
- **Aksi Cepat / Navigasi Sub-Tab Kanan Atas**:
  - Berada di baris header kanan atas sejajar dengan judul halaman.
  - Untuk modul dengan tab internal (seperti RBAC: *Kelola Pengguna*, *Tingkat Peran*, *Matriks Hak Akses*), pill switcher diletakkan di posisi ini dengan tab aktif menggunakan warna korporat `bg-[#0067AC] text-white rounded-full`.
  - Untuk modul dengan aksi pembuatan dokumen (seperti *+ Usulan TNA Baru*, *+ Buat Program Baru*, *+ Buat Batch Kelas*, *+ Buat Voucher / Klaim*, *Unduh Audit CSV*), tombol primer diletakkan di header kanan atas.
  - **Pemberantasan Clutter**: Tombol statis umum (`Tahun Buku`, `Send Class Invitation`, `+ Klaim / Reimburse`) tidak boleh muncul di modul yang tidak terkait.

#### 2. Isolasi Banner Dual-Control Checker
- Banner prioritas *Antrean Dual-Control Checker Aktif* hanya diizinkan tampil pada modul **Executive Dashboard** saat user memiliki peran Checker dan terdapat antrean persetujuan.
- Pada modul operasional lainnya, banner ini dinonaktifkan agar tidak mendorong layout ke bawah dan menjaga fokus pengguna.

#### 3. Jalur Kartu Metrik Ringkas (Standard 4-Metric Strip)
- Terdiri dari 4 kartu ringkas (`grid grid-cols-2 md:grid-cols-4 gap-4`).
- **Spesifikasi Kartu**: `bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3`.
- **Ikon**: Di sisi kiri dalam kotak rounded (`w-10 h-10 rounded-xl flex items-center justify-center font-bold`) dengan variasi warna latar pastel BWS:
  - Blue (`bg-blue-50 text-[#0067AC]`)
  - Emerald (`bg-emerald-50 text-emerald-700`)
  - Purple (`bg-purple-50 text-purple-700`)
  - Amber (`bg-amber-50 text-amber-700`)
- **Teks**: Label micro di atas (`text-[11px] text-gray-400 font-medium`) dan nilai tebal di bawah (`text-xl font-extrabold text-gray-900`).

#### 4. Kontainer Konten Tunggal (`DataTable` / List)
- Menggunakan satu kontainer utama putih (`bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4`).
- Tidak menampilkan judul `<h3>` ganda di dalam kartu tabel jika judul halaman utama sudah ada di header atas.
- Bagian atas tabel langsung menyajikan toolbar pencarian terintegrasi, filter tanggal/kategori, dan tombol export CSV/Excel.

