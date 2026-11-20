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

    <!-- Header Section with Title & Rule Notice -->
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0067AC] to-[#002855] text-white flex items-center justify-center shadow-xs">
            <ClipboardList class="w-5 h-5" />
          </div>
          <div>
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight flex items-center gap-2">
              <span>Training Need Analysis (TNA)</span>
              <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-[#0067AC] border border-blue-100">
                1 Dokumen per Tahun
              </span>
            </h1>
            <p class="text-xs text-gray-500 mt-0.5">
              Dashboard Tahunan Perencanaan & Realisasi Investasi Diklat Bank Woori Saudara (Enterprise Plan)
            </p>
          </div>
        </div>
      </div>

      <!-- Action Button: Create TNA [+] -->
      <div class="flex items-center gap-2.5">
        <button
          @click="loadTnaList"
          class="p-2.5 rounded-xl border border-gray-200 text-gray-600 hover:text-[#0067AC] hover:border-[#0067AC] bg-white transition shadow-2xs"
          title="Muat Ulang Data"
        >
          <RotateCcw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>

        <button
          v-if="canManageTna"
          @click="emit('create-tna')"
          class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm hover:shadow-md transition flex items-center gap-2 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Create TNA [+]</span>
        </button>
      </div>
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

    <!-- MAIN TABLE: List TNA (Identical to Excel Template) -->
    <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm overflow-hidden">
      
      <!-- Table Header Bar -->
      <div class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white px-5 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <ClipboardList class="w-4 h-4 text-white/80" />
          <h2 class="text-sm font-bold tracking-wide uppercase">List TNA (Rencana Tahunan)</h2>
        </div>
        <span class="text-xs text-white/80 font-medium">
          {{ annualTnaList.length }} Tahun Dokumen
        </span>
      </div>

      <!-- Table Content -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
              <th class="py-3 px-4 w-28 text-center">Tahun</th>
              <th class="py-3 px-4 text-right">Investment (Projection)</th>
              <th class="py-3 px-4 text-right">Realization</th>
              <th class="py-3 px-4 text-center w-36">%Percentage</th>
              <th class="py-3 px-4 text-right hidden lg:table-cell">Regulator Target (Min)</th>
              <th class="py-3 px-4 text-center hidden md:table-cell">Status Dokumen</th>
              <th class="py-3 px-4 text-center w-48">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="item in annualTnaList"
              :key="item.id"
              class="hover:bg-blue-50/40 transition group"
            >
              <!-- 1. Tahun -->
              <td class="py-3.5 px-4 text-center font-bold text-gray-900 font-mono">
                <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-extrabold"
                  :class="item.year === 2026 ? 'bg-[#0067AC] text-white shadow-2xs' : 'bg-gray-100 text-gray-700'"
                >
                  {{ item.year }}
                </span>
              </td>

              <!-- 2. Investment (Projection) -->
              <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-900">
                {{ formatRupiah(item.total_budget_requested) }}
              </td>

              <!-- 3. Realization -->
              <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700">
                {{ formatRupiah(item.realization_amount) }}
              </td>

              <!-- 4. %Percentage -->
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-2">
                  <div class="w-16 bg-gray-100 rounded-full h-2 overflow-hidden hidden sm:block">
                    <div
                      class="h-2 rounded-full transition-all duration-500"
                      :class="[
                        Number(item.realization_percentage) >= 90 ? 'bg-emerald-500' :
                        Number(item.realization_percentage) >= 50 ? 'bg-[#0067AC]' : 'bg-amber-500'
                      ]"
                      :style="{ width: `${Math.min(100, item.realization_percentage || 0)}%` }"
                    ></div>
                  </div>
                  <span
                    class="font-mono font-extrabold text-xs px-2 py-0.5 rounded-full"
                    :class="[
                      Number(item.realization_percentage) >= 90 ? 'bg-emerald-50 text-emerald-700' :
                      Number(item.realization_percentage) >= 50 ? 'bg-blue-50 text-[#0067AC]' : 'bg-amber-50 text-amber-700'
                    ]"
                  >
                    {{ item.realization_percentage || (item.total_budget_requested ? Math.round((Number(item.realization_amount)/Number(item.total_budget_requested))*100) : 0) }}%
                  </span>
                </div>
              </td>

              <!-- 5. Regulator Target (Min) -->
              <td class="py-3.5 px-4 text-right font-mono text-gray-500 hidden lg:table-cell">
                {{ item.minimum_regulator_investment ? formatRupiah(item.minimum_regulator_investment) : '-' }}
              </td>

              <!-- 6. Status Dokumen -->
              <td class="py-3.5 px-4 text-center hidden md:table-cell">
                <span
                  class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap"
                  :class="[
                    item.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                    item.status === 'REJECTED' ? 'bg-rose-50 text-rose-700 border border-rose-200' :
                    item.status === 'REVISED' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                    'bg-blue-50 text-[#0067AC] border border-blue-200'
                  ]"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="item.status === 'APPROVED' ? 'bg-emerald-500' : 'bg-[#0067AC] animate-pulse'"
                  ></span>
                  {{ item.approval_status_badge || (item.status === 'APPROVED' ? 'Disetujui Final' : 'Menunggu Review') }}
                </span>
              </td>

              <!-- 7. Action: View / Edit / Delete (Matching Excel wireframe) -->
              <td class="py-3.5 px-4 text-center">
                <div class="inline-flex items-center gap-1 justify-center bg-gray-50 p-1 rounded-xl border border-gray-200">
                  <!-- View Button -->
                  <button
                    @click="emit('view-tna-detail', item.id)"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#0067AC] hover:bg-[#0067AC] hover:text-white transition flex items-center gap-1 cursor-pointer"
                    title="View TNA (Lihat Rincian Program & Sisa Anggaran)"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>View</span>
                  </button>

                  <span class="text-gray-300">|</span>

                  <!-- Edit Button -->
                  <button
                    v-if="canManageTna"
                    @click="emit('edit-tna', item.id)"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-amber-700 hover:bg-amber-600 hover:text-white transition flex items-center gap-1 cursor-pointer"
                    title="Edit TNA (Ubah Pagu & Program)"
                  >
                    <Edit3 class="w-3.5 h-3.5" />
                    <span>Edit</span>
                  </button>

                  <span v-if="canManageTna" class="text-gray-300">|</span>

                  <!-- Delete Button -->
                  <button
                    v-if="canManageTna"
                    @click="confirmDelete(item)"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-600 hover:text-white transition flex items-center gap-1 cursor-pointer"
                    title="Delete TNA (Hapus Rencana Tahunan)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="annualTnaList.length === 0">
              <td colspan="7" class="py-12 text-center text-gray-400">
                <ClipboardList class="w-12 h-12 mx-auto text-gray-300 mb-2 stroke-1" />
                <p class="font-bold text-sm text-gray-700">Belum Ada Dokumen TNA Tahunan</p>
                <p class="text-xs text-gray-400 mt-1">Silakan klik tombol "Create TNA [+]" di atas untuk membuat rencana tahunan.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table Bottom Action Bar (Matching Excel Button Placement) -->
      <div class="p-4 bg-gray-50/70 border-t border-gray-200 flex items-center justify-between flex-wrap gap-3">
        <p class="text-xs text-gray-500">
          Catatan: Setiap tahun anggaran hanya memiliki 1 usulan TNA Induk bank-wide sesuai regulasi OJK & standar BWS.
        </p>
        <button
          v-if="canManageTna"
          @click="emit('create-tna')"
          class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm hover:shadow-md transition flex items-center gap-2 cursor-pointer ml-auto"
        >
          <Plus class="w-4 h-4" />
          <span>Create TNA [+]</span>
        </button>
      </div>
    </div>

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
