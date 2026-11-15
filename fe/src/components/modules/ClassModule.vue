<script setup>
import { computed } from 'vue'
import {
  Calendar,
  Plus,
  Users,
  CheckCircle,
  Clock,
  ShieldCheck,
  MapPin,
  Scale
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

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
  }
})

const emit = defineEmits(['open-class-modal', 'approve-class'])

const pendingClassesCount = computed(() => {
  return props.trainingClasses.filter(c => c.dual_control_status === 'PENDING_CHECKER').length
})

const classColumns = [
  { key: 'class_code', label: 'Batch & Program', sortable: true },
  { key: 'start_date', label: 'Jadwal Pelaksanaan', sortable: true },
  { key: 'location_or_url', label: 'Lokasi / Ruang', sortable: true },
  { key: 'facilitator.name', label: 'Instruktur / Trainer', sortable: true },
  {
    key: 'max_quota',
    label: 'Kapasitas Kuota',
    sortable: true,
    align: 'center',
    format: (val, row) => `${row.enrollments_count || 0} / ${val} Peserta`
  },
  { key: 'dual_control_status', label: 'Status Dual-Control (Maker-Checker)', sortable: true, align: 'center' }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Batch Terbuka</p>
          <p class="text-xl font-bold text-gray-900">{{ trainingClasses.length }} Batch</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <Calendar class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Antrean Checker (Dual Control)</p>
          <p class="text-xl font-bold text-amber-600 font-mono">{{ pendingClassesCount }} Menunggu</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <Scale class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Kursi Peserta</p>
          <p class="text-xl font-bold text-blue-600">650 Kursi</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <Users class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Standar Kepatuhan BI</p>
          <p class="text-xl font-bold text-[#157f4f]">100% Valid</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <ShieldCheck class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Training Classes Standard Table Component -->
    <DataTable
      :data="trainingClasses"
      :columns="classColumns"
      date-key="start_date"
      title="Jadwal & Operasional Kelas Pelatihan (Maker-Checker)"
      subtitle="Manajemen batch, alokasi ruangan diklat, verifikasi four-eyes principle, dan absensi peserta"
      export-filename="jadwal_kelas_diklat_bws_2026"
      :default-per-page="10"
    >
      <!-- Actions in Header -->
      <template #actions>
        <button
          v-if="canCreateClass"
          @click="emit('open-class-modal')"
          class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition"
        >
          <Plus class="w-4 h-4" />
          <span>+ Buat Batch Kelas (Maker)</span>
        </button>
      </template>

      <!-- Custom Cell: Batch & Program -->
      <template #cell-class_code="{ row }">
        <p class="font-bold text-xs text-gray-900 leading-tight">{{ row.program?.title || 'Batch Pelatihan' }}</p>
        <div class="flex items-center gap-1.5 mt-0.5">
          <span class="font-mono text-[11px] font-bold text-[#157f4f]">{{ row.class_code }} ({{ row.batch_name }})</span>
          <span class="text-gray-300">•</span>
          <span class="text-[10px] text-gray-400">Maker: {{ row.maker?.name || 'HC Training' }}</span>
        </div>
      </template>

      <!-- Custom Cell: Schedule -->
      <template #cell-start_date="{ row }">
        <p class="font-semibold text-xs text-gray-800">{{ row.start_date?.substring(0, 10) }} s/d {{ row.end_date?.substring(0, 10) }}</p>
        <p class="text-[10px] text-gray-400">{{ row.method || 'Hybrid' }}</p>
      </template>

      <!-- Custom Cell: Location -->
      <template #cell-location_or_url="{ row }">
        <div class="flex items-center gap-1 text-gray-700">
          <MapPin class="w-3.5 h-3.5 text-rose-500 shrink-0" />
          <span class="text-xs">{{ row.location_or_url || 'BWS Learning Center' }}</span>
        </div>
      </template>

      <!-- Custom Cell: Dual-Control Status -->
      <template #cell-dual_control_status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.dual_control_status === 'APPROVED' ? 'bg-emerald-50 text-[#157f4f]' : row.dual_control_status === 'PENDING_CHECKER' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.dual_control_status === 'APPROVED' ? 'bg-[#157f4f]' : row.dual_control_status === 'PENDING_CHECKER' ? 'bg-amber-500' : 'bg-blue-600'"
          ></span>
          {{ row.dual_control_status === 'APPROVED' ? 'Disetujui Checker' : row.dual_control_status === 'PENDING_CHECKER' ? 'Menunggu Otorisasi Checker' : row.dual_control_status }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          v-if="row.dual_control_status === 'PENDING_CHECKER' && isCheckerRole"
          @click="emit('approve-class', row)"
          class="px-3 py-1.5 rounded-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition shadow-2xs"
          title="Otorisasi Batch ini sebagai Checker"
        >
          Otorisasi
        </button>
        <span v-else-if="row.dual_control_status === 'APPROVED'" class="text-[11px] text-[#157f4f] font-semibold">
          Terotorisasi
        </span>
        <button
          v-else
          class="px-3 py-1.5 rounded-full bg-[#f4f6f5] hover:bg-gray-200 text-gray-800 text-xs font-semibold transition"
        >
          Detail
        </button>
      </template>
    </DataTable>

  </div>
</template>

