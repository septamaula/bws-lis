<script setup>
import { ref, computed, onMounted } from 'vue'
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
  RotateCcw,
  Calendar,
  Layers,
  Eye,
  Edit3,
  Trash2,
  Download,
  CheckCircle2,
  TrendingUp,
  Info,
  X
} from 'lucide-vue-next'
import apiClient from '@/api/client'

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

const emit = defineEmits(['create-tna', 'view-tna-detail', 'edit-tna', 'refresh-list'])

const loading = ref(false)
const localProposals = ref([])
const deletingId = ref(null)
const deleteModalOpen = ref(false)
const targetDeleteProposal = ref(null)
const flashMessage = ref({ type: '', text: '' })

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

async function loadTnaList() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/tna')
    localProposals.value = data.proposals || []
  } catch (err) {
    console.error('Failed to load TNA list:', err)
    localProposals.value = props.tnaProposals
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadTnaList()
})

const annualTnaList = computed(() => {
  const list = localProposals.value.length > 0 ? localProposals.value : props.tnaProposals
  // Urutkan tahun dari yang terbaru
  return [...list].sort((a, b) => Number(b.year) - Number(a.year))
})

const tnaColumns = [
  { key: 'year', label: 'Tahun', sortable: true, align: 'center', width: '100px' },
  {
    key: 'total_budget_requested',
    label: 'Investment (Projection)',
    sortable: true,
    align: 'right',
    format: val => formatRupiah(val)
  },
  {
    key: 'realization_amount',
    label: 'Realization',
    sortable: true,
    align: 'right',
    format: val => formatRupiah(val)
  },
  { key: 'realization_percentage', label: '% Percentage', sortable: true, align: 'center', width: '160px' },
  {
    key: 'minimum_regulator_investment',
    label: 'Regulator Target (Min)',
    sortable: true,
    align: 'right',
    format: val => (val ? formatRupiah(val) : '-')
  },
  { key: 'status', label: 'Status Dokumen', sortable: true, align: 'center', width: '160px' }
]

// KPI Metrics
const totalInvestment = computed(() => {
  return annualTnaList.value.reduce((sum, item) => sum + Number(item.total_budget_requested || 0), 0)
})

const totalRealization = computed(() => {
  return annualTnaList.value.reduce((sum, item) => sum + Number(item.realization_amount || 0), 0)
})

const totalRemaining = computed(() => {
  return Math.max(0, totalInvestment.value - totalRealization.value)
})

const overallPercentage = computed(() => {
  if (totalInvestment.value <= 0) return 0
  return Math.round((totalRealization.value / totalInvestment.value) * 100)
})

function confirmDelete(proposal) {
  targetDeleteProposal.value = proposal
  deleteModalOpen.value = true
}

async function executeDelete() {
  if (!targetDeleteProposal.value) return
  deletingId.value = targetDeleteProposal.value.id
  try {
    const { data } = await apiClient.delete(`/tna/${targetDeleteProposal.value.id}`)
    flashMessage.value = {
      type: 'success',
      text: data.message || `Dokumen TNA Tahun ${targetDeleteProposal.value.year} berhasil dihapus.`
    }
    deleteModalOpen.value = false
    targetDeleteProposal.value = null
    await loadTnaList()
    emit('refresh-list')
    setTimeout(() => {
      flashMessage.value = { type: '', text: '' }
    }, 5000)
  } catch (err) {
    flashMessage.value = {
      type: 'error',
      text: err.response?.data?.message || 'Gagal menghapus dokumen TNA.'
    }
  } finally {
    deletingId.value = null
  }
}
</script>

