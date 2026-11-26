<script setup>
import { computed } from 'vue'
import {
  FileText,
  UploadCloud,
  CheckCircle,
  Clock,
  AlertCircle,
  FileCheck
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import { useI18n } from '@/i18n'

const { l } = useI18n()

const props = defineProps({
  trainingClasses: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['open-task-modal', 'grade-task'])

// Generate structured tasks based on classes or standard banking training tasks
const taskData = computed(() => {
  return [
    {
      id: 1,
      code: '#TSK-2026-041',
      title: l('Action Learning Project: Credit Analyst Batch IV', 'Proyek Pembelajaran Aksi: Analis Kredit Angkatan IV'),
      classTitle: l('Commercial Credit Analysis IV', 'Analisis Kredit Komersial IV'),
      participant: 'Andi Saputra',
      nik: 'NIK-10291',
      file: l('Credit_Feasibility_Analysis_PT_Maju.pdf', 'Analisis_Kelayakan_Kredit_PT_Maju.pdf'),
      grade: 92,
      status: 'GRADED',
      deadline: '2026-05-18',
      date: '2026-05-14'
    },
    {
      id: 2,
      code: '#TSK-2026-042',
      title: l('Case Study: Non-Performing Loan (NPL) Risk Mitigation', 'Studi Kasus: Mitigasi Risiko Kredit Macet (NPL)'),
      classTitle: l('Risk Management Certification SMR 2', 'Sertifikasi Manajemen Risiko SMR 2'),
      participant: 'Budi Santoso',
      nik: 'NIK-10344',
      file: l('Retail_Debtor_Restructuring_Study.xlsx', 'Kajian_Restrukturisasi_Debitur_Retail.xlsx'),
      grade: 88,
      status: 'GRADED',
      deadline: '2026-05-20',
      date: '2026-05-15'
    },
    {
      id: 3,
      code: '#TSK-2026-043',
      title: l('AML-CFT Compliance Audit at Branch Office', 'Audit Kepatuhan APU-PPT di Kantor Cabang'),
      classTitle: l('Anti-Money Laundering & CFT (AML-CFT)', 'Anti Pencucian Uang & PPT (APU-PPT)'),
      participant: 'Citra Lestari',
      nik: 'NIK-10411',
      file: l('Bandung_Branch_Audit_Checklist.pdf', 'Daftar_Periksa_Audit_Cabang_Bandung.pdf'),
      grade: null,
      status: 'SUBMITTED',
      deadline: '2026-05-25',
      date: '2026-05-16'
    },
    {
      id: 4,
      code: '#TSK-2026-044',
      title: l('Corporate Payroll Acquisition Strategy Formulation', 'Perumusan Strategi Akuisisi Nasabah Penggajian Korporasi'),
      classTitle: l('Branch Manager Leadership Bootcamp', 'Pelatihan Kepemimpinan Kepala Cabang'),
      participant: 'Dedi Kurniawan',
      nik: 'NIK-10115',
      file: l('Corporate_Payroll_Innovation_Proposal.pptx', 'Proposal_Inovasi_Penggajian_Korporasi.pptx'),
      grade: null,
      status: 'PENDING',
      deadline: '2026-05-28',
      date: '2026-05-18'
    }
  ]
})

const taskColumns = computed(() => [
  { key: 'code', label: l('Task ID', 'ID Tugas'), sortable: true },
  { key: 'title', label: l('Assignment / Action Plan Title', 'Judul Penugasan / Rencana Aksi'), sortable: true },
  { key: 'participant', label: l('Participant & NIK', 'Peserta & NIK'), sortable: true },
  { key: 'file', label: l('Attached Document', 'Dokumen Terlampir'), sortable: true },
  { key: 'grade', label: l('Score / Grade', 'Nilai'), sortable: true, align: 'center', format: (val) => val ? `${val} / 100` : '-' },
  { key: 'status', label: l('Grading Status', 'Status Penilaian'), sortable: true, align: 'center' },
  { key: 'deadline', label: l('Submission Deadline', 'Batas Pengumpulan'), sortable: true, format: (val) => val ? val.substring(0, 10) : '-' }
])
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <FileText class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Submitted', 'Terkumpul') }}</p>
          <p class="text-xl font-extrabold text-gray-900">42 {{ $l('Documents', 'Dokumen') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Graded', 'Sudah Dinilai') }}</p>
          <p class="text-xl font-extrabold text-[#0067AC]">35 {{ $l('Tasks', 'Tugas') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <Clock class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Pending Grading', 'Menunggu Penilaian') }}</p>
          <p class="text-xl font-extrabold text-amber-600">7 {{ $l('Documents', 'Dokumen') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <FileCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Average Score', 'Rata-rata Nilai') }}</p>
          <p class="text-xl font-extrabold text-purple-700 font-mono">89.4 <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
      </div>
    </div>

    <!-- Task Standard Table Component -->
    <DataTable
      :data="taskData"
      :columns="taskColumns"
      date-key="date"
      export-filename="bws_participant_assignments"
      :default-per-page="10"
    >
      <!-- Custom Cell: Code -->
      <template #cell-code="{ row }">
        <span class="font-mono text-xs font-bold text-[#0067AC] block">{{ row.code }}</span>
        <span class="text-[10px] text-gray-400">{{ row.classTitle }}</span>
      </template>

      <!-- Custom Cell: Title -->
      <template #cell-title="{ row }">
        <p class="font-bold text-xs text-gray-900 leading-tight">{{ row.title }}</p>
      </template>

      <!-- Custom Cell: Participant -->
      <template #cell-participant="{ row }">
        <p class="font-semibold text-xs text-gray-800">{{ row.participant }}</p>
        <p class="font-mono text-[10px] text-gray-400">{{ row.nik }}</p>
      </template>

      <!-- Custom Cell: File -->
      <template #cell-file="{ row }">
        <span class="inline-flex items-center gap-1.5 text-xs text-[#0067AC] font-semibold hover:underline cursor-pointer">
          <FileText class="w-3.5 h-3.5 text-[#0067AC] shrink-0" />
          <span class="truncate max-w-[170px]">{{ row.file }}</span>
        </span>
      </template>

      <!-- Custom Cell: Grade -->
      <template #cell-grade="{ row }">
        <span v-if="row.grade" class="font-mono font-bold text-xs text-[#0067AC] bg-emerald-50 px-2.5 py-1 rounded-full">
          {{ row.grade }} / 100
        </span>
        <span v-else class="text-gray-400 text-xs">-</span>
      </template>

      <!-- Custom Cell: Status -->
      <template #cell-status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.status === 'GRADED' ? 'bg-emerald-50 text-[#0067AC]' : row.status === 'SUBMITTED' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.status === 'GRADED' ? 'bg-[#0067AC]' : row.status === 'SUBMITTED' ? 'bg-blue-600' : 'bg-gray-400'"
          ></span>
          {{ row.status === 'GRADED' ? $l('Graded', 'Selesai Dinilai') : row.status === 'SUBMITTED' ? $l('Pending Grading', 'Menunggu Penilaian') : $l('Not Uploaded', 'Belum Diunggah') }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          v-if="row.status === 'SUBMITTED'"
          @click="emit('grade-task', row)"
          class="px-3 py-1.5 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold transition"
          :title="$l('Grade This Assignment', 'Beri Nilai Tugas Ini')"
        >
          {{ $l('Grade', 'Beri Nilai') }}
        </button>
        <button
          v-else
          class="px-3 py-1.5 rounded-full bg-[#f4f6f5] hover:bg-gray-200 text-gray-800 text-xs font-semibold transition"
        >
          {{ $l('Review', 'Tinjau') }}
        </button>
      </template>
    </DataTable>

  </div>
</template>

