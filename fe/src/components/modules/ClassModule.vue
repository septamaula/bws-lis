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
  Pencil,
  Trash2,
  Filter,
  RotateCcw,
  Presentation,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import apiClient from '@/api/client'
import ClassCreatePage from './classes/ClassCreatePage.vue'
import ClassDetailPage from './classes/ClassDetailPage.vue'
import { useI18n } from '@/i18n'

const { l, locale } = useI18n()

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

// Sub-View Modes: 'list' | 'create' | 'edit' | 'detail'
const internalClassViewMode = ref('list')
const classViewMode = computed({
  get: () => props.viewMode !== undefined && props.viewMode !== null ? props.viewMode : internalClassViewMode.value,
  set: (val) => {
    internalClassViewMode.value = val
    emit('update:viewMode', val)
  }
})
const selectedClassId = ref(null)
const selectedEditClassId = ref(null)
const preselectedProgramId = ref(null)

const loading = ref(false)
const classes = ref([])
const programs = ref([])
const tnaList = ref([])
const feedbackMessage = ref('')
const feedbackType = ref('success')

// Summary statistics
const stats = ref({
  total_classes: 0,
  active_classes: 0,
  pending_checker: 0,
  completed_classes: 0,
  total_enrolled: 0
})

// Filters
const filterTnaId = ref('')
const filterProgramId = ref('')
const filterStatus = ref('')
const filterMethod = ref('')

// Delete Modal State
const showDeleteModal = ref(false)
const classToDelete = ref(null)
const isDeleting = ref(false)

// Programs filtered by selected TNA for dropdown
const availableFilteredPrograms = computed(() => {
  if (!filterTnaId.value) return programs.value
  return programs.value.filter(p => Number(p.tna_proposal_id) === Number(filterTnaId.value))
})

const tnaOptions = computed(() => [
  { value: '', label: l('-- All TNA Proposals --', '-- Seluruh Usulan TNA --') },
  ...tnaList.value.map(t => ({
    value: t.id,
    label: `[${t.document_number || 'TNA-' + t.year}] ${t.title || 'TNA ' + t.year} (${t.tna_status === 'Closed' ? l('Closed', 'Ditutup') : l('Active', 'Aktif')})`
  }))
])

const programOptions = computed(() => [
  { value: '', label: l('-- All Training Programs --', '-- Seluruh Program Pelatihan --') },
  ...availableFilteredPrograms.value.map(p => ({
    value: p.id,
    label: `[${p.program_code}] ${p.title}`
  }))
])

const statusOptions = computed(() => [
  { value: '', label: l('-- All Authorization Statuses --', '-- Seluruh Status Otorisasi --') },
  { value: 'APPROVED', label: l('Approved by Checker', 'Disetujui Verifikator') },
  { value: 'PENDING_CHECKER', label: l('Pending Checker', 'Menunggu Verifikator') },
  { value: 'ONGOING', label: l('Ongoing', 'Sedang Berjalan') },
  { value: 'COMPLETED', label: l('Completed', 'Selesai') }
])

const methodOptions = computed(() => [
  { value: '', label: l('-- All Methods --', '-- Seluruh Metode --') },
  { value: 'In-Classroom', label: l('In-Classroom', 'Tatap Muka (Kelas)') },
  { value: 'Virtual', label: l('Virtual Classroom', 'Kelas Virtual') },
  { value: 'Hybrid', label: l('Hybrid', 'Kombinasi (Hybrid)') },
  { value: 'E-Learning', label: l('ELAPSE E-Learning', 'Pembelajaran Daring ELAPSE') }
])

// Formatted data list for DataTable searching and sorting
const formattedClasses = computed(() => {
  return classes.value.map(c => ({
    ...c,
    program_code: c.program?.program_code || '-',
    program_title: c.program?.title || '-',
    pic_name: c.pic_name || c.maker?.name || 'PIC BWS',
    trainer_name: c.trainer_name || c.facilitator?.name || '-'
  }))
})

