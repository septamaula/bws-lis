<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  TrendingUp,
  Award,
  CheckCircle2,
  HelpCircle,
  FileCheck,
  Star,
  BookOpen,
  Calendar,
  Filter,
  RotateCcw,
  Edit2,
  Eye,
  Check,
  Download,
  Plus,
  X,
  Save,
  Search,
  AlertCircle
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import apiClient from '@/api/client'
import { useI18n } from '@/i18n'

const { l, t } = useI18n()

const props = defineProps({
  enrollments: {
    type: Array,
    default: () => []
  }
})

const loading = ref(false)
const evaluationRecords = ref([])
const programs = ref([])
const classes = ref([])
const stats = ref({
  avg_pre_test: 62,
  avg_post_test: 86.8,
  competency_gain_pct: 44.7,
  pass_rate_pct: 92.5,
  avg_instructor_rating: 4.85,
  total_evaluated: 0
})

// Filters
const filterProgramId = ref('')
const filterClassId = ref('')
const filterStatus = ref('')
const flashSuccess = ref('')

// Add / Edit Evaluation Modal State
const showEvalModal = ref(false)
const modalClassId = ref('')
const modalLoading = ref(false)
const modalSaving = ref(false)
const modalError = ref('')
const modalClassInfo = ref(null)
const modalParticipants = ref([])
const modalSearchParticipant = ref('')
const scoreRecords = ref({})

async function fetchEvaluations() {
  loading.value = true
  try {
    const params = {}
    if (filterProgramId.value) params.program_id = filterProgramId.value
    if (filterClassId.value) params.class_id = filterClassId.value
    if (filterStatus.value) params.completion_status = filterStatus.value

    const { data } = await apiClient.get('/evaluations/overview', { params })
    evaluationRecords.value = data.evaluation_records || []
    stats.value = data.stats || stats.value
    programs.value = data.programs || []
    classes.value = data.classes || []
  } catch (err) {
    console.error('Failed to fetch evaluations:', err)
  } finally {
    loading.value = false
  }
}

const filteredClasses = computed(() => {
  if (!filterProgramId.value) return classes.value
  return classes.value.filter(c => c.program_id === parseInt(filterProgramId.value))
})

const programFilterOptions = computed(() => [
  { value: '', label: l('-- All Training Programs --', '-- Seluruh Program Pelatihan --') },
  ...programs.value.map(p => ({
    value: p.id,
    label: `[${p.program_code}] ${p.title}`
  }))
])

const classFilterOptions = computed(() => [
  { value: '', label: l('-- All Class Batches --', '-- Seluruh Angkatan Kelas --') },
  ...filteredClasses.value.map(c => ({
    value: c.id,
    label: `[${c.class_code}] ${c.batch_name}`
  }))
])

const statusFilterOptions = computed(() => [
  { value: '', label: l('-- All Completion Statuses --', '-- Seluruh Status Kelulusan --') },
  { value: 'PASSED', label: l('Passed', 'Lulus') },
  { value: 'FAILED', label: l('Failed', 'Tidak Lulus') },
  { value: 'IN_PROGRESS', label: l('In Progress / Pending Exam', 'Dalam Proses / Belum Ujian') }
])

const evalColumns = computed(() => [
  { key: 'name', label: l('Participant & NIK', 'Peserta & NIK'), sortable: true },
  { key: 'program_title', label: l('Program & Class Batch', 'Program & Angkatan Kelas'), sortable: true },
  { key: 'department', label: l('Unit / Division', 'Unit / Divisi'), sortable: true, width: '150px' },
  {
    key: 'attendance_pct',
    label: l('Attendance', 'Presensi'),
    sortable: true,
    align: 'center',
    width: '90px',
    format: (val) => `${val || 0}%`
  },
  {
    key: 'pre_score',
    label: l('Pre-Test', 'Tes Awal'),
    sortable: true,
    align: 'center',
    width: '95px',
    format: (val) => val ? `${val} / 100` : '-'
  },
  {
    key: 'post_score',
    label: l('Post-Test', 'Tes Akhir'),
    sortable: true,
    align: 'center',
    width: '95px',
    format: (val) => val ? `${val} / 100` : '-'
  },
  {
    key: 'delta_pct',
    label: l('Gain (Δ%)', 'Peningkatan (Δ%)'),
    sortable: true,
    align: 'center',
    width: '100px'
  },
  { key: 'status', label: l('Completion Result', 'Hasil Kelulusan'), sortable: true, align: 'center', width: '130px' },
  { key: 'instructor_rating', label: l('Trainer Rating', 'Nilai Pengajar'), sortable: true, align: 'center', width: '120px' },
  { key: 'certificate_number', label: l('E-Certificate', 'E-Sertifikat'), sortable: true, width: '160px' },
  { key: 'actions', label: l('Action', 'Aksi'), sortable: false, align: 'center', width: '90px' }
])

