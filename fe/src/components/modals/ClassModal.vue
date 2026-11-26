<script setup>
import { reactive, ref, computed } from 'vue'
import { X, Calendar, Scale } from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useI18n } from '@/i18n'

const { l, t } = useI18n()

const props = defineProps({
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

const methodOptions = computed(() => [
  { value: 'Hybrid', label: l('Hybrid', 'Kombinasi (Hybrid)') },
  { value: 'In-Classroom', label: l('In-Classroom', 'Tatap Muka (Kelas)') },
  { value: 'Virtual Classroom', label: l('Virtual Classroom', 'Kelas Virtual') },
  { value: 'LIS E-Learning', label: l('LIS E-Learning', 'Pembelajaran Daring LIS') }
])

const programOptions = computed(() => {
  return props.programs.map(prog => ({
    value: prog.id,
    label: `${prog.program_code} - ${prog.title}`
  }))
})

async function submit() {
  loading.value = true
  errorMessage.value = ''
  try {
    await apiClient.post('/classes', form)
    emit('submitted', l('Training class batch has been submitted to Checker.', 'Angkatan kelas pelatihan berhasil diajukan ke Verifikator.'))
    emit('close')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || l('Failed to open class batch.', 'Gagal membuka angkatan kelas.')
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
            <h3 class="font-bold text-base text-gray-900">{{ $l('Open Training Class Batch (Maker)', 'Buka Angkatan Kelas Pelatihan (Pembuat)') }}</h3>
            <p class="text-xs text-gray-400">{{ $l('Training batch submission with Dual-Control Checker Authorization', 'Pengajuan Angkatan Diklat dengan Otorisasi Verifikator Kontrol Ganda') }}</p>
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
          <label class="block font-semibold text-gray-700 mb-1">{{ $l('Select Training Program', 'Pilih Program Pelatihan') }}</label>
          <AppSearchableSelect
            v-model="form.program_id"
            :options="programOptions"
            :placeholder="$l('-- Select Training Program --', '-- Pilih Program Pelatihan --')"
            :search-placeholder="$l('Search program code or title...', 'Cari kode atau judul program...')"
            input-class="bg-[#f4f6f5] border-transparent"
          />
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="col-span-2">
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Batch Name', 'Nama Angkatan') }}</label>
            <input type="text" v-model="form.batch_name" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Quota', 'Kuota') }}</label>
            <AppNumberInput v-model="form.max_quota" :suffix="$l('Seats', 'Peserta')" :min="5" :max="100" placeholder="30" required />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Method', 'Metode') }}</label>
            <AppSearchableSelect
              v-model="form.method"
              :options="methodOptions"
              :placeholder="$l('Select Method', 'Pilih Metode')"
              :search-placeholder="$l('Search method...', 'Cari metode...')"
              input-class="bg-[#f4f6f5] border-transparent"
            />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Start Date', 'Tgl Mulai') }}</label>
            <input type="date" v-model="form.start_date" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('End Date', 'Tgl Selesai') }}</label>
            <input type="date" v-model="form.end_date" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">{{ $l('Room / Learning URL', 'Ruangan / Tautan Pembelajaran') }}</label>
          <input type="text" v-model="form.location_or_url" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
        </div>

        <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-amber-800 flex items-start gap-2.5">
          <Scale class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
          <p class="text-[11px] leading-tight">
            <template v-if="$locale === 'en'">
              <strong>Banking Dual-Control Notice:</strong> This batch will start in PENDING CHECKER status and requires authorization from the HC Learning Head (Checker).
            </template>
            <template v-else>
              <strong>Peringatan Kontrol Ganda Perbankan:</strong> Angkatan kelas ini akan berstatus MENUNGGU VERIFIKATOR dan membutuhkan otorisasi dari Kepala Pembelajaran HC.
            </template>
          </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button type="button" @click="emit('close')" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
            {{ $t('common.cancel') }}
          </button>
          <button type="submit" :disabled="loading" class="px-5 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold transition shadow-sm disabled:opacity-50">
            {{ loading ? $l('Saving...', 'Menyimpan...') : $l('Submit to Checker', 'Kirim Pengajuan ke Verifikator') }}
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
