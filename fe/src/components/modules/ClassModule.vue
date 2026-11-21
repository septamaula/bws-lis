<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Calendar,
  Plus,
  Users,
  CheckCircle,
  Clock,
  ShieldCheck,
  MapPin,
  Scale,
  Award,
  BookOpen,
  Eye,
  Filter,
  RotateCcw
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import apiClient from '@/api/client'
import ClassCreatePage from './classes/ClassCreatePage.vue'
import ClassDetailPage from './classes/ClassDetailPage.vue'

const props = defineProps({
  trainingClasses: {
    type: Array,
    default: () => []
  },
  isCheckerRole: {
    type: Boolean,
    default: false
  },
  canCreateClass: {
    type: Boolean,
    default: true
  },
  viewMode: {
    type: String,
    default: 'list'
  }
})

const emit = defineEmits(['open-class-modal', 'approve-class', 'update:viewMode'])

// Sub-View Modes: 'list' | 'create' | 'detail'
const internalClassViewMode = ref('list')
const classViewMode = computed({
  get: () => props.viewMode !== undefined && props.viewMode !== null ? props.viewMode : internalClassViewMode.value,
  set: (val) => {
    internalClassViewMode.value = val
    emit('update:viewMode', val)
  }
})
const selectedClassId = ref(null)
const preselectedProgramId = ref(null)

const loading = ref(false)
const classes = ref([])
const programs = ref([])
const stats = ref({
  total_classes: 0,
  active_classes: 0,
  pending_checker: 0,
  completed_classes: 0,
  total_enrolled: 0
})

// Filters
const filterProgramId = ref('')
const filterStatus = ref('')
const filterMethod = ref('')

async function fetchClasses() {
  loading.value = true
  try {
    const params = {}
    if (filterProgramId.value) params.program_id = filterProgramId.value
    if (filterStatus.value) params.dual_control_status = filterStatus.value
    if (filterMethod.value) params.method = filterMethod.value

    const { data } = await apiClient.get('/classes', { params })
    classes.value = data.classes || []
    stats.value = data.stats || stats.value
  } catch (err) {
    console.error('Failed to fetch classes:', err)
  } finally {
    loading.value = false
  }
}

async function fetchPrograms() {
  try {
    const { data } = await apiClient.get('/programs')
    programs.value = data.programs || []
  } catch (err) {
    console.error('Failed to fetch programs:', err)
  }
}

function openCreate(programId = null) {
  preselectedProgramId.value = programId
  classViewMode.value = 'create'
}

function openDetail(id) {
  selectedClassId.value = id
  classViewMode.value = 'detail'
}

function handleClassCreated(msg) {
  classViewMode.value = 'list'
  fetchClasses()
}

const classColumns = [
  { key: 'class_code', label: 'Batch & Program Diklat', sortable: true },
  { key: 'start_date', label: 'Jadwal & Metode', sortable: true, width: '180px' },
  { key: 'trainer_name', label: 'Instruktur / Trainer', sortable: true, width: '180px' },
  { key: 'location_or_url', label: 'Lokasi / Ruang', sortable: true },
  {
    key: 'max_quota',
    label: 'Kapasitas Kuota',
    sortable: true,
    align: 'center',
    width: '140px',
    format: (val, row) => `${row.enrolled_count || 0} / ${val} Peserta`
  },
  { key: 'dual_control_status', label: 'Status Dual-Control', sortable: true, align: 'center', width: '150px' }
]

onMounted(() => {
  fetchClasses()
  fetchPrograms()
})

defineExpose({
  openCreate,
  openDetail,
  fetchClasses
})
</script>

