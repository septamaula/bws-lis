<script setup>
import { reactive, ref, computed } from 'vue'
import { X, Plus, Trash2, ClipboardList } from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useI18n } from '@/i18n'

const { l, t } = useI18n()

defineProps({
  show: Boolean,
  departments: {
    type: Array,
    default: () => [
      'Commercial & Corporate Banking',
      'Retail & Consumer Banking',
      'Treasury & Capital Market',
      'Risk Management & Compliance',
      'Information Technology & Operations',
      'Human Capital & Legal'
    ]
  }
})

const emit = defineEmits(['close', 'submitted'])
const loading = ref(false)
const errorMessage = ref('')

const priorityOptions = computed(() => [
  { value: 'Critical', label: l('Critical', 'Kritis') },
  { value: 'High', label: l('High', 'Tinggi') },
  { value: 'Medium', label: l('Medium', 'Sedang') },
  { value: 'Low', label: l('Low', 'Rendah') }
])

const form = reactive({
  department: 'Commercial & Corporate Banking',
  year: 2026,
  priority: 'High',
  items: [
    {
      competency_gap: 'Corporate Financial Statement & Cashflow Analysis Skills',
      recommended_program: 'Corporate Credit Analysis & Modeling',
      target_audience: 'Relationship Manager & Credit Analyst',
      target_participants_count: 25,
      estimated_cost: 45000000,
      urgency: 'High'
    }
  ]
})

function addItem() {
  form.items.push({
    competency_gap: '',
    recommended_program: '',
    target_audience: 'Officer & Manager',
    target_participants_count: 10,
    estimated_cost: 15000000,
    urgency: 'Medium'
  })
}

function removeItem(index) {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  }
}

async function submit() {
  loading.value = true
  errorMessage.value = ''
  try {
    await apiClient.post('/tna', form)
    emit('submitted', l('TNA proposal has been submitted to HCBP.', 'Usulan TNA berhasil dikirim ke HCBP.'))
    emit('close')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || l('Failed to submit TNA proposal.', 'Gagal mengajukan usulan TNA.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

    <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-5 z-10 animate-scaleIn">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-full bg-emerald-50 text-[#0067AC] flex items-center justify-center font-bold">
            <ClipboardList class="w-4 h-4" />
          </div>
          <div>
            <h3 class="font-bold text-base text-gray-900">{{ $l('New TNA Proposal Submission Form', 'Formulir Pengajuan Usulan TNA Baru') }}</h3>
            <p class="text-xs text-gray-400">{{ $l('Training Needs Analysis & Annual Budget Ceiling Allocation', 'Analisis Kebutuhan Pelatihan & Alokasi Pagu Anggaran Tahunan') }}</p>
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Division / Department', 'Divisi / Departemen') }}</label>
            <AppSearchableSelect
              v-model="form.department"
              :options="departments"
              :placeholder="$l('Select Department...', 'Pilih Departemen...')"
              :search-placeholder="$l('Search division...', 'Cari divisi...')"
              input-class="bg-[#f4f6f5] rounded-xl px-3 py-2 border-transparent"
            />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Fiscal Year', 'Tahun Anggaran') }}</label>
            <input type="number" v-model.number="form.year" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Priority', 'Prioritas') }}</label>
            <AppSearchableSelect
              v-model="form.priority"
              :options="priorityOptions"
              :placeholder="$l('Select Priority...', 'Pilih Prioritas...')"
              :search-placeholder="$l('Search priority...', 'Cari prioritas...')"
              input-class="bg-[#f4f6f5] rounded-xl px-3 py-2 border-transparent"
            />
          </div>
        </div>

        <div class="space-y-3 pt-2 max-h-64 overflow-y-auto pr-1">
          <div class="flex items-center justify-between">
            <h4 class="font-bold text-gray-800">{{ $l('Competency Needs List', 'Daftar Kebutuhan Kompetensi') }}</h4>
            <button type="button" @click="addItem" class="text-xs text-[#0067AC] font-semibold hover:underline flex items-center gap-1">
              <Plus class="w-3.5 h-3.5" /> {{ $l('Add Requirement', 'Tambah Kebutuhan') }}
            </button>
          </div>

          <div v-for="(item, idx) in form.items" :key="idx" class="p-3 bg-[#f8faf9] rounded-2xl border border-gray-100 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-[11px] text-gray-600">{{ $l('Item', 'Butir') }} #{{ idx + 1 }}</span>
              <button v-if="form.items.length > 1" type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
            <input
              type="text"
              v-model="item.competency_gap"
              :placeholder="$l('Required competency...', 'Kompetensi yang dibutuhkan...')"
              class="w-full bg-white rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
              required
            />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <input
                type="text"
                v-model="item.recommended_program"
                :placeholder="$l('Training program name...', 'Nama program pelatihan...')"
                class="w-full bg-white rounded-xl px-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                required
              />
              <input
                type="text"
                v-model="item.target_audience"
                :placeholder="$l('Target participant roles...', 'Target jabatan peserta...')"
                class="w-full bg-white rounded-xl px-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                required
              />
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-[10px] text-gray-500 mb-0.5">{{ $l('Target Participants', 'Target Peserta') }}</label>
                <input
                  type="number"
                  v-model.number="item.target_participants_count"
                  class="w-full bg-white rounded-xl px-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                  required
                />
              </div>
              <div>
                <label class="block text-[10px] text-gray-500 mb-0.5">{{ $l('Estimated Cost (IDR)', 'Estimasi Biaya (IDR)') }}</label>
                <input
                  type="number"
                  v-model.number="item.estimated_cost"
                  class="w-full bg-white rounded-xl px-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                  required
                />
              </div>
              <div>
                <label class="block text-[10px] text-gray-500 mb-0.5">{{ $l('Urgency', 'Urgensi') }}</label>
                <AppSearchableSelect
                  v-model="item.urgency"
                  :options="priorityOptions"
                  :placeholder="$l('Urgency...', 'Urgensi...')"
                  :search-placeholder="$t('common.search')"
                  input-class="bg-white rounded-xl px-2 py-1.5 border-gray-200"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button type="button" @click="emit('close')" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
            {{ $t('common.cancel') }}
          </button>
          <button type="submit" :disabled="loading" class="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-semibold transition shadow-sm disabled:opacity-50">
            {{ loading ? $l('Submitting...', 'Mengirim...') : $l('Submit Proposal to HCBP', 'Kirim Usulan ke HCBP') }}
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
