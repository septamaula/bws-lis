<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  BookOpen,
  Plus,
  Layers,
  GraduationCap,
  MapPin,
  CheckCircle2,
  Wallet,
  Clock,
  ChevronRight,
  Eye,
  Edit3,
  Trash2,
  RotateCcw,
  Search,
  Calendar,
  AlertTriangle,
  X,
  FileText
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import ProgramCreatePage from './programs/ProgramCreatePage.vue'
import ProgramDetailPage from './programs/ProgramDetailPage.vue'

const props = defineProps({
  trainingPrograms: {
    type: Array,
    default: () => []
  },
  viewMode: {
    type: String,
    default: 'list'
  }
})

const emit = defineEmits(['open-class-create', 'update:viewMode'])

// Sub-View Modes: 'list' | 'create' | 'detail' | 'edit'
const internalViewMode = ref('list')
const programViewMode = computed({
  get: () => props.viewMode !== undefined && props.viewMode !== null ? props.viewMode : internalViewMode.value,
  set: (val) => {
    internalViewMode.value = val
    emit('update:viewMode', val)
  }
})
const selectedProgramId = ref(null)

const loading = ref(false)
const programs = ref([])
const stats = ref({
  total_programs: 0,
  total_budget: 0,
  total_realization: 0,
  total_participants: 0,
  mandatory_count: 0,
  approved_count: 0
})

// Filters matching Excel wireframe (Year 20XX, Process, Search)
const selectedYear = ref('ALL')
const selectedProcess = ref('ALL')
const searchQuery = ref('')

const flashSuccess = ref('')
const flashError = ref('')
const deleteModalOpen = ref(false)
const targetDeleteProgram = ref(null)
const deleting = ref(false)

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

function formatDateDisplay(d) {
  if (!d) return '-'
  try {
    const date = new Date(d)
    if (isNaN(date.getTime())) return d
    return new Intl.DateTimeFormat('id-ID', {
      day: '2-digit',
      month: 'short',
      year: '2-digit'
    }).format(date).replace(/\s/g, '-')
  } catch {
    return d
  }
}

