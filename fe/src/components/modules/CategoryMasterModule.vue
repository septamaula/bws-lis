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
import { useI18n } from '@/i18n'

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

const { l } = useI18n()

const iconOptions = computed(() => [
  { name: 'Building2', label: l('Branch Operations', 'Operasional Cabang') },
  { name: 'CreditCard', label: l('Credit & Financing', 'Kredit & Pembiayaan') },
  { name: 'ShieldCheck', label: l('Risk & Compliance', 'Risiko & Kepatuhan') },
  { name: 'Terminal', label: l('Information Technology', 'Teknologi Informasi') },
  { name: 'Award', label: l('Leadership & Culture', 'Kepemimpinan & Budaya') },
  { name: 'BookOpen', label: l('Academy & Syllabus', 'Akademi & Silabus') }
])

const colorOptions = computed(() => [
  { hex: '#0067AC', label: l('BWS Primary Blue', 'Biru Utama BWS') },
  { hex: '#003876', label: l('BWS Royal Deep', 'Biru Tua BWS') },
  { hex: '#002855', label: l('BWS Navy Dark', 'Biru Dongker BWS') },
  { hex: '#00A0E9', label: l('BWS Cyan Accent', 'Biru Sian BWS') },
  { hex: '#4F46E5', label: l('Banking Indigo', 'Indigo Perbankan') },
  { hex: '#0D9488', label: l('Compliance Teal', 'Toska Kepatuhan') }
])

const colorSelectOptions = computed(() =>
  colorOptions.value.map(c => ({
    value: c.hex,
    label: `● ${c.label}`,
    subLabel: c.hex
  }))
)