<template>
  <div class="flex flex-col gap-6">

    <!-- Top Alert Notification -->
    <div
      v-if="flashMessage.text"
      class="p-4 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs transition animate-fadeIn"
      :class="flashMessage.type === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="flashMessage.type !== 'error'" class="w-4 h-4 text-emerald-600" />
        <AlertTriangle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ flashMessage.text }}</span>
      </div>
      <button @click="flashMessage = { type: '', text: '' }" class="hover:opacity-75">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Calendar class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Tahun Terdata</p>
          <p class="text-lg font-extrabold text-gray-900">{{ annualTnaList.length }} Tahun Anggaran</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold">
          <Wallet class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Proyeksi Investasi</p>
          <p class="text-lg font-extrabold text-gray-900 font-mono">{{ formatRupiah(totalInvestment) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Realisasi Kumulatif</p>
          <p class="text-lg font-extrabold text-emerald-700 font-mono">{{ formatRupiah(totalRealization) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <TrendingUp class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Rata-rata Capaian Realisasi</p>
          <div class="flex items-center gap-2">
            <p class="text-lg font-extrabold text-gray-900 font-mono">{{ overallPercentage }}%</p>
            <span class="text-[10px] text-gray-400">Sisa {{ formatRupiah(totalRemaining) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Standard BWS LIS DataTable for TNA Annual Plan -->
    <DataTable
      :data="annualTnaList"
      :columns="tnaColumns"
      export-filename="rencana_tna_tahunan_bws"
      :show-date-range="false"
      :default-per-page="10"
      footer-note="Catatan: Setiap tahun anggaran hanya memiliki 1 usulan TNA Induk bank-wide sesuai regulasi OJK &amp; standar BWS."
    >
      <!-- Custom Cell: Tahun -->
      <template #cell-year="{ row }">
        <span
          class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-extrabold"
          :class="row.year === 2026 ? 'bg-[#0067AC] text-white shadow-2xs' : 'bg-gray-100 text-gray-700'"
        >
          {{ row.year }}
        </span>
      </template>

      <!-- Custom Cell: Investment -->
      <template #cell-total_budget_requested="{ row }">
        <span class="font-mono font-bold text-gray-900">{{ formatRupiah(row.total_budget_requested) }}</span>
      </template>

      <!-- Custom Cell: Realization -->
      <template #cell-realization_amount="{ row }">
        <span class="font-mono font-bold text-emerald-700">{{ formatRupiah(row.realization_amount) }}</span>
      </template>

      <!-- Custom Cell: % Percentage -->
      <template #cell-realization_percentage="{ row }">
        <div class="flex items-center justify-center gap-2">
          <div class="w-16 bg-gray-100 rounded-full h-2 overflow-hidden hidden sm:block">
            <div
              class="h-2 rounded-full transition-all duration-500"
              :class="[
                Number(row.realization_percentage) >= 90 ? 'bg-emerald-500' :
                Number(row.realization_percentage) >= 50 ? 'bg-[#0067AC]' : 'bg-amber-500'
              ]"
              :style="{ width: `${Math.min(100, row.realization_percentage || 0)}%` }"
            ></div>
          </div>
          <span
            class="font-mono font-extrabold text-xs px-2 py-0.5 rounded-full"
            :class="[
              Number(row.realization_percentage) >= 90 ? 'bg-emerald-50 text-emerald-700' :
              Number(row.realization_percentage) >= 50 ? 'bg-blue-50 text-[#0067AC]' : 'bg-amber-50 text-amber-700'
            ]"
          >
            {{ row.realization_percentage || (row.total_budget_requested ? Math.round((Number(row.realization_amount)/Number(row.total_budget_requested))*100) : 0) }}%
          </span>
        </div>
      </template>

      <!-- Custom Cell: Regulator Target -->
      <template #cell-minimum_regulator_investment="{ row }">
        <span class="font-mono text-gray-500">
          {{ row.minimum_regulator_investment ? formatRupiah(row.minimum_regulator_investment) : '-' }}
        </span>
      </template>

      <!-- Custom Cell: Status Dokumen -->
      <template #cell-status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap"
          :class="[
            row.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
            row.status === 'REJECTED' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
            row.status === 'REVISED' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
            'bg-blue-50 text-[#0067AC] border border-blue-200'
          ]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.status === 'APPROVED' ? 'bg-emerald-500' : 'bg-[#0067AC] animate-pulse'"
          ></span>
          {{ row.approval_status_badge || (row.status === 'APPROVED' ? 'Disetujui Final' : 'Menunggu Review') }}
        </span>
      </template>

      <!-- Action Column -->
      <template #action="{ row }">
        <div class="inline-flex items-center gap-1 justify-center bg-gray-50 p-1 rounded-xl border border-gray-200">
          <button
            @click="emit('view-tna-detail', row.id)"
            class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#0067AC] hover:bg-[#0067AC] hover:text-white transition flex items-center gap-1 cursor-pointer"
            title="View TNA (Lihat Rincian Program & Sisa Anggaran)"
          >
            <Eye class="w-3.5 h-3.5" />
            <span>View</span>
          </button>
          <span class="text-gray-300">|</span>
          <button
            v-if="canManageTna"
            @click="emit('edit-tna', row.id)"
            class="px-2.5 py-1 rounded-lg text-xs font-bold text-amber-700 hover:bg-amber-600 hover:text-white transition flex items-center gap-1 cursor-pointer"
            title="Edit TNA (Ubah Pagu & Program)"
          >
            <Edit3 class="w-3.5 h-3.5" />
            <span>Edit</span>
          </button>
          <span v-if="canManageTna" class="text-gray-300">|</span>
          <button
            v-if="canManageTna"
            @click="confirmDelete(row)"
            class="px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-600 hover:text-white transition flex items-center gap-1 cursor-pointer"
            title="Delete TNA (Hapus Rencana Tahunan)"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>Delete</span>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="deleteModalOpen"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 animate-scaleUp">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
            <Trash2 class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-base font-extrabold text-gray-900">Hapus Dokumen TNA</h3>
            <p class="text-xs text-gray-500">Konfirmasi Penghapusan Rencana Tahunan</p>
          </div>
        </div>

        <div class="bg-rose-50/70 p-3.5 rounded-2xl border border-rose-100 text-xs text-rose-800 leading-relaxed">
          Apakah Anda yakin ingin menghapus dokumen TNA <strong>Tahun {{ targetDeleteProposal?.year }}</strong> ({{ targetDeleteProposal?.proposal_code }})?
          <br><br>
          <span class="font-bold">Peringatan:</span> Seluruh rincian program kerja pelatihan dan pagu anggaran untuk tahun ini akan dihapus dari sistem.
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button
            @click="deleteModalOpen = false; targetDeleteProposal = null"
            class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="executeDelete"
            :disabled="!!deletingId"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>{{ deletingId ? 'Menghapus...' : 'Ya, Hapus Sekarang' }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