async function fetchPrograms() {
  loading.value = true
  try {
    const params = {}
    if (selectedYear.value !== 'ALL') params.year = selectedYear.value
    if (selectedProcess.value !== 'ALL') params.process = selectedProcess.value
    if (searchQuery.value) params.search = searchQuery.value

    const { data } = await apiClient.get('/programs', { params })
    programs.value = data.programs || []
    stats.value = data.stats || stats.value
  } catch (err) {
    console.error('Failed to fetch programs:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPrograms()
})

const availableYears = computed(() => {
  const years = new Set([2026, 2024, 2023, 2022, 2021, 2020])
  programs.value.forEach(p => {
    if (p.year) years.add(p.year)
  })
  return Array.from(years).sort((a, b) => b - a)
})

function openCreate() {
  selectedProgramId.value = null
  programViewMode.value = 'create'
}

function openDetail(id) {
  selectedProgramId.value = id
  programViewMode.value = 'detail'
}

function openEdit(id) {
  selectedProgramId.value = id
  programViewMode.value = 'edit'
}

function handleProgramCreated(msg) {
  flashSuccess.value = msg || 'Program kerja berhasil disimpan.'
  programViewMode.value = 'list'
  fetchPrograms()
  setTimeout(() => {
    flashSuccess.value = ''
  }, 5000)
}

function confirmDelete(program) {
  targetDeleteProgram.value = program
  deleteModalOpen.value = true
}

async function executeDelete() {
  if (!targetDeleteProgram.value) return
  deleting.value = true
  try {
    const { data } = await apiClient.delete(`/programs/${targetDeleteProgram.value.id}`)
    flashSuccess.value = data.message || `Program ${targetDeleteProgram.value.program_code} berhasil dihapus.`
    deleteModalOpen.value = false
    targetDeleteProgram.value = null
    await fetchPrograms()
    setTimeout(() => {
      flashSuccess.value = ''
    }, 5000)
  } catch (err) {
    flashError.value = err.response?.data?.message || 'Gagal menghapus program kerja.'
    setTimeout(() => {
      flashError.value = ''
    }, 5000)
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div>
    <!-- Sub-View: Create or Edit Page -->
    <ProgramCreatePage
      v-if="programViewMode === 'create' || programViewMode === 'edit'"
      :edit-program-id="selectedProgramId"
      @back="programViewMode = 'list'"
      @created="handleProgramCreated"
    />

    <!-- Sub-View: Detail Page -->
    <ProgramDetailPage
      v-else-if="programViewMode === 'detail' && selectedProgramId"
      :program-id="selectedProgramId"
      @back="programViewMode = 'list'"
      @edit="openEdit"
      @open-class-create="(id) => emit('open-class-create', id)"
    />

    <!-- Sub-View: Main Program List Table (Matching Excel Wireframe) -->
    <div v-else class="flex flex-col gap-6">

      <!-- Success / Error Banners -->
      <div
        v-if="flashSuccess"
        class="p-4 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center justify-between shadow-xs animate-fadeIn"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 text-emerald-600" />
          <span>{{ flashSuccess }}</span>
        </div>
        <button @click="flashSuccess = ''" class="hover:opacity-75">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div
        v-if="flashError"
        class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center justify-between shadow-xs animate-fadeIn"
      >
        <div class="flex items-center gap-2">
          <AlertTriangle class="w-4 h-4 text-rose-600" />
          <span>{{ flashError }}</span>
        </div>
        <button @click="flashError = ''" class="hover:opacity-75">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- MAIN TABLE CONTAINER: Program List -->
      <div class="bg-white rounded-2xl border border-gray-200/90 shadow-sm overflow-hidden">
        
        <!-- Table Header Bar -->
        <div class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white px-5 py-3.5 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <BookOpen class="w-4 h-4 text-white/80" />
            <h2 class="text-sm font-bold tracking-wide uppercase">Program List</h2>
          </div>
          <span class="text-xs text-white/80 font-medium">
            {{ programs.length }} Program Terdaftar
          </span>
        </div>

        <!-- Filter Bar matching Excel (20XX, Process, Search) -->
        <div class="p-4 bg-gray-50/80 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
          
          <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
            <!-- 1. Year Filter (20XX) -->
            <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-gray-300 shadow-2xs">
              <Calendar class="w-3.5 h-3.5 text-[#0067AC]" />
              <select
                v-model="selectedYear"
                @change="fetchPrograms"
                class="bg-transparent text-xs font-bold text-gray-800 focus:outline-none cursor-pointer"
              >
                <option value="ALL">Semua Tahun</option>
                <option v-for="yr in availableYears" :key="yr" :value="yr">
                  Tahun {{ yr }}
                </option>
              </select>
            </div>

            <!-- 2. Process / Status Filter -->
            <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-gray-300 shadow-2xs">
              <span class="text-[11px] font-bold text-gray-400">Process:</span>
              <select
                v-model="selectedProcess"
                @change="fetchPrograms"
                class="bg-transparent text-xs font-bold text-gray-800 focus:outline-none cursor-pointer"
              >
                <option value="ALL">Semua Status</option>
                <option value="APPROVED">Disetujui Final</option>
                <option value="SUBMITTED">Menunggu Review</option>
                <option value="DRAFT">Draft</option>
              </select>
            </div>

            <!-- 3. Search Box -->
            <div class="relative flex-1 min-w-[200px] max-w-sm">
              <Search class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="searchQuery"
                @input="fetchPrograms"
                type="text"
                placeholder="Cari program, ID, fasilitator, ruang..."
                class="w-full bg-white text-xs text-gray-800 rounded-xl pl-8 pr-8 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
              />
              <button
                v-if="searchQuery"
                @click="searchQuery = ''; fetchPrograms()"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <button
            @click="fetchPrograms"
            class="p-2 rounded-xl bg-white border border-gray-300 text-gray-600 hover:text-[#0067AC] hover:border-[#0067AC] transition shadow-2xs"
            title="Refresh Table"
          >
            <RotateCcw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>
        </div>

        <!-- Table Content (Columns matching Excel Wireframe) -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white font-bold uppercase tracking-wider text-[11px]">
                <th class="py-3 px-3 w-28 text-center">Program ID</th>
                <th class="py-3 px-4">Program</th>
                <th class="py-3 px-3 w-28 text-center">Start Date</th>
                <th class="py-3 px-3 w-28 text-center">End Date</th>
                <th class="py-3 px-3 w-28">Location</th>
                <th class="py-3 px-3 w-28">Facilitator</th>
                <th class="py-3 px-4 text-right w-36">Budget</th>
                <th class="py-3 px-4 text-right w-36">Realization</th>
                <th class="py-3 px-3 text-center w-48">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="row in programs"
                :key="row.id"
                class="hover:bg-blue-50/40 transition group"
              >
                <!-- 1. Program ID -->
                <td class="py-3.5 px-3 text-center font-bold font-mono text-[#0067AC]">
                  {{ row.program_code }}
                </td>

                <!-- 2. Program Title & Category -->
                <td class="py-3.5 px-4 font-semibold text-gray-900">
                  <div class="flex flex-col">
                    <span class="font-bold text-gray-900 hover:text-[#0067AC] cursor-pointer" @click="openDetail(row.id)">
                      {{ row.title }}
                    </span>
                    <span class="text-[10px] text-gray-400 mt-0.5">
                      {{ row.bi_category || row.category }} • {{ row.program_type || 'In-House' }}
                    </span>
                  </div>
                </td>

                <!-- 3. Start Date -->
                <td class="py-3.5 px-3 text-center font-mono text-gray-700 whitespace-nowrap">
                  {{ formatDateDisplay(row.start_date) }}
                </td>

                <!-- 4. End Date -->
                <td class="py-3.5 px-3 text-center font-mono text-gray-700 whitespace-nowrap">
                  {{ formatDateDisplay(row.end_date) }}
                </td>

                <!-- 5. Location -->
                <td class="py-3.5 px-3 text-gray-700">
                  <span class="inline-flex items-center gap-1 font-medium">
                    <MapPin class="w-3 h-3 text-[#0067AC] shrink-0" />
                    <span class="truncate max-w-[120px]">{{ row.location || 'BWS LC' }}</span>
                  </span>
                </td>

                <!-- 6. Facilitator -->
                <td class="py-3.5 px-3 text-gray-700 font-medium">
                  {{ row.facilitator || '-' }}
                </td>

                <!-- 7. Budget -->
                <td class="py-3.5 px-4 text-right font-mono font-bold text-gray-900">
                  {{ row.total_budget > 0 ? formatRupiah(row.total_budget) : '-' }}
                </td>

                <!-- 8. Realization -->
                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700">
                  {{ row.realization_amount > 0 ? formatRupiah(row.realization_amount) : '-' }}
                </td>

                <!-- 9. Action: View / Edit / Delete (Matching Excel Wireframe) -->
                <td class="py-3.5 px-3 text-center">
                  <div class="inline-flex items-center gap-1 justify-center bg-gray-50 p-1 rounded-xl border border-gray-200">
                    <!-- View -->
                    <button
                      @click="openDetail(row.id)"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#0067AC] hover:bg-[#0067AC] hover:text-white transition flex items-center gap-1 cursor-pointer"
                      title="View Program"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>View</span>
                    </button>

                    <span class="text-gray-300">|</span>

                    <!-- Edit -->
                    <button
                      @click="openEdit(row.id)"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold text-amber-700 hover:bg-amber-600 hover:text-white transition flex items-center gap-1 cursor-pointer"
                      title="Edit Program"
                    >
                      <Edit3 class="w-3.5 h-3.5" />
                      <span>Edit</span>
                    </button>

                    <span class="text-gray-300">|</span>

                    <!-- Delete -->
                    <button
                      @click="confirmDelete(row)"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-600 hover:text-white transition flex items-center gap-1 cursor-pointer"
                      title="Delete Program"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                      <span>Delete</span>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="programs.length === 0">
                <td colspan="9" class="py-12 text-center text-gray-400">
                  <BookOpen class="w-12 h-12 mx-auto text-gray-300 mb-2 stroke-1" />
                  <p class="font-bold text-sm text-gray-700">Tidak ada program pelatihan yang cocok</p>
                  <p class="text-xs text-gray-400 mt-1">Gunakan tombol "Create Program [+]" untuk menambahkan program kerja baru.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Bottom Action Bar (Matching Excel Button Placement) -->
        <div class="p-4 bg-gray-50/70 border-t border-gray-200 flex items-center justify-between flex-wrap gap-3">
          <p class="text-xs text-gray-500">
            Total {{ programs.length }} program kerja dalam rencana pembelajaran BWS LIS.
          </p>
          <button
            @click="openCreate"
            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm hover:shadow-md transition flex items-center gap-2 cursor-pointer ml-auto"
          >
            <Plus class="w-4 h-4" />
            <span>Create Program [+]</span>
          </button>
        </div>

      </div>

    </div>

    <!-- Delete Program Modal -->
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
            <h3 class="text-base font-extrabold text-gray-900">Hapus Program Pelatihan</h3>
            <p class="text-xs text-gray-500">Konfirmasi Penghapusan Program</p>
          </div>
        </div>

        <div class="bg-rose-50/70 p-3.5 rounded-2xl border border-rose-100 text-xs text-rose-800 leading-relaxed">
          Apakah Anda yakin ingin menghapus program <strong>{{ targetDeleteProgram?.program_code }} - {{ targetDeleteProgram?.title }}</strong>?
          <br><br>
          <span class="font-bold">Peringatan:</span> Seluruh data rincian anggaran (RAB) program ini akan ikut terhapus.
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button
            @click="deleteModalOpen = false; targetDeleteProgram = null"
            class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="executeDelete"
            :disabled="deleting"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>{{ deleting ? 'Menghapus...' : 'Ya, Hapus Sekarang' }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
