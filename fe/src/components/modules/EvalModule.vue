<script setup>
import { computed } from 'vue'
import {
  TrendingUp,
  Award,
  CheckCircle,
  HelpCircle,
  FileCheck,
  Star
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  enrollments: {
    type: Array,
    default: () => []
  }
})

// Prepare evaluation dataset with simulated / existing pre and post test scores
const evaluationData = computed(() => {
  return props.enrollments.map((e, index) => {
    // Generate realistic banking evaluation scores based on completion status
    const preScore = e.pre_test_score !== undefined && e.pre_test_score !== null ? e.pre_test_score : (55 + (index * 7) % 30)
    const postScore = e.post_test_score !== undefined && e.post_test_score !== null ? e.post_test_score : (e.completion_status === 'PASSED' ? 82 + (index * 3) % 15 : 64)
    const delta = Math.round(((postScore - preScore) / preScore) * 100)

    return {
      id: e.id,
      nik: e.user?.nik || `NIK-${1000 + index}`,
      name: e.user?.name || `Pegawai BWS ${index + 1}`,
      department: e.user?.department || 'Branch Banking',
      classTitle: e.training_class?.program?.title || 'Diklat Operasional Perbankan',
      batch: e.training_class?.batch_number || 1,
      preScore: preScore,
      postScore: postScore,
      delta: delta,
      status: e.completion_status || 'PASSED',
      feedback: postScore >= 80 ? 'Sangat Baik (Kompeten)' : 'Cukup (Perlu Pemantauan)',
      date: e.created_at || '2026-05-10'
    }
  })
})

const avgPreScore = computed(() => {
  if (!evaluationData.value.length) return 60
  const sum = evaluationData.value.reduce((acc, item) => acc + item.preScore, 0)
  return Math.round(sum / evaluationData.value.length)
})

const avgPostScore = computed(() => {
  if (!evaluationData.value.length) return 85
  const sum = evaluationData.value.reduce((acc, item) => acc + item.postScore, 0)
  return Math.round(sum / evaluationData.value.length)
})

const evalColumns = [
  { key: 'nik', label: 'NIK Pegawai', sortable: true },
  { key: 'name', label: 'Nama Peserta', sortable: true },
  { key: 'classTitle', label: 'Kelas Pelatihan', sortable: true },
  { key: 'preScore', label: 'Pre-Test', sortable: true, align: 'center', format: (val) => `${val} / 100` },
  { key: 'postScore', label: 'Post-Test', sortable: true, align: 'center', format: (val) => `${val} / 100` },
  { key: 'delta', label: 'Kenaikan (%)', sortable: true, align: 'center' },
  { key: 'feedback', label: 'Evaluasi Instruktur', sortable: true },
  { key: 'date', label: 'Tanggal Ujian', sortable: true, format: (val) => val ? val.substring(0, 10) : '-' }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Rata-rata Skor Pre-Test</p>
          <p class="text-xl font-bold text-gray-900 font-mono">{{ avgPreScore }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <HelpCircle class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Rata-rata Skor Post-Test</p>
          <p class="text-xl font-bold text-[#157f4f] font-mono">{{ avgPostScore }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <CheckCircle class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Efektivitas Kenaikan Skill</p>
          <p class="text-xl font-bold text-blue-600 font-mono">+{{ Math.round(((avgPostScore - avgPreScore) / avgPreScore) * 100) }}%</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <TrendingUp class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Indeks Kepuasan Fasilitator</p>
          <p class="text-xl font-bold text-purple-600 font-mono">4.86 <span class="text-xs text-gray-400 font-normal">/ 5.0</span></p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <Star class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Evaluation Scores Standard Table Component -->
    <DataTable
      :data="evaluationData"
      :columns="evalColumns"
      date-key="date"
      title="Hasil Asesmen Kompetensi, Pre-Test & Post-Test ELAPSE"
      subtitle="Pengukuran efektivitas pembelajaran Level 2 Kirkpatrick (Learning Gain) dan umpan balik fasilitator"
      export-filename="hasil_evaluasi_ujian_bws"
      :default-per-page="10"
    >
      <!-- Custom Cell: NIK -->
      <template #cell-nik="{ row }">
        <span class="font-mono text-xs font-bold text-[#157f4f]">{{ row.nik }}</span>
      </template>

      <!-- Custom Cell: Name -->
      <template #cell-name="{ row }">
        <p class="font-bold text-xs text-gray-900">{{ row.name }}</p>
        <p class="text-[10px] text-gray-400">{{ row.department }}</p>
      </template>

      <!-- Custom Cell: Pre-Test -->
      <template #cell-preScore="{ row }">
        <span class="inline-flex items-center font-mono text-xs font-medium px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-700">
          {{ row.preScore }} <span class="text-[10px] text-gray-400 ml-1">/ 100</span>
        </span>
      </template>

      <!-- Custom Cell: Post-Test -->
      <template #cell-postScore="{ row }">
        <span
          class="inline-flex items-center font-mono text-xs font-bold px-2.5 py-0.5 rounded-full border"
          :class="row.postScore >= 75 ? 'bg-emerald-50 text-[#157f4f] border-emerald-200/80' : 'bg-amber-50 text-amber-700 border-amber-200/80'"
        >
          {{ row.postScore }} <span class="text-[10px] opacity-75 ml-1">/ 100</span>
        </span>
      </template>

      <!-- Custom Cell: Delta -->
      <template #cell-delta="{ row }">
        <span
          class="inline-flex items-center gap-1 font-mono text-xs font-bold px-2 py-0.5 rounded-full"
          :class="row.delta > 0 ? 'bg-emerald-50 text-[#157f4f]' : 'bg-rose-50 text-rose-600'"
        >
          <TrendingUp v-if="row.delta > 0" class="w-3 h-3" />
          {{ row.delta > 0 ? `+${row.delta}%` : `${row.delta}%` }}
        </span>
      </template>

      <!-- Custom Cell: Feedback -->
      <template #cell-feedback="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.postScore >= 75 ? 'bg-emerald-50 text-[#157f4f]' : 'bg-amber-50 text-amber-700'"
        >
          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="row.postScore >= 75 ? 'bg-[#157f4f]' : 'bg-amber-500'"></span>
          {{ row.feedback }}
        </span>
      </template>
    </DataTable>

  </div>
</template>