<template>
  <div>

    <!-- Sub-View: Create Class Form -->
    <ClassCreatePage
      v-if="classViewMode === 'create'"
      :initial-program-id="preselectedProgramId"
      @back="classViewMode = 'list'"
      @created="handleClassCreated"
    />

    <!-- Sub-View: Class Detail Page (Overview, Attendance, Evaluation, Roster) -->
    <ClassDetailPage
      v-else-if="classViewMode === 'detail' && selectedClassId"
      :class-id="selectedClassId"
      @back="classViewMode = 'list'"
      @refresh-list="fetchClasses"
    />

    <!-- Sub-View: Main Classes List View -->
    <div v-else class="flex flex-col gap-5">
      
      <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
            <Calendar class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Batch Terdaftar</p>
            <p class="text-xl font-extrabold text-gray-900">{{ classes.length }} Batch</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
            <Scale class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Antrean Checker</p>
            <p class="text-xl font-extrabold text-amber-600 font-mono">{{ stats.pending_checker }} Menunggu</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Peserta Terdaftar</p>
            <p class="text-xl font-extrabold text-gray-900">{{ stats.total_enrolled }} Pegawai</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <ShieldCheck class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Batch Terotorisasi</p>
            <p class="text-xl font-extrabold text-emerald-700">{{ stats.active_classes }} Aktif</p>
          </div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
          <div class="sm:col-span-2">
            <select
              v-model="filterProgramId"
              @change="fetchClasses"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
            >
              <option value="">-- Seluruh Program Pelatihan --</option>
              <option v-for="p in programs" :key="p.id" :value="p.id">
                [{{ p.program_code }}] {{ p.title }}
              </option>
            </select>
          </div>

          <div>
            <select
              v-model="filterStatus"
              @change="fetchClasses"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
            >
              <option value="">-- Seluruh Status Otorisasi --</option>
              <option value="APPROVED">Terotorisasi (APPROVED)</option>
              <option value="PENDING_CHECKER">Menunggu Checker</option>
              <option value="ONGOING">Sedang Berjalan (ONGOING)</option>
              <option value="COMPLETED">Selesai (COMPLETED)</option>
            </select>
          </div>

          <div>
            <select
              v-model="filterMethod"
              @change="fetchClasses"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
            >
              <option value="">-- Seluruh Metode Diklat --</option>
              <option value="In-Classroom">In-Classroom</option>
              <option value="Virtual">Virtual Classroom</option>
              <option value="Hybrid">Hybrid</option>
              <option value="E-Learning">ELAPSE E-Learning</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Training Classes Standard Table Component -->
      <DataTable
        :data="classes"
        :columns="classColumns"
        date-key="start_date"
        export-filename="jadwal_batch_kelas_diklat_bws_2026"
        :default-per-page="10"
      >

        <!-- Custom Cell: Batch & Program -->
        <template #cell-class_code="{ row }">
          <div class="cursor-pointer" @click="openDetail(row.id)">
            <p class="font-bold text-xs text-gray-900 leading-tight hover:text-[#0067AC] transition">
              {{ row.program?.title || 'Batch Pelatihan' }}
            </p>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span class="font-mono text-[11px] font-bold text-[#0067AC]">{{ row.class_code }}</span>
              <span class="text-gray-300">•</span>
              <span class="text-[11px] font-extrabold text-gray-800">{{ row.batch_name }}</span>
            </div>
            <p class="text-[10px] text-gray-400 mt-0.5">
              Maker: {{ row.maker?.name || 'HC Training Officer' }}
            </p>
          </div>
        </template>

        <!-- Custom Cell: Schedule -->
        <template #cell-start_date="{ row }">
          <p class="font-semibold text-xs text-gray-800">{{ row.start_date?.substring(0, 10) }} s/d {{ row.end_date?.substring(0, 10) }}</p>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class="text-[10px] px-2 py-0.2 rounded-full bg-blue-50 text-[#0067AC] font-bold">
              {{ row.method || 'Hybrid' }}
            </span>
            <span class="text-[10px] text-gray-400 font-mono">{{ row.daily_schedule || '08:30 WIB' }}</span>
          </div>
        </template>

        <!-- Custom Cell: Trainer -->
        <template #cell-trainer_name="{ row }">
          <p class="font-bold text-xs text-gray-800">{{ row.trainer_name || row.facilitator?.name || 'Tim Trainer BWS' }}</p>
          <p class="text-[10px] text-gray-400">{{ row.sessions_count || 0 }} Sesi Terjadwal</p>
        </template>

        <!-- Custom Cell: Location -->
        <template #cell-location_or_url="{ row }">
          <div class="flex items-center gap-1 text-gray-700">
            <MapPin class="w-3.5 h-3.5 text-rose-500 shrink-0" />
            <span class="text-xs truncate max-w-[200px]" :title="row.location_or_url">{{ row.location_or_url || 'BWS Learning Center' }}</span>
          </div>
        </template>

        <!-- Custom Cell: Dual-Control Status -->
        <template #cell-dual_control_status="{ row }">
          <span
            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
            :class="row.dual_control_status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700' : row.dual_control_status === 'PENDING_CHECKER' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'"
          >
            <span
              class="w-1.5 h-1.5 rounded-full shrink-0"
              :class="row.dual_control_status === 'APPROVED' ? 'bg-emerald-600' : row.dual_control_status === 'PENDING_CHECKER' ? 'bg-amber-500' : 'bg-blue-600'"
            ></span>
            {{ row.dual_control_status === 'APPROVED' ? 'Disetujui Checker' : row.dual_control_status === 'PENDING_CHECKER' ? 'Menunggu Checker' : row.dual_control_status }}
          </span>
        </template>

        <!-- Action Column Slot -->
        <template #action="{ row }">
          <div class="flex items-center gap-1.5">
            <button
              @click="openDetail(row.id)"
              class="px-3 py-1.5 rounded-full bg-[#EBF4FC] hover:bg-blue-100 text-[#0067AC] text-xs font-bold transition flex items-center gap-1 cursor-pointer"
              title="Kelola Batch, Presensi &amp; Evaluasi"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Kelola &amp; Presensi</span>
            </button>
          </div>
        </template>
      </DataTable>

    </div>

  </div>
</template>
