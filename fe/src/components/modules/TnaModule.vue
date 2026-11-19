<script setup>
import { computed } from 'vue'
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
  AlertTriangle
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

    <!-- TNA Proposals Standard Table Component -->
    <DataTable
      :data="tnaProposals"
      :columns="tnaColumns"
      date-key="created_at"
      export-filename="usulan_tna_bws_2026"
      :default-per-page="10"
    >

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
