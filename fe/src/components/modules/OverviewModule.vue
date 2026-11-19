<script setup>
import { computed } from 'vue'
import {
  Wallet,
  Calendar,
  GraduationCap,
  FileCheck,
  CheckCircle2,
  ArrowUpRight,
  BarChart2,
  Receipt,
  Users,
  Award,
  Clock
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  kpis: {
    type: Object,
    required: true
  },
  trainingClasses: {
    type: Array,
    default: () => []
  },
  financials: {
    type: Array,
    default: () => []
  },
  tnaProposals: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['switch-view', 'open-modal', 'view-tna-detail'])

// Format Rupiah helper
function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

// Prepare Priority Approval Queue dataset from pending classes, vouchers, and TNA
const priorityQueueData = computed(() => {
  const items = []

  // Pending Financial Vouchers
  props.financials.filter(f => f.status === 'PENDING_APPROVAL').forEach(f => {
    items.push({
      id: `TRX-${f.id}`,
      code: f.trx_number || `#TRX-2026-${String(f.id).padStart(3, '0')}`,
      type: 'Transaction (Reimburse)',
      typeColor: 'text-[#0067AC]',
      title: f.description || `Reimburse Operasional Diklat #${f.id}`,
      subtitle: `${formatRupiah(f.amount)} • Bukti Kwitansi Terlampir`,
      pic: f.maker?.name || 'Class PIC',
      picRole: 'Maker / PIC',
      approvalPosition: 'Menunggu Manager / Checker',
      approvalBadgeClass: 'bg-amber-50 text-amber-700',
      approvalDotClass: 'bg-amber-500',
      actionLabel: 'Review & Approve',
      actionType: 'voucher',
      entityId: f.id,
      date: f.created_at || '2026-05-10'
    })
  })

  // Pending Classes (Dual Control)
  props.trainingClasses.filter(c => c.dual_control_status === 'PENDING_CHECKER').forEach(c => {
    items.push({
      id: `CLS-${c.id}`,
      code: c.class_code || `#CLS-2026-${String(c.id).padStart(3, '0')}`,
      type: 'Class (Dual-Control)',
      typeColor: 'text-blue-600',
      title: `${c.program?.title || 'Batch Diklat'} - ${c.batch_name}`,
      subtitle: `${c.location_or_url || 'BWS Learning Center'} • Kuota ${c.max_quota} Peserta`,
      pic: c.maker?.name || 'HC Training Maker',
      picRole: 'HC Maker',
      approvalPosition: 'Menunggu HC Learning Head (Checker)',
      approvalBadgeClass: 'bg-blue-50 text-blue-700',
      approvalDotClass: 'bg-blue-500',
      actionLabel: 'Otorisasi Checker',
      actionType: 'class',
      entityId: c.id,
      date: c.created_at || '2026-05-12'
    })
  })

  // Pending TNA Proposals (Multi-Approval Pipeline)
  props.tnaProposals.filter(t => ['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW', 'REVISION'].includes(t.status)).forEach(t => {
    let approvalPosition = 'Menunggu Review HCBP'
    let approvalBadgeClass = 'bg-purple-50 text-purple-700'
    let approvalDotClass = 'bg-purple-600'
    let actionLabel = 'Validasi HCBP'

    if (t.status === 'CHECKER_REVIEW') {
      approvalPosition = 'Menunggu Checker HC Learning'
      approvalBadgeClass = 'bg-blue-50 text-blue-700'
      approvalDotClass = 'bg-[#0067AC]'
      actionLabel = 'Otorisasi Checker'
    } else if (t.status === 'APPROVER_REVIEW') {
      approvalPosition = 'Menunggu Kadiv HC / BoD'
      approvalBadgeClass = 'bg-amber-50 text-amber-700'
      approvalDotClass = 'bg-amber-500'
      actionLabel = 'Sign-Off Kadiv'
    } else if (t.status === 'REVISION') {
      approvalPosition = 'Perlu Revisi Dept Head'
      approvalBadgeClass = 'bg-rose-50 text-rose-700'
      approvalDotClass = 'bg-rose-500'
      actionLabel = 'Perbaiki Usulan'
    }

    items.push({
      id: `TNA-${t.id}`,
      code: t.proposal_code || `#TNA-2026-${String(t.id).padStart(3, '0')}`,
      type: 'TNA (Perencanaan)',
      typeColor: 'text-[#002855]',
      title: `Usulan TNA Divisi ${t.department} - Tahun ${t.year}`,
      subtitle: `Estimasi Anggaran: ${formatRupiah(t.total_budget_requested)} • ${t.items?.length || 0} Mata Diklat`,
      pic: t.proposer?.name || 'Dept Head',
      picRole: 'Dept Head',
      approvalPosition,
      approvalBadgeClass,
      approvalDotClass,
      actionLabel,
      actionType: 'tna',
      entityId: t.id,
      date: t.created_at || '2026-05-15'
    })
  })

  // Fallback demo data if no pending items exist in DB
  if (items.length === 0) {
    items.push(
      {
        id: 'TRX-2026-089',
        code: '#TRX-2026-089',
        type: 'Transaction (Reimburse)',
        typeColor: 'text-[#0067AC]',
        title: 'Reimburse Ujian Sertifikasi SMR Level 2 (12 Peserta)',
        subtitle: 'Rp 42.000.000 • Bukti Kwitansi LSPP Terlampir',
        pic: 'Nadia Putri',
        picRole: 'Class PIC',
        approvalPosition: 'Menunggu Manager L&D',
        approvalBadgeClass: 'bg-amber-50 text-amber-700',
        approvalDotClass: 'bg-amber-500',
        actionLabel: 'Review & Approve',
        actionType: 'voucher',
        entityId: 1,
        date: '2026-05-10'
      },
      {
        id: 'RPT-2026-Q2',
        code: '#RPT-2026-Q2',
        type: 'All Training Report',
        typeColor: 'text-blue-600',
        title: 'Laporan Evaluasi & Realisasi Pelatihan Kuartal II 2026',
        subtitle: 'Sudah Direview Supervisor L&D • Butuh Approved (1) & (2)',
        pic: 'Hendra Wijaya',
        picRole: 'Spv L&D',
        approvalPosition: 'Approved Report (1) Mgr',
        approvalBadgeClass: 'bg-purple-50 text-purple-700',
        approvalDotClass: 'bg-purple-600',
        actionLabel: 'Sign-Off Report',
        actionType: 'report',
        entityId: 2,
        date: '2026-05-12'
      },
      {
        id: 'TSK-2026-041',
        code: '#TSK-2026-041',
        type: 'Task (Class Activity)',
        typeColor: 'text-[#0067AC]',
        title: 'Action Learning Project: Credit Analyst Batch IV',
        subtitle: '25/25 Peserta telah Upload File • Direview oleh PIC',
        pic: 'Rina Melati',
        picRole: 'Class PIC',
        approvalPosition: 'Menunggu Spv, Mgr & Kadiv',
        approvalBadgeClass: 'bg-[#EBF4FC] text-[#0067AC]',
        approvalDotClass: 'bg-[#0067AC]',
        actionLabel: 'Buka Modul Task',
        actionType: 'task',
        entityId: 3,
        date: '2026-05-14'
      }
    )
  }

  return items
})

