<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Calendar,
  CalendarCheck,
  CalendarClock,
  Plus,
  Edit2,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Clock,
  Wallet,
  Sparkles,
  ShieldCheck,
  Check,
  Save,
  ArrowLeft
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useI18n } from '@/i18n'

const { l, formatLocaleDate } = useI18n()

const loading = ref(true)
const feedbackMsg = ref('')
const feedbackType = ref('success')

const years = ref([])
const activeYear = ref(2026)

// View Mode: 'list' | 'create' | 'edit'
const viewMode = ref('list')
const editingYear = ref(null)
const isSubmitting = ref(false)

const form = ref({
  year: new Date().getFullYear(),
  name: '',
  theme: '',
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: `${new Date().getFullYear()}-12-31`,
  total_budget_cap: 5000000000,
  status: 'AKTIF',
  is_active: false,
  description: ''
})

// Delete Modal
const showDeleteModal = ref(false)
const yearToDelete = ref(null)
const isDeleting = ref(false)

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

async function fetchYears() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/fiscal-years')
    years.value = data.years || []
    activeYear.value = data.active_year || 2026
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to load fiscal year master data.', err.response?.data?.message || 'Gagal memuat master tahun.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchYears()
})

const activeYearData = computed(() => {
  return years.value.find(y => y.is_active) || years.value[0] || {}
})

function openCreateModal() {
  editingYear.value = null
  const nextYear = years.value.length ? Math.max(...years.value.map(y => y.year)) + 1 : new Date().getFullYear() + 1
  form.value = {
    year: nextYear,
    name: l(`Budget & Training Year ${nextYear}`, `Tahun Anggaran & Diklat ${nextYear}`),
    theme: '',
    start_date: `${nextYear}-01-01`,
    end_date: `${nextYear}-12-31`,
    total_budget_cap: 5500000000,
    status: 'PERENCANAAN',
    is_active: false,
    description: ''
  }
  viewMode.value = 'create'
}

function openEditModal(y) {
  editingYear.value = y
  form.value = {
    year: y.year,
    name: y.name,
    theme: y.theme || '',
    start_date: y.start_date ? y.start_date.split('T')[0] : '',
    end_date: y.end_date ? y.end_date.split('T')[0] : '',
    total_budget_cap: y.total_budget_cap || 0,
    status: y.status || 'AKTIF',
    is_active: y.is_active ?? false,
    description: y.description || ''
  }
  viewMode.value = 'edit'
}

