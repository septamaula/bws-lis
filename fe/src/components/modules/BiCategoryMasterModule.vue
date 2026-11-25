<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Scale,
  Plus,
  Edit2,
  Trash2,
  Search,
  CheckCircle2,
  AlertCircle,
  FileText,
  ShieldCheck,
  Building,
  X,
  Save,
  ArrowLeft,
  BookOpenCheck
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import SubpageHeader from '@/components/SubpageHeader.vue'

const emit = defineEmits(['back'])

const loading = ref(true)
const feedbackMsg = ref('')
const feedbackType = ref('success')

const categories = ref([])
const stats = ref({
  total: 0,
  mandatory: 0,
  active: 0
})

const searchQuery = ref('')
const selectedMandatory = ref('ALL')

// Modal state
const showModal = ref(false)
const editingItem = ref(null)
const isSubmitting = ref(false)

const form = ref({
  code: '',
  name: '',
  regulation_reference: '',
  description: '',
  is_mandatory: true,
  is_active: true
})

// Delete modal
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const isDeleting = ref(false)

async function fetchData() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/bi-categories')
    categories.value = data.categories || []
    if (data.stats) {
      stats.value = data.stats
    }
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal memuat master data Kategori BI/OJK.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})

const filteredList = computed(() => {
  return categories.value.filter(item => {
    if (selectedMandatory.value === 'MANDATORY' && !item.is_mandatory) return false
    if (selectedMandatory.value === 'OPTIONAL' && item.is_mandatory) return false

    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase()
    return (
      item.name?.toLowerCase().includes(q) ||
      item.code?.toLowerCase().includes(q) ||
      item.regulation_reference?.toLowerCase().includes(q) ||
      item.description?.toLowerCase().includes(q)
    )
  })
})

function openCreateModal() {
  editingItem.value = null
  const nextNum = String(categories.value.length + 1).padStart(2, '0')
  form.value = {
    code: `BI-REG-${nextNum}`,
    name: '',
    regulation_reference: '',
    description: '',
    is_mandatory: true,
    is_active: true
  }
  showModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  form.value = {
    code: item.code,
    name: item.name,
    regulation_reference: item.regulation_reference || '',
    description: item.description || '',
    is_mandatory: !!item.is_mandatory,
    is_active: !!item.is_active
  }
  showModal.value = true
}

