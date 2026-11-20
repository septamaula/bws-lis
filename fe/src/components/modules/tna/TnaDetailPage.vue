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
  Printer
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

// Total aggregates
const totalInvestment = computed(() => {
  return Number(proposal.value?.total_budget_requested || 0)
})

const totalRealization = computed(() => {
  return Number(proposal.value?.realization_amount || 0)
})

const totalRemaining = computed(() => {
  return Math.max(0, totalInvestment.value - totalRealization.value)
})

const realizationPercentage = computed(() => {
  if (totalInvestment.value <= 0) return 0
  return Math.round((totalRealization.value / totalInvestment.value) * 100)
})

async function downloadDocument() {
  if (!proposal.value) return
  window.open(`${apiClient.defaults.baseURL || '/api'}/tna/${proposal.value.id}/attachment`, '_blank')
}
</script>

<template>
  <div class="flex flex-col gap-6 max-w-5xl mx-auto pb-12">

    <!-- Top Navigation Bar -->
    <div class="flex items-center justify-between">
      <button
        @click="emit('back')"
        class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-[#0067AC] transition cursor-pointer"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke List TNA</span>
      </button>

      <div class="flex items-center gap-2">
        <button
          @click="emit('edit', proposal?.id)"
          class="px-3.5 py-1.5 rounded-xl border border-gray-200 text-gray-700 hover:text-amber-700 hover:border-amber-400 bg-white font-bold text-xs shadow-2xs transition flex items-center gap-1.5 cursor-pointer"
        >
          <Edit3 class="w-3.5 h-3.5" />
          <span>Edit TNA</span>
        </button>
      </div>
    </div>

    <!-- MAIN VIEW CONTAINER: View TNA (Matching Excel Mockup) -->
    <div v-if="proposal" class="bg-white rounded-3xl border border-gray-200/90 shadow-sm overflow-hidden">
      
      <!-- Card Header Bar -->
      <div class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <Calendar class="w-5 h-5 text-white/90" />
          <h2 class="text-base font-extrabold tracking-wide uppercase">
            View TNA - Tahun {{ proposal.year }}
          </h2>
        </div>
        <span class="text-xs bg-white/20 text-white font-bold px-3 py-1 rounded-full">
          {{ proposal.proposal_code }}
        </span>
      </div>

      <!-- Top Information Strip matching Excel Wireframe -->
      <div class="p-6 bg-gray-50/70 border-b border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Year -->
        <div class="flex flex-col">
          <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Year</span>
          <span class="text-xl font-extrabold text-gray-900 font-mono mt-0.5">{{ proposal.year }}</span>
        </div>

        <!-- Document [View] -->
        <div class="flex flex-col">
          <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Document</span>
          <div class="mt-1 flex items-center gap-2">
            <button
              v-if="proposal.attachment_name"
              @click="downloadDocument"
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 text-[#0067AC] hover:bg-[#0067AC] hover:text-white font-bold text-xs transition border border-blue-200 cursor-pointer shadow-2xs"
            >
              <FileText class="w-3.5 h-3.5" />
              <span>[View] {{ proposal.attachment_name }}</span>
            </button>
            <span v-else class="text-xs text-gray-400 italic">Tidak ada dokumen dilampirkan</span>
          </div>
        </div>

        <!-- Minimum Regulator Investment -->
        <div class="flex flex-col">
          <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Regulator Statement (Min)</span>
          <span class="text-base font-extrabold text-[#0067AC] font-mono mt-0.5">
            {{ formatRupiah(proposal.minimum_regulator_investment || 1000000000) }}
          </span>
        </div>

      </div>

      <!-- TABLE: View TNA (Category | Training Program | Investment (Projection) | Realization | Remaining Budget) -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white font-bold text-[11px] uppercase tracking-wider">
              <th class="py-3 px-4 w-1/4">Category</th>
              <th class="py-3 px-4">Training Program</th>
              <th class="py-3 px-4 text-right w-44">Investment (Projection)</th>
              <th class="py-3 px-4 text-right w-40">Realization</th>
              <th class="py-3 px-4 text-right w-40">Remaining Budget</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
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
          </tbody>

          <!-- Table Footer matching Excel Total Row -->
          <tfoot>
            <tr class="bg-gray-100 border-t-2 border-gray-300 font-extrabold text-xs">
              <td colspan="2" class="py-4 px-4 text-right text-gray-900 uppercase tracking-wider text-sm">
                Total
              </td>
              <td class="py-4 px-4 text-right font-mono text-sm text-[#0067AC]">
                {{ formatRupiah(totalInvestment) }}
              </td>
              <td class="py-4 px-4 text-right font-mono text-sm text-emerald-700">
                {{ formatRupiah(totalRealization) }}
              </td>
              <td class="py-4 px-4 text-right font-mono text-sm text-amber-700">
                {{ formatRupiah(totalRemaining) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Bottom Card Information: Realization Progress Bar -->
      <div class="p-6 bg-gray-50/50 border-t border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <TrendingUp class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-semibold uppercase">Tingkat Capaian Realisasi Anggaran</p>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-lg font-extrabold text-emerald-700 font-mono">{{ realizationPercentage }}%</span>
              <span class="text-xs text-gray-500 font-medium">dari total proyeksi {{ formatRupiah(totalInvestment) }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            @click="emit('back')"
            class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 font-bold text-xs hover:bg-gray-100 transition cursor-pointer"
          >
            Kembali ke List TNA
          </button>
          <button
            @click="emit('edit', proposal?.id)"
            class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#002855] text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5 cursor-pointer"
          >
            <Edit3 class="w-3.5 h-3.5" />
            <span>Edit Dokumen Ini</span>
          </button>
        </div>
      </div>

    </div>

  </div>
</template>
