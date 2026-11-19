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
  Download
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import apiClient from '@/api/client'

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

const evalColumns = [
  { key: 'name', label: 'Peserta & NIK', sortable: true },
  { key: 'program_title', label: 'Program & Batch Kelas', sortable: true },
  { key: 'department', label: 'Unit / Divisi', sortable: true, width: '150px' },
  {
    key: 'attendance_pct',
    label: 'Presensi',
    sortable: true,
    align: 'center',
    width: '90px',
    format: (val) => `${val || 0}%`
  },
  {
    key: 'pre_score',
    label: 'Pre-Test',
    sortable: true,
    align: 'center',
    width: '95px',
    format: (val) => val ? `${val} / 100` : '-'
  },
  {
    key: 'post_score',
    label: 'Post-Test',
    sortable: true,
    align: 'center',
    width: '95px',
    format: (val) => val ? `${val} / 100` : '-'
  },
  {
    key: 'delta_pct',
    label: 'Gain (Δ%)',
    sortable: true,
    align: 'center',
    width: '100px'
  },
  { key: 'status', label: 'Hasil Kelulusan', sortable: true, align: 'center', width: '130px' },
  { key: 'instructor_rating', label: 'Rating Trainer', sortable: true, align: 'center', width: '120px' },
  { key: 'certificate_number', label: 'E-Sertifikat', sortable: true, width: '160px' }
]

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
          <p class="text-[11px] text-gray-400 font-medium">Rata-rata Pre-Test</p>
          <p class="text-xl font-extrabold text-gray-900 font-mono">{{ stats.avg_pre_test }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Rata-rata Post-Test</p>
          <p class="text-xl font-extrabold text-emerald-700 font-mono">{{ stats.avg_post_test }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <TrendingUp class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Peningkatan Kompetensi</p>
          <p class="text-xl font-extrabold text-[#0067AC] font-mono">+{{ stats.competency_gain_pct }}%</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold">
          <Award class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Tingkat Kelulusan</p>
          <p class="text-xl font-extrabold text-teal-800 font-mono">{{ stats.pass_rate_pct }}%</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Star class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Rating Instruktur</p>
          <p class="text-xl font-extrabold text-purple-700 font-mono">{{ stats.avg_instructor_rating }} <span class="text-xs text-gray-400 font-normal">/ 5.0</span></p>
        </div>
      </div>
    </div>

    <!-- Header & Interactive Filter Bar -->
    <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-2xs space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC] uppercase tracking-wider">
              Learning Assessment &amp; Growth Analytics
            </span>
            <span class="text-xs text-gray-400">• BWS LIS Competency Matrix</span>
          </div>
          <h2 class="text-lg font-extrabold text-[#002855] mt-1">
            Rekapitulasi Nilai Pre-Test, Post-Test &amp; Evaluasi Penyelenggaraan
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Monitoring efektivitas pelatihan perbankan berdasarkan model evaluasi Kirkpatrick Level 1 (Reaction/Rating) &amp; Level 2 (Learning Gain).
          </p>
        </div>

        <button
          @click="fetchEvaluations"
          class="w-9 h-9 rounded-full bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200 cursor-pointer"
          title="Segarkan Data"
        >
          <RotateCcw class="w-4 h-4" />
        </button>
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-gray-100 text-xs">
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1">Filter Program Pelatihan</label>
          <select
            v-model="filterProgramId"
            @change="filterClassId = ''; fetchEvaluations()"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
          >
            <option value="">-- Seluruh Program Pelatihan --</option>
            <option v-for="p in programs" :key="p.id" :value="p.id">
              [{{ p.program_code }}] {{ p.title }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1">Filter Batch Kelas</label>
          <select
            v-model="filterClassId"
            @change="fetchEvaluations"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
          >
            <option value="">-- Seluruh Batch Kelas --</option>
            <option v-for="c in filteredClasses" :key="c.id" :value="c.id">
              [{{ c.class_code }}] {{ c.batch_name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1">Status Kelulusan</label>
          <select
            v-model="filterStatus"
            @change="fetchEvaluations"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
          >
            <option value="">-- Seluruh Status Kelulusan --</option>
            <option value="PASSED">LULUS (Passed)</option>
            <option value="FAILED">TIDAK LULUS (Failed)</option>
            <option value="IN_PROGRESS">Dalam Proses / Belum Ujian</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <DataTable
      :data="evaluationRecords"
      :columns="evalColumns"
      date-key="date"
      export-filename="rekapitulasi_evaluasi_ujian_diklat_bws_2026"
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
          {{ row.status === 'PASSED' ? 'LULUS (' + (row.grade || 'B') + ')' : row.status === 'FAILED' ? 'TIDAK LULUS' : 'Dalam Proses' }}
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
        <span v-else class="text-[10px] text-gray-400 italic">Belum terbit</span>
      </template>
    </DataTable>

  </div>
</template>
