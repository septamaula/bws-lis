<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  ShieldCheck,
  Plus,
  Edit2,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Clock,
  Layers,
  ArrowRight,
  Sparkles,
  Sliders,
  DollarSign,
  UserCheck,
  Building,
  Save,
  X
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const loading = ref(true)
const modules = ref([])
const availableRoles = ref([])
const selectedModuleCode = ref('tna')

const feedback = ref({ type: '', message: '' })

// Modal State for Step Create/Edit
const showStepModal = ref(false)
const editingStep = ref(null)
const stepForm = ref({
  step_name: '',
  role_name: 'HCBP',
  min_amount: 0,
  max_amount: null,
  sla_days: 2,
  is_active: true
})
const savingStep = ref(false)

function formatRupiah(num) {
  if (num === null || num === undefined) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

async function fetchSettings() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/approvals/settings')
    modules.value = data.modules || []
    availableRoles.value = data.availableRoles || []
    if (!selectedModuleCode.value && modules.value.length > 0) {
      selectedModuleCode.value = modules.value[0].module_code
    }
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal memuat pengaturan multi-approval.'
    }
  } finally {
    loading.value = false
  }
}

const currentModule = computed(() => {
  return modules.value.find(m => m.module_code === selectedModuleCode.value)
})

function openCreateStepModal() {
  editingStep.value = null
  stepForm.value = {
    step_name: '',
    role_name: availableRoles.value[0] || 'HCBP',
    min_amount: 0,
    max_amount: null,
    sla_days: 2,
    is_active: true
  }
  showStepModal.value = true
}

function openEditStepModal(step) {
  editingStep.value = step
  stepForm.value = {
    step_name: step.step_name,
    role_name: step.role_name,
    min_amount: step.min_amount || 0,
    max_amount: step.max_amount || null,
    sla_days: step.sla_days || 2,
    is_active: step.is_active
  }
  showStepModal.value = true
}

async function saveStep() {
  if (!stepForm.value.step_name.trim()) {
    feedback.value = { type: 'error', message: 'Nama tahapan approval wajib diisi.' }
    return
  }

  savingStep.value = true
  feedback.value = { type: '', message: '' }

  try {
    if (editingStep.value) {
      const { data } = await apiClient.put(`/approvals/settings/steps/${editingStep.value.id}`, stepForm.value)
      feedback.value = { type: 'success', message: data.message }
    } else {
      const { data } = await apiClient.post(`/approvals/settings/${selectedModuleCode.value}/steps`, stepForm.value)
      feedback.value = { type: 'success', message: data.message }
    }
    showStepModal.value = false
    await fetchSettings()
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menyimpan tahapan approval.'
    }
  } finally {
    savingStep.value = false
  }
}

