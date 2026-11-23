<script setup>
import { ref } from 'vue'
import {
  Printer,
  ExternalLink,
  Workflow,
  FileSpreadsheet,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  ShieldCheck,
  FileText,
  Globe2,
  KeyRound,
  UserCheck
} from 'lucide-vue-next'

function handlePrint() {
  window.print()
}

function openStandalone() {
  window.open('/bagan_flowchart_landscape.html', '_blank')
}
</script>

<template>
  <div class="space-y-6 w-full">

    <!-- Top Action & Info Bar -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#002855] to-[#0067AC] text-white flex items-center justify-center shadow-md">
          <Workflow class="w-6 h-6 text-white" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-[#0067AC] border border-blue-100">
              Dokumentasi Master
            </span>
            <span class="text-xs text-gray-400 font-medium">Bagan Alir Operasional</span>
          </div>
          <h1 class="text-xl font-black text-[#002855] tracking-tight">
            Bagan Alir (Flowchart) Proses Bisnis Aplikasi
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            Alur Terintegrasi: Login & Sync API HR &rarr; TNA & Anggaran &rarr; Kelas Dual-Control &rarr; Evaluasi & Sertifikasi (Import Excel) &rarr; Realisasi & Laporan
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 self-start md:self-auto">
        <button
          @click="openStandalone"
          class="px-4 py-2.5 rounded-2xl border border-gray-200 hover:border-[#0067AC] bg-white hover:bg-blue-50/50 text-[#002855] font-bold text-xs shadow-2xs hover:shadow-xs transition flex items-center gap-2 cursor-pointer"
        >
          <ExternalLink class="w-4 h-4 text-[#0067AC]" />
          <span>Buka di Tab Baru</span>
        </button>
        <button
          @click="handlePrint"
          class="px-4 py-2.5 rounded-2xl bg-[#0067AC] hover:bg-[#002855] text-white font-bold text-xs shadow-sm hover:shadow transition flex items-center gap-2 cursor-pointer"
        >
          <Printer class="w-4 h-4" />
          <span>Cetak A4 Landscape</span>
        </button>
      </div>
    </div>

    <!-- MAIN FLOWCHART CONTAINER (LANDSCAPE A4 STYLE) -->
    <div class="bg-white rounded-3xl border border-gray-200 p-5 sm:p-6 shadow-xs flex flex-col gap-4">
      
      <!-- Flowchart Sub-header & Legend -->
      <div class="flex flex-wrap items-center justify-between pb-3 border-b border-gray-200 gap-2">
        <div class="flex items-center gap-2">
          <span class="text-xs font-black uppercase text-[#002855] tracking-wider">
            Alur Horizontal (Landscape)
          </span>
          <span class="text-[11px] text-gray-400 font-semibold">5 Fase End-to-End</span>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-2 text-[10px]">
          <span class="inline-flex items-center gap-1 font-semibold text-gray-600 bg-gray-50 px-2 py-1 rounded-md border border-gray-200">
            <span class="w-2.5 h-2.5 rounded-full bg-[#002855]"></span> Terminator (Mulai/Selesai)
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-blue-900 bg-blue-50 px-2 py-1 rounded-md border border-blue-300">
            <Globe2 class="w-2.5 h-2.5 text-[#0067AC]" /> Sync API Aplikasi HR
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-gray-600 bg-gray-50 px-2 py-1 rounded-md border border-gray-200">
            <span class="w-2.5 h-2.5 bg-white border border-[#0067AC] rounded-sm"></span> Proses Kerja
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-emerald-800 bg-emerald-50 px-2 py-1 rounded-md border border-emerald-300">
            <FileSpreadsheet class="w-2.5 h-2.5 text-emerald-600" /> Import Excel
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-amber-900 bg-amber-50 px-2 py-1 rounded-md border border-amber-300">
            <span class="w-2.5 h-2.5 bg-amber-400 rounded-sm"></span> Titik Otorisasi (Keputusan)
          </span>
        </div>
      </div>

      <!-- 5 HORIZONTAL COLUMNS (LANDSCAPE FLOW) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2.5 bg-gray-50/70 p-3 rounded-2xl border border-gray-200">
        
        <!-- ============================================== -->
        <!-- LANE 1: OTENTIKASI & SYNC API HR (GATEWAY)     -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-sky-300 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">1. Otentikasi & API HR</span>
            <span class="text-[8px] bg-sky-400/20 px-1 py-0.5 rounded text-sky-200 font-bold shrink-0">Gateway</span>
          </div>

          <!-- Start Terminator -->
          <div class="rounded-full bg-[#002855] text-white text-center py-1.5 px-2 font-extrabold text-[9px] shadow-xs border-2 border-[#001733]">
            ● MULAI AKSES SISTEM
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.1: Input Kredensial -->
          <div class="bg-sky-50/70 border-2 border-sky-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-sky-700 block">TAHAP 1.1</span>
            <p class="text-[11px] font-black text-[#002855] leading-tight">Input NIK & Password</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Form Login Pegawai Bank</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.2: Sync API Core HR -->
          <div class="bg-blue-50/90 border-2 border-[#0067AC] rounded-xl p-2 text-center relative shadow-2xs">
            <span class="text-[7px] bg-[#0067AC] text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <Globe2 class="w-2 h-2 inline" /> SYNC API HR
            </span>
            <span class="text-[8px] font-bold text-[#0067AC] block mt-0.5">TAHAP 1.2</span>
            <p class="text-[11px] font-black text-[#002855] leading-tight">Sinkronisasi API Aplikasi HR</p>
            <p class="text-[9px] text-blue-800 font-medium mt-0.5">Validasi NIK, Jabatan, Unit & Status Aktif di HRIS</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Status HRIS Valid? -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              ◇ KEPUTUSAN
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">Status HRIS Aktif?</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; Tolak</span>
              <span class="text-emerald-700">Aktif &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.3: RBAC Mapping -->
          <div class="bg-indigo-50/70 border-2 border-indigo-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-indigo-700 block">TAHAP 1.3</span>
            <p class="text-[11px] font-black text-indigo-950 leading-tight">Pemetaan Peran (RBAC)</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Token Sesi & Otorisasi Hak Akses</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 2: TNA & ANGGARAN                         -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-blue-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">2. TNA & Anggaran</span>
            <span class="text-[8px] bg-blue-400/20 px-1 py-0.5 rounded text-blue-200 font-bold shrink-0">Maker</span>
          </div>

          <!-- State -->
          <div class="rounded-full bg-blue-50 text-blue-900 border border-blue-300 text-center py-1.5 px-2 font-extrabold text-[9px]">
            &bull; Usulan Diklat Unit Kerja
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 2.1: Input TNA -->
          <div class="bg-blue-50/70 border-2 border-[#0067AC] rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-[#0067AC] block">TAHAP 2.1</span>
            <p class="text-[11px] font-black text-[#002855] leading-tight">Input Usulan TNA Bank-Wide</p>
            <p class="text-[9px] text-gray-500 mt-0.5">1 Dokumen / Tahun & Target Investasi</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Approval TNA -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              ◇ KEPUTUSAN
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">Approval TNA?</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; Revisi</span>
              <span class="text-emerald-700">Setuju &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 2.2: Program & RAB 10 Komponen -->
          <div class="bg-indigo-50/60 border-2 border-indigo-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-indigo-700 block">TAHAP 2.2</span>
            <p class="text-[11px] font-black text-indigo-950 leading-tight">Program & RAB 10 Komponen</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Fee Trainer, SPPD, Konsumsi, dll.</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Multi-Approval -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              ◇ KEPUTUSAN
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">Multi-Approval RAB?</p>
            <p class="text-[8px] text-emerald-700 font-extrabold mt-0.5">Disetujui &rarr; Ready for Class</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 3: KELAS & DUAL-CONTROL                   -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-emerald-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-emerald-800 text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">3. Eksekusi Kelas</span>
            <span class="text-[8px] bg-emerald-400/20 px-1 py-0.5 rounded text-emerald-200 font-bold shrink-0">Maker-Checker</span>
          </div>

          <!-- State -->
          <div class="rounded-full bg-emerald-50 text-emerald-900 border border-emerald-300 text-center py-1.5 px-2 font-extrabold text-[9px]">
            &bull; Program Siap Dikelaskan
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.1: Buat Batch Kelas -->
          <div class="bg-emerald-50/50 border-2 border-emerald-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-emerald-700 block">TAHAP 3.1 (MAKER)</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">Setting Jadwal & Kuota</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Tatap Muka / Virtual / Hybrid</p>
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Decision: Dual-Control Checker -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              ◇ KEPUTUSAN
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">Dual-Control Checker?</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; Tolak</span>
              <span class="text-emerald-700">Lolos &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.2: Blast Undangan -->
          <div class="bg-slate-50 border-2 border-slate-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-slate-700 block">TAHAP 3.2</span>
            <p class="text-[11px] font-black text-slate-900 leading-tight">Blast Undangan Resmi</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Email Korporat & Jadwal di PWA</p>
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.3: Pelaksanaan Kelas -->
          <div class="bg-emerald-50/70 border-2 border-emerald-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-emerald-700 block">TAHAP 3.3</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">Pelaksanaan Pelatihan</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Materi Diklat & Sesi Pembelajaran</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 4: EVALUASI & SERTIFIKASI (IMPORT EXCEL)  -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-teal-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-teal-800 text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">4. Evaluasi & Sertifikasi</span>
            <span class="text-[8px] bg-teal-400/20 px-1 py-0.5 rounded text-teal-200 font-bold shrink-0">Import Data</span>
          </div>

          <!-- Process 4.1: Evaluasi Pelatihan -->
          <div class="bg-teal-50/50 border-2 border-teal-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-teal-700 block">TAHAP 4.1</span>
            <p class="text-[11px] font-black text-teal-950 leading-tight">Evaluasi Pelatihan</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Kuesioner Feedback Peserta</p>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Process 4.2: Import Excel Absen & Score -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-2 text-center relative shadow-2xs">
            <span class="text-[7px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <FileSpreadsheet class="w-2 h-2 inline" /> IMPORT EXCEL
            </span>
            <span class="text-[8px] font-bold text-emerald-800 block mt-0.5">TAHAP 4.2</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">Import Absen & Score Nilai</p>
            <p class="text-[9px] text-emerald-700 font-medium mt-0.5">Rekap Absensi, Pre-Test & Post-Test</p>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Process 4.3: Import Excel Kelulusan -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-2 text-center relative shadow-2xs">
            <span class="text-[7px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <FileSpreadsheet class="w-2 h-2 inline" /> IMPORT EXCEL
            </span>
            <span class="text-[8px] font-bold text-emerald-800 block mt-0.5">TAHAP 4.3</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">Import Kelulusan Peserta</p>
            <p class="text-[9px] text-emerald-700 font-medium mt-0.5">Status Kelulusan dari File Excel</p>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Decision: Kelulusan -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              ◇ KEPUTUSAN
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">Hasil Import Kelulusan?</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; Tidak Lulus</span>
              <span class="text-emerald-700">Lulus &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Process 4.4: E-Sertifikat -->
          <div class="bg-purple-50/70 border-2 border-purple-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-purple-700 block">TAHAP 4.4</span>
            <p class="text-[11px] font-black text-purple-950 leading-tight">E-Sertifikat QR Validasi</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Terbit Otomatis bagi Peserta Lulus</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 5: KEUANGAN, AUDIT & REALISASI            -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-rose-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">5. Keuangan & Audit</span>
            <span class="text-[8px] bg-rose-400/20 px-1 py-0.5 rounded text-rose-200 font-bold shrink-0">Finance</span>
          </div>

          <!-- Process 5.1: Klaim Biaya -->
          <div class="bg-rose-50/40 border-2 border-rose-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-rose-700 block">TAHAP 5.1</span>
            <p class="text-[11px] font-black text-rose-950 leading-tight">Klaim Realisasi Biaya</p>
            <p class="text-[9px] text-gray-500 mt-0.5">10 Komponen RAB & Kwitansi Riil</p>
          </div>

          <div class="w-0.5 h-3 bg-rose-600 mx-auto"></div>

          <!-- Decision: Approval Finance -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              ◇ KEPUTUSAN
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">Approval Keuangan?</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; Revisi</span>
              <span class="text-emerald-700">Disetujui &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-rose-600 mx-auto"></div>

          <!-- Process 5.2: Integrasi ELAPSE -->
          <div class="bg-cyan-50/60 border-2 border-cyan-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-cyan-700 block">TAHAP 5.2</span>
            <p class="text-[11px] font-black text-cyan-950 leading-tight">Integrasi Aplikasi ELAPSE</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Pencairan Dana & Potong Sisa Pagu TNA</p>
          </div>

          <div class="w-0.5 h-3 bg-cyan-600 mx-auto"></div>

          <!-- Process 5.3: Audit Trail -->
          <div class="bg-slate-50 border-2 border-slate-700 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-slate-700 block">TAHAP 5.3</span>
            <p class="text-[11px] font-black text-slate-900 leading-tight">SHA-256 Audit Trail Log</p>
            <p class="text-[9px] text-gray-500 mt-0.5">Pencatatan Riwayat & Keamanan Data</p>
          </div>

          <div class="w-0.5 h-3 bg-[#002855] mx-auto"></div>

          <!-- Terminator Selesai: Realisasi dan Laporan -->
          <div class="rounded-full bg-[#002855] text-white text-center py-2 px-2 font-black text-[10px] shadow-sm border-2 border-[#001733] tracking-wide">
            ■ REALISASI DAN LAPORAN
          </div>
        </div>

      </div>

      <!-- Footer Info Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between pt-2 border-t border-gray-100 text-[11px] text-gray-500 gap-2">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span>Siklus Operasional: Otentikasi & Sync API HR &rarr; TNA & Anggaran &rarr; Eksekusi Kelas &rarr; Evaluasi (Import Excel) &rarr; Realisasi dan Laporan</span>
        </div>
        <div class="text-[10px] font-semibold text-gray-400">
          Learning Integrated System &copy; 2026
        </div>
      </div>

    </div>

  </div>
</template>
