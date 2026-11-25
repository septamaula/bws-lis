<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  FolderTree,
  FolderPlus,
  Edit2,
  Trash2,
  Search,
  CheckCircle2,
  AlertCircle,
  Building2,
  CreditCard,
  ShieldCheck,
  Terminal,
  Award,
  BookOpen,
  Check
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const loading = ref(true)
const feedbackMsg = ref('')
const feedbackType = ref('success')

const categories = ref([])
const searchCategory = ref('')

// Category Modal
const showCategoryModal = ref(false)
const editingCategory = ref(null)
const categoryForm = ref({
  code: '',
  name: '',
  description: '',
  icon: 'Building2',
  color: '#0067AC',
  is_active: true
})

// Delete Modal
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const isDeleting = ref(false)

const iconOptions = [
  { name: 'Building2', label: 'Operasional Cabang' },
  { name: 'CreditCard', label: 'Kredit & Pembiayaan' },
  { name: 'ShieldCheck', label: 'Risiko & Kepatuhan' },
  { name: 'Terminal', label: 'Teknologi Informasi' },
  { name: 'Award', label: 'Leadership & Budaya' },
  { name: 'BookOpen', label: 'Akademi & Silabus' }
]

const colorOptions = [
  { hex: '#0067AC', label: 'BWS Primary Blue' },
  { hex: '#003876', label: 'BWS Royal Deep' },
  { hex: '#002855', label: 'BWS Navy Dark' },
  { hex: '#00A0E9', label: 'BWS Cyan Accent' },
  { hex: '#4F46E5', label: 'Indigo Perbankan' },
  { hex: '#0D9488', label: 'Teal Kepatuhan' }
]

const colorSelectOptions = computed(() =>
  colorOptions.map(c => ({
    value: c.hex,
    label: `● ${c.label}`,
    subLabel: c.hex
  }))
)

const iconSelectOptions = computed(() =>
  iconOptions.map(i => ({
    value: i.name,
    label: i.label,
    subLabel: i.name
  }))
)

// Data Fetching
async function fetchData() {
  loading.value = true
  try {
    const resCats = await apiClient.get('/categories')
    categories.value = resCats.data.categories || []
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal memuat kategori pelatihan.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})

// Computed Filtered Categories
const filteredCategories = computed(() => {
  if (!searchCategory.value.trim()) return categories.value
  const q = searchCategory.value.toLowerCase()
  return categories.value.filter(c =>
    c.name?.toLowerCase().includes(q) ||
    c.code?.toLowerCase().includes(q) ||
    c.description?.toLowerCase().includes(q)
  )
})

// Category Handlers
function openCreateCategoryModal() {
  editingCategory.value = null
  categoryForm.value = {
    code: 'CAT-' + (categories.value.length + 1).toString().padStart(2, '0'),
    name: '',
    description: '',
    icon: 'Building2',
    color: '#0067AC',
    is_active: true
  }
  showCategoryModal.value = true
}

function openEditCategoryModal(cat) {
  editingCategory.value = cat
  categoryForm.value = {
    code: cat.code,
    name: cat.name,
    description: cat.description || '',
    icon: cat.icon || 'Building2',
    color: cat.color || '#0067AC',
    is_active: cat.is_active ?? true
  }
  showCategoryModal.value = true
}

