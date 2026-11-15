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

const participantColumns = [
  { key: 'user.nik', label: 'NIK Pegawai', sortable: true },
  { key: 'user.name', label: 'Nama Pegawai', sortable: true },
  { key: 'user.department', label: 'Divisi / Unit Kerja', sortable: true },
  { key: 'user.branch', label: 'Kantor Cabang', sortable: true },
  { key: 'training_class.program.title', label: 'Kelas Pelatihan Terdaftar', sortable: true },
  {
    key: 'elapse_progress_pct',
    label: 'Progress ELAPSE',
    sortable: true,
    align: 'center',
    format: (val) => `${val ?? 100}%`
  },
  { key: 'completion_status', label: 'Status Kelulusan', sortable: true, align: 'center' },
  {
    key: 'created_at',
    label: 'Tgl Pendaftaran',
    sortable: true,
    format: (val) => val ? val.substring(0, 10) : '-'
  }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Peserta Terdaftar</p>
          <p class="text-xl font-bold text-gray-900">{{ enrollments.length }} Pegawai</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <Users class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Peserta Lulus (Passed)</p>
          <p class="text-xl font-bold text-[#157f4f]">{{ passedCount }} Pegawai</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <CheckCircle class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Tingkat Kelulusan (Pass Rate)</p>
          <p class="text-xl font-bold text-blue-600">{{ passRate }}%</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <Award class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Rata-rata Kehadiran</p>
          <p class="text-xl font-bold text-purple-600">96.8%</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <UserCheck class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Participants Standard Table Component -->
    <DataTable
      :data="enrollments"
      :columns="participantColumns"
      date-key="created_at"
      title="Database Peserta Pelatihan & Peserta ELAPSE"
      subtitle="Monitoring kehadiran absensi, rekam jejak sertifikasi internal, dan status kelulusan pegawai Bank Woori Saudara"
      export-filename="daftar_peserta_diklat_bws"
      :default-per-page="10"
    >
      <!-- Custom Cell: NIK -->
      <template #cell-user-nik="{ row }">
        <span class="font-mono text-xs font-bold text-[#157f4f]">{{ row.user?.nik || 'NIK-BWS' }}</span>
      </template>

      <!-- Custom Cell: Name -->
      <template #cell-user-name="{ row }">
        <p class="font-bold text-xs text-gray-900">{{ row.user?.name }}</p>
        <p class="text-[10px] text-gray-400">{{ row.user?.email }}</p>
      </template>

      <!-- Custom Cell: Class -->
      <template #cell-training_class-program-title="{ row }">
        <p class="font-semibold text-xs text-gray-800 line-clamp-1">{{ row.training_class?.program?.title || 'Batch Pelatihan' }}</p>
        <p class="text-[10px] text-gray-400">{{ row.training_class?.batch_name || 'Batch I' }}</p>
      </template>

      <!-- Custom Cell: Completion Status -->
      <template #cell-completion_status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.completion_status === 'PASSED' ? 'bg-emerald-50 text-[#157f4f]' : row.completion_status === 'FAILED' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.completion_status === 'PASSED' ? 'bg-[#157f4f]' : row.completion_status === 'FAILED' ? 'bg-rose-500' : 'bg-blue-600'"
          ></span>
          {{ row.completion_status === 'PASSED' ? 'Lulus (Passed)' : row.completion_status === 'FAILED' ? 'Tidak Lulus' : 'Sedang Berjalan' }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          v-if="row.completion_status === 'PASSED'"
          @click="emit('view-certificate', row)"
          class="px-3 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-[#157f4f] text-xs font-semibold transition"
          title="Lihat E-Sertifikat Kelulusan"
        >
          Sertifikat
        </button>
        <span v-else class="text-[11px] text-gray-400">
          In Training
        </span>
      </template>
    </DataTable>

  </div>
</template>

