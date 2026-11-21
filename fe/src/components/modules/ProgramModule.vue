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

const programColumns = [
  { key: 'program_code', label: 'Program ID', sortable: true, align: 'center', width: '120px' },
  { key: 'title', label: 'Program Pelatihan & Kategori', sortable: true },
  { key: 'start_date', label: 'Jadwal Diklat', sortable: true, width: '160px', align: 'center' },
  { key: 'location', label: 'Lokasi / Ruang', sortable: true, width: '140px' },
  { key: 'facilitator', label: 'Fasilitator', sortable: true, width: '150px' },
  {
    key: 'total_budget',
    label: 'Pagu Anggaran',
    sortable: true,
    align: 'right',
    width: '130px',
    format: val => (val > 0 ? formatRupiah(val) : '-')
  },
  {
    key: 'realization_amount',
    label: 'Realisasi',
    sortable: true,
    align: 'right',
    width: '130px',
    format: val => (val > 0 ? formatRupiah(val) : '-')
  }
]

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

      <!-- Standard BWS LIS DataTable for Training Programs -->
      <DataTable
        :data="programs"
        :columns="programColumns"
        date-key="start_date"
        export-filename="program_kerja_diklat_bws_2026"
        :default-per-page="10"
      >
        <!-- Custom Filters: Year & Process -->
        <template #filters>
          <!-- 1. Year Filter (20XX) -->
          <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-full border border-gray-200 text-xs shadow-2xs">
            <Calendar class="w-3.5 h-3.5 text-[#0067AC]" />
            <select
              v-model="selectedYear"
              @change="fetchPrograms"
              class="bg-transparent text-[11px] font-bold text-gray-800 focus:outline-none cursor-pointer"
            >
              <option value="ALL">Semua Tahun</option>
              <option v-for="yr in availableYears" :key="yr" :value="yr">
                Tahun {{ yr }}
              </option>
            </select>
          </div>

          <!-- 2. Process / Status Filter -->
          <div class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-full border border-gray-200 text-xs shadow-2xs">
            <span class="text-[10px] font-bold text-gray-400">Status:</span>
            <select
              v-model="selectedProcess"
              @change="fetchPrograms"
              class="bg-transparent text-[11px] font-bold text-gray-800 focus:outline-none cursor-pointer"
            >
              <option value="ALL">Semua Status</option>
              <option value="APPROVED">Disetujui Final</option>
              <option value="SUBMITTED">Menunggu Review</option>
              <option value="DRAFT">Draft</option>
            </select>
          </div>
        </template>

        <!-- Custom Cell: Program ID -->
        <template #cell-program_code="{ row }">
          <span class="font-bold font-mono text-[#0067AC]">{{ row.program_code }}</span>
        </template>

        <!-- Custom Cell: Title & Category -->
        <template #cell-title="{ row }">
          <div class="flex flex-col cursor-pointer" @click="openDetail(row.id)">
            <span class="font-bold text-gray-900 hover:text-[#0067AC] transition leading-tight">
              {{ row.title }}
            </span>
            <span class="text-[10px] text-gray-400 mt-0.5">
              {{ row.bi_category || row.category }} • {{ row.program_type || 'In-House' }}
            </span>
          </div>
        </template>

        <!-- Custom Cell: Schedule -->
        <template #cell-start_date="{ row }">
          <div class="flex flex-col items-center">
            <span class="font-mono text-gray-800 font-semibold text-xs">{{ formatDateDisplay(row.start_date) }}</span>
            <span class="text-[10px] text-gray-400 font-mono">s/d {{ formatDateDisplay(row.end_date) }}</span>
          </div>
        </template>

        <!-- Custom Cell: Location -->
        <template #cell-location="{ row }">
          <span class="inline-flex items-center gap-1 font-medium text-gray-700">
            <MapPin class="w-3.5 h-3.5 text-[#0067AC] shrink-0" />
            <span class="truncate max-w-[130px]">{{ row.location || 'BWS LC' }}</span>
          </span>
        </template>

        <!-- Custom Cell: Facilitator -->
        <template #cell-facilitator="{ row }">
          <span class="text-gray-700 font-medium">{{ row.facilitator || '-' }}</span>
        </template>

        <!-- Custom Cell: Budget -->
        <template #cell-total_budget="{ row }">
          <span class="font-mono font-bold text-gray-900">{{ row.total_budget > 0 ? formatRupiah(row.total_budget) : '-' }}</span>
        </template>

        <!-- Custom Cell: Realization -->
        <template #cell-realization_amount="{ row }">
          <span class="font-mono font-bold text-emerald-700">{{ row.realization_amount > 0 ? formatRupiah(row.realization_amount) : '-' }}</span>
        </template>

        <!-- Action Column -->
        <template #action="{ row }">
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
        </template>
      </DataTable>

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