const iconSelectOptions = computed(() =>
  iconOptions.value.map(i => ({
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
    feedbackMsg.value = l('Failed to load training categories.', err.response?.data?.message || 'Gagal memuat kategori pelatihan.')
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
      await apiClient.put(`/categories/${editingCategory.value.id}`, categoryForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = l('Category updated successfully.', 'Kategori berhasil diperbarui.')
    } else {
      await apiClient.post('/categories', categoryForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = l('New category created successfully.', 'Kategori baru berhasil dibuat.')
    }
    showCategoryModal.value = false
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to save category.', err.response?.data?.message || 'Gagal menyimpan kategori.')
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
    await apiClient.delete(`/categories/${itemToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = l('Category deleted successfully.', 'Kategori berhasil dihapus.')
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to delete category.', err.response?.data?.message || 'Gagal menghapus kategori.')
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
          <p class="text-[11px] text-gray-400 font-medium">{{ l('Total Categories', 'Total Kategori') }}</p>
          <p class="text-xl font-extrabold text-gray-900">{{ categories.length }} {{ l('Categories', 'Kategori') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ l('Active Categories', 'Kategori Aktif') }}</p>
          <p class="text-xl font-extrabold text-gray-900">{{ categories.filter(c => c.is_active).length }} {{ l('Active', 'Aktif') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Building2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ l('System Integration', 'Integrasi Sistem') }}</p>
          <p class="text-xs font-extrabold text-gray-800">{{ l('Synced with TNA & Classes', 'Tersinkronisasi ke TNA & Kelas') }}</p>
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
            :placeholder="l('Search category name or code...', 'Cari nama atau kode kategori...')"
            class="w-full bg-[#F8FAFC] focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition"
          />
        </div>

        <div class="flex items-center justify-between sm:justify-end gap-3">
          <span class="text-xs text-gray-400">{{ l('Total:', 'Total:') }} {{ filteredCategories.length }} {{ l('categories', 'kategori') }}</span>
            <AppButton
            variant="primary"
            pill
            icon="folder-plus"
            @click="openCreateCategoryModal"
          >
            {{ l('Add Category', 'Tambah Kategori') }}
          </AppButton>
        </div>
      </div>

      <!-- Categories Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-100">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4 w-28">{{ l('Code', 'Kode') }}</th>
              <th class="py-3 px-4">{{ l('Training Category Name', 'Nama Kategori Pelatihan') }}</th>
              <th class="py-3 px-4">{{ l('Competency Target Description', 'Deskripsi Sasaran Kompetensi') }}</th>
              <th class="py-3 px-4 text-center w-28">{{ l('Status', 'Status') }}</th>
              <th class="py-3 px-4 text-center w-24">{{ l('Actions', 'Aksi') }}</th>
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
                  {{ cat.is_active ? l('ACTIVE', 'AKTIF') : l('INACTIVE', 'NON-AKTIF') }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    @click="openEditCategoryModal(cat)"
                    class="p-1.5 rounded-lg text-[#0067AC] hover:bg-blue-50 transition cursor-pointer"
                    :title="l('Edit Category', 'Ubah Kategori')"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="confirmDelete(cat)"
                    class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    :title="l('Delete Category', 'Hapus Kategori')"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredCategories.length === 0">
              <td colspan="6" class="py-8 text-center text-gray-400 italic">
                {{ loading ? l('Loading category data...', 'Memuat data kategori...') : l('No categories found.', 'Tidak ada kategori ditemukan.') }}
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
                {{ editingCategory ? l('Edit Training Category', 'Ubah Kategori Pelatihan') : l('Add New Category', 'Tambah Kategori Baru') }}
              </h3>
              <p class="text-[11px] text-gray-400">{{ l('Core training competency tracks at Bank Woori Saudara', 'Rumpun keahlian utama diklat Bank Woori Saudara') }}</p>
            </div>
          </div>
          <button @click="showCategoryModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitCategoryForm" class="space-y-4 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">{{ l('Category Code *', 'Kode Kategori *') }}</label>
              <input
                v-model="categoryForm.code"
                type="text"
                required
                placeholder="CAT-OPS"
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono uppercase"
              />
            </div>

            <div class="space-y-1">
              <label class="block font-bold text-gray-700">{{ l('Corporate Accent Color *', 'Warna Aksen Korporat *') }}</label>
              <AppSearchableSelect
                v-model="categoryForm.color"
                :options="colorSelectOptions"
                :placeholder="l('Select accent color...', 'Pilih warna aksen...')"
                :search-placeholder="l('Search color...', 'Cari warna...')"
                input-class="bg-[#F8FAFC] rounded-xl px-3 py-2 border-gray-200 font-bold"
              />
            </div>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">{{ l('Training Category Name *', 'Nama Kategori Pelatihan *') }}</label>
            <input
              v-model="categoryForm.name"
              type="text"
              required
              :placeholder="l('e.g. Banking Operations & Service', 'Contoh: Operasional & Layanan Perbankan')"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">{{ l('Category Icon *', 'Ikon Kategori *') }}</label>
            <AppSearchableSelect
              v-model="categoryForm.icon"
              :options="iconSelectOptions"
              :placeholder="l('Select category icon...', 'Pilih ikon kategori...')"
              :search-placeholder="l('Search icon...', 'Cari ikon...')"
              input-class="bg-[#F8FAFC] rounded-xl px-3 py-2 border-gray-200"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">{{ l('Competency Target Description', 'Deskripsi Sasaran Kompetensi') }}</label>
            <textarea
              v-model="categoryForm.description"
              rows="3"
              :placeholder="l('Describe the curriculum scope and target competencies for this category...', 'Jelaskan cakupan materi dan kompetensi dalam kategori ini...')"
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
            <label for="catActive" class="font-bold text-gray-700 cursor-pointer">{{ l('Active Category Status for TNA Proposals', 'Status Kategori Aktif untuk Usulan TNA') }}</label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showCategoryModal = false"
              class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold transition"
            >
              {{ l('Cancel', 'Batal') }}
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white font-bold transition shadow-xs"
            >
              {{ l('Save Category', 'Simpan Kategori') }}
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
            {{ l('Delete Training Category?', 'Hapus Kategori Pelatihan?') }}
          </h3>
          <p class="text-xs text-gray-500 mt-1">
            {{ l('Are you sure you want to delete', 'Apakah Anda yakin ingin menghapus') }} <strong>"{{ itemToDelete?.name }}"</strong>?
          </p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold text-xs transition"
          >
            {{ l('Cancel', 'Batal') }}
          </button>
          <button
            @click="executeDelete"
            :disabled="isDeleting"
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-xs flex items-center gap-1.5"
          >
            <span v-if="isDeleting">{{ l('Deleting...', 'Menghapus...') }}</span>
            <span v-else>{{ l('Yes, Delete Data', 'Ya, Hapus Data') }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
