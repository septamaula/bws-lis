<script setup>
import { computed } from 'vue'
import {
  Users,
  GraduationCap,
  Award,
  CheckCircle,
  Building,
  UserCheck
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import { useI18n } from '@/i18n'

const { l } = useI18n()

const props = defineProps({
  enrollments: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['view-participant-card', 'view-certificate'])

const passedCount = computed(() => {
  return props.enrollments.filter(e => e.completion_status === 'PASSED').length
})

const passRate = computed(() => {
  if (!props.enrollments.length) return 0
  return Math.round((passedCount.value / props.enrollments.length) * 100)
})

const participantColumns = computed(() => [
  { key: 'user.nik', label: l('Employee NIK', 'NIK Pegawai'), sortable: true },
  { key: 'user.name', label: l('Employee Name', 'Nama Pegawai'), sortable: true },
  { key: 'user.department', label: l('Division / Work Unit', 'Divisi / Unit Kerja'), sortable: true },
  { key: 'user.branch', label: l('Branch Office', 'Kantor Cabang'), sortable: true },
  { key: 'training_class.program.title', label: l('Enrolled Training Class', 'Kelas Pelatihan Terdaftar'), sortable: true },
  {
    key: 'elapse_progress_pct',
    label: l('LIS Learning Progress', 'Progres Belajar LIS'),
    sortable: true,
    align: 'center',
    format: (val) => `${val ?? 100}%`
  },
  { key: 'completion_status', label: l('Completion Status', 'Status Kelulusan'), sortable: true, align: 'center' },
  {
    key: 'created_at',
    label: l('Enrollment Date', 'Tgl Pendaftaran'),
    sortable: true,
    format: (val) => val ? val.substring(0, 10) : '-'
  }
])
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Users class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Total Registered', 'Total Terdaftar') }}</p>
          <p class="text-xl font-extrabold text-gray-900">{{ enrollments.length }} {{ $l('Employees', 'Pegawai') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Passed Participants', 'Peserta Lulus') }}</p>
          <p class="text-xl font-extrabold text-[#0067AC]">{{ passedCount }} {{ $l('Employees', 'Pegawai') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Award class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Pass Rate', 'Tingkat Kelulusan') }}</p>
          <p class="text-xl font-extrabold text-gray-900">{{ passRate }}%</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <UserCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Average Attendance', 'Rata-rata Presensi') }}</p>
          <p class="text-xl font-extrabold text-gray-900">96.8%</p>
        </div>
      </div>
    </div>

    <!-- Participants Standard Table Component -->
    <DataTable
      :data="enrollments"
      :columns="participantColumns"
      date-key="created_at"
      export-filename="bws_training_participants"
      :default-per-page="10"
    >
      <!-- Custom Cell: NIK -->
      <template #cell-user-nik="{ row }">
        <span class="font-mono text-xs font-bold text-[#0067AC]">{{ row.user?.nik || 'NIK-BWS' }}</span>
      </template>

      <!-- Custom Cell: Name -->
      <template #cell-user-name="{ row }">
        <p class="font-bold text-xs text-gray-900">{{ row.user?.name }}</p>
        <p class="text-[10px] text-gray-400">{{ row.user?.email }}</p>
      </template>

      <!-- Custom Cell: Class -->
      <template #cell-training_class-program-title="{ row }">
        <p class="font-semibold text-xs text-gray-800 line-clamp-1">{{ row.training_class?.program?.title || $l('Training Batch', 'Angkatan Pelatihan') }}</p>
        <p class="text-[10px] text-gray-400">{{ row.training_class?.batch_name || $l('Batch I', 'Angkatan I') }}</p>
      </template>

      <!-- Custom Cell: Completion Status -->
      <template #cell-completion_status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.completion_status === 'PASSED' ? 'bg-emerald-50 text-[#0067AC]' : row.completion_status === 'FAILED' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.completion_status === 'PASSED' ? 'bg-[#0067AC]' : row.completion_status === 'FAILED' ? 'bg-rose-500' : 'bg-blue-600'"
          ></span>
          {{ row.completion_status === 'PASSED' ? $l('Passed', 'Lulus') : row.completion_status === 'FAILED' ? $l('Failed', 'Tidak Lulus') : $l('In Progress', 'Sedang Berjalan') }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          v-if="row.completion_status === 'PASSED'"
          @click="emit('view-certificate', row)"
          class="px-3 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-[#0067AC] text-xs font-semibold transition"
          :title="$l('View Completion E-Certificate', 'Lihat E-Sertifikat Kelulusan')"
        >
          {{ $l('Certificate', 'Sertifikat') }}
        </button>
        <span v-else class="text-[11px] text-gray-400">
          {{ $l('In Training', 'Dalam Pelatihan') }}
        </span>
      </template>
    </DataTable>

  </div>
</template>