async function deleteStep(step) {
  if (!confirm(`Hapus tahapan approval '${step.step_name}' dari modul ini?`)) return

  try {
    const { data } = await apiClient.delete(`/approvals/settings/steps/${step.id}`)
    feedback.value = { type: 'success', message: data.message }
    await fetchSettings()
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menghapus tahapan approval.'
    }
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<template>
  <div class="space-y-6">

    <!-- Header Description Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-3">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
            <Sliders class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-base sm:text-lg font-extrabold text-[#002855]">
              Pengaturan Alur Multi-Approval Modul
            </h2>
            <p class="text-xs text-gray-400">
              Konfigurasi tahapan verifikasi, batas plafon nominal, SLA respon, dan peran approver per modul operasional.
            </p>
          </div>
        </div>

        <button
          @click="openCreateStepModal"
          class="bg-[#0067AC] hover:bg-[#00528c] text-white px-4 py-2 rounded-full text-xs font-bold flex items-center gap-1.5 transition shadow-sm"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>+ Tambah Tahapan Approval</span>
        </button>
      </div>

      <!-- Alert Banner -->
      <div v-if="feedback.message" class="w-full pt-2">
        <div
          class="p-3.5 rounded-2xl text-xs flex items-center justify-between border"
          :class="feedback.type === 'success' ? 'bg-[#EBF4FC] border-blue-200 text-[#0067AC]' : 'bg-rose-50 border-rose-200 text-rose-700'"
        >
          <div class="flex items-center gap-2">
            <CheckCircle2 v-if="feedback.type === 'success'" class="w-4 h-4 shrink-0" />
            <AlertCircle v-else class="w-4 h-4 shrink-0" />
            <span class="font-semibold">{{ feedback.message }}</span>
          </div>
          <button @click="feedback.message = ''" class="font-bold">&times;</button>
        </div>
      </div>
    </div>

    <!-- Module Selector Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
      <button
        v-for="m in modules"
        :key="m.module_code"
        @click="selectedModuleCode = m.module_code"
        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 shrink-0 border"
        :class="selectedModuleCode === m.module_code
          ? 'bg-[#002855] text-white border-[#002855] shadow-sm'
          : 'bg-white text-gray-700 border-gray-200 hover:border-[#0067AC] hover:text-[#0067AC]'"
      >
        <Layers class="w-3.5 h-3.5" />
        <span>{{ m.module_name }}</span>
        <span
          class="text-[10px] px-1.5 py-0.2 rounded-full font-mono"
          :class="selectedModuleCode === m.module_code ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'"
        >
          {{ m.flow_steps?.length || 0 }} Step
        </span>
      </button>
    </div>

    <!-- Current Module Content & Steps -->
    <div v-if="currentModule" class="space-y-6">

      <!-- Visual Flowchart Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div>
            <h3 class="text-sm font-bold text-gray-900">Alur Pengesahan Berjenjang: {{ currentModule.module_name }}</h3>
            <p class="text-xs text-gray-400">{{ currentModule.description }}</p>
          </div>
          <span class="text-xs font-mono font-bold text-[#0067AC] bg-[#EBF4FC] px-3 py-1 rounded-full">
            MODUL: {{ currentModule.module_code.toUpperCase() }}
          </span>
        </div>

        <!-- Flowchart Sequence Nodes -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 overflow-x-auto py-2">
          <!-- Step 0: Maker -->
          <div class="p-3.5 rounded-2xl bg-gray-50 border border-gray-200 shrink-0 w-full md:w-56 space-y-1">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Originator / Pemohon</span>
            <h4 class="text-xs font-bold text-gray-900">Pengajuan Usulan</h4>
            <p class="text-[11px] text-gray-500">Dept Head / Unit Kerja Pembuat</p>
          </div>

          <ArrowRight class="w-4 h-4 text-gray-300 hidden md:block shrink-0" />

          <!-- Steps 1..N -->
          <template v-for="(step, sIdx) in currentModule.flow_steps" :key="step.id">
            <div
              class="p-3.5 rounded-2xl bg-[#EBF4FC]/60 border border-[#0067AC]/30 shrink-0 w-full md:w-56 space-y-1 relative"
            >
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#0067AC] block">
                  Tahap {{ step.step_order }}
                </span>
                <span class="text-[10px] text-gray-400 font-medium">SLA: {{ step.sla_days }}d</span>
              </div>
              <h4 class="text-xs font-bold text-[#002855] leading-snug">{{ step.step_name }}</h4>
              <p class="text-[11px] font-semibold text-[#0067AC]">Peran: {{ step.role_name }}</p>
              <span v-if="step.min_amount > 0" class="text-[10px] font-mono text-purple-700 block">
                Min: {{ formatRupiah(step.min_amount) }}
              </span>
            </div>

            <ArrowRight
              v-if="sIdx < (currentModule.flow_steps?.length - 1)"
              class="w-4 h-4 text-gray-300 hidden md:block shrink-0"
            />
          </template>

          <ArrowRight class="w-4 h-4 text-gray-300 hidden md:block shrink-0" />

          <!-- Terminal: Selesai -->
          <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 shrink-0 w-full md:w-52 space-y-1">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Status Akhir</span>
            <h4 class="text-xs font-bold text-emerald-900">Disetujui Final</h4>
            <p class="text-[11px] text-emerald-700">Resmi Masuk Rencana Kerja BWS</p>
          </div>
        </div>
      </div>

      <!-- Steps Table -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <h3 class="text-sm font-bold text-gray-900">Daftar Konfigurasi Tahapan ({{ currentModule.flow_steps?.length || 0 }} Tahap)</h3>
          <button
            @click="openCreateStepModal"
            class="text-xs font-bold text-[#0067AC] hover:underline flex items-center gap-1"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Tambah Tahap Baru</span>
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
              <tr>
                <th class="py-3 px-3 text-center">Urutan</th>
                <th class="py-3 px-3">Nama Tahapan Approval</th>
                <th class="py-3 px-3">Peran Approver (Role Level)</th>
                <th class="py-3 px-3">Batas Plafon Nominal (Threshold)</th>
                <th class="py-3 px-3 text-center">SLA Hari</th>
                <th class="py-3 px-3 text-center">Status</th>
                <th class="py-3 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="step in currentModule.flow_steps" :key="step.id" class="hover:bg-gray-50/60 transition">
                <td class="py-3 px-3 text-center font-bold text-[#0067AC] font-mono">
                  Step {{ step.step_order }}
                </td>
                <td class="py-3 px-3 font-bold text-gray-900">
                  {{ step.step_name }}
                </td>
                <td class="py-3 px-3">
                  <span class="px-2.5 py-1 rounded-full bg-[#EBF4FC] text-[#0067AC] font-semibold text-[11px]">
                    {{ step.role_name }}
                  </span>
                </td>
                <td class="py-3 px-3 font-mono text-gray-600 text-[11px]">
                  <template v-if="step.min_amount > 0 && step.max_amount">
                    {{ formatRupiah(step.min_amount) }} s/d {{ formatRupiah(step.max_amount) }}
                  </template>
                  <template v-else-if="step.min_amount > 0">
                    &gt; {{ formatRupiah(step.min_amount) }}
                  </template>
                  <template v-else-if="step.max_amount">
                    &le; {{ formatRupiah(step.max_amount) }}
                  </template>
                  <template v-else>
                    Tanpa Limit Plafon (Semua Nominal)
                  </template>
                </td>
                <td class="py-3 px-3 text-center font-bold text-gray-800">
                  {{ step.sla_days }} Hari Kerja
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="step.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                  >
                    {{ step.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="openEditStepModal(step)"
                      class="p-1.5 rounded-lg text-gray-500 hover:text-[#0067AC] hover:bg-[#EBF4FC] transition"
                      title="Edit Konfigurasi Tahap"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="deleteStep(step)"
                      class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition"
                      title="Hapus Tahap"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- MODAL TAMBAH / EDIT TAHAPAN APPROVAL -->
    <div
      v-if="showStepModal"
      class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full border border-gray-100 shadow-2xl space-y-5 animate-scaleIn">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-[#0067AC]" />
            <h3 class="text-sm font-bold text-gray-900">
              {{ editingStep ? 'Edit Tahapan Approval' : 'Tambah Tahapan Approval Baru' }}
            </h3>
          </div>
          <button @click="showStepModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="saveStep" class="space-y-4 text-xs">
          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Nama Tahapan Approval *</label>
            <input
              type="text"
              required
              v-model="stepForm.step_name"
              class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              placeholder="Contoh: Reviewer 1 - Human Capital Business Partner"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Peran yang Ditunjuk (Role Level) *</label>
            <select
              v-model="stepForm.role_name"
              required
              class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-semibold text-[#002855]"
            >
              <option v-for="r in availableRoles" :key="r" :value="r">{{ r }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Batas Minimum (Rp)</label>
              <input
                type="number"
                v-model.number="stepForm.min_amount"
                min="0"
                step="1000000"
                class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2 text-xs border border-gray-200 font-mono"
                placeholder="0"
              />
            </div>
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Batas Maksimum (Rp)</label>
              <input
                type="number"
                v-model.number="stepForm.max_amount"
                min="0"
                step="1000000"
                class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2 text-xs border border-gray-200 font-mono"
                placeholder="Kosongkan jika tanpa batas"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3 items-center">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">SLA Respon (Hari Kerja) *</label>
              <input
                type="number"
                v-model.number="stepForm.sla_days"
                required
                min="1"
                max="30"
                class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2 text-xs border border-gray-200 font-mono font-bold"
              />
            </div>
            <div class="pt-4 flex items-center gap-2">
              <input
                type="checkbox"
                id="step-active"
                v-model="stepForm.is_active"
                class="w-4 h-4 rounded text-[#0067AC] focus:ring-[#0067AC]"
              />
              <label for="step-active" class="font-bold text-gray-700">Status Tahap Aktif</label>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showStepModal = false"
              class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingStep"
              class="px-6 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-bold shadow-md transition disabled:opacity-50"
            >
              {{ savingStep ? 'Menyimpan...' : 'Simpan Tahapan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
