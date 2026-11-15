<script setup>
import { computed } from 'vue'
import {
  ClipboardList,
  Plus,
  Wallet,
  CheckCircle,
  Clock,
  Building2,
  FileText
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

const emit = defineEmits(['open-tna-modal', 'view-tna-items'])

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
  return props.tnaProposals.reduce((sum, item) => sum + Number(item.total_budget_approved || 0), 0)
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
    label: 'Disetujui HCBP',
    sortable: true,
    align: 'right',
    format: (val) => formatRupiah(val)
  },
  { key: 'status', label: 'Status Dokumen', sortable: true, align: 'center' },
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
    
    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Usulan TNA</p>
          <p class="text-xl font-bold text-gray-900">{{ tnaProposals.length }} Proposal</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <ClipboardList class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Pagu Diusulkan</p>
          <p class="text-xl font-bold text-gray-900 font-mono">{{ formatRupiah(totalRequested) }}</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <Wallet class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Pagu Disetujui HCBP</p>
          <p class="text-xl font-bold text-[#157f4f] font-mono">{{ formatRupiah(totalApproved) }}</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <CheckCircle class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Kebutuhan Pelatihan</p>
          <p class="text-xl font-bold text-gray-900">34 Program Kerja</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <Building2 class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- TNA Proposals Standard Table Component -->
    <DataTable
      :data="tnaProposals"
      :columns="tnaColumns"
      date-key="created_at"
      title="Daftar Usulan Training Needs Analysis (TNA)"
      subtitle="Perencanaan kebutuhan kompetensi tahunan per divisi dan unit operasional Bank Woori Saudara"
      export-filename="usulan_tna_bws_2026"
      :default-per-page="10"
    >
      <!-- Actions in Header -->
      <template #actions>
        <button
          v-if="canManageTna"
          @click="emit('open-tna-modal')"
          class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition"
        >
          <Plus class="w-4 h-4" />
          <span>+ Usulan TNA Baru</span>
        </button>
      </template>

      <!-- Custom Cell: Proposal Code -->
      <template #cell-proposal_code="{ row }">
        <span class="font-mono text-xs font-bold text-[#157f4f] block">{{ row.proposal_code }}</span>
        <span class="text-[11px] text-gray-400">Revisi {{ row.revision_count || 0 }}</span>
      </template>

      <!-- Custom Cell: Department -->
      <template #cell-department="{ row }">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600 shrink-0">
            <Building2 class="w-3.5 h-3.5 text-[#157f4f]" />
          </div>
          <div>
            <p class="font-bold text-xs text-gray-900">{{ row.department }}</p>
            <p class="text-[10px] text-gray-400">{{ (row.items && row.items.length) || 0 }} Kebutuhan Skill</p>
          </div>
        </div>
      </template>

      <!-- Custom Cell: Status -->
      <template #cell-status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.status === 'APPROVED' ? 'bg-emerald-50 text-[#157f4f]' : 'bg-amber-50 text-amber-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.status === 'APPROVED' ? 'bg-[#157f4f]' : 'bg-amber-500'"
          ></span>
          {{ row.status === 'APPROVED' ? 'Disetujui HCBP' : 'Menunggu Review' }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          @click="emit('view-tna-items', row)"
          class="px-3 py-1.5 rounded-full bg-[#f4f6f5] hover:bg-gray-200 text-gray-800 text-xs font-semibold transition"
          title="Lihat Rincian Item TNA"
        >
          Rincian
        </button>
      </template>
    </DataTable>

  </div>
</template>

