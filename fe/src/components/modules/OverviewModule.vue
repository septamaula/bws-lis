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
import { useI18n } from '@/i18n'

const { l, t } = useI18n()

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
      type: l('Transaction (Reimbursement)', 'Transaksi (Penggantian Biaya)'),
      typeColor: 'text-[#0067AC]',
      title: f.description || l('Operational Training Expense Claim #{id}', 'Klaim Biaya Operasional Pelatihan #{id}', { id: f.id }),
      subtitle: `${formatRupiah(f.amount)} • ${l('Receipt Attached', 'Bukti Kuitansi Terlampir')}`,
      pic: f.maker?.name || l('Class PIC', 'PIC Kelas'),
      picRole: l('Maker / PIC', 'Pembuat / PIC'),
      approvalPosition: l('Awaiting Manager / Checker', 'Menunggu Manajer / Pemeriksa'),
      approvalBadgeClass: 'bg-amber-50 text-amber-700',
      approvalDotClass: 'bg-amber-500',
      actionLabel: l('Review & Approve', 'Tinjau & Setujui'),
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
      type: l('Class (Dual-Control)', 'Kelas (Kendali Ganda)'),
      typeColor: 'text-blue-600',
      title: `${c.program?.title || l('Training Batch', 'Angkatan Pelatihan')} - ${c.batch_name}`,
      subtitle: `${c.location_or_url || 'BWS Learning Center'} • ${l('Quota {quota} Participants', 'Kuota {quota} Peserta', { quota: c.max_quota })}`,
      pic: c.maker?.name || 'HC Training Maker',
      picRole: l('HC Maker', 'Pembuat HC'),
      approvalPosition: l('Awaiting HC Learning Head (Checker)', 'Menunggu Kepala Pembelajaran HC (Pemeriksa)'),
      approvalBadgeClass: 'bg-blue-50 text-blue-700',
      approvalDotClass: 'bg-blue-500',
      actionLabel: l('Checker Authorization', 'Otorisasi Pemeriksa'),
      actionType: 'class',
      entityId: c.id,
      date: c.created_at || '2026-05-12'
    })
  })

  // Pending TNA Proposals (Multi-Approval Pipeline)
  props.tnaProposals.filter(t => ['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW', 'REVISION'].includes(t.status)).forEach(t => {
    let approvalPosition = l('Awaiting HCBP Review', 'Menunggu Tinjauan HCBP')
    let approvalBadgeClass = 'bg-purple-50 text-purple-700'
    let approvalDotClass = 'bg-purple-600'
    let actionLabel = l('HCBP Validation', 'Validasi HCBP')

    if (t.status === 'CHECKER_REVIEW') {
      approvalPosition = l('Awaiting HC Learning Checker', 'Menunggu Pemeriksa Pembelajaran HC')
      approvalBadgeClass = 'bg-blue-50 text-blue-700'
      approvalDotClass = 'bg-[#0067AC]'
      actionLabel = l('Checker Authorization', 'Otorisasi Pemeriksa')
    } else if (t.status === 'APPROVER_REVIEW') {
      approvalPosition = l('Awaiting HC Div Head / BoD', 'Menunggu Kepala Divisi HC / Direksi')
      approvalBadgeClass = 'bg-amber-50 text-amber-700'
      approvalDotClass = 'bg-amber-500'
      actionLabel = l('Div Head Sign-Off', 'Pengesahan Kadiv')
    } else if (t.status === 'REVISION') {
      approvalPosition = l('Requires Dept Head Revision', 'Perlu Revisi Kepala Departemen')
      approvalBadgeClass = 'bg-rose-50 text-rose-700'
      approvalDotClass = 'bg-rose-500'
      actionLabel = l('Revise Proposal', 'Perbaiki Usulan')
    }

    items.push({
      id: `TNA-${t.id}`,
      code: t.proposal_code || `#TNA-2026-${String(t.id).padStart(3, '0')}`,
      type: l('TNA (Annual Plan)', 'TNA (Perencanaan)'),
      typeColor: 'text-[#002855]',
      title: l('{dept} Division TNA Proposal - Year {year}', 'Usulan TNA Divisi {dept} - Tahun {year}', { dept: t.department, year: t.year }),
      subtitle: l('Estimated Budget: {budget} • {count} Training Items', 'Estimasi Anggaran: {budget} • {count} Mata Pelatihan', { budget: formatRupiah(t.total_budget_requested), count: t.items?.length || 0 }),
      pic: t.proposer?.name || 'Dept Head',
      picRole: l('Dept Head', 'Kepala Departemen'),
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
        type: l('Transaction (Reimbursement)', 'Transaksi (Penggantian Biaya)'),
        typeColor: 'text-[#0067AC]',
        title: l('SMR Level 2 Certification Exam Reimbursement (12 Participants)', 'Penggantian Biaya Ujian Sertifikasi SMR Tingkat 2 (12 Peserta)'),
        subtitle: l('IDR 42,000,000 • Official LSPP Receipt Attached', 'Rp 42.000.000 • Bukti Kuitansi Resmi LSPP Terlampir'),
        pic: 'Nadia Putri',
        picRole: l('Class PIC', 'PIC Kelas'),
        approvalPosition: l('Awaiting L&D Manager', 'Menunggu Manajer L&D'),
        approvalBadgeClass: 'bg-amber-50 text-amber-700',
        approvalDotClass: 'bg-amber-500',
        actionLabel: l('Review & Approve', 'Tinjau & Setujui'),
        actionType: 'voucher',
        entityId: 1,
        date: '2026-05-10'
      },
      {
        id: 'RPT-2026-Q2',
        code: '#RPT-2026-Q2',
        type: l('Training Report', 'Laporan Pelatihan'),
        typeColor: 'text-blue-600',
        title: l('Q2 2026 Training Evaluation & Realization Report', 'Laporan Evaluasi & Realisasi Pelatihan Triwulan II 2026'),
        subtitle: l('Reviewed by L&D Supervisor • Awaiting Tier 1 & Tier 2 Sign-Off', 'Sudah Ditinjau Supervisor L&D • Membutuhkan Pengesahan Tahap 1 & 2'),
        pic: 'Hendra Wijaya',
        picRole: 'Spv L&D',
        approvalPosition: l('Sign-Off Level 1 (Manager)', 'Pengesahan Tahap 1 (Manajer)'),
        approvalBadgeClass: 'bg-purple-50 text-purple-700',
        approvalDotClass: 'bg-purple-600',
        actionLabel: l('Sign-Off Report', 'Sahkan Laporan'),
        actionType: 'report',
        entityId: 2,
        date: '2026-05-12'
      },
      {
        id: 'TSK-2026-041',
        code: '#TSK-2026-041',
        type: l('Task (Class Activity)', 'Penugasan (Aktivitas Kelas)'),
        typeColor: 'text-[#0067AC]',
        title: l('Action Learning Project: Credit Analyst Batch IV', 'Proyek Pembelajaran Aksi: Analis Kredit Angkatan IV'),
        subtitle: l('25/25 Participants Submitted • Reviewed by PIC', '25/25 Peserta Sudah Mengunggah Berkas • Ditinjau oleh PIC'),
        pic: 'Rina Melati',
        picRole: l('Class PIC', 'PIC Kelas'),
        approvalPosition: l('Awaiting Spv, Mgr & Div Head', 'Menunggu Supervisor, Manajer & Kepala Divisi'),
        approvalBadgeClass: 'bg-[#EBF4FC] text-[#0067AC]',
        approvalDotClass: 'bg-[#0067AC]',
        actionLabel: l('Open Task Module', 'Buka Modul Penugasan'),
        actionType: 'task',
        entityId: 3,
        date: '2026-05-14'
      }
    )
  }

  return items
})