const priorityColumns = [
  { key: 'code', label: 'Modul & ID', sortable: true },
  { key: 'title', label: 'Deskripsi Aktivitas', sortable: true },
  { key: 'pic', label: 'Pengaju / PIC', sortable: true },
  { key: 'approvalPosition', label: 'Posisi Approval Saat Ini', sortable: true },
  { key: 'date', label: 'Tanggal Pengajuan', sortable: true, format: (val) => val ? val.substring(0, 10) : '-' }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top 12-Col Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
      
      <!-- Left 8 Cols -->
      <div class="lg:col-span-8 flex flex-col gap-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
          
          <!-- BWS Training Budget Card -->
          <div class="md:col-span-5 flex flex-col gap-5">
            <div class="bg-white rounded-3xl p-5 shadow-sm flex-1 flex flex-col justify-between border border-gray-100">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <h2 class="font-bold text-base text-gray-900">Pagu Anggaran L&D 2026</h2>
                  <p class="text-xs text-gray-400">Approved TNA Budget vs Realisasi</p>
                </div>
                <button
                  @click="emit('switch-view', 'tna')"
                  class="w-8 h-8 rounded-full bg-[#f4f6f5] flex items-center justify-center text-gray-600 hover:bg-[#0067AC] hover:text-white transition"
                  title="Lihat Detail TNA"
                >
                  <ArrowUpRight class="w-4 h-4" />
                </button>
              </div>

              <!-- Green ATM Card Style -->
              <div class="bg-gradient-to-br from-[#002855] via-[#003876] to-[#0067AC] text-white rounded-2xl p-5 flex flex-col justify-between min-h-[165px] shadow-lg relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-36 h-36 rounded-full bg-white/5 pointer-events-none"></div>
                <div class="flex items-center justify-between relative z-10">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-sm tracking-wider uppercase">BANK WOORI SAUDARA</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#00A0E9]"></span>
                  </div>
                  <span class="text-[10px] bg-white/15 px-2.5 py-0.5 rounded-full font-semibold text-sky-200">FY 2026</span>
                </div>
                <div class="my-3 relative z-10">
                  <p class="text-[11px] text-sky-200/90 mb-0.5">Total Approved Training Budget</p>
                  <p class="text-2xl font-bold tracking-tight text-white">
                    {{ formatRupiah(kpis.total_budget_approved || 8450000000) }}
                  </p>
                </div>
                <div class="flex items-center justify-between text-xs text-sky-200/90 pt-2 border-t border-white/15 relative z-10">
                  <span>Terserap: <strong class="text-white">{{ formatRupiah(kpis.total_budget_disbursed || 5180000000) }} ({{ kpis.budget_utilization_pct || 61.3 }}%)</strong></span>
                  <span class="text-white font-medium">34 Program</span>
                </div>
              </div>
            </div>

            <!-- Mandatory Certification KPI Card -->
            <div class="bg-white rounded-3xl px-5 py-4 shadow-sm flex items-center justify-between border border-gray-100">
              <div>
                <p class="text-xs text-gray-400 mb-0.5">Realisasi Sertifikasi Wajib (YTD)</p>
                <p class="text-xl font-bold text-gray-900">412 / 450 Lulus</p>
              </div>
              <span class="bg-[#0067AC] text-white text-xs font-semibold px-3 py-1 rounded-full">
                91.5% Target
              </span>
            </div>
          </div>

          <!-- Monthly Training Implementation Bar Chart -->
          <div class="md:col-span-7 bg-white rounded-3xl p-5 shadow-sm flex flex-col justify-between border border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-[#f4f6f5] flex items-center justify-center text-gray-600">
                  <BarChart2 class="w-4 h-4 text-[#0067AC]" />
                </div>
                <div>
                  <h2 class="font-bold text-base text-gray-900">Realisasi Kelas & Serapan Anggaran</h2>
                  <p class="text-[11px] text-gray-400">Monitoring Bulanan Training Operation 2026</p>
                </div>
              </div>
              <div class="bg-[#f4f6f5] p-1 rounded-full flex items-center text-xs font-medium">
                <span class="px-3 py-1 rounded-full bg-[#0067AC] text-white font-semibold shadow-xs">S1 2026</span>
                <span class="px-3 py-1 rounded-full text-gray-500">Full Year</span>
              </div>
            </div>

            <!-- Chart Bars -->
            <div class="mt-6 flex gap-4 items-end h-52 relative">
              <div class="flex flex-col justify-between h-full text-[11px] text-gray-400 pb-6 pr-1 select-none">
                <span>1.5M</span><span>1.2M</span><span>900jt</span><span>600jt</span><span>300jt</span><span>0</span>
              </div>
              <div class="flex-1 h-full grid grid-cols-6 gap-3 items-end pt-8">
                
                <!-- JAN -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    8 Kelas
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[44%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">JAN</span>
                </div>

                <!-- FEB -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    12 Kelas
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[68%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">FEB</span>
                </div>

                <!-- MAR -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    10 Kelas
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[55%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">MAR</span>
                </div>

                <!-- APR -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    14 Kelas
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[75%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">APR</span>
                </div>

                <!-- MEI (Active Peak) -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md whitespace-nowrap z-10">
                    18 Kelas (Puncak)
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[95%] rounded-full bg-[#0067AC] transition hover:brightness-110 shadow-sm"></div>
                  <span class="text-[11px] font-bold text-[#0067AC]">MEI</span>
                </div>

                <!-- JUN -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    15 Kelas
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[80%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">JUN</span>
                </div>

              </div>
            </div>

          </div>
        </div>

        <!-- Antrean Review & Approval Prioritas Powered by Standard DataTable -->
        <DataTable
          :data="priorityQueueData"
          :columns="priorityColumns"
          date-key="date"
          title="Antrean Review & Approval Prioritas"
          subtitle="Dokumen lintas modul (Program, Kelas, Task, Transaction, Report) yang membutuhkan tindakan"
          export-filename="antrean_approval_prioritas_bws"
          :default-per-page="5"
        >
          <!-- Custom Header Action: Switch to Transaction Module -->
          <template #actions>
            <button
              @click="emit('switch-view', 'transaction')"
              class="text-xs font-semibold text-[#0067AC] hover:text-[#003876] hover:underline flex items-center gap-1.5 transition"
            >
              Lihat Semua Transaksi & Approval &rarr;
            </button>
          </template>

          <!-- Custom Cell: Modul & ID -->
          <template #cell-code="{ row }">
            <span class="font-mono text-xs font-bold block" :class="row.typeColor">{{ row.code }}</span>
            <span class="text-[11px] text-gray-400">{{ row.type }}</span>
          </template>

          <!-- Custom Cell: Deskripsi Aktivitas -->
          <template #cell-title="{ row }">
            <p class="font-semibold text-gray-900 leading-tight">{{ row.title }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">{{ row.subtitle }}</p>
          </template>

          <!-- Custom Cell: Pengaju / PIC -->
          <template #cell-pic="{ row }">
            <p class="font-medium text-gray-800">{{ row.pic }}</p>
            <p class="text-[11px] text-gray-400">{{ row.picRole }}</p>
          </template>

          <!-- Custom Cell: Posisi Approval -->
          <template #cell-approvalPosition="{ row }">
            <span
              class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 rounded-full whitespace-nowrap"
              :class="row.approvalBadgeClass"
            >
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="row.approvalDotClass"></span>
              {{ row.approvalPosition }}
            </span>
          </template>

          <!-- Custom Action Column Slot -->
          <template #action="{ row }">
            <button
              v-if="row.actionType === 'voucher'"
              @click="emit('open-modal', 'modal-transaction')"
              class="inline-flex items-center justify-center bg-[#0067AC] text-white text-xs font-semibold px-4 py-1.5 rounded-full hover:bg-[#00528c] transition whitespace-nowrap shadow-xs"
            >
              {{ row.actionLabel }}
            </button>
            <button
              v-else-if="row.actionType === 'class'"
              @click="emit('open-modal', 'modal-class')"
              class="inline-flex items-center justify-center bg-blue-600 text-white text-xs font-semibold px-4 py-1.5 rounded-full hover:bg-blue-700 transition whitespace-nowrap shadow-xs"
            >
              {{ row.actionLabel }}
            </button>
            <button
              v-else-if="row.actionType === 'tna'"
              @click="emit('view-tna-detail', row.entityId); emit('switch-view', 'tna')"
              class="inline-flex items-center justify-center bg-[#002855] text-white text-xs font-semibold px-4 py-1.5 rounded-full hover:bg-[#003876] transition whitespace-nowrap shadow-xs"
            >
              {{ row.actionLabel }}
            </button>
            <button
              v-else
              @click="emit('switch-view', row.actionType)"
              class="inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-semibold px-4 py-1.5 rounded-full transition whitespace-nowrap"
            >
              {{ row.actionLabel }}
            </button>
          </template>
        </DataTable>

      </div>

      <!-- Right 4 Cols -->
      <div class="lg:col-span-4 flex flex-col gap-5">
        
        <!-- Total Jam Pelatihan (LIS) Card -->
        <div class="bg-white rounded-3xl p-5 shadow-sm flex flex-col justify-between border border-gray-100">
          <div class="flex items-start justify-between">
            <div>
              <h2 class="font-bold text-base text-gray-900">Total Jam Pelatihan (LIS)</h2>
              <p class="text-xs text-gray-400">Akumulasi Learning Hours Karyawan BWS</p>
            </div>
            <button
              @click="emit('switch-view', 'participants')"
              class="w-8 h-8 rounded-full bg-[#f4f6f5] flex items-center justify-center text-gray-600 hover:bg-[#0067AC] hover:text-white transition"
              title="Lihat Peserta"
            >
              <ArrowUpRight class="w-4 h-4" />
            </button>
          </div>
          
          <div class="text-center my-4">
            <p class="text-3xl font-bold text-gray-900">28,450 <span class="text-sm text-gray-400 font-normal">Jam</span></p>
            <p class="text-[11px] text-[#0067AC] font-semibold mt-0.5">+19.4% dibanding periode 2025</p>
          </div>

          <!-- Mini Sparkline Simulation -->
          <div class="h-16 flex items-end justify-between gap-1 pt-3 border-t border-gray-100">
            <div class="flex-1 bg-blue-100 rounded-t h-[40%]"></div>
            <div class="flex-1 bg-blue-200 rounded-t h-[60%]"></div>
            <div class="flex-1 bg-blue-300 rounded-t h-[50%]"></div>
            <div class="flex-1 bg-sky-400 rounded-t h-[75%]"></div>
            <div class="flex-1 bg-blue-500 rounded-t h-[70%]"></div>
            <div class="flex-1 bg-[#0067AC] rounded-t h-[100%]"></div>
          </div>
        </div>

        <!-- Kalender & Agenda Pelatihan Pekan Ini -->
        <div class="bg-white rounded-3xl p-5 shadow-sm flex flex-col gap-4 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="font-bold text-base text-gray-900">Agenda Pekan Ini</h2>
              <p class="text-xs text-gray-400">Pelatihan Aktif BWS Learning Center</p>
            </div>
            <span class="text-xs font-semibold bg-[#EBF4FC] text-[#0067AC] px-2.5 py-1 rounded-full">
              4 Batch
            </span>
          </div>

          <div class="space-y-3">
            <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-100 flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-[#0067AC] uppercase tracking-wide">Risk Management</span>
                <p class="font-semibold text-xs text-gray-900 mt-0.5">SMR Level 2 Sertifikasi</p>
                <p class="text-[11px] text-gray-400">12 Mei - 14 Mei 2026 • R. Diklat 101</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">Ongoing</span>
            </div>

            <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-100 flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wide">Lending & Credit</span>
                <p class="font-semibold text-xs text-gray-900 mt-0.5">Commercial Credit Analysis IV</p>
                <p class="text-[11px] text-gray-400">14 Mei - 16 Mei 2026 • Hybrid Zoom</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700">Confirmed</span>
            </div>

            <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-100 flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wide">Operational</span>
                <p class="font-semibold text-xs text-gray-900 mt-0.5">Anti-Money Laundering (APU-PPT)</p>
                <p class="text-[11px] text-gray-400">18 Mei 2026 • Portal Belajar LIS</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700">Upcoming</span>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>

