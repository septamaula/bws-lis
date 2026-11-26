<script setup>
import { useI18n } from '@/i18n'

const { l } = useI18n()
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
  // Cetak langsung HANYA bagan flowchart (tanpa menu aplikasi / navbar / sidebar)
  const existingIframe = document.getElementById('flowchart-print-iframe')
  if (existingIframe) {
    document.body.removeChild(existingIframe)
  }

  const iframe = document.createElement('iframe')
  iframe.id = 'flowchart-print-iframe'
  iframe.style.position = 'fixed'
  iframe.style.right = '0'
  iframe.style.bottom = '0'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = '0'
  iframe.style.opacity = '0'
  iframe.src = '/bagan_flowchart_landscape.html'
  document.body.appendChild(iframe)

  iframe.onload = () => {
    setTimeout(() => {
      try {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
      } catch (e) {
        // Fallback jika iframe print terblokir oleh browser
        window.open('/bagan_flowchart_landscape.html', '_blank')
      }
      setTimeout(() => {
        try {
          if (iframe.parentNode) {
            document.body.removeChild(iframe)
          }
        } catch (_) {}
      }, 10000)
    }, 400)
  }
}

function openStandalone() {
  window.open('/bagan_flowchart_landscape.html', '_blank')
}
</script>

<template>
  <div class="space-y-6 w-full">

    <!-- Top Action & Info Bar (Sembunyi otomatis saat dicetak) -->
    <div class="flowchart-action-bar no-print bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#002855] to-[#0067AC] text-white flex items-center justify-center shadow-md">
          <Workflow class="w-6 h-6 text-white" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-[#0067AC] border border-blue-100">
              {{ l('Master Documentation', 'Dokumentasi Master') }}
            </span>
            <span class="text-xs text-gray-400 font-medium">{{ l('Operational Flowchart', 'Bagan Alir Operasional') }}</span>
          </div>
          <h1 class="text-xl font-black text-[#002855] tracking-tight">
            {{ l('Application Business Process Flowchart', 'Bagan Alir Proses Bisnis Aplikasi') }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ l('Integrated Flow: Login & HR API Sync -> TNA & Budget -> Dual-Control Class -> Evaluation & Certification (Excel Import) -> Realization & Reporting', 'Alur Terintegrasi: Masuk & Sinkronisasi API HR -> TNA & Anggaran -> Kelas Kontrol Ganda -> Evaluasi & Sertifikasi (Impor Excel) -> Realisasi & Laporan') }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 self-start md:self-auto">
        <button
          @click="openStandalone"
          class="px-4 py-2.5 rounded-2xl border border-gray-200 hover:border-[#0067AC] bg-white hover:bg-blue-50/50 text-[#002855] font-bold text-xs shadow-2xs hover:shadow-xs transition flex items-center gap-2 cursor-pointer"
        >
          <ExternalLink class="w-4 h-4 text-[#0067AC]" />
          <span>{{ l('Open in New Tab', 'Buka di Tab Baru') }}</span>
        </button>
        <button
          @click="handlePrint"
          class="px-4 py-2.5 rounded-2xl bg-[#0067AC] hover:bg-[#002855] text-white font-bold text-xs shadow-sm hover:shadow transition flex items-center gap-2 cursor-pointer"
        >
          <Printer class="w-4 h-4" />
          <span>{{ l('Print Chart (1 Page)', 'Cetak Bagan (1 Halaman)') }}</span>
        </button>
      </div>
    </div>

    <!-- MAIN FLOWCHART CONTAINER (LANDSCAPE A4 STYLE) -->
    <div class="flowchart-main-card bg-white rounded-3xl border border-gray-200 p-5 sm:p-6 shadow-xs flex flex-col gap-4">
      
      <!-- Flowchart Sub-header & Legend -->
      <div class="flex flex-wrap items-center justify-between pb-3 border-b border-gray-200 gap-2">
        <div class="flex items-center gap-2">
          <span class="text-xs font-black uppercase text-[#002855] tracking-wider">
            {{ l('Horizontal Flow (Landscape)', 'Alur Horizontal (Lanskap)') }}
          </span>
          <span class="text-[11px] text-gray-400 font-semibold">{{ l('5 End-to-End Phases', '5 Fase Menyeluruh') }}</span>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-2 text-[10px]">
          <span class="inline-flex items-center gap-1 font-semibold text-gray-600 bg-gray-50 px-2 py-1 rounded-md border border-gray-200">
            <span class="w-2.5 h-2.5 rounded-full bg-[#002855]"></span> {{ l('Terminator (Start/End)', 'Terminator (Mulai/Selesai)') }}
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-blue-900 bg-blue-50 px-2 py-1 rounded-md border border-blue-300">
            <Globe2 class="w-2.5 h-2.5 text-[#0067AC]" /> {{ l('HR API Sync', 'Sinkronisasi API HR') }}
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-gray-600 bg-gray-50 px-2 py-1 rounded-md border border-gray-200">
            <span class="w-2.5 h-2.5 bg-white border border-[#0067AC] rounded-sm"></span> {{ l('Work Process', 'Proses Kerja') }}
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-emerald-800 bg-emerald-50 px-2 py-1 rounded-md border border-emerald-300">
            <FileSpreadsheet class="w-2.5 h-2.5 text-emerald-600" /> {{ l('Excel Import', 'Impor Excel') }}
          </span>
          <span class="inline-flex items-center gap-1 font-semibold text-amber-900 bg-amber-50 px-2 py-1 rounded-md border border-amber-300">
            <span class="w-2.5 h-2.5 bg-amber-400 rounded-sm"></span> {{ l('Authorization Point (Decision)', 'Titik Otorisasi (Keputusan)') }}
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
            <span class="truncate">{{ l('1. Authentication & HR API', '1. Autentikasi & API HR') }}</span>
            <span class="text-[8px] bg-sky-400/20 px-1 py-0.5 rounded text-sky-200 font-bold shrink-0">{{ l('Gateway', 'Gerbang Akses') }}</span>
          </div>

          <!-- Start Terminator -->
          <div class="rounded-full bg-[#002855] text-white text-center py-1.5 px-2 font-extrabold text-[9px] shadow-xs border-2 border-[#001733]">
            {{ l('● START SYSTEM ACCESS', '● MULAI AKSES SISTEM') }}
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.1: Input Kredensial -->
          <div class="bg-sky-50/70 border-2 border-sky-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-sky-700 block">{{ l('STEP 1.1', 'TAHAP 1.1') }}</span>
            <p class="text-[11px] font-black text-[#002855] leading-tight">{{ l('Enter Username & Password', 'Input Nama Pengguna & Kata Sandi') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Bank Employee Login Form', 'Formulir Masuk Pegawai Bank') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.2: Sync API Core HR -->
          <div class="bg-blue-50/90 border-2 border-[#0067AC] rounded-xl p-2 text-center relative shadow-2xs">
            <span class="text-[7px] bg-[#0067AC] text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <Globe2 class="w-2 h-2 inline" /> {{ l('HR API SYNC', 'SINKRONISASI API HR') }}
            </span>
            <span class="text-[8px] font-bold text-[#0067AC] block mt-0.5">{{ l('STEP 1.2', 'TAHAP 1.2') }}</span>
            <p class="text-[11px] font-black text-[#002855] leading-tight">{{ l('HR Application API Sync', 'Sinkronisasi API Aplikasi HR') }}</p>
            <p class="text-[9px] text-blue-800 font-medium mt-0.5">{{ l('Validate Employee Account, Position, Unit & Active Status in HRIS', 'Validasi Akun Pegawai, Jabatan, Unit & Status Aktif di HRIS') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Status HRIS Valid? -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">{{ l('HRIS Status Active?', 'Status HRIS Aktif?') }}</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Reject', 'Tolak') }}</span>
              <span class="text-emerald-700">{{ l('Active', 'Aktif') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.3: RBAC Mapping -->
          <div class="bg-indigo-50/70 border-2 border-indigo-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-indigo-700 block">{{ l('STEP 1.3', 'TAHAP 1.3') }}</span>
            <p class="text-[11px] font-black text-indigo-950 leading-tight">{{ l('Role Mapping (RBAC)', 'Pemetaan Peran (RBAC)') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Session Token & Access Rights Authorization', 'Token Sesi & Otorisasi Hak Akses') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 2: TNA & ANGGARAN                         -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-blue-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('2. TNA & Budget', '2. TNA & Anggaran') }}</span>
            <span class="text-[8px] bg-blue-400/20 px-1 py-0.5 rounded text-blue-200 font-bold shrink-0">{{ l('Maker', 'Pembuat') }}</span>
          </div>

          <!-- State -->
          <div class="rounded-full bg-blue-50 text-blue-900 border border-blue-300 text-center py-1.5 px-2 font-extrabold text-[9px]">
            &bull; {{ l('Unit Training Proposals', 'Usulan Diklat Unit Kerja') }}
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 2.1: Input TNA -->
          <div class="bg-blue-50/70 border-2 border-[#0067AC] rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-[#0067AC] block">{{ l('STEP 2.1', 'TAHAP 2.1') }}</span>
            <p class="text-[11px] font-black text-[#002855] leading-tight">{{ l('Submit Bank-Wide TNA Proposal', 'Input Usulan TNA Seluruh Bank') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('1 Document / Year & Investment Target', '1 Dokumen / Tahun & Target Investasi') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Approval TNA -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">{{ l('TNA Approved?', 'TNA Disetujui?') }}</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Revise', 'Revisi') }}</span>
              <span class="text-emerald-700">{{ l('Approved', 'Setuju') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Process 2.2: Program & RAB 10 Komponen -->
          <div class="bg-indigo-50/60 border-2 border-indigo-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-indigo-700 block">{{ l('STEP 2.2', 'TAHAP 2.2') }}</span>
            <p class="text-[11px] font-black text-indigo-950 leading-tight">{{ l('Programs & 10-Component Budget', 'Program & RAB 10 Komponen') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Trainer Fee, Travel Allowance, Meals, etc.', 'Honorarium Pengajar, SPPD, Konsumsi, dll.') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Multi-Approval -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">{{ l('Budget Multi-Approval?', 'Persetujuan Berjenjang RAB?') }}</p>
            <p class="text-[8px] text-emerald-700 font-extrabold mt-0.5">{{ l('Approved -> Ready for Class', 'Disetujui -> Siap Dikelaskan') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 3: KELAS & DUAL-CONTROL                   -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-emerald-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-emerald-800 text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('3. Class Execution', '3. Eksekusi Kelas') }}</span>
            <span class="text-[8px] bg-emerald-400/20 px-1 py-0.5 rounded text-emerald-200 font-bold shrink-0">{{ l('Maker-Checker', 'Pembuat-Pemeriksa') }}</span>
          </div>

          <!-- State -->
          <div class="rounded-full bg-emerald-50 text-emerald-900 border border-emerald-300 text-center py-1.5 px-2 font-extrabold text-[9px]">
            &bull; {{ l('Class-Ready Programs', 'Program Siap Dikelaskan') }}
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.1: Buat Batch Kelas -->
          <div class="bg-emerald-50/50 border-2 border-emerald-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-emerald-700 block">{{ l('STEP 3.1 (MAKER)', 'TAHAP 3.1 (PEMBUAT)') }}</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">{{ l('Configure Schedule & Quota', 'Atur Jadwal & Kuota') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('In-Person / Virtual / Hybrid', 'Tatap Muka / Virtual / Hibrida') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Decision: Dual-Control Checker -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">{{ l('Dual-Control Checker?', 'Pemeriksa Kontrol Ganda?') }}</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Reject', 'Tolak') }}</span>
              <span class="text-emerald-700">{{ l('Passed', 'Lolos') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.2: Blast Undangan -->
          <div class="bg-slate-50 border-2 border-slate-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-slate-700 block">{{ l('STEP 3.2', 'TAHAP 3.2') }}</span>
            <p class="text-[11px] font-black text-slate-900 leading-tight">{{ l('Official Invitation Blast', 'Kirim Undangan Resmi') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Corporate Email & PWA Schedule', 'Email Korporat & Jadwal di PWA') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.3: Pelaksanaan Kelas -->
          <div class="bg-emerald-50/70 border-2 border-emerald-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-emerald-700 block">{{ l('STEP 3.3', 'TAHAP 3.3') }}</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">{{ l('Training Delivery', 'Pelaksanaan Pelatihan') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Course Materials & Learning Sessions', 'Materi Diklat & Sesi Pembelajaran') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 4: EVALUASI & SERTIFIKASI (IMPORT EXCEL)  -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-teal-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-teal-800 text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('4. Evaluation & Certification', '4. Evaluasi & Sertifikasi') }}</span>
            <span class="text-[8px] bg-teal-400/20 px-1 py-0.5 rounded text-teal-200 font-bold shrink-0">{{ l('Data Import', 'Impor Data') }}</span>
          </div>

          <!-- Process 4.1: Evaluasi Pelatihan -->
          <div class="bg-teal-50/50 border-2 border-teal-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-teal-700 block">{{ l('STEP 4.1', 'TAHAP 4.1') }}</span>
            <p class="text-[11px] font-black text-teal-950 leading-tight">{{ l('Training Evaluation', 'Evaluasi Pelatihan') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Participant Feedback Questionnaire', 'Kuesioner Umpan Balik Peserta') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Process 4.2: Import Excel Absen & Score -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-2 text-center relative shadow-2xs">
            <span class="text-[7px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <FileSpreadsheet class="w-2 h-2 inline" /> {{ l('EXCEL IMPORT', 'IMPOR EXCEL') }}
            </span>
            <span class="text-[8px] font-bold text-emerald-800 block mt-0.5">{{ l('STEP 4.2', 'TAHAP 4.2') }}</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">{{ l('Import Attendance & Test Scores', 'Impor Presensi & Nilai Ujian') }}</p>
            <p class="text-[9px] text-emerald-700 font-medium mt-0.5">{{ l('Attendance Recap, Pre-Test & Post-Test', 'Rekap Presensi, Ujian Awal & Ujian Akhir') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Process 4.3: Import Excel Kelulusan -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-2 text-center relative shadow-2xs">
            <span class="text-[7px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <FileSpreadsheet class="w-2 h-2 inline" /> {{ l('EXCEL IMPORT', 'IMPOR EXCEL') }}
            </span>
            <span class="text-[8px] font-bold text-emerald-800 block mt-0.5">{{ l('STEP 4.3', 'TAHAP 4.3') }}</span>
            <p class="text-[11px] font-black text-emerald-950 leading-tight">{{ l('Import Participant Graduation', 'Impor Kelulusan Peserta') }}</p>
            <p class="text-[9px] text-emerald-700 font-medium mt-0.5">{{ l('Graduation Status from Excel File', 'Status Kelulusan dari Berkas Excel') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Decision: Kelulusan -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">{{ l('Graduation Import Result?', 'Hasil Impor Kelulusan?') }}</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Did Not Pass', 'Tidak Lulus') }}</span>
              <span class="text-emerald-700">{{ l('Passed', 'Lulus') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-teal-600 mx-auto"></div>

          <!-- Process 4.4: E-Sertifikat -->
          <div class="bg-purple-50/70 border-2 border-purple-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-purple-700 block">{{ l('STEP 4.4', 'TAHAP 4.4') }}</span>
            <p class="text-[11px] font-black text-purple-950 leading-tight">{{ l('QR-Verified E-Certificate', 'E-Sertifikat Validasi QR') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Issued Automatically for Passing Participants', 'Terbit Otomatis bagi Peserta Lulus') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 5: KEUANGAN, AUDIT & REALISASI            -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-rose-200 p-2.5 flex flex-col justify-between gap-2 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[10px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('5. Finance & Audit', '5. Keuangan & Audit') }}</span>
            <span class="text-[8px] bg-rose-400/20 px-1 py-0.5 rounded text-rose-200 font-bold shrink-0">{{ l('Finance', 'Keuangan') }}</span>
          </div>

          <!-- Process 5.1: Klaim Biaya -->
          <div class="bg-rose-50/40 border-2 border-rose-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-rose-700 block">{{ l('STEP 5.1', 'TAHAP 5.1') }}</span>
            <p class="text-[11px] font-black text-rose-950 leading-tight">{{ l('Expense Realization Claim', 'Klaim Realisasi Biaya') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('10 Budget Components & Actual Receipts', '10 Komponen RAB & Kuitansi Riil') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-rose-600 mx-auto"></div>

          <!-- Decision: Approval Finance -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[7px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[10px] font-black text-amber-950 mt-0.5">{{ l('Finance Approved?', 'Disetujui Keuangan?') }}</p>
            <div class="flex items-center justify-between text-[8px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Revise', 'Revisi') }}</span>
              <span class="text-emerald-700">{{ l('Approved', 'Disetujui') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-3 bg-rose-600 mx-auto"></div>

          <!-- Process 5.2: Integrasi ELAPSE -->
          <div class="bg-cyan-50/60 border-2 border-cyan-600 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-cyan-700 block">{{ l('STEP 5.2', 'TAHAP 5.2') }}</span>
            <p class="text-[11px] font-black text-cyan-950 leading-tight">{{ l('ELAPSE Application Integration', 'Integrasi Aplikasi ELAPSE') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('Fund Disbursement & TNA Ceiling Deduction', 'Pencairan Dana & Potong Sisa Pagu TNA') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-cyan-600 mx-auto"></div>

          <!-- Process 5.3: Audit Trail -->
          <div class="bg-slate-50 border-2 border-slate-700 rounded-xl p-2 text-center">
            <span class="text-[8px] font-bold text-slate-700 block">{{ l('STEP 5.3', 'TAHAP 5.3') }}</span>
            <p class="text-[11px] font-black text-slate-900 leading-tight">{{ l('SHA-256 Audit Trail Log', 'Log Jejak Audit SHA-256') }}</p>
            <p class="text-[9px] text-gray-500 mt-0.5">{{ l('History Logging & Data Security', 'Pencatatan Riwayat & Keamanan Data') }}</p>
          </div>

          <div class="w-0.5 h-3 bg-[#002855] mx-auto"></div>

          <!-- Terminator Selesai: Realisasi dan Laporan -->
          <div class="rounded-full bg-[#002855] text-white text-center py-2 px-2 font-black text-[10px] shadow-sm border-2 border-[#001733] tracking-wide">
            {{ l('■ REALIZATION & REPORTING', '■ REALISASI DAN LAPORAN') }}
          </div>
        </div>

      </div>

      <!-- Footer Info Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between pt-2 border-t border-gray-100 text-[11px] text-gray-500 gap-2">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span>{{ l('Operational Cycle: Authentication & HR API Sync -> TNA & Budget -> Class Execution -> Evaluation (Excel Import) -> Realization & Reporting', 'Siklus Operasional: Autentikasi & Sinkronisasi API HR -> TNA & Anggaran -> Eksekusi Kelas -> Evaluasi (Impor Excel) -> Realisasi dan Laporan') }}</span>
        </div>
        <div class="text-[10px] font-semibold text-gray-400">
          Learning Integrated System &copy; 2026
        </div>
      </div>

    </div>

  </div>
</template>

<style>
@media print {
  @page {
    size: A4 landscape;
    margin: 4mm;
  }

  /* Sembunyikan SEMUA menu, navbar, sidebar, subheader, dan tombol aksi aplikasi */
  header,
  aside,
  #page-subheader,
  .flowchart-action-bar,
  .no-print {
    display: none !important;
  }

  /* Reset layout dan body agar murni bagan yang tercetak */
  body, html, #app {
    background: #ffffff !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
  }

  /* Hapus padding pembungkus luar dari Dashboard */
  .min-h-screen, .px-4, .py-3, .md\:px-6, .md\:py-4, .gap-5 {
    padding: 0 !important;
    margin: 0 !important;
    gap: 0 !important;
    background: transparent !important;
  }

  /* Kotak utama bagan tampil penuh dan pas di 1 halaman A4 Landscape */
  .flowchart-main-card {
    border: 1px solid #cbd5e1 !important;
    box-shadow: none !important;
    padding: 3mm !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }
}
</style>
