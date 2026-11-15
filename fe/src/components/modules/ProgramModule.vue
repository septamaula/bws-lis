<script setup>
import { computed } from 'vue'
import {
  BookOpen,
  Plus,
  Layers,
  GraduationCap,
  Award,
  Video,
  MapPin,
  CheckCircle2
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  trainingPrograms: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['open-program-modal'])

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

const programColumns = [
  { key: 'program_code', label: 'Kode Program', sortable: true },
  { key: 'title', label: 'Nama Program Pelatihan', sortable: true },
  { key: 'category', label: 'Kategori Diklat', sortable: true },
  { key: 'is_mandatory', label: 'Sifat Diklat', sortable: true, align: 'center', format: (val) => (val ? 'Wajib OJK/BI' : 'Pilihan') },
  { key: 'duration_hours', label: 'Durasi (Jam)', sortable: true, align: 'center' },
  {
    key: 'passing_grade',
    label: 'Passing Grade',
    sortable: true,
    align: 'center',
    format: (val) => `${val || 75}%`
  },
  {
    key: 'classes_count',
    label: 'Jumlah Batch',
    sortable: true,
    align: 'center',
    format: (val) => `${val || 0} Batch`
  },
  {
    key: 'created_at',
    label: 'Tgl Penetapan',
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
          <p class="text-xs text-gray-400 mb-0.5">Total Program Terdaftar</p>
          <p class="text-xl font-bold text-gray-900">{{ trainingPrograms.length }} Program</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <BookOpen class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Sertifikasi Wajib OJK/BI</p>
          <p class="text-xl font-bold text-blue-600">12 Program</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <Award class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Metode Hybrid & Online</p>
          <p class="text-xl font-bold text-purple-600">22 Program</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <Video class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Target Total Peserta</p>
          <p class="text-xl font-bold text-gray-900">1,250 Pegawai</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <GraduationCap class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Training Programs Standard Table Component -->
    <DataTable
      :data="trainingPrograms"
      :columns="programColumns"
      date-key="created_at"
      title="Master Program Kerja & Kurikulum Pelatihan"
      subtitle="Katalog program pelatihan perbankan, sertifikasi regulator, dan pengembangan kepemimpinan Bank Woori Saudara"
      export-filename="program_kerja_ld_bws_2026"
      :default-per-page="10"
    >
      <!-- Actions in Header -->
      <template #actions>
        <button
          @click="emit('open-program-modal')"
          class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition"
        >
          <Plus class="w-4 h-4" />
          <span>+ Buat Program Baru</span>
        </button>
      </template>

      <!-- Custom Cell: Code -->
      <template #cell-program_code="{ row }">
        <span class="font-mono text-xs font-bold text-[#157f4f] block">{{ row.program_code }}</span>
        <span class="text-[10px] text-gray-400 font-semibold">{{ row.category }}</span>
      </template>

      <!-- Custom Cell: Title -->
      <template #cell-title="{ row }">
        <p class="font-bold text-xs text-gray-900">{{ row.title }}</p>
        <p class="text-[11px] text-gray-400 line-clamp-1 mt-0.5">{{ row.description || 'Silabus standar kompetensi perbankan BWS' }}</p>
      </template>

      <!-- Custom Cell: Mandatory -->
      <template #cell-is_mandatory="{ row }">
        <span
          class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-0.5 rounded-full"
          :class="row.is_mandatory ? 'bg-amber-50 text-amber-800' : 'bg-blue-50 text-blue-700'"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="row.is_mandatory ? 'bg-amber-500' : 'bg-blue-600'"></span>
          {{ row.is_mandatory ? 'Wajib OJK/BI' : 'Pilihan' }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          class="px-3 py-1.5 rounded-full bg-[#f4f6f5] hover:bg-gray-200 text-gray-800 text-xs font-semibold transition"
          title="Lihat Silabus Program"
        >
          Silabus
        </button>
      </template>
    </DataTable>

  </div>
</template>