async function submitCategoryForm() {
  feedbackMsg.value = ''
  try {
    if (editingCategory.value) {
      const { data } = await apiClient.put(`/categories/${editingCategory.value.id}`, categoryForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Kategori berhasil diperbarui.'
    } else {
      const { data } = await apiClient.post('/categories', categoryForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Kategori baru berhasil dibuat.'
    }
    showCategoryModal.value = false
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menyimpan kategori.'
  }
}

// Delete Handlers
function confirmDelete(item) {
  itemToDelete.value = item
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!itemToDelete.value) return
  isDeleting.value = true
  feedbackMsg.value = ''
  try {
    const { data } = await apiClient.delete(`/categories/${itemToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = data.message || 'Kategori berhasil dihapus.'
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menghapus kategori.'
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

    <!-- Top Metric Counters -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <FolderTree class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Kategori</p>
          <p class="text-xl font-extrabold text-gray-900">{{ categories.length }} Kategori</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Kategori Aktif</p>
          <p class="text-xl font-extrabold text-gray-900">{{ categories.filter(c => c.is_active).length }} Aktif</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Building2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Integrasi Sistem</p>
          <p class="text-xs font-extrabold text-gray-800">Tersinkronisasi ke TNA & Kelas</p>
        </div>
      </div>
    </div>

    <!-- Main Card Container: Categories List -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-5">
      
      <!-- Action Bar: Search & Add Button -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
        <div class="relative flex-1 max-w-md">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="searchCategory"
            type="text"
            placeholder="Cari nama atau kode kategori..."
            class="w-full bg-[#F8FAFC] focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition"
          />
        </div>

        <div class="flex items-center justify-between sm:justify-end gap-3">
          <span class="text-xs text-gray-400">Total: {{ filteredCategories.length }} kategori</span>
          <AppButton
            variant="primary"
            pill
            icon="folder-plus"
            @click="openCreateCategoryModal"
          >
            Tambah Kategori
          </AppButton>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-100">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4 w-28">Kode</th>
              <th class="py-3 px-4">Nama Kategori Pelatihan</th>
              <th class="py-3 px-4">Deskripsi Sasaran Kompetensi</th>
              <th class="py-3 px-4 text-center w-28">Status</th>
              <th class="py-3 px-4 text-center w-24">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(cat, idx) in filteredCategories"
              :key="cat.id"
              class="hover:bg-blue-50/30 transition group"
            >
              <td class="py-3.5 px-4 text-center text-gray-400 font-semibold">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4 font-mono font-bold text-[#0067AC]">
                {{ cat.code }}
              </td>
              <td class="py-3.5 px-4 font-bold text-gray-900">
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-7 h-7 rounded-lg flex items-center justify-center text-white shrink-0 shadow-2xs"
                    :style="{ backgroundColor: cat.color || '#0067AC' }"
                  >
                    <Building2 v-if="cat.icon === 'Building2'" class="w-3.5 h-3.5" />
                    <CreditCard v-else-if="cat.icon === 'CreditCard'" class="w-3.5 h-3.5" />
                    <ShieldCheck v-else-if="cat.icon === 'ShieldCheck'" class="w-3.5 h-3.5" />
                    <Terminal v-else-if="cat.icon === 'Terminal'" class="w-3.5 h-3.5" />
                    <Award v-else-if="cat.icon === 'Award'" class="w-3.5 h-3.5" />
                    <BookOpen v-else class="w-3.5 h-3.5" />
                  </div>
                  <span>{{ cat.name }}</span>
                </div>
              </td>
              <td class="py-3.5 px-4 text-gray-600 leading-relaxed max-w-md">
                {{ cat.description || '-' }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                  :class="cat.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600 border border-gray-200'"
                >
                  {{ cat.is_active ? 'AKTIF' : 'NON-AKTIF' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="openEditCategoryModal(cat)"
                    class="p-1.5 rounded-lg text-[#0067AC] hover:bg-blue-50 transition cursor-pointer"
                    title="Edit Kategori"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="confirmDelete(cat)"
                    class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    title="Hapus Kategori"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredCategories.length === 0">
              <td colspan="6" class="py-8 text-center text-gray-400 italic">
                {{ loading ? 'Memuat data kategori...' : 'Tidak ada kategori ditemukan.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- MODAL: TAMBAH / EDIT KATEGORI -->
    <div v-if="showCategoryModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 animate-scaleIn">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
              <FolderTree class="w-4 h-4" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-gray-900">
                {{ editingCategory ? 'Edit Kategori Pelatihan' : 'Tambah Kategori Baru' }}
              </h3>
              <p class="text-[11px] text-gray-400">Rumpun keahlian utama diklat Bank Woori Saudara</p>
            </div>
          </div>
          <button @click="showCategoryModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitCategoryForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Kode Kategori *</label>
              <input
                v-model="categoryForm.code"
                type="text"
                required
                placeholder="CAT-OPS"
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono uppercase"
              />
            </div>

            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Warna Aksen Korporat *</label>
              <AppSearchableSelect
                v-model="categoryForm.color"
                :options="colorSelectOptions"
                placeholder="Pilih warna aksen..."
                search-placeholder="Cari warna..."
                input-class="bg-[#F8FAFC] rounded-xl px-3 py-2 border-gray-200 font-bold"
              />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Nama Kategori Pelatihan *</label>
            <input
              v-model="categoryForm.name"
              type="text"
              required
              placeholder="Contoh: Operasional & Layanan Perbankan"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Ikon Kategori *</label>
            <AppSearchableSelect
              v-model="categoryForm.icon"
              :options="iconSelectOptions"
              placeholder="Pilih ikon kategori..."
              search-placeholder="Cari ikon..."
              input-class="bg-[#F8FAFC] rounded-xl px-3 py-2 border-gray-200"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Deskripsi Sasaran Kompetensi</label>
            <textarea
              v-model="categoryForm.description"
              rows="3"
              placeholder="Jelaskan cakupan materi dan kompetensi dalam kategori ini..."
              class="w-full bg-[#F8FAFC] rounded-xl p-3 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none leading-relaxed"
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="catActive"
              v-model="categoryForm.is_active"
              class="rounded text-[#0067AC] focus:ring-[#0067AC]"
            />
            <label for="catActive" class="font-bold text-gray-700 cursor-pointer">Status Kategori Aktif untuk Usulan TNA</label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showCategoryModal = false"
              class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold transition"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white font-bold transition shadow-xs"
            >
              Simpan Kategori
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: KONFIRMASI HAPUS -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-gray-100 space-y-4 animate-scaleIn text-center">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <Trash2 class="w-6 h-6" />
        </div>
        <div>
          <h3 class="font-bold text-base text-gray-900">
            Hapus Kategori Pelatihan?
          </h3>
          <p class="text-xs text-gray-500 mt-1">
            Apakah Anda yakin ingin menghapus <strong>"{{ itemToDelete?.name }}"</strong>?
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
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs flex items-center gap-1.5"
          >
            <span v-if="isDeleting">Menghapus...</span>
            <span v-else>Ya, Hapus Data</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