const priorityColumns = computed(() => [
  { key: 'code', label: l('Module & ID', 'Modul & ID'), sortable: true },
  { key: 'title', label: l('Activity Description', 'Deskripsi Aktivitas'), sortable: true },
  { key: 'pic', label: l('Requester / PIC', 'Pengaju / PIC'), sortable: true },
  { key: 'approvalPosition', label: l('Current Approval Stage', 'Posisi Persetujuan Saat Ini'), sortable: true },
  { key: 'date', label: l('Submission Date', 'Tanggal Pengajuan'), sortable: true, format: (val) => val ? val.substring(0, 10) : '-' }
])
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
                  <h2 class="font-bold text-base text-gray-900">{{ $l('2026 L&D Budget Ceiling', 'Pagu Anggaran L&D 2026') }}</h2>
                  <p class="text-xs text-gray-400">{{ $l('Approved TNA Budget vs. Realization', 'Anggaran TNA Disetujui vs. Realisasi') }}</p>
                </div>
                <button
                  @click="emit('switch-view', 'tna')"
                  class="w-8 h-8 rounded-full bg-[#f4f6f5] flex items-center justify-center text-gray-600 hover:bg-[#0067AC] hover:text-white transition"
                  :title="$l('View TNA Details', 'Lihat Rincian TNA')"
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
                  <p class="text-[11px] text-sky-200/90 mb-0.5">{{ $l('Total Approved Training Budget', 'Total Anggaran Pelatihan Disetujui') }}</p>
                  <p class="text-2xl font-bold tracking-tight text-white">
                    {{ formatRupiah(kpis.total_budget_approved || 8450000000) }}
                  </p>
                </div>
                <div class="flex items-center justify-between text-xs text-sky-200/90 pt-2 border-t border-white/15 relative z-10">
                  <span>{{ $l('Utilized:', 'Terserap:') }} <strong class="text-white">{{ formatRupiah(kpis.total_budget_disbursed || 5180000000) }} ({{ kpis.budget_utilization_pct || 61.3 }}%)</strong></span>
                  <span class="text-white font-medium">{{ $l('34 Programs', '34 Program') }}</span>
                </div>
              </div>
            </div>

            <!-- Mandatory Certification KPI Card -->
            <div class="bg-white rounded-3xl px-5 py-4 shadow-sm flex items-center justify-between border border-gray-100">
              <div>
                <p class="text-xs text-gray-400 mb-0.5">{{ $l('Mandatory Certification Fulfillment (YTD)', 'Realisasi Sertifikasi Wajib (Tahun Berjalan)') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $l('412 / 450 Passed', '412 / 450 Lulus') }}</p>
              </div>
              <span class="bg-[#0067AC] text-white text-xs font-semibold px-3 py-1 rounded-full">
                {{ $l('91.5% Target', '91,5% Target') }}
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
                  <h2 class="font-bold text-base text-gray-900">{{ $l('Class Realization & Budget Absorption', 'Realisasi Kelas & Serapan Anggaran') }}</h2>
                  <p class="text-[11px] text-gray-400">{{ $l('2026 Monthly Training Operations Monitoring', 'Pemantauan Bulanan Operasional Pelatihan 2026') }}</p>
                </div>
              </div>
              <div class="bg-[#f4f6f5] p-1 rounded-full flex items-center text-xs font-medium">
                <span class="px-3 py-1 rounded-full bg-[#0067AC] text-white font-semibold shadow-xs">{{ $l('H1 2026', 'Semester 1 2026') }}</span>
                <span class="px-3 py-1 rounded-full text-gray-500">{{ $l('Full Year', 'Satu Tahun Penuh') }}</span>
              </div>
            </div>

            <!-- Chart Bars -->
            <div class="mt-6 flex gap-4 items-end h-52 relative">
              <div class="flex flex-col justify-between h-full text-[11px] text-gray-400 pb-6 pr-1 select-none">
                <span>{{ $l('1.5B', '1,5M') }}</span><span>{{ $l('1.2B', '1,2M') }}</span><span>{{ $l('900M', '900jt') }}</span><span>{{ $l('600M', '600jt') }}</span><span>{{ $l('300M', '300jt') }}</span><span>0</span>
              </div>
              <div class="flex-1 h-full grid grid-cols-6 gap-3 items-end pt-8">
                
                <!-- JAN -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    {{ $l('8 Classes', '8 Kelas') }}
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[44%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">JAN</span>
                </div>

                <!-- FEB -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    {{ $l('12 Classes', '12 Kelas') }}
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[68%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">FEB</span>
                </div>

                <!-- MAR -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    {{ $l('10 Classes', '10 Kelas') }}
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[55%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">MAR</span>
                </div>

                <!-- APR -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    {{ $l('14 Classes', '14 Kelas') }}
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[75%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">APR</span>
                </div>

                <!-- MAY (Active Peak) -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md whitespace-nowrap z-10">
                    {{ $l('18 Classes (Peak)', '18 Kelas (Puncak)') }}
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[95%] rounded-full bg-[#0067AC] transition hover:brightness-110 shadow-sm"></div>
                  <span class="text-[11px] font-bold text-[#0067AC]">{{ $l('MAY', 'MEI') }}</span>
                </div>

                <!-- JUN -->
                <div class="flex flex-col items-center gap-2 h-full justify-end relative group cursor-pointer">
                  <div class="absolute -top-7 bg-gray-900 text-white text-[10px] font-semibold px-2 py-0.5 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none z-10">
                    {{ $l('15 Classes', '15 Kelas') }}
                    <div class="absolute left-1/2 -bottom-1 -translate-x-1/2 w-1.5 h-1.5 bg-gray-900 rotate-45"></div>
                  </div>
                  <div class="w-full max-w-[42px] h-[80%] rounded-full bg-[#93C5FD] transition hover:brightness-95"></div>
                  <span class="text-[11px] text-gray-400 group-hover:text-gray-700">JUN</span>
                </div>

              </div>
            </div>

          </div>
        </div>

        <!-- Priority Review & Approval Queue DataTable -->
        <DataTable
          :data="priorityQueueData"
          :columns="priorityColumns"
          date-key="date"
          :title="$l('Priority Review & Approval Queue', 'Antrean Tinjauan & Persetujuan Prioritas')"
          :subtitle="$l('Cross-module items (Programs, Classes, Tasks, Transactions, Reports) awaiting action', 'Dokumen lintas modul (Program, Kelas, Tugas, Transaksi, Laporan) yang membutuhkan tindakan')"
          export-filename="bws_priority_approval_queue"
          :default-per-page="5"
        >
          <!-- Custom Header Action: Switch to Transaction Module -->
          <template #actions>
            <button
              @click="emit('switch-view', 'transaction')"
              class="text-xs font-semibold text-[#0067AC] hover:text-[#003876] hover:underline flex items-center gap-1.5 transition"
            >
              {{ $l('View All Transactions & Approvals', 'Lihat Semua Transaksi & Persetujuan') }} &rarr;
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
              <h2 class="font-bold text-base text-gray-900">{{ $l('Total Training Hours (LIS)', 'Total Jam Pelatihan (LIS)') }}</h2>
              <p class="text-xs text-gray-400">{{ $l('Cumulative BWS Employee Learning Hours', 'Akumulasi Jam Belajar Karyawan BWS') }}</p>
            </div>
            <button
              @click="emit('switch-view', 'participants')"
              class="w-8 h-8 rounded-full bg-[#f4f6f5] flex items-center justify-center text-gray-600 hover:bg-[#0067AC] hover:text-white transition"
              :title="$l('View Participants', 'Lihat Peserta')"
            >
              <ArrowUpRight class="w-4 h-4" />
            </button>
          </div>
          
          <div class="text-center my-4">
            <p class="text-3xl font-bold text-gray-900">28,450 <span class="text-sm text-gray-400 font-normal">{{ $l('Hours', 'Jam') }}</span></p>
            <p class="text-[11px] text-[#0067AC] font-semibold mt-0.5">{{ $l('+19.4% compared to 2025 period', '+19,4% dibandingkan periode 2025') }}</p>
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

        <!-- Weekly Training Agenda -->
        <div class="bg-white rounded-3xl p-5 shadow-sm flex flex-col gap-4 border border-gray-100">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="font-bold text-base text-gray-900">{{ $l("This Week's Agenda", 'Agenda Pekan Ini') }}</h2>
              <p class="text-xs text-gray-400">{{ $l('Active Classes at BWS Learning Center', 'Pelatihan Aktif di Pusat Pembelajaran BWS') }}</p>
            </div>
            <span class="text-xs font-semibold bg-[#EBF4FC] text-[#0067AC] px-2.5 py-1 rounded-full">
              {{ $l('4 Batches', '4 Angkatan') }}
            </span>
          </div>

          <div class="space-y-3">
            <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-100 flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-[#0067AC] uppercase tracking-wide">{{ $l('Risk Management', 'Manajemen Risiko') }}</span>
                <p class="font-semibold text-xs text-gray-900 mt-0.5">{{ $l('SMR Level 2 Certification', 'Sertifikasi Manajemen Risiko Tingkat 2') }}</p>
                <p class="text-[11px] text-gray-400">{{ $l('May 12 - May 14, 2026 • Training Room 101', '12 Mei - 14 Mei 2026 • Ruang Pelatihan 101') }}</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">{{ $l('Ongoing', 'Berlangsung') }}</span>
            </div>

            <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-100 flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wide">{{ $l('Lending & Credit', 'Kredit & Pembiayaan') }}</span>
                <p class="font-semibold text-xs text-gray-900 mt-0.5">{{ $l('Commercial Credit Analysis IV', 'Analisis Kredit Komersial IV') }}</p>
                <p class="text-[11px] text-gray-400">{{ $l('May 14 - May 16, 2026 • Hybrid Zoom', '14 Mei - 16 Mei 2026 • Hibrida Zoom') }}</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700">{{ $l('Confirmed', 'Terkonfirmasi') }}</span>
            </div>

            <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-100 flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wide">{{ $l('Operational', 'Operasional') }}</span>
                <p class="font-semibold text-xs text-gray-900 mt-0.5">{{ $l('Anti-Money Laundering (AML-CFT)', 'Anti Pencucian Uang (APU-PPT)') }}</p>
                <p class="text-[11px] text-gray-400">{{ $l('May 18, 2026 • LIS Learning Portal', '18 Mei 2026 • Portal Pembelajaran LIS') }}</p>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700">{{ $l('Upcoming', 'Akan Datang') }}</span>
            </div>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>