const modalClassOptions = computed(() =>
  classes.value.map(c => {
    const prog = programs.value.find(p => p.id === c.program_id)
    return {
      value: c.id,
      label: `[${c.class_code}] ${c.batch_name}`,
      subLabel: prog ? `${prog.program_code} • ${prog.title}` : ''
    }
  })
)

const ratingSelectOptions = [
  { value: 5, label: '5 ★' },
  { value: 4, label: '4 ★' },
  { value: 3, label: '3 ★' },
  { value: 2, label: '2 ★' },
  { value: 1, label: '1 ★' }
]

const filteredModalParticipants = computed(() => {
  if (!modalSearchParticipant.value.trim()) return modalParticipants.value
  const q = modalSearchParticipant.value.toLowerCase()
  return modalParticipants.value.filter(p =>
    p.name?.toLowerCase().includes(q) ||
    p.nik?.toLowerCase().includes(q) ||
    p.department?.toLowerCase().includes(q)
  )
})

async function openAddEvalModal(preselectedClassId = '', searchParticipantNik = '') {
  showEvalModal.value = true
  modalError.value = ''
  modalSearchParticipant.value = searchParticipantNik || ''
  const targetId = preselectedClassId || filterClassId.value || (classes.value[0]?.id ?? '')
  modalClassId.value = targetId
  if (targetId) {
    await loadClassEvaluation(targetId)
  } else {
    modalClassInfo.value = null
    modalParticipants.value = []
    scoreRecords.value = {}
  }
}

async function loadClassEvaluation(classId) {
  if (!classId) {
    modalClassInfo.value = null
    modalParticipants.value = []
    scoreRecords.value = {}
    return
  }
  modalLoading.value = true
  modalError.value = ''
  try {
    const { data } = await apiClient.get(`/classes/${classId}/evaluation`)
    modalClassInfo.value = {
      class_id: data.class_id,
      class_code: data.class_code,
      batch_name: data.batch_name,
      program_title: data.program_title,
      passing_grade: data.passing_grade ?? 75
    }
    modalParticipants.value = data.participants || []
    const map = {}
    modalParticipants.value.forEach(p => {
      map[p.id] = {
        enrollment_id: p.id,
        pre_test_score: p.pre_test_score ?? '',
        post_test_score: p.post_test_score ?? '',
        task_score: p.task_score ?? '',
        eval_instructor_rating: p.eval_instructor_rating ?? 5,
        eval_material_rating: p.eval_material_rating ?? 5,
        eval_facility_rating: p.eval_facility_rating ?? 5,
        eval_notes: p.eval_notes ?? ''
      }
    })
    scoreRecords.value = map
  } catch (err) {
    modalError.value = err.response?.data?.message || l('Failed to load class evaluation participants.', 'Gagal memuat daftar peserta evaluasi kelas.')
  } finally {
    modalLoading.value = false
  }
}

async function saveEvaluationScores() {
  if (!modalClassId.value || modalParticipants.value.length === 0) return
  modalSaving.value = true
  modalError.value = ''
  try {
    const scoresPayload = Object.values(scoreRecords.value).map(r => ({
      enrollment_id: r.enrollment_id,
      pre_test_score: r.pre_test_score === '' ? null : Number(r.pre_test_score),
      post_test_score: r.post_test_score === '' ? null : Number(r.post_test_score),
      task_score: r.task_score === '' ? null : Number(r.task_score),
      eval_instructor_rating: Number(r.eval_instructor_rating || 5),
      eval_material_rating: Number(r.eval_material_rating || 5),
      eval_facility_rating: Number(r.eval_facility_rating || 5),
      eval_notes: r.eval_notes || null
    }))
    await apiClient.post(`/classes/${modalClassId.value}/evaluation/scores`, {
      scores: scoresPayload
    })
    flashSuccess.value = l('Participant scores and evaluations have been saved.', 'Nilai dan evaluasi peserta berhasil disimpan.')
    showEvalModal.value = false
    await fetchEvaluations()
  } catch (err) {
    modalError.value = err.response?.data?.message || l('Failed to save participant evaluation data.', 'Gagal menyimpan data evaluasi peserta.')
  } finally {
    modalSaving.value = false
  }
}