async function submitForm() {
  if (!form.value.code || !form.value.name) {
    alert('Kode dan Nama Kategori wajib diisi!')
    return
  }

  isSubmitting.value = true
  feedbackMsg.value = ''

  try {
    if (editingItem.value) {
      const { data } = await apiClient.put(`/bi-categories/${editingItem.value.id}`, form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Kategori BI berhasil diperbarui.'
    } else {
      const { data } = await apiClient.post('/bi-categories', form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Kategori BI baru berhasil ditambahkan.'
    }
    showModal.value = false
    await fetchData()
    setTimeout(() => {
      feedbackMsg.value = ''
    }, 4000)
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan kategori BI.')
  } finally {
    isSubmitting.value = false
  }
}

function confirmDelete(item) {
  itemToDelete.value = item
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!itemToDelete.value) return
  isDeleting.value = true
  try {
    const { data } = await apiClient.delete(`/bi-categories/${itemToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = data.message || 'Kategori BI berhasil dihapus.'
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchData()
    setTimeout(() => {
      feedbackMsg.value = ''
    }, 4000)
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus kategori BI.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-16">

    <!-- Subpage Header -->
    <SubpageHeader
      back-label="Kembali ke Program List"
      title="Kategori Regulasi BI / OJK"
      subtitle="Klasifikasi kurikulum kepatuhan Bank Indonesia & OJK yang dirujuk saat penyusunan program kerja diklat"
      badge="Regulasi Bank Indonesia & OJK"
      @back="emit('back')"
    />

    <!-- Feedback Alert -->
    <div
      v-if="feedbackMsg"
      :class="[
        'p-4 rounded-2xl flex items-center gap-3 text-xs font-semibold transition shadow-2xs border',
        feedbackType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'
      ]"
    >
      <CheckCircle2 v-if="feedbackType === 'success'" class="w-5 h-5 text-emerald-600 shrink-0" />
      <AlertCircle v-else class="w-5 h-5 text-rose-600 shrink-0" />
      <span>{{ feedbackMsg }}</span>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      
      <!-- Total Categories -->
      <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between">
        <div class="flex flex-col gap-1">
          <span class="text-[11px] font-bold tracking-wider uppercase text-gray-400">Total Kategori Regulasi</span>
          <span class="text-2xl font-black text-gray-900">{{ stats.total }}</span>
          <span class="text-[10px] text-gray-500 font-semibold">Tercatat dalam sistem</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Scale class="w-6 h-6" />
        </div>
      </div>

      <!-- Mandatory Categories -->
      <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between">
        <div class="flex flex-col gap-1">
          <span class="text-[11px] font-bold tracking-wider uppercase text-amber-500">Wajib Regulasi (Mandatori)</span>
          <span class="text-2xl font-black text-amber-600">{{ stats.mandatory }}</span>
          <span class="text-[10px] text-gray-500 font-semibold">POJK / PBI / PADG BI</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
          <ShieldCheck class="w-6 h-6" />
        </div>
      </div>

      <!-- Active Categories -->
      <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs flex items-center justify-between">
        <div class="flex flex-col gap-1">
          <span class="text-[11px] font-bold tracking-wider uppercase text-emerald-600">Status Aktif Digunakan</span>
          <span class="text-2xl font-black text-emerald-700">{{ stats.active }}</span>
          <span class="text-[10px] text-emerald-600 font-semibold">Muncul di pilihan dropdown</span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
          <BookOpenCheck class="w-6 h-6" />
        </div>
      </div>

    </div>

    <!-- Main Content Panel -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-2xs p-6 flex flex-col gap-5">
      
      <!-- Toolbar -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Search & Filter -->
        <div class="flex flex-wrap items-center gap-3 flex-1">
          
          <div class="relative min-w-[260px] max-w-sm flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode, nama kategori, dasar regulasi..."
              class="w-full bg-gray-50 border border-gray-200 rounded-2xl pl-10 pr-4 py-2.5 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0067AC] focus:bg-white transition"
            />
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
          </div>

          <div class="flex items-center gap-2 min-w-[210px]">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider shrink-0">Mandatori:</span>
            <AppSearchableSelect
              v-model="selectedMandatory"
              :options="[
                { value: 'ALL', label: 'Semua Sifat' },
                { value: 'MANDATORY', label: 'Wajib Regulasi' },
                { value: 'OPTIONAL', label: 'Non-Mandatori / Pendukung' }
              ]"
              placeholder="Semua Sifat"
              search-placeholder="Cari sifat..."
              input-class="bg-gray-50 rounded-2xl py-2 px-3 border-gray-200 font-bold text-gray-700"
            />
          </div>

        </div>

        <!-- Add Button -->
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#0067AC] hover:bg-[#00558F] text-white text-xs font-bold rounded-2xl shadow-xs transition cursor-pointer shrink-0"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Kategori BI Baru</span>
        </button>

      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-100">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/80 border-b border-gray-200 text-[11px] font-extrabold text-gray-600 uppercase tracking-wider">
              <th class="py-3 px-4 w-28">Kode</th>
              <th class="py-3 px-4">Nama Kategori Regulasi</th>
              <th class="py-3 px-4">Dasar Hukum / Regulasi Acuan</th>
              <th class="py-3 px-4 w-36 text-center">Sifat Regulasi</th>
              <th class="py-3 px-4 w-24 text-center">Status</th>
              <th class="py-3 px-4 w-28 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-xs">
            <tr v-if="loading">
              <td colspan="6" class="py-12 text-center text-gray-400 font-semibold">
                Memuat data kategori BI...
              </td>
            </tr>

            <tr v-else-if="filteredList.length === 0">
              <td colspan="6" class="py-12 text-center text-gray-400 font-semibold">
                Tidak ada kategori BI yang sesuai dengan filter pencarian.
              </td>
            </tr>

            <tr
              v-for="item in filteredList"
              :key="item.id"
              class="hover:bg-blue-50/30 transition group"
            >
              <!-- Code -->
              <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                <span class="inline-block px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-[11px] border border-gray-200">
                  {{ item.code }}
                </span>
              </td>

              <!-- Name & Description -->
              <td class="py-3.5 px-4">
                <div class="font-extrabold text-gray-900 group-hover:text-[#0067AC] transition">
                  {{ item.name }}
                </div>
                <div v-if="item.description" class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">
                  {{ item.description }}
                </div>
              </td>

              <!-- Regulation Reference -->
              <td class="py-3.5 px-4 font-medium text-gray-700">
                <div class="flex items-center gap-1.5">
                  <FileText class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                  <span class="text-xs font-semibold">{{ item.regulation_reference || '-' }}</span>
                </div>
              </td>

              <!-- Mandatory Badge -->
              <td class="py-3.5 px-4 text-center">
                <span
                  v-if="item.is_mandatory"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200"
                >
                  <ShieldCheck class="w-3 h-3 text-amber-600" />
                  Wajib Regulasi
                </span>
                <span
                  v-else
                  class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200"
                >
                  Pendukung / Non-Mandatori
                </span>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 text-center">
                <span
                  v-if="item.is_active"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Aktif
                </span>
                <span
                  v-else
                  class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200"
                >
                  Non-Aktif
                </span>
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="openEditModal(item)"
                    class="p-1.5 text-gray-400 hover:text-[#0067AC] hover:bg-blue-50 rounded-lg transition cursor-pointer"
                    title="Edit Kategori BI"
                  >
                    <Edit2 class="w-4 h-4" />
                  </button>
                  <button
                    @click="confirmDelete(item)"
                    class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer"
                    title="Hapus Kategori BI"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Modal Form (Create/Edit) -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 animate-scaleUp">
        
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
              <Scale class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">
                {{ editingItem ? 'Edit Kategori BI / OJK' : 'Tambah Kategori BI Baru' }}
              </h3>
              <p class="text-[11px] text-gray-500">Konfigurasi klasifikasi kepatuhan regulasi diklat</p>
            </div>
          </div>
          <button
            @click="showModal = false"
            class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="flex flex-col gap-3.5 text-xs">
          
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">
              Kode Kategori <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.code"
              type="text"
              required
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 font-mono font-bold text-gray-900 focus:border-[#0067AC] focus:outline-none uppercase"
              placeholder="e.g. BI-OPS-01"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">
              Nama Kategori Regulasi <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 font-bold text-gray-900 focus:border-[#0067AC] focus:outline-none"
              placeholder="e.g. Operasional Perbankan & Sistem Pembayaran"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">
              Dasar Regulasi Acuan (POJK / PADG / UU)
            </label>
            <input
              v-model="form.regulation_reference"
              type="text"
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
              placeholder="e.g. PADG BI No. 24/2022 & SKNBI/BI-FAST"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">
              Deskripsi / Ruang Lingkup Materi
            </label>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
              placeholder="Keterangan materi diklat yang tercakup..."
            ></textarea>
          </div>

          <div class="flex flex-col gap-2 pt-1 border-t border-gray-100">
            <div class="flex items-center gap-2">
              <input
                id="cat-mandatory"
                v-model="form.is_mandatory"
                type="checkbox"
                class="w-4 h-4 rounded text-amber-600 border-gray-300 focus:ring-amber-500 cursor-pointer"
              />
              <label for="cat-mandatory" class="font-bold text-gray-700 cursor-pointer">
                Wajib Regulasi BI / OJK (Mandatori Kepatuhan)
              </label>
            </div>

            <div class="flex items-center gap-2">
              <input
                id="cat-active"
                v-model="form.is_active"
                type="checkbox"
                class="w-4 h-4 rounded text-[#0067AC] border-gray-300 focus:ring-[#0067AC] cursor-pointer"
              />
              <label for="cat-active" class="font-bold text-gray-700 cursor-pointer">
                Status Aktif (Tersedia untuk dirujuk pada Form Usulan Program)
              </label>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 mt-2">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-5 py-2 rounded-xl text-xs font-bold bg-[#0067AC] hover:bg-[#00558F] text-white shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <Save class="w-3.5 h-3.5" />
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Kategori' }}</span>
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 animate-scaleUp">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
            <Trash2 class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-base font-extrabold text-gray-900">Hapus Kategori BI</h3>
            <p class="text-xs text-gray-500">Konfirmasi Penghapusan Regulasi</p>
          </div>
        </div>

        <div class="bg-rose-50/70 p-3.5 rounded-2xl border border-rose-100 text-xs text-rose-800 leading-relaxed">
          Apakah Anda yakin ingin menghapus kategori <strong>{{ itemToDelete?.name }}</strong> ({{ itemToDelete?.code }})?
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button
            @click="showDeleteModal = false; itemToDelete = null"
            class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="executeDelete"
            :disabled="isDeleting"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Sekarang' }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