async function submitForm() {
  feedbackMsg.value = ''
  isSubmitting.value = true
  try {
    if (editingYear.value) {
      const { data } = await apiClient.put(`/fiscal-years/${editingYear.value.id}`, form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = l('Fiscal year master updated successfully.', data.message || 'Master tahun berhasil diperbarui.')
    } else {
      const { data } = await apiClient.post('/fiscal-years', form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = l('New fiscal year added successfully.', data.message || 'Tahun anggaran baru berhasil ditambahkan.')
    }
    viewMode.value = 'list'
    await fetchYears()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to save fiscal year master.', err.response?.data?.message || 'Gagal menyimpan master tahun.')
  } finally {
    isSubmitting.value = false
  }
}

async function handleSetActive(y) {
  feedbackMsg.value = ''
  try {
    const { data } = await apiClient.post(`/fiscal-years/${y.id}/set-active`)
    feedbackType.value = 'success'
    feedbackMsg.value = l(`Fiscal year ${y.year} has been set as the Active Current Year.`, data.message || `Tahun ${y.year} berhasil ditetapkan sebagai Tahun Berjalan Aktif.`)
    await fetchYears()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to update active fiscal year.', err.response?.data?.message || 'Gagal mengubah tahun aktif.')
  }
}

function confirmDelete(y) {
  yearToDelete.value = y
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!yearToDelete.value) return
  isDeleting.value = true
  feedbackMsg.value = ''
  try {
    const { data } = await apiClient.delete(`/fiscal-years/${yearToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = l(`Fiscal year ${yearToDelete.value.year} deleted successfully.`, data.message || `Tahun anggaran ${yearToDelete.value.year} berhasil dihapus.`)
    showDeleteModal.value = false
    yearToDelete.value = null
    await fetchYears()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to delete fiscal year record.', err.response?.data?.message || 'Gagal menghapus data tahun.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">

    <!-- Feedback Banner -->
    <div v-if="feedbackMsg" class="transition-all animate-fadeIn">
      <div
        class="px-4 py-3 rounded-2xl text-xs flex items-center justify-between shadow-2xs border"
        :class="feedbackType === 'success' ? 'bg-[#EBF4FC] text-[#0067AC] border-blue-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 v-if="feedbackType === 'success'" class="w-4 h-4 shrink-0 text-[#0067AC]" />
          <AlertCircle v-else class="w-4 h-4 shrink-0 text-rose-600" />
          <span>{{ feedbackMsg }}</span>
        </div>
        <button @click="feedbackMsg = ''" class="font-bold text-sm hover:opacity-75 cursor-pointer">&times;</button>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- VIEW 1: FORM PAGE (TAMBAH / EDIT MASTER TAHUN)                -->
    <!-- ============================================================= -->
    <div v-if="viewMode === 'create' || viewMode === 'edit'" class="space-y-6 animate-fadeIn">
      <!-- Subpage Header -->
      <SubpageHeader
        :back-label="l('Back to Fiscal Year List', 'Kembali ke Daftar Tahun Anggaran')"
        :title="viewMode === 'edit' ? l(`Edit Fiscal & Training Year ${form.year}`, `Ubah Tahun Anggaran & Diklat ${form.year}`) : l('Add Fiscal & Training Year', 'Tambah Tahun Anggaran & Diklat')"
        :subtitle="l('Configure fiscal year cycles, budget ceilings, and BWS annual strategic themes', 'Konfigurasi siklus tahun buku, plafon pagu anggaran, dan tema strategis tahunan BWS')"
        :badge="viewMode === 'edit' ? l('Edit Mode', 'Mode Ubah') : l('New Year', 'Tahun Baru')"
        @back="viewMode = 'list'"
      />

      <!-- Form Container Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-gray-100 space-y-6">
        <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
          <div class="w-10 h-10 rounded-2xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold shadow-2xs">
            <Calendar class="w-5 h-5" />
          </div>
          <div>
            <h3 class="font-extrabold text-base text-gray-900">
              {{ viewMode === 'edit' ? l(`Update Fiscal Year ${form.year}`, `Perbarui Data Tahun Anggaran ${form.year}`) : l('New Fiscal & Training Year Form', 'Formulir Tahun Anggaran & Diklat Baru') }}
            </h3>
            <p class="text-xs text-gray-400">{{ l('Ensure fiscal cycles and budget ceilings align with BWS management approval', 'Pastikan siklus dan plafon pagu anggaran sesuai ketetapan manajemen BWS') }}</p>
          </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-5 text-xs">
          <!-- Row 1: Tahun & Status -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="space-y-1.5">
              <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
                {{ l('Fiscal Year', 'Tahun Buku') }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.number="form.year"
                type="number"
                min="2020"
                max="2040"
                required
                :disabled="viewMode === 'edit'"
                class="w-full bg-[#F8FAFC] rounded-xl px-4 py-2.5 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-bold text-gray-900 font-mono disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
              <p class="text-[10px] text-gray-400">{{ l('Fiscal year for training program proposals and budgeting.', 'Tahun fiskal usulan program dan penganggaran diklat.') }}</p>
            </div>

            <div class="space-y-1.5">
              <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
                {{ l('Cycle Status', 'Status Siklus') }} <span class="text-rose-500">*</span>
              </label>
              <AppSearchableSelect
                v-model="form.status"
                :options="[
                  { value: 'AKTIF', label: l('ACTIVE (Current Year)', 'AKTIF (Tahun Berjalan)'), subLabel: l('Current Fiscal Year', 'Tahun Anggaran Berjalan') },
                  { value: 'PERENCANAAN', label: l('PLANNING (TNA Cycle)', 'PERENCANAAN (Siklus TNA)'), subLabel: l('Proposal Window Open', 'Siklus Usulan Dibuka') },
                  { value: 'TUTUP_BUKU', label: l('CLOSED (Audit Completed)', 'TUTUP BUKU (Audit Selesai)'), subLabel: l('Archived / Closed', 'Arsip / Tutup') }
                ]"
                :placeholder="l('Select cycle status...', 'Pilih status siklus...')"
                :search-placeholder="l('Search status...', 'Cari status...')"
                input-class="rounded-xl px-4 py-2.5 border-gray-200 font-bold text-gray-800 bg-[#F8FAFC]"
              />
              <p class="text-[10px] text-gray-400">{{ l('Status determines whether this year is open for TNA proposals.', 'Status menentukan apakah tahun ini dibuka untuk usulan TNA.') }}</p>
            </div>
          </div>

          <!-- Row 2: Nama Periode Lengkap -->
          <div class="space-y-1.5">
            <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
              {{ l('Full Period Name', 'Nama Periode Lengkap') }} <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              :placeholder="l('e.g. Budget & Training Year 2028', 'Contoh: Tahun Anggaran & Diklat 2028')"
              class="w-full bg-[#F8FAFC] rounded-xl px-4 py-2.5 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium text-gray-800"
            />
          </div>

          <!-- Row 3: Tema Strategis Tahunan -->
          <div class="space-y-1.5">
            <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
              {{ l('Annual Strategic Theme', 'Tema Strategis Tahunan') }}
            </label>
            <input
              v-model="form.theme"
              type="text"
              :placeholder="l('e.g. Accelerating Digital Transformation & Risk Management Strengthening', 'Contoh: Akselerasi Transformasi Digital & Penguatan Manajemen Risiko')"
              class="w-full bg-[#F8FAFC] rounded-xl px-4 py-2.5 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium text-gray-800"
            />
            <p class="text-[10px] text-gray-400">{{ l('BWS strategic objectives guiding training priorities.', 'Arah sasaran strategis BWS sebagai panduan prioritas diklat.') }}</p>
          </div>

          <!-- Row 4: Tanggal Mulai & Tanggal Berakhir -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="space-y-1.5">
              <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
                {{ l('Start Date', 'Tanggal Mulai') }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full bg-[#F8FAFC] rounded-xl px-4 py-2.5 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono text-gray-800"
              />
            </div>

            <div class="space-y-1.5">
              <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
                {{ l('End Date', 'Tanggal Berakhir') }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.end_date"
                type="date"
                required
                class="w-full bg-[#F8FAFC] rounded-xl px-4 py-2.5 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono text-gray-800"
              />
            </div>
          </div>

          <!-- Row 5: Plafon Pagu Anggaran Tahunan -->
          <div class="space-y-1.5">
            <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
              {{ l('Annual Budget Ceiling (IDR)', 'Plafon Pagu Anggaran Tahunan (Rp)') }}
            </label>
            <AppNumberInput
              v-model="form.total_budget_cap"
              :prefix="l('IDR', 'Rp')"
              placeholder="5.000.000.000"
            />
            <p class="text-[10px] text-gray-400 font-mono">
              {{ l('Maximum training budget ceiling available for proposals in this fiscal year.', 'Batas total plafon anggaran diklat yang dapat diajukan pada tahun ini.') }}
            </p>
          </div>

          <!-- Row 6: Deskripsi / Catatan Tambahan -->
          <div class="space-y-1.5">
            <label class="block font-bold text-gray-700 uppercase tracking-wider text-[11px]">
              {{ l('Description / Additional Notes (Optional)', 'Deskripsi / Catatan Tambahan (Opsional)') }}
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              :placeholder="l('Board directives, Board Decree references, or regulatory provisions...', 'Catatan arahan direksi, referensi SK Direksi, atau ketentuan regulator...')"
              class="w-full bg-[#F8FAFC] rounded-xl px-4 py-2.5 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none text-xs text-gray-800"
            ></textarea>
          </div>

          <!-- Row 7: Checkbox Aktif -->
          <div class="flex items-center gap-3 p-4 bg-blue-50/50 rounded-2xl border border-blue-100">
            <input
              type="checkbox"
              id="yearActiveCheck"
              v-model="form.is_active"
              class="w-4 h-4 rounded text-[#0067AC] focus:ring-[#0067AC] cursor-pointer"
            />
            <label for="yearActiveCheck" class="text-xs font-bold text-gray-700 cursor-pointer">
              {{ l('Set as Primary Active System Year', 'Tetapkan sebagai Tahun Aktif Utama Sistem') }}
              <span class="block text-[10px] text-gray-500 font-normal mt-0.5">
                {{ l('This fiscal year will be set as the active default across TNA, Program, and Reporting modules.', 'Tahun ini akan menjadi bawaan aktif pada seluruh modul TNA, Program, dan Pelaporan.') }}
              </span>
            </label>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-between pt-5 border-t border-gray-100">
            <button
              type="button"
              @click="viewMode = 'list'"
              class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-100 font-bold transition text-xs cursor-pointer"
            >
              {{ l('Cancel', 'Batal') }}
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-6 py-2.5 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white font-bold transition shadow-xs text-xs flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ isSubmitting ? l('Saving...', 'Menyimpan...') : l('Save Fiscal Period', 'Simpan Periode Tahun') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- VIEW 2: LIST VIEW (KPIS, BANNER, TABEL MASTER TAHUN)          -->
    <!-- ============================================================= -->
    <div v-else class="space-y-6 animate-fadeIn">

      <!-- Top KPI Counters -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
            <CalendarCheck class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ l('Active Fiscal Year', 'Tahun Buku Aktif') }}</p>
            <p class="text-xl font-extrabold text-[#002855]">{{ activeYearData.year || 2026 }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <Wallet class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ l('Active Budget Ceiling', 'Plafon Anggaran Aktif') }}</p>
            <p class="text-base font-extrabold text-gray-900 truncate">{{ formatRupiah(activeYearData.total_budget_cap) }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#00A0E9] flex items-center justify-center font-bold">
            <CalendarClock class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ l('Planning Cycles', 'Siklus Perencanaan') }}</p>
            <p class="text-xl font-extrabold text-gray-900">{{ years.filter(y => y.status === 'PERENCANAAN').length }} {{ l('Years', 'Tahun') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <Calendar class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ l('Total Recorded', 'Total Terdata') }}</p>
            <p class="text-xl font-extrabold text-gray-900">{{ years.length }} {{ l('Periods', 'Periode') }}</p>
          </div>
        </div>
      </div>

      <!-- Active Year Focus Banner -->
      <div class="bg-gradient-to-r from-[#002855] via-[#003876] to-[#005088] rounded-3xl p-5 text-white shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border border-blue-900/30">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-white shrink-0 shadow-inner">
            <CalendarCheck class="w-6 h-6 text-amber-300" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-base font-extrabold uppercase tracking-wide">{{ l('BWS Current Year:', 'Tahun Berjalan BWS:') }} {{ activeYearData.year }}</span>
              <span class="text-[10px] bg-emerald-400 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                {{ l('CURRENTLY ACTIVE', 'SEDANG AKTIF') }}
              </span>
            </div>
            <p class="text-xs text-blue-100/90 mt-0.5 max-w-xl">
              {{ activeYearData.theme || l('Accelerating Digital Transformation, POJK 29/2022 Cybersecurity Strengthening & NPL Mitigation.', 'Akselerasi Transformasi Digital, Penguatan Keamanan Siber POJK 29/2022 & Mitigasi NPL.') }}
            </p>
          </div>
        </div>

        <AppButton
          variant="white"
          icon="plus"
          size="md"
          @click="openCreateModal"
        >
          {{ l('Add Fiscal Year', 'Tambah Tahun Anggaran') }}
        </AppButton>
      </div>

      <!-- Years Table Card -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div>
            <h3 class="font-bold text-sm text-gray-900">{{ l('Fiscal Year Cycles & Training Periods', 'Daftar Siklus Tahun Anggaran & Periode Pelatihan') }}</h3>
            <p class="text-xs text-gray-400">{{ l('Fiscal year management governs TNA proposal windows, training budgeting, and OJK regulatory reporting.', 'Pengelolaan tahun buku menentukan periode usulan TNA, penganggaran diklat, dan pelaporan OJK.') }}</p>
          </div>
          <AppButton
            variant="primary"
            icon="plus"
            size="sm"
            @click="openCreateModal"
          >
            {{ l('Add Period', 'Tambah Periode') }}
          </AppButton>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-gray-100">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-24 text-center">{{ l('Year', 'Tahun') }}</th>
                <th class="py-3 px-4">{{ l('Period Name & Strategic Theme', 'Nama Periode & Tema Strategis') }}</th>
                <th class="py-3 px-4">{{ l('Date Range', 'Rentang Periode Tanggal') }}</th>
                <th class="py-3 px-4">{{ l('Budget Ceiling', 'Plafon Pagu Anggaran') }}</th>
                <th class="py-3 px-4 text-center">{{ l('Book Status', 'Status Buku') }}</th>
                <th class="py-3 px-4 text-center w-40">{{ l('Actions', 'Aksi') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(y, idx) in years"
                :key="y.id"
                class="hover:bg-blue-50/30 transition group"
                :class="{ 'bg-blue-50/20 font-medium': y.is_active }"
              >
                <td class="py-3.5 px-4 text-center text-gray-400 font-semibold">{{ idx + 1 }}</td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    class="font-mono text-sm font-extrabold px-2.5 py-1 rounded-xl"
                    :class="y.is_active ? 'bg-[#0067AC] text-white shadow-2xs' : 'bg-gray-100 text-gray-800'"
                  >
                    {{ y.year }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-gray-900 flex items-center gap-2">
                    <span>{{ y.name }}</span>
                    <span v-if="y.is_active" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                      {{ l('Currently Active', 'Aktif Berjalan') }}
                    </span>
                  </div>
                  <p v-if="y.theme" class="text-gray-400 text-[11px] mt-0.5 line-clamp-1 italic">
                    "{{ y.theme }}"
                  </p>
                </td>
                <td class="py-3.5 px-4 font-mono text-[11px] text-gray-600">
                  {{ y.start_date ? y.start_date.split('T')[0] : '-' }} {{ l('to', 's/d') }} {{ y.end_date ? y.end_date.split('T')[0] : '-' }}
                </td>
                <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                  {{ formatRupiah(y.total_budget_cap) }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block"
                    :class="{
                      'bg-emerald-100 text-emerald-800 border border-emerald-200': y.status === 'AKTIF',
                      'bg-sky-100 text-sky-800 border border-sky-200': y.status === 'PERENCANAAN',
                      'bg-gray-100 text-gray-600 border border-gray-200': y.status === 'TUTUP_BUKU'
                    }"
                  >
                    {{ y.status === 'AKTIF' ? l('ACTIVE', 'AKTIF') : (y.status === 'PERENCANAAN' ? l('PLANNING', 'PERENCANAAN') : l('CLOSED', 'TUTUP BUKU')) }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      v-if="!y.is_active"
                      @click="handleSetActive(y)"
                      class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#0067AC] hover:bg-blue-50 transition border border-blue-200 cursor-pointer"
                      :title="l('Set as Primary Current Year', 'Jadikan Tahun Berjalan Utama')"
                    >
                      {{ l('Set Active', 'Aktifkan') }}
                    </button>
                    <button
                      @click="openEditModal(y)"
                      class="p-1.5 rounded-lg text-gray-600 hover:text-[#0067AC] hover:bg-blue-50 transition cursor-pointer"
                      :title="l('Edit Fiscal Period', 'Ubah Periode Tahun')"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      v-if="!y.is_active"
                      @click="confirmDelete(y)"
                      class="p-1.5 rounded-lg text-gray-600 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                      :title="l('Delete Fiscal Period', 'Hapus Periode Tahun')"
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

    <!-- ============================================================= -->
    <!-- MODAL: HAPUS TAHUN                                            -->
    <!-- ============================================================= -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-gray-100 space-y-4 animate-scaleIn text-center">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <Trash2 class="w-6 h-6" />
        </div>
        <div>
          <h3 class="font-bold text-base text-gray-900">{{ l(`Delete Fiscal Period ${yearToDelete?.year}?`, `Hapus Periode Tahun ${yearToDelete?.year}?`) }}</h3>
          <p class="text-xs text-gray-500 mt-1">
            {{ l('Are you sure you want to delete fiscal year record', 'Apakah Anda yakin ingin menghapus data tahun anggaran') }} <strong>"{{ yearToDelete?.name }}"</strong>?
          </p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold text-xs transition cursor-pointer"
          >
            {{ l('Cancel', 'Batal') }}
          </button>
          <button
            @click="executeDelete"
            :disabled="isDeleting"
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs cursor-pointer"
          >
            <span v-if="isDeleting">{{ l('Deleting...', 'Menghapus...') }}</span>
            <span v-else>{{ l('Yes, Delete Data', 'Ya, Hapus Data') }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