// Columns matching SS2 Excel Wireframe:
// Class ID | Program ID | PIC | Program | Start Date | End Date | Location | Facilitator | Action
const classColumns = computed(() => [
  { key: 'class_code', label: l('Class ID', 'Kode Kelas'), sortable: true, width: '130px', align: 'center' },
  { key: 'program_code', label: l('Program ID', 'Kode Program'), sortable: true, width: '130px', align: 'center' },
  { key: 'pic_name', label: l('PIC', 'Penanggung Jawab'), sortable: true, width: '140px' },
  { key: 'program_title', label: l('Program', 'Program'), sortable: true },
  { key: 'start_date', label: l('Start Date', 'Tanggal Mulai'), sortable: true, width: '120px', align: 'center' },
  { key: 'end_date', label: l('End Date', 'Tanggal Selesai'), sortable: true, width: '120px', align: 'center' },
  { key: 'location_or_url', label: l('Location', 'Lokasi'), sortable: true, width: '160px' },
  { key: 'trainer_name', label: l('Facilitator', 'Fasilitator'), sortable: true, width: '160px' }
])

function formatDateShort(val) {
  if (!val) return '-'
  try {
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    const day = String(d.getDate()).padStart(2, '0')
    const months = locale.value === 'en'
      ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      : ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    const month = months[d.getMonth()]
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
  } catch (e) {
    return val
  }
}

