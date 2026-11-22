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
  Check
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const loading = ref(true)
const feedbackMsg = ref('')
const feedbackType = ref('success')

const years = ref([])
const activeYear = ref(2026)

// Year Modal
const showModal = ref(false)
const editingYear = ref(null)
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
    feedbackMsg.value = err.response?.data?.message || 'Gagal memuat master tahun.'
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
    name: `Tahun Anggaran & Diklat ${nextYear}`,
    theme: '',
    start_date: `${nextYear}-01-01`,
    end_date: `${nextYear}-12-31`,
    total_budget_cap: 5500000000,
    status: 'PERENCANAAN',
    is_active: false,
    description: ''
  }
  showModal.value = true
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
  showModal.value = true
}

async function submitForm() {
  feedbackMsg.value = ''
  try {
    if (editingYear.value) {
      const { data } = await apiClient.put(`/fiscal-years/${editingYear.value.id}`, form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Master tahun berhasil diperbarui.'
    } else {
      const { data } = await apiClient.post('/fiscal-years', form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Tahun anggaran baru berhasil ditambahkan.'
    }
    showModal.value = false
    await fetchYears()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menyimpan master tahun.'
  }
}

async function handleSetActive(y) {
  feedbackMsg.value = ''
  try {
    const { data } = await apiClient.post(`/fiscal-years/${y.id}/set-active`)
    feedbackType.value = 'success'
    feedbackMsg.value = data.message
    await fetchYears()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal mengubah tahun aktif.'
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
    feedbackMsg.value = data.message
    showDeleteModal.value = false
    yearToDelete.value = null
    await fetchYears()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menghapus tahun.'
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">

    <!-- Feedback Banner -->
    <div v-if="feedbackMsg" class="transition-all">
      <div
        class="px-4 py-3 rounded-2xl text-xs flex items-center justify-between shadow-2xs border"
        :class="feedbackType === 'success' ? 'bg-[#EBF4FC] text-[#0067AC] border-blue-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 v-if="feedbackType === 'success'" class="w-4 h-4 shrink-0 text-[#0067AC]" />
          <AlertCircle v-else class="w-4 h-4 shrink-0 text-rose-600" />
          <span>{{ feedbackMsg }}</span>
        </div>
        <button @click="feedbackMsg = ''" class="font-bold text-sm hover:opacity-75">&times;</button>
      </div>
    </div>

    <!-- Top KPI Counters -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <CalendarCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Tahun Buku Aktif</p>
          <p class="text-xl font-extrabold text-[#002855]">{{ activeYearData.year || 2026 }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <Wallet class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Plafon Anggaran Aktif</p>
          <p class="text-base font-extrabold text-gray-900 truncate">{{ formatRupiah(activeYearData.total_budget_cap) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#00A0E9] flex items-center justify-center font-bold">
          <CalendarClock class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Siklus Perencanaan</p>
          <p class="text-xl font-extrabold text-gray-900">{{ years.filter(y => y.status === 'PERENCANAAN').length }} Tahun</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Calendar class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Terdata</p>
          <p class="text-xl font-extrabold text-gray-900">{{ years.length }} Periode</p>
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
            <span class="text-base font-extrabold uppercase tracking-wide">Tahun Berjalan BWS: {{ activeYearData.year }}</span>
            <span class="text-[10px] bg-emerald-400 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
              SEDANG AKTIF
            </span>
          </div>
          <p class="text-xs text-blue-100/90 mt-0.5 max-w-xl">
            {{ activeYearData.theme || 'Akselerasi Transformasi Digital, Penguatan Keamanan Siber POJK 29/2022 & Mitigasi NPL.' }}
          </p>
        </div>
      </div>

      <AppButton
        variant="white"
        icon="plus"
        size="md"
        @click="openCreateModal"
      >
        Tambah Tahun Anggaran
      </AppButton>
    </div>

    <!-- Years Table Card -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-4">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div>
          <h3 class="font-bold text-sm text-gray-900">Daftar Siklus Tahun Anggaran & Periode Pelatihan</h3>
          <p class="text-xs text-gray-400">Pengelolaan tahun buku menentukan periode usulan TNA, penganggaran diklat, dan pelaporan OJK.</p>
        </div>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-gray-100">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4 w-24 text-center">Tahun</th>
              <th class="py-3 px-4">Nama Periode & Tema Strategis</th>
              <th class="py-3 px-4">Rentang Periode Tanggal</th>
              <th class="py-3 px-4">Plafon Pagu Anggaran</th>
              <th class="py-3 px-4 text-center">Status Buku</th>
              <th class="py-3 px-4 text-center w-40">Aksi</th>
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
                <div class="flex items-center gap-2">
                  <p class="font-bold text-gray-900">{{ y.name }}</p>
                  <span
                    v-if="y.is_active"
                    class="text-[9px] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded-full font-bold uppercase"
                  >
                    Aktif
                  </span>
                </div>
                <p class="text-[11px] text-gray-500 mt-0.5">{{ y.theme || '-' }}</p>
              </td>
              <td class="py-3.5 px-4 text-gray-600 font-mono text-[11px]">
                {{ y.start_date ? y.start_date.split('T')[0] : '01-01' }} s/d {{ y.end_date ? y.end_date.split('T')[0] : '12-31' }}
              </td>
              <td class="py-3.5 px-4 font-mono font-bold text-[#0067AC]">
                {{ formatRupiah(y.total_budget_cap) }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                  :class="{
                    'bg-emerald-50 text-emerald-700 border border-emerald-200': y.status === 'AKTIF',
                    'bg-sky-50 text-[#0067AC] border border-blue-200': y.status === 'PERENCANAAN',
                    'bg-gray-100 text-gray-600 border border-gray-200': y.status === 'TUTUP_BUKU'
                  }"
                >
                  {{ y.status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    v-if="!y.is_active"
                    @click="handleSetActive(y)"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold text-[#0067AC] hover:bg-blue-50 transition border border-blue-200"
                    title="Jadikan Tahun Berjalan Utama"
                  >
                    Set Aktif
                  </button>
                  <button
                    @click="openEditModal(y)"
                    class="p-1.5 rounded-lg text-gray-600 hover:text-[#0067AC] hover:bg-blue-50 transition"
                    title="Edit Periode Tahun"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    v-if="!y.is_active"
                    @click="confirmDelete(y)"
                    class="p-1.5 rounded-lg text-gray-600 hover:text-rose-600 hover:bg-rose-50 transition"
                    title="Hapus Periode Tahun"
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

    <!-- ============================================================= -->
    <!-- MODAL: TAMBAH / EDIT MASTER TAHUN                             -->
    <!-- ============================================================= -->
    <div v-if="showModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 animate-scaleIn">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
              <Calendar class="w-4 h-4" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-gray-900">
                {{ editingYear ? 'Edit Tahun Anggaran' : 'Tambah Tahun Anggaran & Diklat' }}
              </h3>
              <p class="text-[11px] text-gray-400">Konfigurasi siklus tahun buku dan pagu anggaran BWS</p>
            </div>
          </div>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Tahun Buku *</label>
              <input
                v-model="form.year"
                type="number"
                min="2020"
                max="2040"
                required
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-bold text-gray-900"
              />
            </div>

            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Status Siklus *</label>
              <select
                v-model="form.status"
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-bold"
              >
                <option value="AKTIF">AKTIF (Tahun Berjalan)</option>
                <option value="PERENCANAAN">PERENCANAAN (TNA Cycle)</option>
                <option value="TUTUP_BUKU">TUTUP_BUKU (Audit Selesai)</option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Nama Periode Lengkap *</label>
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Contoh: Tahun Anggaran & Diklat 2026"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Tema Strategis Tahunan (Strategic Theme)</label>
            <input
              v-model="form.theme"
              type="text"
              placeholder="Contoh: Akselerasi Transformasi Digital & Penguatan Manajemen Risiko"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Tanggal Mulai *</label>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono"
              />
            </div>

            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Tanggal Berakhir *</label>
              <input
                v-model="form.end_date"
                type="date"
                required
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono"
              />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Plafon Pagu Anggaran Tahunan (Rp)</label>
            <AppNumberInput
              v-model="form.total_budget_cap"
              prefix="Rp"
              placeholder="5.000.000.000"
            />
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="yearActiveCheck"
              v-model="form.is_active"
              class="rounded text-[#0067AC] focus:ring-[#0067AC]"
            />
            <label for="yearActiveCheck" class="font-bold text-gray-700 cursor-pointer">
              Tetapkan sebagai Tahun Aktif Utama Sistem
            </label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold transition"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white font-bold transition shadow-xs"
            >
              Simpan Periode Tahun
            </button>
          </div>
        </form>
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
          <h3 class="font-bold text-base text-gray-900">Hapus Periode Tahun {{ yearToDelete?.year }}?</h3>
          <p class="text-xs text-gray-500 mt-1">
            Apakah Anda yakin ingin menghapus data tahun anggaran <strong>"{{ yearToDelete?.name }}"</strong>?
          </p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold text-xs transition"
          >
            Batal
          </button>
          <button
            @click="executeDelete"
            :disabled="isDeleting"
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs"
          >
            <span v-if="isDeleting">Menghapus...</span>
            <span v-else>Ya, Hapus Data</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
