<script setup>
import { reactive, ref } from 'vue'
import { X, Calendar, Scale } from 'lucide-vue-next'
import apiClient from '@/api/client'

defineProps({
  show: Boolean,
  programs: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'submitted'])
const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  program_id: '',
  batch_name: 'Batch I - 2026',
  method: 'Hybrid',
  start_date: '2026-06-01',
  end_date: '2026-06-03',
  location_or_url: 'BWS Learning Center - R. Diklat 101',
  max_quota: 25,
  facilitator_id: null
})

async function submit() {
  loading.value = true
  errorMessage.value = ''
  try {
    const { data } = await apiClient.post('/classes', form)
    emit('submitted', data.message)
    emit('close')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal membuka batch kelas.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-5 z-10 animate-scaleIn">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
            <Calendar class="w-4 h-4" />
          </div>
          <div>
            <h3 class="font-bold text-base text-gray-900">Buka Batch Kelas Pelatihan (Maker)</h3>
            <p class="text-xs text-gray-400">Pengajuan Batch Diklat dengan Otorisasi Dual-Control Checker</p>
          </div>
        </div>
        <button @click="emit('close')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="submit" class="space-y-4 text-xs">
        <div>
          <label class="block font-semibold text-gray-700 mb-1">Pilih Program Pelatihan</label>
          <select v-model="form.program_id" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required>
            <option value="" disabled>-- Pilih Program Pelatihan --</option>
            <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.program_code }} - {{ prog.title }}</option>
          </select>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="col-span-2">
            <label class="block font-semibold text-gray-700 mb-1">Nama Batch</label>
            <input type="text" v-model="form.batch_name" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Kuota</label>
            <input type="number" v-model.number="form.max_quota" min="5" max="100" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Metode</label>
            <select v-model="form.method" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none">
              <option value="Hybrid">Hybrid</option>
              <option value="In-Classroom">In-Classroom</option>
              <option value="Virtual Classroom">Virtual Classroom</option>
              <option value="LIS E-Learning">LIS E-Learning</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Tgl Mulai</label>
            <input type="date" v-model="form.start_date" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Tgl Selesai</label>
            <input type="date" v-model="form.end_date" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">Ruangan / URL Pembelajaran</label>
          <input type="text" v-model="form.location_or_url" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
        </div>

        <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-amber-800 flex items-start gap-2.5">
          <Scale class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <p class="text-[11px] leading-tight">
            <strong>Peringatan Dual-Control Perbankan:</strong> Batch akan berstatus PENDING_CHECKER dan membutuhkan otorisasi dari HC Learning Head (Checker).
          </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button type="button" @click="emit('close')" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
            Batal
          </button>
          <button type="submit" :disabled="loading" class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold transition shadow-sm disabled:opacity-50">
            {{ loading ? 'Menyimpan...' : 'Kirim Pengajuan ke Checker' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
.animate-scaleIn {
  animation: scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
