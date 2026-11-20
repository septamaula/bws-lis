<script setup>
import { ref, computed } from 'vue'
import {
  ClipboardList,
  Plus,
  Wallet,
  CheckCircle,
  Clock,
  Building2,
  FileText,
  ExternalLink,
  ChevronRight,
  ShieldCheck,
  AlertTriangle,
  Filter,
  RotateCcw,
  Calendar,
  Layers,
  ChevronDown,
  X
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  tnaProposals: {
    type: Array,
    default: () => []
  },
  canManageTna: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['open-tna-modal', 'create-tna', 'view-tna-detail'])

// ==========================================
// FILTER STATE
// ==========================================
const selectedStatusFilter = ref('ALL')
const selectedDepartmentFilter = ref('ALL')
const selectedYearFilter = ref('ALL')
const selectedBudgetRange = ref('ALL')

// Distinct Departments
const departmentList = computed(() => {
  const set = new Set()
  props.tnaProposals.forEach(p => {
    if (p.department) set.add(p.department)
  })
  return Array.from(set).sort()
})

// Distinct Years
const yearList = computed(() => {
  const set = new Set()
  props.tnaProposals.forEach(p => {
    if (p.year) set.add(p.year)
  })
  return Array.from(set).sort((a, b) => b - a)
})

// Status Breakdown Counts
const statusCounts = computed(() => {
  const total = props.tnaProposals.length
  const pending = props.tnaProposals.filter(p => ['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW'].includes(p.status)).length
  const approved = props.tnaProposals.filter(p => p.status === 'APPROVED').length
  const revised = props.tnaProposals.filter(p => p.status === 'REVISED').length
  const rejected = props.tnaProposals.filter(p => p.status === 'REJECTED').length
  return { total, pending, approved, revised, rejected }
})

// Filter Pipeline
const filteredTnaProposals = computed(() => {
  return props.tnaProposals.filter(item => {
    // 1. Status Filter
    if (selectedStatusFilter.value !== 'ALL') {
      if (selectedStatusFilter.value === 'PENDING') {
        if (!['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW'].includes(item.status)) return false
      } else if (item.status !== selectedStatusFilter.value) {
        return false
      }
    }

    // 2. Department Filter
    if (selectedDepartmentFilter.value !== 'ALL' && item.department !== selectedDepartmentFilter.value) {
      return false
    }

    // 3. Year Filter
    if (selectedYearFilter.value !== 'ALL' && String(item.year) !== String(selectedYearFilter.value)) {
      return false
    }

    // 4. Budget Range Filter
    if (selectedBudgetRange.value !== 'ALL') {
      const budget = Number(item.total_budget_requested || 0)
      if (selectedBudgetRange.value === 'under_100m' && budget >= 100000000) return false
      if (selectedBudgetRange.value === '100m_to_500m' && (budget < 100000000 || budget > 500000000)) return false
      if (selectedBudgetRange.value === 'above_500m' && budget <= 500000000) return false
    }

    return true
  })
})

const isFilterActive = computed(() => {
  return (
    selectedStatusFilter.value !== 'ALL' ||
    selectedDepartmentFilter.value !== 'ALL' ||
    selectedYearFilter.value !== 'ALL' ||
    selectedBudgetRange.value !== 'ALL'
  )
})

const activeFilterCount = computed(() => {
  let count = 0
  if (selectedStatusFilter.value !== 'ALL') count++
  if (selectedDepartmentFilter.value !== 'ALL') count++
  if (selectedYearFilter.value !== 'ALL') count++
  if (selectedBudgetRange.value !== 'ALL') count++
  return count
})

function resetFilters() {
  selectedStatusFilter.value = 'ALL'
  selectedDepartmentFilter.value = 'ALL'
  selectedYearFilter.value = 'ALL'
  selectedBudgetRange.value = 'ALL'
}

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

const totalRequested = computed(() => {
  return props.tnaProposals.reduce((sum, item) => sum + Number(item.total_budget_requested || 0), 0)
})

const totalApproved = computed(() => {
  return props.tnaProposals
    .filter(item => item.status === 'APPROVED' || item.total_budget_approved)
    .reduce((sum, item) => sum + Number(item.total_budget_approved || 0), 0)
})

const totalProgramCount = computed(() => {
  return props.tnaProposals.reduce((sum, item) => sum + (item.items ? item.items.length : 0), 0)
})

