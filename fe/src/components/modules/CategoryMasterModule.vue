<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  FolderTree,
  FolderPlus,
  Layers,
  Plus,
  Edit2,
  Trash2,
  Search,
  Filter,
  CheckCircle2,
  AlertCircle,
  Building2,
  CreditCard,
  ShieldCheck,
  Terminal,
  Award,
  ChevronDown,
  ChevronRight,
  ExternalLink,
  Sparkles,
  Tag,
  BookOpen
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const loading = ref(true)
const activeView = ref('categories') // 'categories' | 'sub_categories'
const feedbackMsg = ref('')
const feedbackType = ref('success')

const categories = ref([])
const subCategories = ref([])
const expandedCategoryIds = ref(new Set())

// Filters
const searchCategory = ref('')
const searchSub = ref('')
const filterCategoryParent = ref('ALL')

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

// Sub-Category Modal
const showSubCategoryModal = ref(false)
const editingSubCategory = ref(null)
const subCategoryForm = ref({
  category_id: '',
  code: '',
  name: '',
  description: '',
  target_competency: '',
  is_active: true
})

// Delete Modal
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const deleteType = ref('category') // 'category' | 'sub_category'
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

// Data Fetching
async function fetchData() {
  loading.value = true
  try {
    const [resCats, resSubs] = await Promise.all([
      apiClient.get('/categories?with_subs=1'),
      apiClient.get('/sub-categories')
    ])
    categories.value = resCats.data.categories || []
    subCategories.value = resSubs.data.subCategories || []
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal memuat master kategori.'
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

// Computed Filtered Sub-Categories
const filteredSubCategories = computed(() => {
  let list = subCategories.value
  if (filterCategoryParent.value !== 'ALL') {
    list = list.filter(s => s.category_id === Number(filterCategoryParent.value))
  }
  if (searchSub.value.trim()) {
    const q = searchSub.value.toLowerCase()
    list = list.filter(s =>
      s.name?.toLowerCase().includes(q) ||
      s.code?.toLowerCase().includes(q) ||
      s.target_competency?.toLowerCase().includes(q) ||
      s.category?.name?.toLowerCase().includes(q)
    )
  }
  return list
})

// Toggle Category Expand
function toggleExpand(id) {
  if (expandedCategoryIds.value.has(id)) {
    expandedCategoryIds.value.delete(id)
  } else {
    expandedCategoryIds.value.add(id)
  }
}

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

// Sub-Category Handlers
function openCreateSubCategoryModal(parentCat = null) {
  editingSubCategory.value = null
  const defaultCatId = parentCat ? parentCat.id : (categories.value[0]?.id || '')
  const parentCode = parentCat ? parentCat.code.replace('CAT-', 'SUB-') : 'SUB-01'
  subCategoryForm.value = {
    category_id: defaultCatId,
    code: `${parentCode}-${Math.floor(100 + Math.random() * 900)}`,
    name: '',
    description: '',
    target_competency: '',
    is_active: true
  }
  showSubCategoryModal.value = true
}

function openEditSubCategoryModal(sub) {
  editingSubCategory.value = sub
  subCategoryForm.value = {
    category_id: sub.category_id,
    code: sub.code,
    name: sub.name,
    description: sub.description || '',
    target_competency: sub.target_competency || '',
    is_active: sub.is_active ?? true
  }
  showSubCategoryModal.value = true
}

async function submitSubCategoryForm() {
  feedbackMsg.value = ''
  try {
    if (editingSubCategory.value) {
      const { data } = await apiClient.put(`/sub-categories/${editingSubCategory.value.id}`, subCategoryForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Sub-Kategori berhasil diperbarui.'
    } else {
      const { data } = await apiClient.post('/sub-categories', subCategoryForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Sub-Kategori baru berhasil ditambahkan.'
    }
    showSubCategoryModal.value = false
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menyimpan sub-kategori.'
  }
}

// Delete Handlers
function confirmDelete(item, type) {
  itemToDelete.value = item
  deleteType.value = type
  showDeleteModal.value = true
}

async function executeDelete() {
  if (!itemToDelete.value) return
  isDeleting.value = true
  feedbackMsg.value = ''
  try {
    if (deleteType.value === 'category') {
      const { data } = await apiClient.delete(`/categories/${itemToDelete.value.id}`)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Kategori berhasil dihapus.'
    } else {
      const { data } = await apiClient.delete(`/sub-categories/${itemToDelete.value.id}`)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Sub-Kategori berhasil dihapus.'
    }
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menghapus data.'
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
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <FolderTree class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Kategori Induk</p>
          <p class="text-xl font-extrabold text-gray-900">{{ categories.length }} Rumpun</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#00A0E9] flex items-center justify-center font-bold">
          <Layers class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Sub-Kategori</p>
          <p class="text-xl font-extrabold text-gray-900">{{ subCategories.length }} Mata Diklat</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Status Sub-Kategori</p>
          <p class="text-xl font-extrabold text-gray-900">{{ subCategories.filter(s => s.is_active).length }} Aktif</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Tag class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Rata-rata Sub</p>
          <p class="text-xl font-extrabold text-gray-900">
            {{ categories.length ? (subCategories.length / categories.length).toFixed(1) : 0 }} per Kategori
          </p>
        </div>
      </div>
    </div>

    <!-- Main Card Container -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-5">
      
      <!-- Sub-Tab Switcher & Action Buttons -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-100 pb-4">
        
        <!-- Tab Pills -->
        <div class="flex items-center gap-1.5 p-1 bg-[#F8FAFC] border border-gray-200/80 rounded-full">
          <button
            @click="activeView = 'categories'"
            class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-2"
            :class="activeView === 'categories' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
          >
            <FolderTree class="w-3.5 h-3.5" />
            <span>Kategori Induk ({{ categories.length }})</span>
          </button>

          <button
            @click="activeView = 'sub_categories'"
            class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-2"
            :class="activeView === 'sub_categories' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
          >
            <Layers class="w-3.5 h-3.5" />
            <span>Sub-Kategori ({{ subCategories.length }})</span>
          </button>
        </div>

        <!-- Add Button Contextual -->
        <div class="flex items-center gap-2">
          <AppButton
            v-if="activeView === 'categories'"
            variant="primary"
            pill
            icon="folder-plus"
            @click="openCreateCategoryModal"
          >
            Tambah Kategori Induk
          </AppButton>

          <AppButton
            v-else
            variant="primary"
            pill
            icon="plus"
            @click="openCreateSubCategoryModal()"
          >
            Tambah Sub-Kategori Baru
          </AppButton>
        </div>
      </div>

      <!-- ============================================================= -->
      <!-- VIEW 1: MASTER CATEGORY (KATEGORI INDUK)                      -->
      <!-- ============================================================= -->
      <div v-if="activeView === 'categories'" class="space-y-4">
        
        <!-- Search Bar -->
        <div class="flex items-center justify-between gap-3">
          <div class="relative flex-1 max-w-md">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchCategory"
              type="text"
              placeholder="Cari nama atau kode kategori induk..."
              class="w-full bg-[#F8FAFC] focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition"
            />
          </div>
          <span class="text-xs text-gray-400">Menampilkan {{ filteredCategories.length }} kategori</span>
        </div>

        <!-- Categories Table -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-32">Kode</th>
                <th class="py-3 px-4">Nama Rumpun / Kategori Induk</th>
                <th class="py-3 px-4">Deskripsi Sasaran Kompetensi</th>
                <th class="py-3 px-4 text-center">Sub-Kategori</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center w-36">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <template v-for="(cat, idx) in filteredCategories" :key="cat.id">
                <tr class="hover:bg-blue-50/30 transition group">
                  <td class="py-3.5 px-4 text-center text-gray-400 font-semibold">{{ idx + 1 }}</td>
                  <td class="py-3.5 px-4 font-mono font-bold text-[#0067AC]">
                    {{ cat.code }}
                  </td>
                  <td class="py-3.5 px-4 font-bold text-gray-900">
                    <div class="flex items-center gap-2">
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
                  <td class="py-3.5 px-4 text-gray-600 max-w-sm truncate" :title="cat.description">
                    {{ cat.description || '-' }}
                  </td>
                  <td class="py-3.5 px-4 text-center">
                    <button
                      @click="toggleExpand(cat.id)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border transition cursor-pointer"
                      :class="expandedCategoryIds.has(cat.id) ? 'bg-[#0067AC] text-white border-[#0067AC]' : 'bg-[#EBF4FC] text-[#0067AC] border-blue-200 hover:bg-[#d8eafb]'"
                    >
                      <Layers class="w-3 h-3" />
                      <span>{{ cat.sub_categories_count || (cat.sub_categories ? cat.sub_categories.length : 0) }} Sub</span>
                      <ChevronDown class="w-3 h-3 transition-transform" :class="{ 'rotate-180': expandedCategoryIds.has(cat.id) }" />
                    </button>
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
                        @click="openCreateSubCategoryModal(cat)"
                        class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 transition"
                        title="Tambah Sub-Kategori ke Rumpun Ini"
                      >
                        <Plus class="w-3.5 h-3.5" />
                      </button>
                      <button
                        @click="openEditCategoryModal(cat)"
                        class="p-1.5 rounded-lg text-[#0067AC] hover:bg-blue-50 transition"
                        title="Edit Kategori Induk"
                      >
                        <Edit2 class="w-3.5 h-3.5" />
                      </button>
                      <button
                        @click="confirmDelete(cat, 'category')"
                        class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition"
                        title="Hapus Kategori"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Nested Expanded Sub-Categories Row -->
                <tr v-if="expandedCategoryIds.has(cat.id)" class="bg-[#F8FAFC]">
                  <td colspan="7" class="p-4 pl-12 border-b border-gray-200">
                    <div class="bg-white rounded-2xl p-4 border border-blue-200 shadow-2xs space-y-3">
                      <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <div class="flex items-center gap-2">
                          <span class="w-2 h-2 rounded-full bg-[#0067AC]"></span>
                          <span class="font-bold text-xs text-[#002855]">Daftar Sub-Kategori di dalam {{ cat.name }}</span>
                        </div>
                        <button
                          @click="openCreateSubCategoryModal(cat)"
                          class="text-xs font-bold text-[#0067AC] hover:underline flex items-center gap-1 cursor-pointer"
                        >
                          <Plus class="w-3 h-3" />
                          <span>Tambah Sub-Kategori</span>
                        </button>
                      </div>

                      <div v-if="!cat.sub_categories || cat.sub_categories.length === 0" class="text-xs text-gray-400 py-2 italic text-center">
                        Belum ada sub-kategori pada kategori induk ini.
                      </div>

                      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <div
                          v-for="sub in cat.sub_categories"
                          :key="sub.id"
                          class="p-2.5 rounded-xl bg-[#F8FAFC] border border-gray-100 flex items-start justify-between gap-2 hover:border-[#0067AC]/30 transition"
                        >
                          <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                              <span class="font-mono text-[10px] font-bold text-[#0067AC] bg-white px-1.5 py-0.5 rounded border border-gray-200">{{ sub.code }}</span>
                              <span class="font-bold text-gray-900">{{ sub.name }}</span>
                            </div>
                            <p class="text-[11px] text-gray-500">{{ sub.description }}</p>
                            <p v-if="sub.target_competency" class="text-[10px] text-blue-700 font-medium">
                              🎯 Target: {{ sub.target_competency }}
                            </p>
                          </div>
                          <div class="flex items-center gap-1 shrink-0">
                            <button
                              @click="openEditSubCategoryModal(sub)"
                              class="p-1 rounded text-gray-500 hover:text-[#0067AC]"
                              title="Edit"
                            >
                              <Edit2 class="w-3 h-3" />
                            </button>
                            <button
                              @click="confirmDelete(sub, 'sub_category')"
                              class="p-1 rounded text-gray-500 hover:text-rose-600"
                              title="Hapus"
                            >
                              <Trash2 class="w-3 h-3" />
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============================================================= -->
      <!-- VIEW 2: MASTER SUB-CATEGORY (didalam sub ada categorynya)      -->
      <!-- ============================================================= -->
      <div v-else class="space-y-4">
        
        <!-- Filter & Search Bar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
          <div class="flex items-center gap-2 flex-1 max-w-lg">
            
            <!-- Category Filter Dropdown -->
            <select
              v-model="filterCategoryParent"
              class="bg-[#F8FAFC] text-xs font-semibold text-gray-700 rounded-full px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shrink-0"
            >
              <option value="ALL">Semua Kategori Induk ({{ categories.length }})</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>

            <!-- Search Sub Input -->
            <div class="relative flex-1">
              <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                v-model="searchSub"
                type="text"
                placeholder="Cari sub-kategori, kode, atau sasaran materi..."
                class="w-full bg-[#F8FAFC] focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition"
              />
            </div>
          </div>

          <span class="text-xs text-gray-400">Menampilkan {{ filteredSubCategories.length }} sub-kategori</span>
        </div>

        <!-- Sub-Categories Table (with clear parent Category column) -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-32">Kode Sub</th>
                <th class="py-3 px-4">Nama Sub-Kategori Diklat</th>
                <th class="py-3 px-4">Kategori Induk (Parent Category)</th>
                <th class="py-3 px-4">Sasaran / Target Kompetensi</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(sub, idx) in filteredSubCategories"
                :key="sub.id"
                class="hover:bg-blue-50/30 transition group"
              >
                <td class="py-3.5 px-4 text-center text-gray-400 font-semibold">{{ idx + 1 }}</td>
                <td class="py-3.5 px-4 font-mono font-bold text-[#0067AC]">
                  {{ sub.code }}
                </td>
                <td class="py-3.5 px-4">
                  <p class="font-bold text-gray-900">{{ sub.name }}</p>
                  <p class="text-[11px] text-gray-400 line-clamp-1" :title="sub.description">{{ sub.description }}</p>
                </td>
                <td class="py-3.5 px-4">
                  <!-- Highlighted Parent Category Badge ("didalam sub ada categorynya") -->
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border shadow-2xs"
                    :style="{
                      backgroundColor: '#EBF4FC',
                      color: sub.category?.color || '#0067AC',
                      borderColor: '#B9DCF7'
                    }"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: sub.category?.color || '#0067AC' }"></span>
                    {{ sub.category?.name || 'Kategori Tidak Ditemukan' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-gray-700 font-medium">
                  {{ sub.target_competency || '-' }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                    :class="sub.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600 border border-gray-200'"
                  >
                    {{ sub.is_active ? 'AKTIF' : 'NON-AKTIF' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="openEditSubCategoryModal(sub)"
                      class="p-1.5 rounded-lg text-[#0067AC] hover:bg-blue-50 transition"
                      title="Edit Sub-Kategori"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="confirmDelete(sub, 'sub_category')"
                      class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 transition"
                      title="Hapus Sub-Kategori"
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
    <!-- MODAL: TAMBAH / EDIT KATEGORI INDUK                           -->
    <!-- ============================================================= -->
    <div v-if="showCategoryModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 animate-scaleIn">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
              <FolderTree class="w-4 h-4" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-gray-900">
                {{ editingCategory ? 'Edit Kategori Induk' : 'Tambah Kategori Induk Baru' }}
              </h3>
              <p class="text-[11px] text-gray-400">Rumpun keahlian utama pelatihan Bank Woori Saudara</p>
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
              <select
                v-model="categoryForm.color"
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-bold"
                :style="{ color: categoryForm.color }"
              >
                <option v-for="c in colorOptions" :key="c.hex" :value="c.hex" :style="{ color: c.hex }">
                  ● {{ c.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Nama Kategori Induk *</label>
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
            <select
              v-model="categoryForm.icon"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
            >
              <option v-for="i in iconOptions" :key="i.name" :value="i.name">{{ i.label }} ({{ i.name }})</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Deskripsi Sasaran Kompetensi</label>
            <textarea
              v-model="categoryForm.description"
              rows="2"
              placeholder="Jelaskan cakupan materi dan kompetensi dalam rumpun ini..."
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

    <!-- ============================================================= -->
    <!-- MODAL: TAMBAH / EDIT SUB-KATEGORI (Pilih Kategori Induk)       -->
    <!-- ============================================================= -->
    <div v-if="showSubCategoryModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 animate-scaleIn">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
              <Layers class="w-4 h-4" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-gray-900">
                {{ editingSubCategory ? 'Edit Sub-Kategori Diklat' : 'Tambah Sub-Kategori Baru' }}
              </h3>
              <p class="text-[11px] text-gray-400">Mata diklat spesifik yang bernaung di bawah Kategori Induk</p>
            </div>
          </div>
          <button @click="showSubCategoryModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg">&times;</button>
        </div>

        <form @submit.prevent="submitSubCategoryForm" class="space-y-4 text-xs">
          
          <!-- Parent Category Selector ("didalam sub ada categorynya") -->
          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Kategori Induk (Parent Category) *</label>
            <select
              v-model="subCategoryForm.category_id"
              required
              class="w-full bg-[#EBF4FC] rounded-xl px-3 py-2.5 border border-blue-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-bold text-[#002855]"
            >
              <option value="" disabled>Pilih Kategori Induk...</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">
                {{ c.code }} — {{ c.name }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Kode Sub-Kategori *</label>
              <input
                v-model="subCategoryForm.code"
                type="text"
                required
                placeholder="SUB-OPS-001"
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono uppercase"
              />
            </div>

            <div class="space-y-1">
              <label class="block font-bold text-gray-700">Status Aktif *</label>
              <select
                v-model="subCategoryForm.is_active"
                class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
              >
                <option :value="true">AKTIF (Dapat Dipilih)</option>
                <option :value="false">NON-AKTIF</option>
              </select>
            </div>
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Nama Sub-Kategori Diklat *</label>
            <input
              v-model="subCategoryForm.name"
              type="text"
              required
              placeholder="Contoh: Teller & Service Excellence"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Target / Sasaran Kompetensi</label>
            <input
              v-model="subCategoryForm.target_competency"
              type="text"
              placeholder="Contoh: SOP Kas, Deteksi Uang Palsu, Handling Complain"
              class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-medium"
            />
          </div>

          <div class="space-y-1">
            <label class="block font-bold text-gray-700">Deskripsi Rincian</label>
            <textarea
              v-model="subCategoryForm.description"
              rows="2"
              placeholder="Penjelasan ringkas materi dan tujuan pembelajaran..."
              class="w-full bg-[#F8FAFC] rounded-xl p-3 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none leading-relaxed"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showSubCategoryModal = false"
              class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-100 font-bold transition"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white font-bold transition shadow-xs"
            >
              Simpan Sub-Kategori
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: KONFIRMASI HAPUS                                       -->
    <!-- ============================================================= -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-gray-100 space-y-4 animate-scaleIn text-center">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
          <Trash2 class="w-6 h-6" />
        </div>
        <div>
          <h3 class="font-bold text-base text-gray-900">
            Hapus {{ deleteType === 'category' ? 'Kategori Induk' : 'Sub-Kategori' }}?
          </h3>
          <p class="text-xs text-gray-500 mt-1">
            Apakah Anda yakin ingin menghapus <strong>"{{ itemToDelete?.name }}"</strong>?
            <span v-if="deleteType === 'category'" class="block text-rose-600 font-semibold mt-1 text-[11px]">
              Perhatian: Seluruh sub-kategori di dalam kategori ini juga akan ikut dihapus.
            </span>
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