onMounted(() => {
  fetchEvaluations()
})
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <HelpCircle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Average Pre-Test', 'Rata-rata Tes Awal') }}</p>
          <p class="text-xl font-extrabold text-gray-900 font-mono">{{ stats.avg_pre_test }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Average Post-Test', 'Rata-rata Tes Akhir') }}</p>
          <p class="text-xl font-extrabold text-emerald-700 font-mono">{{ stats.avg_post_test }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <TrendingUp class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Competency Gain', 'Peningkatan Kompetensi') }}</p>
          <p class="text-xl font-extrabold text-[#0067AC] font-mono">+{{ stats.competency_gain_pct }}%</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold">
          <Award class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Pass Rate', 'Tingkat Kelulusan') }}</p>
          <p class="text-xl font-extrabold text-teal-800 font-mono">{{ stats.pass_rate_pct }}%</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Star class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Instructor Rating', 'Penilaian Pengajar') }}</p>
          <p class="text-xl font-extrabold text-purple-700 font-mono">{{ stats.avg_instructor_rating }} <span class="text-xs text-gray-400 font-normal">/ 5.0</span></p>
        </div>
      </div>
    </div>

    <!-- Flash Success Banner -->
    <div
      v-if="flashSuccess"
      class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-xs font-semibold"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ flashSuccess }}</span>
      </div>
      <button @click="flashSuccess = ''" class="text-emerald-600 hover:text-emerald-800 cursor-pointer">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Header & Interactive Filter Bar -->
    <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-2xs space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC] uppercase tracking-wider">
              {{ $l('Learning Assessment & Growth Analytics', 'Evaluasi Pembelajaran & Analisis Kompetensi') }}
            </span>
            <span class="text-xs text-gray-400">• {{ $l('BWS LIS Competency Matrix', 'Matriks Kompetensi BWS LIS') }}</span>
          </div>
          <h2 class="text-lg font-extrabold text-[#002855] mt-1">
            {{ $l('Pre-Test, Post-Test & Delivery Evaluation Summary', 'Rekapitulasi Nilai Tes Awal, Tes Akhir & Evaluasi Penyelenggaraan') }}
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ $l('Monitoring banking training effectiveness under Kirkpatrick Level 1 (Reaction/Rating) & Level 2 (Learning Gain).', 'Pemantauan efektivitas pelatihan perbankan berdasarkan model evaluasi Kirkpatrick Tingkat 1 (Reaksi/Penilaian) & Tingkat 2 (Peningkatan Kompetensi).') }}
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            @click="openAddEvalModal()"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#0067AC] hover:bg-[#00558F] text-white text-xs font-bold shadow-xs transition cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{{ $l('Add Evaluation', 'Tambah Evaluasi') }}</span>
          </button>

          <button
            @click="fetchEvaluations"
            class="w-9 h-9 rounded-full bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200 cursor-pointer"
            :title="$l('Refresh Data', 'Segarkan Data')"
          >
            <RotateCcw class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-gray-100 text-xs">
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1">{{ $l('Training Program Filter', 'Filter Program Pelatihan') }}</label>
          <AppSearchableSelect
            v-model="filterProgramId"
            :options="programFilterOptions"
            :placeholder="$l('-- All Training Programs --', '-- Seluruh Program Pelatihan --')"
            :search-placeholder="$l('Search program code or title...', 'Cari kode atau judul program...')"
            input-class="bg-[#f8fafc] border-gray-200 text-gray-700"
            @change="filterClassId = ''; fetchEvaluations()"
          />
        </div>

        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1">{{ $l('Class Batch Filter', 'Filter Angkatan Kelas') }}</label>
          <AppSearchableSelect
            v-model="filterClassId"
            :options="classFilterOptions"
            :placeholder="$l('-- All Class Batches --', '-- Seluruh Angkatan Kelas --')"
            :search-placeholder="$l('Search class code or batch...', 'Cari kode kelas atau angkatan...')"
            input-class="bg-[#f8fafc] border-gray-200 text-gray-700"
            @change="fetchEvaluations"
          />
        </div>

        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1">{{ $l('Completion Status', 'Status Kelulusan') }}</label>
          <AppSearchableSelect
            v-model="filterStatus"
            :options="statusFilterOptions"
            :placeholder="$l('-- All Completion Statuses --', '-- Seluruh Status Kelulusan --')"
            :search-placeholder="$l('Search completion status...', 'Cari status kelulusan...')"
            input-class="bg-[#f8fafc] border-gray-200 text-gray-700"
            @change="fetchEvaluations"
          />
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :data="evaluationRecords"
      :columns="evalColumns"
      date-key="date"
      export-filename="bws_training_evaluations_summary"
      :default-per-page="10"
    >
      <!-- Custom Cell: Name & NIK -->
      <template #cell-name="{ row }">
        <p class="font-bold text-xs text-gray-900 leading-tight">{{ row.name }}</p>
        <p class="font-mono text-[10px] text-gray-400 mt-0.5">{{ row.nik }}</p>
      </template>

      <!-- Custom Cell: Program & Batch -->
      <template #cell-program_title="{ row }">
        <p class="font-bold text-xs text-[#002855] leading-tight">{{ row.program_title }}</p>
        <div class="flex items-center gap-1.5 mt-0.5">
          <span class="font-mono text-[10px] text-[#0067AC] font-bold">{{ row.class_code }}</span>
          <span class="text-gray-300">•</span>
          <span class="text-[10px] text-gray-500 font-semibold">{{ row.batch_name }}</span>
        </div>
      </template>

      <!-- Custom Cell: Presensi % -->
      <template #cell-attendance_pct="{ row }">
        <span
          class="font-mono text-xs font-bold"
          :class="row.attendance_pct >= 80 ? 'text-emerald-700' : 'text-rose-600'"
        >
          {{ row.attendance_pct }}%
        </span>
      </template>

      <!-- Custom Cell: Delta Gain % -->
      <template #cell-delta_pct="{ row }">
        <span
          v-if="row.post_score && row.pre_score"
          class="font-mono text-xs font-black inline-flex items-center gap-0.5"
          :class="row.post_score >= row.pre_score ? 'text-emerald-700' : 'text-rose-600'"
        >
          {{ row.post_score >= row.pre_score ? '+' : '' }}{{ row.delta_pct }}%
        </span>
        <span v-else class="text-gray-300">-</span>
      </template>

      <!-- Custom Cell: Status Kelulusan -->
      <template #cell-status="{ row }">
        <span
          class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold whitespace-nowrap"
          :class="row.status === 'PASSED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : row.status === 'FAILED' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-gray-100 text-gray-600'"
        >
          {{ row.status === 'PASSED' ? $l('PASSED', 'LULUS') + ' (' + (row.grade || 'B') + ')' : row.status === 'FAILED' ? $l('FAILED', 'TIDAK LULUS') : $l('In Progress', 'Dalam Proses') }}
        </span>
      </template>

      <!-- Custom Cell: Instructor Rating -->
      <template #cell-instructor_rating="{ row }">
        <div class="flex items-center justify-center gap-1 text-amber-500 font-bold text-xs">
          <span>{{ row.instructor_rating }}</span>
          <Star class="w-3.5 h-3.5 fill-amber-400 text-amber-400" />
        </div>
      </template>

      <!-- Custom Cell: E-Sertifikat -->
      <template #cell-certificate_number="{ row }">
        <div v-if="row.certificate_number" class="flex items-center gap-1.5 text-[11px] font-mono text-emerald-700 font-bold">
          <FileCheck class="w-3.5 h-3.5 shrink-0" />
          <span class="truncate" :title="row.certificate_number">{{ row.certificate_number }}</span>
        </div>
        <span v-else class="text-[10px] text-gray-400 italic">{{ $l('Not issued yet', 'Belum terbit') }}</span>
      </template>

      <!-- Custom Cell: Aksi -->
      <template #cell-actions="{ row }">
        <button
          @click="openAddEvalModal(row.class_id, row.nik !== '-' ? row.nik : row.name)"
          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-[#EBF4FC] hover:bg-[#0067AC] text-[#0067AC] hover:text-white text-[11px] font-bold transition cursor-pointer"
          :title="$l('Enter / Edit Evaluation Scores', 'Isi / Ubah Nilai Evaluasi')"
        >
          <Edit2 class="w-3 h-3" />
          <span>{{ $l('Score', 'Nilai') }}</span>
        </button>
      </template>
    </DataTable>

    <!-- Modal: Input / Tambah Evaluasi Peserta -->
    <div
      v-if="showEvalModal"
      class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
    >
      <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" @click="showEvalModal = false"></div>

      <div class="relative bg-white rounded-3xl max-w-6xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10 max-h-[90vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
              <Star class="w-4 h-4" />
            </div>
            <div>
              <h3 class="font-extrabold text-sm text-[#002855]">{{ $l('Enter & Update Training Evaluation Scores', 'Pengisian & Pembaruan Nilai Evaluasi Pelatihan') }}</h3>
              <p class="text-[11px] text-gray-500">{{ $l('Select a class batch then enter Pre-Test, Post-Test, Assignment scores, and Evaluation Ratings (1–5 scale).', 'Pilih angkatan kelas lalu isi nilai Tes Awal, Tes Akhir, Tugas, dan Penilaian Evaluasi (Skala 1–5).') }}</p>
            </div>
          </div>
          <button
            @click="showEvalModal = false"
            class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition cursor-pointer"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Class Selector & Search Bar -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-[#f8fafc] p-3.5 rounded-2xl border border-gray-200 text-xs shrink-0">
          <div>
            <label class="block text-[11px] font-bold text-gray-600 mb-1">{{ $l('Select Class Batch *', 'Pilih Angkatan Kelas *') }}</label>
            <AppSearchableSelect
              v-model="modalClassId"
              :options="modalClassOptions"
              :placeholder="$l('-- Select Class Batch --', '-- Pilih Angkatan Kelas --')"
              :search-placeholder="$l('Search class code, batch, or program...', 'Cari kode kelas, angkatan, atau program...')"
              input-class="bg-white border-gray-200 text-gray-800 font-semibold"
              @change="loadClassEvaluation"
            />
          </div>

          <div>
            <label class="block text-[11px] font-bold text-gray-600 mb-1">{{ $l('Search Participant in This Class', 'Cari Peserta di Kelas Ini') }}</label>
            <div class="relative">
              <Search class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5 pointer-events-none" />
              <input
                v-model="modalSearchParticipant"
                type="text"
                :placeholder="$l('Type participant name, NIK, or division...', 'Ketik nama, NIK, atau divisi peserta...')"
                class="w-full bg-white border border-gray-200 rounded-xl pl-8 pr-3 py-2 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC]"
              />
            </div>
          </div>
        </div>

        <!-- Passing Grade Info -->
        <div v-if="modalClassInfo" class="flex flex-wrap items-center justify-between gap-2 px-1 text-xs shrink-0">
          <div class="flex items-center gap-2">
            <span class="font-bold text-[#002855]">{{ modalClassInfo.program_title }}</span>
            <span class="text-gray-300">•</span>
            <span class="font-mono text-[#0067AC] font-bold">{{ modalClassInfo.class_code }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-bold">
              {{ $l('Passing Grade:', 'Nilai Minimum Lulus:') }} {{ modalClassInfo.passing_grade }}
            </span>
            <span class="text-[11px] text-gray-500 font-medium">
              {{ modalParticipants.length }} {{ $l('Registered Participants', 'Peserta Terdaftar') }}
            </span>
          </div>
        </div>

        <!-- Error Alert -->
        <div v-if="modalError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2 shrink-0">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ modalError }}</span>
        </div>

        <!-- Participants Evaluation Table -->
        <div class="overflow-auto flex-1 border border-gray-200 rounded-2xl">
          <div v-if="modalLoading" class="py-12 text-center text-xs text-gray-400">
            {{ $l('Loading class participants...', 'Memuat daftar peserta kelas...') }}
          </div>
          <div v-else-if="!modalClassId" class="py-12 text-center text-xs text-gray-400">
            {{ $l('Please select a class batch first.', 'Silakan pilih angkatan kelas terlebih dahulu.') }}
          </div>
          <div v-else-if="filteredModalParticipants.length === 0" class="py-12 text-center text-xs text-gray-400">
            {{ $l('No participants enrolled in this class batch yet (enroll participants via Class Schedule / Participants menu).', 'Belum ada peserta terdaftar di angkatan kelas ini (tambahkan peserta melalui menu Jadwal Kelas / Peserta).') }}
          </div>
          <table v-else class="w-full text-left text-xs border-collapse">
            <thead class="bg-[#f8fafc] text-gray-600 font-bold border-b border-gray-200 sticky top-0 z-10">
              <tr>
                <th class="py-2.5 px-3">{{ $l('Participant & NIK', 'Peserta & NIK') }}</th>
                <th class="py-2.5 px-2 text-center w-22">{{ $l('Pre-Test', 'Tes Awal') }}</th>
                <th class="py-2.5 px-2 text-center w-22">{{ $l('Post-Test', 'Tes Akhir') }}</th>
                <th class="py-2.5 px-2 text-center w-22">{{ $l('Assignment', 'Tugas') }}</th>
                <th class="py-2.5 px-2 text-center w-28">{{ $l('Trainer Rating', 'Nilai Pengajar') }}</th>
                <th class="py-2.5 px-2 text-center w-28">{{ $l('Material Rating', 'Nilai Materi') }}</th>
                <th class="py-2.5 px-2 text-center w-28">{{ $l('Committee Rating', 'Nilai Panitia') }}</th>
                <th class="py-2.5 px-3 min-w-[160px]">{{ $l('Evaluation Notes', 'Catatan Evaluasi') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="p in filteredModalParticipants" :key="p.id" class="hover:bg-blue-50/30">
                <td class="py-2 px-3">
                  <p class="font-bold text-gray-900 leading-tight">{{ p.name }}</p>
                  <p class="font-mono text-[10px] text-gray-400">{{ p.nik }} • {{ p.department || '-' }}</p>
                </td>
                <td class="py-2 px-2">
                  <input
                    v-model="scoreRecords[p.id].pre_test_score"
                    type="number"
                    min="0"
                    max="100"
                    step="0.5"
                    placeholder="0-100"
                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-1.5 text-center font-mono focus:border-[#0067AC] focus:outline-none"
                  />
                </td>
                <td class="py-2 px-2">
                  <input
                    v-model="scoreRecords[p.id].post_test_score"
                    type="number"
                    min="0"
                    max="100"
                    step="0.5"
                    placeholder="0-100"
                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-1.5 text-center font-mono font-bold text-[#0067AC] focus:border-[#0067AC] focus:outline-none"
                  />
                </td>
                <td class="py-2 px-2">
                  <input
                    v-model="scoreRecords[p.id].task_score"
                    type="number"
                    min="0"
                    max="100"
                    step="0.5"
                    placeholder="0-100"
                    class="w-full bg-white border border-gray-200 rounded-lg px-2 py-1.5 text-center font-mono focus:border-[#0067AC] focus:outline-none"
                  />
                </td>
                <td class="py-2 px-2">
                  <AppSearchableSelect
                    v-model="scoreRecords[p.id].eval_instructor_rating"
                    :options="ratingSelectOptions"
                    placeholder="5 ★"
                    :search-placeholder="$t('common.search')"
                    input-class="bg-white border-gray-200 rounded-lg px-2 py-1.5 text-amber-600 font-bold"
                  />
                </td>
                <td class="py-2 px-2">
                  <AppSearchableSelect
                    v-model="scoreRecords[p.id].eval_material_rating"
                    :options="ratingSelectOptions"
                    placeholder="5 ★"
                    :search-placeholder="$t('common.search')"
                    input-class="bg-white border-gray-200 rounded-lg px-2 py-1.5 text-amber-600 font-bold"
                  />
                </td>
                <td class="py-2 px-2">
                  <AppSearchableSelect
                    v-model="scoreRecords[p.id].eval_facility_rating"
                    :options="ratingSelectOptions"
                    placeholder="5 ★"
                    :search-placeholder="$t('common.search')"
                    input-class="bg-white border-gray-200 rounded-lg px-2 py-1.5 text-amber-600 font-bold"
                  />
                </td>
                <td class="py-2 px-3">
                  <input
                    v-model="scoreRecords[p.id].eval_notes"
                    type="text"
                    :placeholder="$l('Brief notes...', 'Catatan singkat...')"
                    class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 focus:border-[#0067AC] focus:outline-none"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 shrink-0">
          <button
            type="button"
            @click="showEvalModal = false"
            class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold transition cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="button"
            :disabled="modalSaving || modalParticipants.length === 0"
            @click="saveEvaluationScores"
            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00558F] text-white text-xs font-bold shadow-xs transition disabled:opacity-50 cursor-pointer"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ modalSaving ? $l('Saving...', 'Menyimpan...') : $l('Save Evaluation', 'Simpan Evaluasi') }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
