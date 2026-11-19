# Bank Woori Saudara (BWS) — Learning Integrated System (LIS)

> **Platform Terpadu Pengelolaan Diklat, Sertifikasi Wajib OJK, dan Pembelajaran Mandiri (ELAPSE) PT Bank Woori Saudara Indonesia 1906 Tbk.**

![BWS Corporate Blue](https://img.shields.io/badge/Brand-Bank%20Woori%20Saudara-0067AC?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.5%20LTS-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.42.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5%20LTS-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![React](https://img.shields.io/badge/React-19.0%20TS-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![MySQL](https://img.shields.io/badge/MySQL-8.4%20LTS-00758F?style=for-the-badge&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)

---

## 📌 Ringkasan Sistem

**BWS Learning Integrated System (LIS)** adalah platform perbankan modern yang mengintegrasikan proses end-to-end Learning & Development:
- Master Kategori & Sub-Kategori Diklat Berjenjang (*Parent-Child*)
- Master Tahun Anggaran (*Fiscal Year*)
- Perencanaan Pelatihan Tahunan (*Training Needs Analysis* / TNA) Ramping Multiple-Add
- Rencana Program Kerja dengan Rincian Anggaran Biaya (RAB Komprehensif: Narasumber, Transport, Konsumsi, Materi)
- Dual Otorisasi Program (*Approval by Document* & *Multi-Approval Workflow*)
- Pelaksanaan Batch Kelas (1 Program Banyak Kelas) dengan Jadwal Sesi Harian Otomatis
- Modul Presensi Kehadiran Digital (Check-in, Izin, Sakit, Alpa, Kalkulasi % Live)
- Evaluasi Pembelajaran Bertingkat (*Kirkpatrick Framework Level 1 & 2*) & Penerbitan E-Sertifikat Berbarcode
- Audit Trail Kepatuhan Regulasi OJK (*Immutable Logs*)
- Integrasi Sistem Internal: Core HRIS BWS (Login/SSO), SMTP Server BWS (Email Notifikasi), CBT BWS (Ujian), dan Aplikasi Keuangan (Reimbursement)
- Portal Pembelajaran Mobile PWA Karyawan (*ELAPSE*)
- **Manajemen Pengguna & Matriks Hak Akses (RBAC 8 Peran)**.

---

## 🏛️ Arsitektur Aplikasi (Decoupled Multi-Application)

```
bws-lis/
├── be/                  # Central Backend REST API (Laravel 13 + PHP 8.5 LTS + Sanctum)
├── fe/                  # Aplikasi 1: Portal Operasional & Admin (Vue 3.5 LTS + Vite 6 + Tailwind CSS)
├── pwa-portal/          # Aplikasi 2: Portal Belajar Pegawai (React 19 + TypeScript + Vite PWA)
├── _dokumentasi/        # Handover Arsitektur, Skema RBAC, dan Spesifikasi Teknis
├── docker-compose.yml   # Konfigurasi 4 Container Microservices
└── index.php            # Laragon Hub Launcher
```

---

## 🚀 Panduan Memulai Cepat (*Quick Start*)

### Menggunakan Docker Compose (Direkomendasikan untuk Ubuntu & Windows)
Cukup jalankan satu perintah:
```bash
docker compose up -d
```
Semua 4 service akan langsung berjalan otomatis:
- **Aplikasi 1 (Portal Admin & Operasional)**: [http://localhost:5173](http://localhost:5173)
- **Aplikasi 2 (Portal Mobile Belajar Peserta)**: [http://localhost:5174](http://localhost:5174)
- **Backend REST API**: [http://localhost:8001/api](http://localhost:8001/api) (Internal Container: Port `8000`)
- **MySQL 8.4 Database**: Port `3307` (Internal Container: Port `3306`, DB: `bws_lis_db`)

---

## 👥 Matriks Akun Pengguna & Peran (RBAC)

Password default untuk seluruh akun: **`password123`**

| ID | Username | Nama Pegawai | Email Login | Tingkat Peran (RBAC) | NIK Karyawan |
| :---: | :--- | :--- | :--- | :--- | :--- |
| **1** | `admin` | Budi Pratama | `admin@bws.co.id` | Super Admin (Full Access) | BWS-ADM-001 |
| **2** | `hendro` | Hendro Santoso | `depthead.it@bws.co.id` | Dept Head | BWS-DHD-042 |
| **3** | `siti` | Siti Rahmawati | `hcbp@bws.co.id` | HCBP | BWS-HCP-019 |
| **4** | `aditya` | Aditya Nugraha | `maker.training@bws.co.id` | HC Training (Maker) | BWS-TRN-101 |
| **5** | `maya` | Dr. Maya Wulandari | `checker.head@bws.co.id` | HC Learning Head (Checker) | BWS-TRN-002 |
| **6** | `bambang` | Bambang Soedjarwo | `facilitator@bws.co.id` | Facilitator / Trainer | BWS-FAC-014 |
| **7** | `yazid` | Yazid Bustomi | `participant@bws.co.id` | Peserta Diklat | BWS-EMP-9821 |
| **8** | `kwon` | Kwon Jin-Woo | `bod.executive@bws.co.id` | Executive / BoD | BWS-EXEC-003 |

---

## 🎨 Identitas Visual Bank Woori Saudara (BWS)

- **Logo**: Vector Logo Resmi Bank Woori Saudara (Luminous Sphere, Glowing Horizon, dan Slanted Bold Text BWS BANK WOORI SAUDARA) berdampingan dengan teks **`LIS - Learning Integrated System`**.
- **Warna Korporat**:
  - `Woori Navy`: `#002855`
  - `Woori Deep Blue`: `#003876`
  - `Woori Royal Blue`: `#0067AC`
  - `Horizon Sky Cyan`: `#00A0E9`
  - `Light Tint`: `#EBF4FC`
- Bersih dari skema warna hijau pada elemen antarmuka utama.

---

## 📐 Standar Tata Letak & Arsitektur UI Modul
Seluruh modul operasional (*TNA, Program, Kelas, Peserta, Evaluasi, Penugasan, Transaksi, Sertifikasi, Reporting, RBAC*) — **kecuali modul Executive Dashboard/Analytics** — mengikuti standar terpadu:
1. **Header Tunggal Uncontained**: Judul halaman `h1` dan subjudul `p` bersih tanpa kartu rounded ganda.
2. **Aksi Kontekstual di Header Kanan Atas**: Tombol utama pembuatan data atau sub-tab diletakkan di baris header kanan atas. Tombol umum yang tidak relevan dihilangkan dari modul terkait.
3. **Isolasi Dual-Control Banner**: Banner kuning Checker hanya aktif pada Executive Dashboard.
4. **Jalur 4 Kartu Metrik Ringkas**: 4 kartu metrik (`rounded-2xl p-4`) dengan ikon di sisi kiri (`w-10 h-10 rounded-xl`) dan tipografi seragam.
5. **Kontainer Konten Bersih**: Tabel dan list disajikan dalam kontainer tunggal tanpa judul `<h3>` inner yang berulang.

---

## 📚 Dokumentasi Terkait
- Handover Lengkap: [`_dokumentasi/BWS_LIS_TECH_STACK_AND_ARCHITECTURE_HANDOVER.md`](_dokumentasi/BWS_LIS_TECH_STACK_AND_ARCHITECTURE_HANDOVER.md)
- Walkthrough Rinci: [`walkthrough.md`](file:///C:/Users/drapp/.gemini/antigravity/brain/906fda55-3c42-484e-ab67-0385f3c89a62/walkthrough.md)

