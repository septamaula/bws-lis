<script setup>
import { useI18n } from '@/i18n'
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
  UserCheck,
  Award,
  ListTodo,
  Download,
  CreditCard,
  Star
} from 'lucide-vue-next'

const { l } = useI18n()

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
  <div class="space-y-5 w-full">

    <!-- Top Action & Info Bar (Sembunyi otomatis saat dicetak) -->
    <div class="flowchart-action-bar no-print bg-white rounded-3xl p-5 md:p-6 border border-gray-100 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
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
            {{ l('Application Business Process Flowchart', 'Bagan Alir (Flowchart) Proses Bisnis Aplikasi') }}
          </h1>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ l('Integrated Flow: Login & HR Sync -> TNA & Budget -> Class & Task Module -> Class Quality Evaluation & Certification -> LIS Claim, Excel Export & Manual Reimbursement', 'Alur Terintegrasi: Login & Sync API HR -> TNA & Anggaran -> Kelas & Modul Task -> Evaluasi Kualitas Kelas & Sertifikasi -> Klaim LIS, Export Excel & Reimburse Manual') }}
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
          <span>{{ l('Print / Save PDF (1 Page)', 'Cetak Bagan (1 Halaman)') }}</span>
        </button>
      </div>
    </div>

    <!-- MAIN FLOWCHART CONTAINER (LANDSCAPE A4 STYLE) -->
    <div class="flowchart-main-card bg-white rounded-3xl border border-gray-200 p-4 sm:p-5 shadow-xs flex flex-col gap-3">
      
      <!-- Flowchart Sub-header -->
      <div class="flex flex-wrap items-center justify-between pb-2 border-b border-gray-200 gap-2">
        <div class="flex items-center gap-2">
          <span class="text-xs font-black uppercase text-[#002855] tracking-wider">
            {{ l('Horizontal Flow (Landscape)', 'Alur Horizontal (Landscape)') }}
          </span>
          <span class="text-[11px] text-gray-400 font-semibold">{{ l('5 End-to-End Operational Phases', '5 Fase End-to-End Operasional Bank') }}</span>
        </div>
        <span class="text-[10px] text-slate-400 font-medium hidden sm:inline">
          {{ l('Banking Standard Operating Procedure (A4 Landscape Ready)', 'Format Standar SOP Perbankan (A4 Landscape Ready)') }}
        </span>
      </div>

      <!-- COMPREHENSIVE COLOR LEGEND BAR (JELAS SAAT DI LAYAR & SAAT PRINT/PDF) -->
      <div class="flowchart-legend-box bg-slate-50 border border-slate-200 rounded-2xl p-2.5 flex flex-col gap-1.5">
        <div class="flex items-center justify-between">
          <span class="text-[8.5px] font-black uppercase text-[#002855] tracking-wider flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-[#0067AC]"></span> {{ l('COLOR & SYMBOL LEGEND (COMPREHENSIVE FLOWCHART GUIDE):', 'PETUNJUK WARNA & KATEGORI SIMBOL (LEGEND FLOWCHART):') }}
          </span>
          <span class="text-[8px] text-slate-400 font-semibold">{{ l('Clearly Preserved on Save PDF / Print A4 Landscape', 'Tersimpan Jelas Saat Save PDF / Cetak 1 Halaman') }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-10 gap-1.5 text-[8px]">
          <!-- 1. Terminator -->
          <div class="flex items-center gap-1 bg-white px-2 py-1 rounded-lg border border-slate-200 shadow-2xs">
            <span class="w-2.5 h-2.5 rounded-full bg-[#002855] shrink-0"></span>
            <span class="font-bold text-[#002855] leading-tight">{{ l('Terminator (Start/End)', 'Terminator (Mulai/Selesai)') }}</span>
          </div>
          <!-- 2. Sync HR -->
          <div class="flex items-center gap-1 bg-[#EFF6FF] px-2 py-1 rounded-lg border border-[#0067AC]/40 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#0067AC] rounded-xs shrink-0"></span>
            <span class="font-bold text-[#0067AC] leading-tight">{{ l('HR App API Sync', 'Sync API Aplikasi HR') }}</span>
          </div>
          <!-- 3. Proses Operasional -->
          <div class="flex items-center gap-1 bg-white px-2 py-1 rounded-lg border border-[#0067AC] shadow-2xs">
            <span class="w-2.5 h-2.5 bg-white border border-[#0067AC] rounded-xs shrink-0"></span>
            <span class="font-bold text-slate-700 leading-tight">{{ l('Process / Form', 'Proses Kerja / Form') }}</span>
          </div>
          <!-- 4. Evaluasi Mutu Kelas -->
          <div class="flex items-center gap-1 bg-[#F0FDFA] px-2 py-1 rounded-lg border border-[#0D9488]/40 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#0D9488] rounded-xs shrink-0"></span>
            <span class="font-bold text-teal-900 leading-tight">{{ l('Class Quality Eval', 'Evaluasi Mutu Kelas') }}</span>
          </div>
          <!-- 5. Import Excel -->
          <div class="flex items-center gap-1 bg-[#F0FDF4] px-2 py-1 rounded-lg border border-[#16A34A]/40 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#16A34A] rounded-xs shrink-0"></span>
            <span class="font-bold text-emerald-800 leading-tight">{{ l('Excel Import (Score)', 'Import Excel (Absen/Nilai)') }}</span>
          </div>
          <!-- 6. Modul Task -->
          <div class="flex items-center gap-1 bg-[#EEF2FF] px-2 py-1 rounded-lg border border-[#6366F1]/50 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#4F46E5] rounded-xs shrink-0"></span>
            <span class="font-bold text-indigo-900 leading-tight">{{ l('Self-Task Module', 'Modul Task Mandiri') }}</span>
          </div>
          <!-- 7. Sertifikasi -->
          <div class="flex items-center gap-1 bg-[#FAF5FF] px-2 py-1 rounded-lg border border-[#9333EA]/50 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#7E22CE] rounded-xs shrink-0"></span>
            <span class="font-bold text-purple-900 leading-tight">{{ l('Certification (Int/Ext)', 'Sertifikasi (Internal/Luar)') }}</span>
          </div>
          <!-- 8. Titik Otorisasi -->
          <div class="flex items-center gap-1 bg-[#FEF3C7] px-2 py-1 rounded-lg border border-[#D97706]/50 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#D97706] rounded-xs shrink-0"></span>
            <span class="font-bold text-amber-950 leading-tight">{{ l('Decision Gate', 'Otorisasi (Keputusan)') }}</span>
          </div>
          <!-- 9. Klaim LIS -->
          <div class="flex items-center gap-1 bg-[#FFF1F2] px-2 py-1 rounded-lg border border-[#E11D48]/40 shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#E11D48] rounded-xs shrink-0"></span>
            <span class="font-bold text-rose-900 leading-tight">{{ l('LIS Expense Claim', 'Klaim Biaya di LIS') }}</span>
          </div>
          <!-- 10. Export Excel Reimburse -->
          <div class="flex items-center gap-1 bg-[#F0FDF4] px-2 py-1 rounded-lg border border-[#059669] shadow-2xs">
            <span class="w-2.5 h-2.5 bg-[#059669] rounded-xs shrink-0"></span>
            <span class="font-bold text-emerald-900 leading-tight">{{ l('Excel Export Reimburse', 'Export Excel Reimburse') }}</span>
          </div>
        </div>
      </div>

      <!-- 5 HORIZONTAL COLUMNS (LANDSCAPE FLOW) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2 bg-gray-50/70 p-2.5 rounded-2xl border border-gray-200">
        
        <!-- ============================================== -->
        <!-- LANE 1: OTENTIKASI & SYNC API HR (GATEWAY)     -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-sky-300 p-2.5 flex flex-col justify-between gap-1.5 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[9px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('1. Auth & HR API', '1. Otentikasi & API HR') }}</span>
            <span class="text-[7px] bg-sky-400/20 px-1 py-0.5 rounded text-sky-200 font-bold shrink-0">Gateway</span>
          </div>

          <!-- Start Terminator -->
          <div class="rounded-full bg-[#002855] text-white text-center py-1.5 px-2 font-extrabold text-[8.5px] shadow-xs border-2 border-[#001733]">
            ● {{ l('START SYSTEM ACCESS', 'MULAI AKSES SISTEM') }}
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.1: Input Kredensial -->
          <div class="bg-sky-50/70 border-2 border-sky-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-sky-700 block">{{ l('STEP 1.1', 'TAHAP 1.1') }}</span>
            <p class="text-[10px] font-black text-[#002855] leading-tight">{{ l('Input Username & Password', 'Input Username & Password') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('Bank Employee Login Form', 'Form Login Pegawai Bank') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.2: Sync API Core HR -->
          <div class="bg-blue-50/90 border-2 border-[#0067AC] rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-[#0067AC] text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <Globe2 class="w-2 h-2 inline" /> SYNC API HR
            </span>
            <span class="text-[7.5px] font-bold text-[#0067AC] block mt-0.5">{{ l('STEP 1.2', 'TAHAP 1.2') }}</span>
            <p class="text-[10px] font-black text-[#002855] leading-tight">{{ l('HR Application API Sync', 'Sinkronisasi API Aplikasi HR') }}</p>
            <p class="text-[8px] text-blue-800 font-medium mt-0.5">{{ l('Validate Employee Account, Title, Unit & Status in HRIS', 'Validasi Akun Pegawai, Jabatan, Unit & Status di HRIS') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Status HRIS Valid? -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[9px] font-black text-amber-950 mt-0.5">{{ l('HRIS Status Active?', 'Status HRIS Aktif?') }}</p>
            <div class="flex items-center justify-between text-[7.5px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Reject', 'Tolak') }}</span>
              <span class="text-emerald-700">{{ l('Active', 'Aktif') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Process 1.3: RBAC Mapping -->
          <div class="bg-indigo-50/70 border-2 border-indigo-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-indigo-700 block">{{ l('STEP 1.3', 'TAHAP 1.3') }}</span>
            <p class="text-[10px] font-black text-indigo-950 leading-tight">{{ l('Role-Based Access Mapping (RBAC)', 'Pemetaan Peran (RBAC)') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('Session Token & Access Permissions', 'Token Sesi & Otorisasi Hak Akses') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 2: TNA & ANGGARAN (PERENCANAAN)           -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-blue-200 p-2.5 flex flex-col justify-between gap-1.5 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[9px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('2. TNA & Budget', '2. TNA & Anggaran') }}</span>
            <span class="text-[7px] bg-blue-400/20 px-1 py-0.5 rounded text-blue-200 font-bold shrink-0">{{ l('Planning', 'Perencanaan') }}</span>
          </div>

          <!-- State -->
          <div class="rounded-full bg-blue-50 text-blue-900 border border-blue-300 text-center py-1 px-2 font-extrabold text-[8px]">
            &bull; {{ l('Work Unit Training Proposal', 'Usulan Diklat Unit Kerja') }}
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Process 2.1: Input TNA -->
          <div class="bg-blue-50/70 border-2 border-[#0067AC] rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-[#0067AC] block">{{ l('STEP 2.1', 'TAHAP 2.1') }}</span>
            <p class="text-[10px] font-black text-[#002855] leading-tight">{{ l('Input Bank-Wide Annual TNA', 'Input Usulan TNA Bank-Wide') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('1 Doc / Year & OJK Target', '1 Dokumen / Tahun & Target Regulasi OJK') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Approval TNA -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[9px] font-black text-amber-950 mt-0.5">{{ l('Approval TNA Divisi L&D?', 'Approval TNA Divisi L&D?') }}</p>
            <div class="flex items-center justify-between text-[7.5px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Revise', 'Revisi') }}</span>
              <span class="text-emerald-700">{{ l('Approve', 'Setuju') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Process 2.2: Program & RAB 10 Komponen -->
          <div class="bg-blue-50/60 border-2 border-blue-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-blue-700 block">{{ l('STEP 2.2', 'TAHAP 2.2') }}</span>
            <p class="text-[10px] font-black text-blue-950 leading-tight">{{ l('Program & 10-Item Budget (RAB)', 'Program & RAB 10 Komponen') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('Trainer Fee, Meals, Hotel, Transport, etc.', 'Fee Trainer, SPPD, Konsumsi, Venue, dll.') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-[#0067AC] mx-auto"></div>

          <!-- Decision: Multi-Approval -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[9px] font-black text-amber-950 mt-0.5">{{ l('Multi-Approval RAB?', 'Multi-Approval RAB?') }}</p>
            <p class="text-[7.5px] text-emerald-700 font-extrabold mt-0.5">{{ l('Approved -> Ready for Class', 'Disetujui -> Ready for Class') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 3: KELAS & MODUL TASK MANDIRI             -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-emerald-200 p-2.5 flex flex-col justify-between gap-1.5 shadow-2xs">
          <div class="bg-emerald-800 text-white py-1 px-2 rounded-xl text-[9px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('3. Class & Task Module', '3. Kelas & Modul Task') }}</span>
            <span class="text-[7px] bg-emerald-400/20 px-1 py-0.5 rounded text-emerald-200 font-bold shrink-0">{{ l('Execution', 'Pelaksanaan') }}</span>
          </div>

          <!-- State -->
          <div class="rounded-full bg-emerald-50 text-emerald-900 border border-emerald-300 text-center py-1 px-2 font-extrabold text-[8px]">
            &bull; {{ l('Program Ready for Class Scheduling', 'Program Siap Dikelaskan') }}
          </div>

          <div class="w-0.5 h-2.5 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.1: Buat Batch Kelas & Setup Evaluasi Kualitas -->
          <div class="bg-emerald-50/50 border-2 border-emerald-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-emerald-700 block">{{ l('STEP 3.1 (MAKER)', 'TAHAP 3.1 (MAKER)') }}</span>
            <p class="text-[10px] font-black text-emerald-950 leading-tight">{{ l('Schedule, Quota & Quality Setup', 'Jadwal, Kuota & Setup Evaluasi') }}</p>
            <p class="text-[7.5px] text-gray-500 mt-0.5">{{ l('In-Person/Virtual + Class Quality Metrics Setup', 'Tatap Muka/Virtual + Setup Evaluasi Mutu Kelas') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-emerald-600 mx-auto"></div>

          <!-- Decision: Dual-Control Checker -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[9px] font-black text-amber-950 mt-0.5">{{ l('Dual-Control Checker?', 'Dual-Control Checker?') }}</p>
            <div class="flex items-center justify-between text-[7.5px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Reject', 'Tolak') }}</span>
              <span class="text-emerald-700">{{ l('Passed', 'Lolos') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-2.5 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.2: Blast Undangan -->
          <div class="bg-slate-50 border-2 border-slate-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-slate-700 block">{{ l('STEP 3.2', 'TAHAP 3.2') }}</span>
            <p class="text-[10px] font-black text-slate-900 leading-tight">{{ l('Blast Official Invites & Schedule', 'Blast Undangan & Jadwal') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('Corporate Email & PWA Calendar', 'Email Korporat & Kalender di PWA') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.3: MODUL TASK MANDIRI -->
          <div class="bg-indigo-50/80 border-2 border-indigo-600 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-indigo-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <ListTodo class="w-2 h-2 inline" /> {{ l('SELF-TASK MODULE', 'MODUL TASK MANDIRI') }}
            </span>
            <span class="text-[7.5px] font-bold text-indigo-700 block mt-0.5">{{ l('STEP 3.3', 'TAHAP 3.3') }}</span>
            <p class="text-[10px] font-black text-indigo-950 leading-tight">{{ l('Self-Task Module', 'Modul Task Mandiri') }}</p>
            <p class="text-[7.5px] text-indigo-900 font-semibold leading-tight mt-0.5">
              &bull; {{ l('Training-Related: Action Learning / Class Tasks', 'Terkait Pelatihan: Action Learning / Tugas Kelas') }}<br>
              &bull; {{ l('Non-Training: Work Unit Independent Assignment', 'Luar Pelatihan: Penugasan Mandiri Unit Kerja') }}
            </p>
            <span class="text-[7px] text-indigo-700 block mt-0.5">{{ l('Participant Submission & Supervisor Grading', 'Submisi & Penilaian Atasan / Trainer') }}</span>
          </div>

          <div class="w-0.5 h-2.5 bg-emerald-600 mx-auto"></div>

          <!-- Process 3.4: Pelaksanaan Kelas -->
          <div class="bg-emerald-50/70 border-2 border-emerald-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-emerald-700 block">{{ l('STEP 3.4', 'TAHAP 3.4') }}</span>
            <p class="text-[10px] font-black text-emerald-950 leading-tight">{{ l('Training Delivery Session', 'Pelaksanaan Pelatihan') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('Learning Materials & Interactive Classroom Delivery', 'Materi Diklat & Sesi Pembelajaran Interaktif') }}</p>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 4: EVALUASI KUALITAS & SERTIFIKASI       -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-teal-200 p-2.5 flex flex-col justify-between gap-1.5 shadow-2xs">
          <div class="bg-teal-800 text-white py-1 px-2 rounded-xl text-[9px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('4. Quality & Cert.', '4. Kualitas & Sertifikasi') }}</span>
            <span class="text-[7px] bg-teal-400/20 px-1 py-0.5 rounded text-teal-200 font-bold shrink-0">{{ l('Quality & License', 'Mutu & Lisensi') }}</span>
          </div>

          <!-- Process 4.1: EVALUASI KUALITAS KELAS & PENGAJAR (USER REQUIREMENT) -->
          <div class="bg-teal-50/90 border-2 border-teal-600 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-teal-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <Star class="w-2 h-2 inline text-amber-300" /> {{ l('CLASS QUALITY EVAL', 'EVALUASI KUALITAS KELAS') }}
            </span>
            <span class="text-[7.5px] font-bold text-teal-800 block mt-0.5">{{ l('STEP 4.1', 'TAHAP 4.1') }}</span>
            <p class="text-[10px] font-black text-teal-950 leading-tight">{{ l('Class & Trainer Quality Evaluation', 'Evaluasi Mutu & Kualitas Kelas') }}</p>
            <p class="text-[7.5px] text-teal-900 font-semibold leading-tight mt-0.5">
              &bull; {{ l('3-Dimension Rating: Trainer, Material, Committee', 'Rating 3 Dimensi: Pengajar, Materi, Panitia') }}<br>
              &bull; {{ l('Class Quality Score Output (Kirkpatrick L1)', 'Skor Indeks Mutu Kelas (Quality Score)') }}<br>
              &bull; {{ l('Participant Feedback Questionnaire in PWA', 'Kuesioner Feedback Peserta di PWA') }}
            </p>
          </div>

          <div class="w-0.5 h-2.5 bg-teal-600 mx-auto"></div>

          <!-- Process 4.2: Import Excel Absen & Score -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <FileSpreadsheet class="w-2 h-2 inline" /> IMPORT EXCEL
            </span>
            <span class="text-[7.5px] font-bold text-emerald-800 block mt-0.5">{{ l('STEP 4.2', 'TAHAP 4.2') }}</span>
            <p class="text-[10px] font-black text-emerald-950 leading-tight">{{ l('Import Attendance & Scores', 'Import Absen & Score Nilai') }}</p>
            <p class="text-[7.5px] text-emerald-700 font-medium mt-0.5">{{ l('Attendance, Pre-Test & Post-Test (Competency Gain)', 'Rekap Absensi, Pre & Post-Test (Gain Kompetensi)') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-teal-600 mx-auto"></div>

          <!-- Process 4.3: Import Excel Kelulusan -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <FileSpreadsheet class="w-2 h-2 inline" /> IMPORT EXCEL
            </span>
            <span class="text-[7.5px] font-bold text-emerald-800 block mt-0.5">{{ l('STEP 4.3', 'TAHAP 4.3') }}</span>
            <p class="text-[10px] font-black text-emerald-950 leading-tight">{{ l('Import Participant Graduation', 'Import Kelulusan Peserta') }}</p>
            <p class="text-[8px] text-emerald-700 font-medium mt-0.5">{{ l('Graduation Status from Excel File', 'Status Kelulusan dari File Excel') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-teal-600 mx-auto"></div>

          <!-- Decision: Kelulusan -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[9px] font-black text-amber-950 mt-0.5">{{ l('Graduation Import Result?', 'Hasil Import Kelulusan?') }}</p>
            <div class="flex items-center justify-between text-[7.5px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Did Not Pass', 'Tidak Lulus') }}</span>
              <span class="text-emerald-700">{{ l('Passed', 'Lulus') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-2.5 bg-teal-600 mx-auto"></div>

          <!-- DUA JALUR SERTIFIKASI -->
          <div class="flex flex-col gap-1.5">
            <!-- Jalur Internal: E-Sertifikat -->
            <div class="bg-purple-50/70 border-2 border-purple-600 rounded-xl p-1.5 text-center">
              <span class="text-[7.5px] font-bold text-purple-700 block">{{ l('STEP 4.4 (INTERNAL)', 'TAHAP 4.4 (INTERNAL)') }}</span>
              <p class="text-[10px] font-black text-purple-950 leading-tight">{{ l('QR-Validated E-Certificate', 'E-Sertifikat QR Validasi') }}</p>
              <p class="text-[7.5px] text-purple-700 mt-0.5">{{ l('Auto-Issued for Passing Participants', 'Terbit Otomatis bagi Peserta Lulus') }}</p>
            </div>
            <!-- Jalur Eksternal: Modul Sertifikasi Luar -->
            <div class="bg-purple-50/90 border-2 border-purple-700 rounded-xl p-1.5 text-center relative shadow-2xs">
              <span class="text-[6.5px] bg-purple-700 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
                <Award class="w-2 h-2 inline" /> {{ l('EXTERNAL CERTIFICATION', 'SERTIFIKASI EKSTERNAL') }}
              </span>
              <span class="text-[7.5px] font-bold text-purple-800 block mt-0.5">{{ l('STEP 4.5 (EXTERNAL)', 'TAHAP 4.5 (EKSTERNAL)') }}</span>
              <p class="text-[10px] font-black text-purple-950 leading-tight">{{ l('External Certification Module', 'Modul Sertifikasi Luar') }}</p>
              <p class="text-[7.5px] text-purple-800 font-medium mt-0.5">{{ l('User Applies External Certificate -> L&D Verification -> License DB', 'User Ajukan Sertifikat Luar -> Verifikasi L&D -> Database Lisensi') }}</p>
            </div>
          </div>
        </div>

        <!-- ============================================== -->
        <!-- LANE 5: KEUANGAN, REIMBURSE & AUDIT TRAIL      -->
        <!-- ============================================== -->
        <div class="bg-white rounded-2xl border border-rose-200 p-2.5 flex flex-col justify-between gap-1.5 shadow-2xs">
          <div class="bg-[#002855] text-white py-1 px-2 rounded-xl text-[9px] font-black uppercase tracking-wider flex items-center justify-between">
            <span class="truncate">{{ l('5. Finance & Audit', '5. Keuangan & Audit') }}</span>
            <span class="text-[7px] bg-rose-400/20 px-1 py-0.5 rounded text-rose-200 font-bold shrink-0">{{ l('Finance & Audit', 'Finance & Audit') }}</span>
          </div>

          <!-- Process 5.1: Klaim Biaya di LIS -->
          <div class="bg-rose-50/50 border-2 border-rose-600 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-rose-700 block">{{ l('STEP 5.1', 'TAHAP 5.1') }}</span>
            <p class="text-[10px] font-black text-rose-950 leading-tight">{{ l('Expense Claim Submission in LIS', 'Pengajuan Klaim di LIS') }}</p>
            <p class="text-[8px] text-rose-800 font-medium mt-0.5">{{ l('Claim Processed in LIS (10 Budget Components & Actual Receipts)', 'Klaim Dilaksanakan di LIS (10 Komponen RAB & Kwitansi Riil)') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-rose-600 mx-auto"></div>

          <!-- Decision: Approval Keuangan di LIS -->
          <div class="bg-amber-50 border-2 border-amber-500 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-amber-500 text-white font-extrabold px-1 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 whitespace-nowrap">
              {{ l('◇ DECISION', '◇ KEPUTUSAN') }}
            </span>
            <p class="text-[9px] font-black text-amber-950 mt-0.5">{{ l('Finance Approved in LIS?', 'Approval Keuangan di LIS?') }}</p>
            <p class="text-[7.5px] text-amber-700">{{ l('Maker - Checker Finance in LIS', 'Maker - Checker Keuangan LIS') }}</p>
            <div class="flex items-center justify-between text-[7.5px] font-bold px-1 mt-0.5">
              <span class="text-rose-600">&larr; {{ l('Revise', 'Revisi') }}</span>
              <span class="text-emerald-700">{{ l('Approved', 'Disetujui') }} &darr;</span>
            </div>
          </div>

          <div class="w-0.5 h-2.5 bg-rose-600 mx-auto"></div>

          <!-- Process 5.2: Export Excel & Reimburse Manual -->
          <div class="bg-emerald-50/90 border-2 border-emerald-600 rounded-xl p-1.5 text-center relative shadow-2xs">
            <span class="text-[6.5px] bg-emerald-600 text-white font-extrabold px-1.5 py-0.2 rounded-full absolute -top-2 left-1/2 -translate-x-1/2 flex items-center gap-0.5 whitespace-nowrap">
              <Download class="w-2 h-2 inline" /> EXPORT EXCEL & REIMBURSE
            </span>
            <span class="text-[7.5px] font-bold text-emerald-800 block mt-0.5">{{ l('STEP 5.2', 'TAHAP 5.2') }}</span>
            <p class="text-[10px] font-black text-emerald-950 leading-tight">{{ l('Excel Export & Manual Reimbursement', 'Export Excel & Reimburse Manual') }}</p>
            <p class="text-[7.5px] text-emerald-800 font-semibold leading-tight mt-0.5">
              &bull; {{ l('Approved LIS Claim Exported to Excel', 'Data Klaim LIS Diekspor ke Excel') }}<br>
              &bull; {{ l('User Processes Manual Reimbursement to Bank Finance', 'User Proses Reimburse Manual ke Keuangan Bank') }}<br>
              &bull; {{ l('LIS Automatically Deducts & Realizes TNA/Program Ceiling', 'LIS Otomatis Potong & Realisasi Pagu TNA/Program') }}
            </p>
          </div>

          <div class="w-0.5 h-2.5 bg-rose-600 mx-auto"></div>

          <!-- Process 5.3: Audit Trail -->
          <div class="bg-slate-50 border-2 border-slate-700 rounded-xl p-1.5 text-center">
            <span class="text-[7.5px] font-bold text-slate-700 block">{{ l('STEP 5.3', 'TAHAP 5.3') }}</span>
            <p class="text-[10px] font-black text-slate-900 leading-tight">{{ l('SHA-256 Audit Trail Log', 'SHA-256 Audit Trail Log') }}</p>
            <p class="text-[8px] text-gray-500 mt-0.5">{{ l('Tamper-Proof History & Audit Compliance', 'Pencatatan Riwayat & Kepatuhan Audit') }}</p>
          </div>

          <div class="w-0.5 h-2.5 bg-[#002855] mx-auto"></div>

          <!-- Terminator Selesai: Realisasi dan Laporan -->
          <div class="rounded-full bg-[#002855] text-white text-center py-2 px-2 font-black text-[9.5px] shadow-sm border-2 border-[#001733] tracking-wide">
            ■ {{ l('REALIZATION AND REPORTING', 'REALISASI DAN LAPORAN') }}
          </div>
        </div>

      </div>

      <!-- Footer Info Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between pt-2 border-t border-gray-100 text-[10px] text-gray-500 gap-2">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          <span>{{ l('Operational Cycle: Authentication & HR API Sync -> TNA & Budget -> Class & Task Module -> Class Quality Evaluation & Certification -> LIS Claim, Excel Export & Manual Reimbursement', 'Siklus Operasional: Otentikasi & Sync API HR -> TNA & Anggaran -> Kelas & Modul Task -> Evaluasi Kualitas Kelas & Sertifikasi -> Klaim LIS, Export Excel & Reimburse Manual') }}</span>
        </div>
        <div class="text-[9.5px] font-semibold text-gray-400">
          Learning Integrated System &copy; 2026 Bank Woori Saudara
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
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
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
    padding: 2.5mm !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }

  /* Pastikan legend tercetak dengan jelas */
  .flowchart-legend-box {
    border: 1px solid #cbd5e1 !important;
    background-color: #f8fafc !important;
    padding: 2mm !important;
  }
}
</style>
