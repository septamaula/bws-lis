<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  Calendar,
  Wallet,
  CheckCircle,
  FileText,
  ExternalLink,
  ChevronRight,
  Eye,
  Download,
  Edit3,
  TrendingUp,
  Clock,
  ShieldCheck,
  Building2,
  Printer,
  Loader2
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const props = defineProps({
  proposalId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['back', 'edit', 'refresh-list'])

const loading = ref(true)
const proposal = ref(null)
const flowSteps = ref([])
const currentStep = ref(null)
const canApprove = ref(false)

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

async function loadDetail() {
  loading.value = true
  try {
    const { data } = await apiClient.get(`/tna/${props.proposalId}`)
    proposal.value = data.proposal
    flowSteps.value = data.flowSteps || []
    currentStep.value = data.currentStep || null
    canApprove.value = data.canApprove || false
  } catch (err) {
    console.error('Failed to load TNA detail:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadDetail()
})

// Group items by category for the grouped table view
const groupedItems = computed(() => {
  if (!proposal.value || !proposal.value.items) return {}
  const groups = {}
  proposal.value.items.forEach(item => {
    const cat = item.category_name || item.category?.name || 'General Training'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(item)
  })
  return groups
})

// Total aggregates for the breakdown table (sum of items)
const totalInvestment = computed(() => {
  if (proposal.value?.items && proposal.value.items.length > 0) {
    const sum = proposal.value.items.reduce((s, item) => s + Number(item.budget || item.estimated_cost || 0), 0)
    if (sum > 0) return sum
  }
  return Number(proposal.value?.total_budget_requested || 0)
})

const totalRealization = computed(() => {
  if (proposal.value?.items && proposal.value.items.length > 0) {
    return proposal.value.items.reduce((s, item) => s + Number(item.realization_amount || 0), 0)
  }
  return Number(proposal.value?.realization_amount || 0)
})

const totalRemaining = computed(() => {
  return Math.max(0, totalInvestment.value - totalRealization.value)
})

// Annual plan progress (from proposal header)
const annualInvestment = computed(() => Number(proposal.value?.total_budget_requested || totalInvestment.value || 0))
const annualRealization = computed(() => Number(proposal.value?.realization_amount ?? totalRealization.value ?? 0))
const realizationPercentage = computed(() => {
  if (annualInvestment.value <= 0) return 0
  return Math.round((annualRealization.value / annualInvestment.value) * 100)
})

const detailColumns = [
  { key: 'category', label: 'Category', width: '25%' },
  { key: 'recommended_program', label: 'Training Program' },
  { key: 'budget', label: 'Investment (Projection)', align: 'right', width: '180px' },
  { key: 'realization_amount', label: 'Realization', align: 'right', width: '160px' },
  { key: 'remaining_budget', label: 'Remaining Budget', align: 'right', width: '160px' }
]

const downloadingDoc = ref(false)

async function downloadDocument() {
  if (!proposal.value) return
  downloadingDoc.value = true
  try {
    const response = await apiClient.get(`/tna/${proposal.value.id}/attachment`, {
      responseType: 'blob'
    })

    if (response.data.type === 'application/json') {
      const text = await response.data.text()
      const json = JSON.parse(text)
      alert(json.message || 'Dokumen belum tersedia secara fisik.')
      return
    }

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (err) {
    console.error('Failed to view attachment:', err)
    // Fallback: direct window.open
    window.open(`${apiClient.defaults.baseURL || '/api'}/tna/${proposal.value.id}/attachment`, '_blank')
  } finally {
    downloadingDoc.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-12">

    <!-- Standard Subpage Header (Navigation & Actions) -->
    <SubpageHeader
      back-label="Kembali ke List TNA"
      title="Rincian Usulan TNA"
      :subtitle="proposal ? `Tahun Anggaran ${proposal.year} • Perencanaan & Realisasi Investasi Diklat Bank Woori Saudara` : ''"
      :status-badge="proposal?.proposal_code"
      @back="emit('back')"
    >
      <template #actions>
        <AppButton
          variant="primary"
          icon="edit"
          size="sm"
          @click="emit('edit', proposal?.id)"
        >
          Edit Dokumen TNA
        </AppButton>
      </template>
    </SubpageHeader>

    <!-- 1. Top Information Card: Year, Document, Regulator Target -->
    <div v-if="proposal" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Year -->
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold shrink-0">
          <Calendar class="w-5 h-5" />
        </div>
        <div>
          <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Tahun Anggaran</span>
          <span class="text-xl font-extrabold text-gray-900 font-mono">{{ proposal.year }}</span>
        </div>
      </div>

      <!-- Document Lampiran -->
      <div class="flex items-center gap-3.5 min-w-0">
        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold shrink-0">
          <FileText class="w-5 h-5" />
        </div>
        <div class="min-w-0 flex-1">
          <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Dokumen Lampiran</span>
          <div v-if="proposal.attachment_name" class="mt-1 flex items-center gap-1.5 flex-wrap">
            <button
              :disabled="downloadingDoc"
              @click="downloadDocument"
              class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-blue-50/70 hover:bg-[#0067AC] text-gray-800 hover:text-white border border-blue-200/80 hover:border-[#0067AC] transition-all duration-200 cursor-pointer shadow-2xs max-w-full disabled:opacity-50"
              :title="`Klik untuk membuka file: ${proposal.attachment_name}`"
            >
              <div class="w-5 h-5 rounded-md bg-rose-50 text-rose-600 group-hover:bg-white group-hover:text-rose-600 flex items-center justify-center shrink-0 border border-rose-100 group-hover:border-transparent transition">
                <FileText class="w-3 h-3" />
              </div>
              <span class="text-xs font-bold text-gray-800 group-hover:text-white truncate max-w-[130px] sm:max-w-[170px] transition">
                {{ proposal.attachment_name }}
              </span>
              <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-white/80 group-hover:bg-white/20 text-[#0067AC] group-hover:text-white border border-blue-100 group-hover:border-transparent transition shrink-0">
                <Eye v-if="!downloadingDoc" class="w-2.5 h-2.5" />
                <Loader2 v-else class="w-2.5 h-2.5 animate-spin" />
                <span>Lihat File</span>
              </span>
            </button>
            <a
              :href="`${apiClient.defaults.baseURL || '/api'}/tna/${proposal.id}/attachment?download=1`"
              target="_blank"
              class="p-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-[#0067AC] border border-gray-200 transition shadow-2xs cursor-pointer flex items-center justify-center shrink-0"
              title="Unduh file dokumen"
            >
              <Download class="w-3.5 h-3.5" />
            </a>
          </div>
          <span v-else class="text-xs text-gray-400 italic block mt-1">Tidak ada dokumen dilampirkan</span>
        </div>
      </div>

      <!-- Minimum Regulator Investment -->
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold shrink-0">
          <Wallet class="w-5 h-5" />
        </div>
        <div>
          <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Regulator Target (Min)</span>
          <span class="text-base font-extrabold text-[#0067AC] font-mono">
            {{ formatRupiah(proposal.minimum_regulator_investment || 1000000000) }}
          </span>
        </div>
      </div>

    </div>

    <!-- 2. Standard Detail Table: Breakdown Anggaran & Program -->
    <DetailTable
      v-if="proposal"
      title="Rincian Alokasi Program & Realisasi Anggaran"
      subtitle="Breakdown usulan pagu investasi dan realisasi per kategori kompetensi"
      badge="Breakdown Tahunan"
      :columns="detailColumns"
    >
      <template #tbody>
        <template v-for="(items, categoryName) in groupedItems" :key="categoryName">
          <tr
            v-for="(row, idx) in items"
            :key="row.id || idx"
            class="hover:bg-blue-50/30 transition"
          >
            <!-- Grouped Category: Rowspan or distinct category styling -->
            <td
              v-if="idx === 0"
              :rowspan="items.length"
              class="py-3 px-4 font-extrabold text-gray-900 border-r border-gray-100 align-top bg-gray-50/40"
            >
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#0067AC]"></span>
                <span>{{ categoryName }}</span>
              </div>
              <span class="text-[10px] text-gray-400 font-normal block ml-4 mt-0.5">
                {{ items.length }} Program Pelatihan
              </span>
            </td>

            <!-- Training Program -->
            <td class="py-3 px-4 font-semibold text-gray-800">
              {{ row.recommended_program || row.sub_category_name || 'Program Pelatihan' }}
            </td>

            <!-- Investment (Projection) -->
            <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
              {{ formatRupiah(row.budget || row.estimated_cost) }}
            </td>

            <!-- Realization -->
            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
              {{ formatRupiah(row.realization_amount) }}
            </td>

            <!-- Remaining Budget -->
            <td class="py-3 px-4 text-right font-mono font-bold text-amber-700">
              {{ formatRupiah(row.remaining_budget !== undefined ? row.remaining_budget : Math.max(0, (row.budget || row.estimated_cost) - (row.realization_amount || 0))) }}
            </td>
          </tr>
        </template>
      </template>

      <!-- Table Footer matching Excel Total Row -->
      <template #tfoot>
        <tr class="bg-[#f8faf9] border-t-2 border-gray-300 font-extrabold text-xs">
          <td colspan="2" class="py-3.5 px-4 text-right text-gray-900 uppercase tracking-wider text-xs">
            Total
          </td>
          <td class="py-3.5 px-4 text-right font-mono text-sm text-[#0067AC]">
            {{ formatRupiah(totalInvestment) }}
          </td>
          <td class="py-3.5 px-4 text-right font-mono text-sm text-emerald-700">
            {{ formatRupiah(totalRealization) }}
          </td>
          <td class="py-3.5 px-4 text-right font-mono text-sm text-amber-700">
            {{ formatRupiah(totalRemaining) }}
          </td>
        </tr>
      </template>

      <!-- Bottom Card Information: Realization Progress Bar -->
      <template #bottom>
        <div class="p-6 flex items-center justify-between">
          <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold shrink-0">
              <TrendingUp class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider">Tingkat Capaian Realisasi Anggaran</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span class="text-lg font-extrabold text-emerald-700 font-mono">{{ realizationPercentage }}%</span>
                <span class="text-xs text-gray-500 font-medium">dari total proyeksi {{ formatRupiah(annualInvestment) }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </DetailTable>

  </div>
</template>