const tnaColumns = [
  { key: 'proposal_code', label: 'Kode Proposal', sortable: true },
  { key: 'department', label: 'Divisi / Departemen', sortable: true },
  { key: 'year', label: 'Tahun', sortable: true, align: 'center' },
  { key: 'proposer.name', label: 'Pengaju (Dept Head)', sortable: true },
  {
    key: 'total_budget_requested',
    label: 'Pagu Diusulkan',
    sortable: true,
    align: 'right',
    format: (val) => formatRupiah(val)
  },
  {
    key: 'total_budget_approved',
    label: 'Disetujui Otorisator',
    sortable: true,
    align: 'right',
    format: (val) => val ? formatRupiah(val) : '-'
  },
  { key: 'status', label: 'Status Dokumen & Tahapan Approval', sortable: true, align: 'center' },
  {
    key: 'created_at',
    label: 'Tgl Pengajuan',
    sortable: true,
    format: (val) => val ? val.substring(0, 10) : '-'
  }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <ClipboardList class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Usulan TNA</p>
          <p class="text-xl font-extrabold text-gray-900">{{ tnaProposals.length }} Proposal</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Wallet class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Pagu Diusulkan</p>
          <p class="text-xl font-extrabold text-gray-900 font-mono">{{ formatRupiah(totalRequested) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Pagu Disahkan</p>
          <p class="text-xl font-extrabold text-[#0067AC] font-mono">{{ formatRupiah(totalApproved) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <Building2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Kebutuhan Diklat</p>
          <p class="text-xl font-extrabold text-gray-900">{{ totalProgramCount || 34 }} Program Kerja</p>
        </div>
      </div>
    </div>

    <!-- Quick Status Filter Segmented Bar -->
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full">
        <button
          @click="selectedStatusFilter = 'ALL'"
          :class="[
            selectedStatusFilter === 'ALL'
              ? 'bg-[#0067AC] text-white shadow-sm ring-2 ring-[#0067AC]/20'
              : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'
          ]"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer"
        >
          <span>Semua Usulan</span>
          <span
            :class="selectedStatusFilter === 'ALL' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'"
            class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold font-mono"
          >{{ statusCounts.total }}</span>
        </button>

        <button
          @click="selectedStatusFilter = 'PENDING'"
          :class="[
            selectedStatusFilter === 'PENDING'
              ? 'bg-amber-500 text-white shadow-sm ring-2 ring-amber-500/20'
              : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'
          ]"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer"
        >
          <Clock class="w-3.5 h-3.5" />
          <span>Menunggu Review</span>
          <span
            :class="selectedStatusFilter === 'PENDING' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'"
            class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold font-mono"
          >{{ statusCounts.pending }}</span>
        </button>

        <button
          @click="selectedStatusFilter = 'APPROVED'"
          :class="[
            selectedStatusFilter === 'APPROVED'
              ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-600/20'
              : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'
          ]"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer"
        >
          <CheckCircle class="w-3.5 h-3.5" />
          <span>Disetujui Final</span>
          <span
            :class="selectedStatusFilter === 'APPROVED' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700'"
            class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold font-mono"
          >{{ statusCounts.approved }}</span>
        </button>

        <button
          @click="selectedStatusFilter = 'REVISED'"
          :class="[
            selectedStatusFilter === 'REVISED'
              ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-600/20'
              : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'
          ]"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer"
        >
          <AlertTriangle class="w-3.5 h-3.5" />
          <span>Perlu Revisi</span>
          <span
            :class="selectedStatusFilter === 'REVISED' ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700'"
            class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold font-mono"
          >{{ statusCounts.revised }}</span>
        </button>

        <button
          @click="selectedStatusFilter = 'REJECTED'"
          :class="[
            selectedStatusFilter === 'REJECTED'
              ? 'bg-rose-600 text-white shadow-sm ring-2 ring-rose-600/20'
              : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'
          ]"
          class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0 cursor-pointer"
        >
          <X class="w-3.5 h-3.5" />
          <span>Ditolak</span>
          <span
            :class="selectedStatusFilter === 'REJECTED' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-700'"
            class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold font-mono"
          >{{ statusCounts.rejected }}</span>
        </button>
      </div>

      <!-- Reset Filter Quick Action -->
      <button
        v-if="isFilterActive"
        @click="resetFilters"
        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition cursor-pointer shrink-0"
        title="Reset semua filter ke kondisi awal"
      >
        <RotateCcw class="w-3.5 h-3.5" />
        <span>Reset Filter ({{ activeFilterCount }} aktif)</span>
      </button>
    </div>

    <!-- TNA Proposals Standard Table Component -->
    <DataTable
      :data="filteredTnaProposals"
      :columns="tnaColumns"
      date-key="created_at"
      export-filename="usulan_tna_bws_2026"
      :default-per-page="10"
    >
      <!-- Filters Slot for Specific Criteria -->
      <template #filters>
        <!-- Department / Divisi Filter -->
        <div class="relative inline-flex items-center">
          <select
            v-model="selectedDepartmentFilter"
            class="bg-white text-xs font-semibold text-gray-700 pl-3 pr-8 py-2 rounded-full border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shadow-2xs appearance-none cursor-pointer hover:border-gray-300"
            title="Filter berdasarkan Divisi / Unit Kerja"
          >
            <option value="ALL">Semua Divisi</option>
            <option v-for="dept in departmentList" :key="dept" :value="dept">
              {{ dept }}
            </option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 pointer-events-none" />
        </div>

        <!-- Fiscal Year Filter -->
        <div class="relative inline-flex items-center">
          <select
            v-model="selectedYearFilter"
            class="bg-white text-xs font-semibold text-gray-700 pl-3 pr-8 py-2 rounded-full border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shadow-2xs appearance-none cursor-pointer hover:border-gray-300"
            title="Filter berdasarkan Tahun Anggaran"
          >
            <option value="ALL">Semua Tahun</option>
            <option v-for="yr in yearList" :key="yr" :value="yr">
              Tahun {{ yr }}
            </option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 pointer-events-none" />
        </div>

        <!-- Budget Range Filter -->
        <div class="relative inline-flex items-center">
          <select
            v-model="selectedBudgetRange"
            class="bg-white text-xs font-semibold text-gray-700 pl-3 pr-8 py-2 rounded-full border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shadow-2xs appearance-none cursor-pointer hover:border-gray-300"
            title="Filter berdasarkan Rentang Pagu Anggaran"
          >
            <option value="ALL">Semua Pagu Anggaran</option>
            <option value="under_100m">&lt; Rp 100 Juta</option>
            <option value="100m_to_500m">Rp 100 Jt - Rp 500 Jt</option>
            <option value="above_500m">&gt; Rp 500 Juta</option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 pointer-events-none" />
        </div>
      </template>

      <!-- Custom Cell: Proposal Code -->
      <template #cell-proposal_code="{ row }">
        <button
          @click="emit('view-tna-detail', row.id)"
          class="text-left group cursor-pointer"
          title="Klik untuk membuka Rincian Dokumen TNA"
        >
          <span class="font-mono text-xs font-extrabold text-[#0067AC] group-hover:underline flex items-center gap-1">
            <span>{{ row.proposal_code }}</span>
            <ExternalLink class="w-3 h-3 opacity-0 group-hover:opacity-100 transition" />
          </span>
          <span class="text-[11px] text-gray-400 block">Revisi {{ row.revision_count || 0 }}</span>
        </button>
      </template>

      <!-- Custom Cell: Department -->
      <template #cell-department="{ row }">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 shrink-0">
            <Building2 class="w-3.5 h-3.5 text-[#0067AC]" />
          </div>
          <div>
            <p class="font-bold text-xs text-gray-900">{{ row.department }}</p>
            <p class="text-[10px] text-gray-400">{{ (row.items && row.items.length) || 0 }} Kebutuhan Skill</p>
          </div>
        </div>
      </template>

      <!-- Custom Cell: Status & Multi-Approval Badge -->
      <template #cell-status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1 rounded-full whitespace-nowrap"
          :class="[
            row.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
            row.status === 'REJECTED' ? 'bg-rose-50 text-rose-800 border border-rose-200' :
            row.status === 'REVISED' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
            row.status === 'CHECKER_REVIEW' ? 'bg-[#EBF4FC] text-[#0067AC] border border-blue-200' :
            'bg-sky-50 text-sky-800 border border-sky-200'
          ]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="[
              row.status === 'APPROVED' ? 'bg-emerald-500' :
              row.status === 'REJECTED' ? 'bg-rose-500' :
              row.status === 'REVISED' ? 'bg-amber-500' :
              'bg-[#0067AC] animate-pulse'
            ]"
          ></span>
          {{ row.approval_status_badge || (row.status === 'APPROVED' ? 'Disetujui Final' : 'Menunggu Review') }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          @click="emit('view-tna-detail', row.id)"
          class="px-3.5 py-1.5 rounded-full bg-[#EBF4FC] hover:bg-[#0067AC] text-[#0067AC] hover:text-white text-xs font-bold transition flex items-center gap-1 shadow-2xs"
          title="Buka Halaman Rincian & Multi-Approval"
        >
          <span>Rincian</span>
          <ChevronRight class="w-3 h-3" />
        </button>
      </template>
    </DataTable>

  </div>
</template>