async function fetchClasses() {
  loading.value = true
  try {
    const params = {}
    if (filterTnaId.value) params.tna_id = filterTnaId.value
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

async function fetchTnaList() {
  try {
    const { data } = await apiClient.get('/tna')
    tnaList.value = data.proposals || []
  } catch (err) {
    console.error('Failed to fetch TNA list:', err)
  }
}

function onFilterTnaChange() {
  filterProgramId.value = ''
  fetchClasses()
}

function resetFilters() {
  filterTnaId.value = ''
  filterProgramId.value = ''
  filterStatus.value = ''
  filterMethod.value = ''
  fetchClasses()
}

function openCreate(programId = null) {
  selectedEditClassId.value = null
  preselectedProgramId.value = programId
  classViewMode.value = 'create'
}

function openEdit(id) {
  selectedEditClassId.value = id
  preselectedProgramId.value = null
  classViewMode.value = 'edit'
}

function openDetail(id) {
  selectedClassId.value = id
  classViewMode.value = 'detail'
}

function handleBackFromForm() {
  selectedEditClassId.value = null
  preselectedProgramId.value = null
  classViewMode.value = 'list'
}

function handleClassCreated(msg) {
  selectedEditClassId.value = null
  preselectedProgramId.value = null
  classViewMode.value = 'list'
  feedbackType.value = 'success'
  feedbackMessage.value = msg || l('Training class batch has been saved!', 'Angkatan kelas pelatihan berhasil disimpan!')
  fetchClasses()
}

function confirmDelete(cls) {
  classToDelete.value = cls
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!classToDelete.value) return
  isDeleting.value = true
  try {
    await apiClient.delete(`/classes/${classToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMessage.value = l(
      `Class ${classToDelete.value.class_code} has been deleted.`,
      `Kelas ${classToDelete.value.class_code} berhasil dihapus.`
    )
    showDeleteModal.value = false
    classToDelete.value = null
    fetchClasses()
  } catch (err) {
    console.error('Failed to delete class:', err)
    feedbackType.value = 'error'
    feedbackMessage.value = err.response?.data?.message || l('Failed to delete class batch.', 'Gagal menghapus angkatan kelas.')
    showDeleteModal.value = false
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  fetchClasses()
  fetchPrograms()
  fetchTnaList()
})

defineExpose({
  openCreate,
  openEdit,
  openDetail,
  fetchClasses
})
</script>

<template>
  <div>

    <!-- Sub-View: Create or Edit Class Form (SS2 Layout) -->
    <ClassCreatePage
      v-if="classViewMode === 'create' || classViewMode === 'edit'"
      :initial-program-id="preselectedProgramId"
      :edit-class-id="selectedEditClassId"
      @back="handleBackFromForm"
      @created="handleClassCreated"
    />

    <!-- Sub-View: Class Detail Page (Overview, Attendance, Evaluation, Roster) -->
    <ClassDetailPage
      v-else-if="classViewMode === 'detail' && selectedClassId"
      :class-id="selectedClassId"
      @back="classViewMode = 'list'"
      @refresh-list="fetchClasses"
    />

    <!-- Sub-View: Main Classes List View (SS2 Grid Table) -->
    <div v-else class="flex flex-col gap-5">

      <!-- Feedback Toast Banner -->
      <div
        v-if="feedbackMessage"
        :class="[
          'p-4 rounded-2xl flex items-center justify-between gap-3 text-xs font-semibold shadow-2xs border transition',
          feedbackType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'
        ]"
      >
        <div class="flex items-center gap-2.5">
          <CheckCircle2 v-if="feedbackType !== 'error'" class="w-5 h-5 text-emerald-600 shrink-0" />
          <AlertCircle v-else class="w-5 h-5 text-rose-600 shrink-0" />
          <span>{{ feedbackMessage }}</span>
        </div>
        <button
          @click="feedbackMessage = ''"
          class="text-gray-400 hover:text-gray-700 text-xs font-bold p-1 cursor-pointer"
        >
          ✕
        </button>
      </div>

      <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
            <Presentation class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Registered Batches', 'Angkatan Terdaftar') }}</p>
            <p class="text-xl font-extrabold text-gray-900">{{ classes.length }} {{ $l(classes.length === 1 ? 'Batch' : 'Batches', 'Angkatan') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
            <Scale class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Checker Queue', 'Antrean Verifikasi') }}</p>
            <p class="text-xl font-extrabold text-amber-600 font-mono">{{ stats.pending_checker }} {{ $l('Pending', 'Menunggu') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Enrolled Participants', 'Peserta Terdaftar') }}</p>
            <p class="text-xl font-extrabold text-gray-900">{{ stats.total_enrolled }} {{ $l('Employees', 'Pegawai') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <ShieldCheck class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Authorized Batches', 'Angkatan Terotorisasi') }}</p>
            <p class="text-xl font-extrabold text-emerald-700">{{ stats.active_classes }} {{ $l('Active', 'Aktif') }}</p>
          </div>
        </div>
      </div>

      <!-- Filters Strip -->
      <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
          <!-- Filter TNA -->
          <div>
            <label class="block text-[11px] font-bold text-gray-600 mb-1">{{ $l('TNA Proposal Filter', 'Filter Usulan TNA') }}</label>
            <AppSearchableSelect
              v-model="filterTnaId"
              :options="tnaOptions"
              :placeholder="$l('-- All TNA Proposals --', '-- Seluruh Usulan TNA --')"
              @change="onFilterTnaChange"
            />
          </div>

          <!-- Filter Program -->
          <div>
            <label class="block text-[11px] font-bold text-gray-600 mb-1">{{ $l('Work Program Filter', 'Filter Program Kerja') }}</label>
            <AppSearchableSelect
              v-model="filterProgramId"
              :options="programOptions"
              :placeholder="$l('-- All Training Programs --', '-- Seluruh Program Pelatihan --')"
              @change="fetchClasses"
            />
          </div>

          <!-- Filter Dual-Control Status -->
          <div>
            <label class="block text-[11px] font-bold text-gray-600 mb-1">{{ $l('Authorization Status Filter', 'Filter Status Otorisasi') }}</label>
            <AppSearchableSelect
              v-model="filterStatus"
              :options="statusOptions"
              :placeholder="$l('-- All Authorization Statuses --', '-- Seluruh Status Otorisasi --')"
              @change="fetchClasses"
            />
          </div>

          <!-- Filter Metode & Reset -->
          <div>
            <label class="block text-[11px] font-bold text-gray-600 mb-1">{{ $l('Training Method', 'Metode Pelatihan') }}</label>
            <div class="flex items-center gap-2">
              <AppSearchableSelect
                v-model="filterMethod"
                :options="methodOptions"
                :placeholder="$l('-- All Methods --', '-- Seluruh Metode --')"
                class="flex-1"
                @change="fetchClasses"
              />
              <button
                v-if="filterTnaId || filterProgramId || filterStatus || filterMethod"
                @click="resetFilters"
                type="button"
                class="p-2 rounded-xl text-gray-400 hover:text-[#0067AC] hover:bg-blue-50 transition cursor-pointer shrink-0"
                :title="$l('Reset Filters', 'Atur Ulang Filter')"
              >
                <RotateCcw class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Training Classes SS2 Standard DataTable Component -->
      <DataTable
        :data="formattedClasses"
        :columns="classColumns"
        date-key="start_date"
        export-filename="daftar_batch_kelas_diklat_bws_2026"
        :default-per-page="10"
      >

        <!-- SS2 Column 1: Class ID -->
        <template #cell-class_code="{ row }">
          <div class="flex flex-col items-center">
            <span
              @click="openDetail(row.id)"
              class="font-mono font-bold text-xs text-[#0067AC] bg-blue-50/80 px-2.5 py-0.5 rounded-md border border-blue-100 hover:bg-blue-100 transition cursor-pointer"
              :title="$l('Click for class details', 'Klik untuk detail kelas')"
            >
              {{ row.class_code }}
            </span>
            <span class="text-[10px] text-gray-500 font-medium mt-0.5">{{ row.batch_name }}</span>
          </div>
        </template>

        <!-- SS2 Column 2: Program ID -->
        <template #cell-program_code="{ row }">
          <div class="flex flex-col items-center">
            <span class="font-mono text-xs font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded-md border border-gray-200">
              {{ row.program_code }}
            </span>
          </div>
        </template>

        <!-- SS2 Column 3: PIC -->
        <template #cell-pic_name="{ row }">
          <div class="flex items-center gap-1.5">
            <span class="font-semibold text-xs text-gray-800">{{ row.pic_name }}</span>
          </div>
        </template>

        <!-- SS2 Column 4: Program -->
        <template #cell-program_title="{ row }">
          <div class="cursor-pointer max-w-[280px]" @click="openDetail(row.id)">
            <p class="font-bold text-xs text-gray-900 leading-snug hover:text-[#0067AC] transition truncate" :title="row.program_title">
              {{ row.program_title }}
            </p>
            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-gray-400">
              <span class="px-1.5 py-0.2 rounded bg-gray-50 text-gray-600 border border-gray-100 font-medium">
                {{ row.method || 'In-Classroom' }}
              </span>
              <span>•</span>
              <span class="font-medium text-gray-600">{{ row.enrolled_count || 0 }} {{ $l('Participants', 'Peserta') }}</span>
            </div>
          </div>
        </template>

        <!-- SS2 Column 5: Start Date -->
        <template #cell-start_date="{ row }">
          <span class="font-medium text-xs text-gray-700 font-mono">
            {{ formatDateShort(row.start_date) }}
          </span>
        </template>

        <!-- SS2 Column 6: End Date -->
        <template #cell-end_date="{ row }">
          <span class="font-medium text-xs text-gray-700 font-mono">
            {{ formatDateShort(row.end_date) }}
          </span>
        </template>

        <!-- SS2 Column 7: Location -->
        <template #cell-location_or_url="{ row }">
          <div class="flex items-center gap-1.5 text-gray-700 max-w-[180px]">
            <MapPin class="w-3.5 h-3.5 text-rose-500 shrink-0" />
            <span class="text-xs truncate font-medium" :title="row.location_or_url">
              {{ row.location_or_url || $l('Room 1', 'Ruang 1') }}
            </span>
          </div>
        </template>

        <!-- SS2 Column 8: Facilitator -->
        <template #cell-trainer_name="{ row }">
          <div class="text-xs font-semibold text-gray-800 truncate max-w-[180px]" :title="row.trainer_name">
            {{ row.trainer_name }}
          </div>
        </template>

        <!-- SS2 Column 9: Action (View / Edit / Delete) -->
        <template #action="{ row }">
          <div class="flex items-center justify-end gap-1.5">
            <!-- View / Detail -->
            <button
              type="button"
              @click="openDetail(row.id)"
              class="px-2.5 py-1 rounded-lg bg-blue-50 text-[#0067AC] hover:bg-blue-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
              :title="$l('View Details, Attendance & Evaluation', 'Lihat Detail, Presensi & Evaluasi')"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>{{ $l('Details', 'Rincian') }}</span>
            </button>

            <!-- Edit -->
            <button
              type="button"
              @click="openEdit(row.id)"
              class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
              :title="$l('Edit Class Batch', 'Ubah Angkatan Kelas')"
            >
              <Pencil class="w-3.5 h-3.5" />
              <span>{{ $t('common.edit') }}</span>
            </button>

            <!-- Delete -->
            <button
              type="button"
              @click="confirmDelete(row)"
              class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
              :title="$l('Delete Class', 'Hapus Kelas')"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>{{ $t('common.delete') }}</span>
            </button>
          </div>
        </template>
      </DataTable>

    </div>

    <!-- Delete Confirmation Modal (Standard BWS Modal) -->
    <div
      v-if="showDeleteModal && classToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform transition-all animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3 text-rose-600 mb-4">
          <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center shrink-0">
            <Trash2 class="w-6 h-6 text-rose-600" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900 text-base">{{ $l('Delete Class Batch', 'Hapus Angkatan Kelas') }}</h3>
            <p class="text-xs text-gray-500">{{ $l('Confirm class cancellation and deletion', 'Konfirmasi pembatalan dan penghapusan data kelas') }}</p>
          </div>
        </div>

        <div class="bg-gray-50 rounded-2xl p-4 mb-4 border border-gray-100 space-y-2 text-xs">
          <div class="flex justify-between items-center">
            <span class="text-gray-400">{{ $l('Class ID:', 'Kode Kelas:') }}</span>
            <span class="font-mono font-bold text-[#0067AC]">{{ classToDelete.class_code }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-400">{{ $l('Batch:', 'Angkatan:') }}</span>
            <span class="font-bold text-gray-800">{{ classToDelete.batch_name }}</span>
          </div>
          <div class="flex justify-between items-start">
            <span class="text-gray-400">{{ $l('Program:', 'Program:') }}</span>
            <span class="font-bold text-gray-800 text-right max-w-[200px] truncate" :title="classToDelete.program?.title">
              {{ classToDelete.program?.title || '-' }}
            </span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-400">{{ $l('Schedule:', 'Jadwal:') }}</span>
            <span class="font-medium text-gray-700 font-mono">
              {{ formatDateShort(classToDelete.start_date) }} {{ $l('to', 's/d') }} {{ formatDateShort(classToDelete.end_date) }}
            </span>
          </div>
        </div>

        <div class="bg-rose-50 text-rose-700 p-3 rounded-2xl border border-rose-100 text-xs mb-6 leading-relaxed">
          <template v-if="$locale === 'en'">
            <strong>Warning:</strong> All session agendas, learning materials, photo documentation, and participant records for this class will be permanently deleted.
          </template>
          <template v-else>
            <strong>Peringatan:</strong> Seluruh data sesi agenda, dokumen materi, dokumentasi foto, dan riwayat peserta pada kelas ini akan ikut terhapus permanen.
          </template>
        </div>

        <div class="flex items-center justify-end gap-2.5">
          <button
            type="button"
            @click="showDeleteModal = false; classToDelete = null"
            :disabled="isDeleting"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 transition cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="button"
            @click="executeDelete"
            :disabled="isDeleting"
            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <span v-if="isDeleting">{{ $l('Deleting...', 'Menghapus...') }}</span>
            <span v-else>{{ $l('Yes, Delete Class', 'Ya, Hapus Kelas') }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
