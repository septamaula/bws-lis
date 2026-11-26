<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  MapPin,
  Plus,
  Edit2,
  Trash2,
  Search,
  CheckCircle2,
  AlertCircle,
  Building,
  Monitor,
  Users,
  X,
  Save,
  ArrowLeft
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import SubpageHeader from '@/components/SubpageHeader.vue'
import { useI18n } from '@/i18n'

const { l } = useI18n()

const emit = defineEmits(['back'])

const loading = ref(true)
const feedbackMsg = ref('')
const feedbackType = ref('success')

const locations = ref([])
const stats = ref({
  total: 0,
  active: 0,
  physical: 0,
  virtual: 0,
  auditorium: 0
})

const searchQuery = ref('')
const selectedType = ref('ALL')

// Modal state
const showModal = ref(false)
const editingItem = ref(null)
const isSubmitting = ref(false)

const form = ref({
  code: '',
  name: '',
  type: 'Physical Room',
  building: 'BWS Corporate Learning Center',
  floor: 'Lt. 8',
  capacity: 30,
  facilities: '',
  is_active: true
})

// Delete modal
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const isDeleting = ref(false)

const typeOptions = [
  'Physical Room',
  'Virtual Platform',
  'Auditorium',
  'Computer Lab'
]

const getLocationTypeLabel = (type) => {
  const map = {
    'Physical Room': l('Physical Room', 'Ruang Fisik'),
    'Virtual Platform': l('Virtual Platform', 'Platform Virtual'),
    'Auditorium': l('Auditorium', 'Auditorium'),
    'Computer Lab': l('Computer Lab', 'Laboratorium Komputer')
  }
  return map[type] || type
}

const filterTypeOptions = computed(() => [
  { value: 'ALL', label: l('All Room Types', 'Semua Tipe Ruangan') },
  ...typeOptions.map(t => ({ value: t, label: getLocationTypeLabel(t) }))
])

const typeSelectOptions = computed(() =>
  typeOptions.map(t => ({ value: t, label: getLocationTypeLabel(t) }))
)

async function fetchData() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/training-locations')
    locations.value = data.locations || []
    if (data.stats) {
      stats.value = data.stats
    }
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to load room master data.', err.response?.data?.message || 'Gagal memuat data master ruangan.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})

const filteredList = computed(() => {
  return locations.value.filter(item => {
    const matchType = selectedType.value === 'ALL' || item.type === selectedType.value
    if (!matchType) return false
    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase()
    return (
      item.name?.toLowerCase().includes(q) ||
      item.code?.toLowerCase().includes(q) ||
      item.building?.toLowerCase().includes(q) ||
      item.facilities?.toLowerCase().includes(q)
    )
  })
})

function openCreateModal() {
  editingItem.value = null
  form.value = {
    code: '',
    name: '',
    type: 'Physical Room',
    building: 'BWS Corporate Learning Center',
    floor: l('8th Floor', 'Lt. 8'),
    capacity: 30,
    facilities: '',
    is_active: true
  }
  showModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  form.value = {
    code: item.code,
    name: item.name,
    type: item.type || 'Physical Room',
    building: item.building || '',
    floor: item.floor || '',
    capacity: item.capacity || 30,
    facilities: item.facilities || '',
    is_active: Boolean(item.is_active)
  }
  showModal.value = true
}

async function handleSubmit() {
  if (!form.value.name) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Room name is required.', 'Nama ruangan wajib diisi.')
    return
  }

  isSubmitting.value = true
  try {
    if (editingItem.value) {
      await apiClient.put(`/training-locations/${editingItem.value.id}`, form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = l(`Room "${form.value.name}" updated successfully.`, `Ruangan "${form.value.name}" berhasil diperbarui.`)
    } else {
      await apiClient.post('/training-locations', form.value)
      feedbackType.value = 'success'
      feedbackMsg.value = l(`Room "${form.value.name}" added to Master Data.`, `Ruangan "${form.value.name}" berhasil ditambahkan ke Data Master.`)
    }
    showModal.value = false
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to save room data.', err.response?.data?.message || 'Gagal menyimpan data ruangan.')
  } finally {
    isSubmitting.value = false
  }
}

function confirmDelete(item) {
  itemToDelete.value = item
  showDeleteModal.value = true
}

async function handleDelete() {
  if (!itemToDelete.value) return
  isDeleting.value = true
  try {
    await apiClient.delete(`/training-locations/${itemToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = l(`Room "${itemToDelete.value.name}" deleted successfully.`, `Ruangan "${itemToDelete.value.name}" berhasil dihapus.`)
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = l('Failed to delete room data.', err.response?.data?.message || 'Gagal menghapus data ruangan.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Subpage Header -->
    <SubpageHeader
      :title="l('Training Rooms & Locations Master', 'Master Ruangan & Lokasi Diklat')"
      :subtitle="l('Manage training rooms, auditoriums, banking labs, and virtual classroom platforms', 'Kelola direktori ruang pelatihan, auditorium, laboratorium perbankan, dan platform kelas virtual')"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <button
            @click="emit('back')"
            class="px-4 py-2 border border-gray-200 text-gray-700 rounded-2xl hover:bg-gray-50 font-semibold text-xs flex items-center gap-1.5 transition cursor-pointer"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
            {{ l('Back to Class List', 'Kembali ke Daftar Kelas') }}
          </button>
          <button
            @click="openCreateModal"
            class="px-4 py-2 bg-[#0067AC] text-white rounded-2xl hover:bg-[#005288] font-bold text-xs flex items-center gap-1.5 shadow-sm transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            {{ l('Add New Room', 'Tambah Ruangan Baru') }}
          </button>
        </div>
      </template>
    </SubpageHeader>

    <!-- Feedback Banner -->
    <div
      v-if="feedbackMsg"
      class="p-4 rounded-2xl text-xs font-semibold flex items-center justify-between transition"
      :class="feedbackType === 'error' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="feedbackType === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ feedbackMsg }}</span>
      </div>
      <button @click="feedbackMsg = ''" class="text-gray-400 hover:text-gray-600 cursor-pointer">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Building class="w-5 h-5" />
        </div>
        <div>
          <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ l('Total Rooms', 'Total Ruangan') }}</div>
          <div class="text-xl font-black text-gray-900">{{ stats.total }}</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ l('Active in Use', 'Aktif Digunakan') }}</div>
          <div class="text-xl font-black text-gray-900">{{ stats.active }}</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
          <MapPin class="w-5 h-5" />
        </div>
        <div>
          <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ l('Physical Rooms', 'Ruang Fisik') }}</div>
          <div class="text-xl font-black text-gray-900">{{ stats.physical }}</div>
        </div>
      </div>

      <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
          <Monitor class="w-5 h-5" />
        </div>
        <div>
          <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ l('Virtual Platforms', 'Platform Virtual') }}</div>
          <div class="text-xl font-black text-gray-900">{{ stats.virtual }}</div>
        </div>
      </div>
    </div>

    <!-- Filters & Table Container -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-2xs overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <!-- Type Filter -->
          <div class="min-w-[190px]">
            <AppSearchableSelect
              v-model="selectedType"
              :options="filterTypeOptions"
              :placeholder="l('All Room Types...', 'Semua Tipe Ruangan...')"
              :search-placeholder="l('Search type...', 'Cari tipe...')"
              input-class="bg-[#f8fafc] border-gray-200 rounded-xl px-3 py-2 font-medium text-gray-700"
            />
          </div>
        </div>

        <!-- Search Input -->
        <div class="relative w-full sm:w-72">
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5 pointer-events-none" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="l('Search room, code, building, facilities...', 'Cari ruang, kode, gedung, fasilitas...')"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-xs font-medium text-gray-700 focus:outline-none focus:border-[#0067AC]"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-[#f8fafc] text-gray-500 font-bold border-b border-gray-100">
            <tr>
              <th class="py-3 px-4 w-12 text-center">No</th>
              <th class="py-3 px-4 w-28">{{ l('Code', 'Kode') }}</th>
              <th class="py-3 px-4">{{ l('Room Name & Location', 'Nama Ruangan & Lokasi') }}</th>
              <th class="py-3 px-4 w-36">{{ l('Room Type', 'Tipe Ruang') }}</th>
              <th class="py-3 px-4 w-32">{{ l('Building / Floor', 'Gedung / Lantai') }}</th>
              <th class="py-3 px-4 w-24 text-center">{{ l('Capacity', 'Kapasitas') }}</th>
              <th class="py-3 px-4 w-24 text-center">{{ l('Status', 'Status') }}</th>
              <th class="py-3 px-4 w-24 text-center">{{ l('Actions', 'Aksi') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 font-medium text-gray-700">
            <tr v-if="loading">
              <td colspan="8" class="text-center py-10 text-gray-400">
                {{ l('Loading room data...', 'Memuat data ruangan...') }}
              </td>
            </tr>
            <tr v-else-if="filteredList.length === 0">
              <td colspan="8" class="text-center py-10 text-gray-400">
                {{ l('No room data found.', 'Tidak ada data ruangan ditemukan.') }}
              </td>
            </tr>
            <tr
              v-else
              v-for="(item, idx) in filteredList"
              :key="item.id"
              class="hover:bg-[#f8fafc] transition"
            >
              <td class="py-3.5 px-4 text-center text-gray-400">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4">
                <span class="font-mono font-bold text-[#0067AC] bg-blue-50 px-2 py-0.5 rounded-md text-[11px]">
                  {{ item.code }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-bold text-gray-900">{{ item.name }}</div>
                <div v-if="item.facilities" class="text-[11px] text-gray-400 truncate max-w-md">
                  {{ item.facilities }}
                </div>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="{
                    'bg-indigo-50 text-indigo-700': item.type === 'Physical Room',
                    'bg-purple-50 text-purple-700': item.type === 'Virtual Platform',
                    'bg-amber-50 text-amber-700': item.type === 'Auditorium',
                    'bg-emerald-50 text-emerald-700': item.type === 'Computer Lab'
                  }"
                >
                  {{ getLocationTypeLabel(item.type) }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-gray-600">
                <div>{{ item.building || '-' }}</div>
                <div class="text-[10px] text-gray-400">{{ item.floor || '-' }}</div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="font-bold text-gray-800">{{ item.capacity }}</span>
                <span class="text-[10px] text-gray-400 block">{{ l('seats', 'kursi') }}</span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                >
                  {{ item.is_active ? l('Active', 'Aktif') : l('Inactive', 'Non-Aktif') }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <button
                    @click="openEditModal(item)"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-[#0067AC] hover:bg-blue-50 transition cursor-pointer"
                    :title="l('Edit Room', 'Ubah Ruangan')"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="confirmDelete(item)"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    :title="l('Delete Room', 'Hapus Ruangan')"
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

    <!-- Modal Form (Create/Edit) -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
              <MapPin class="w-4 h-4" />
            </div>
            <h3 class="font-extrabold text-sm text-gray-900">
              {{ editingItem ? l('Edit Room Master', 'Ubah Data Ruangan') : l('Add New Room', 'Tambah Ruangan Baru') }}
            </h3>
          </div>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-gray-700 mb-1">{{ l('Room Name & Location', 'Nama Ruangan & Lokasi') }} <span class="text-rose-500">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              :placeholder="l('e.g. Room 1 - BWS Corporate Learning Center', 'Contoh: Ruang 1 - BWS Corporate Learning Center')"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-gray-700 mb-1">{{ l('Room Code', 'Kode Ruang') }}</label>
              <input
                v-model="form.code"
                type="text"
                :placeholder="l('e.g. RNG-01 (Auto if blank)', 'Contoh: RNG-01 (Otomatis jika kosong)')"
                class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-[#0067AC] focus:outline-none focus:border-[#0067AC]"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 mb-1">{{ l('Room Type', 'Tipe Ruangan') }}</label>
              <AppSearchableSelect
                v-model="form.type"
                :options="typeSelectOptions"
                :placeholder="l('Select room type...', 'Pilih tipe ruangan...')"
                :search-placeholder="l('Search type...', 'Cari tipe...')"
                input-class="bg-[#f8fafc] border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="col-span-2">
              <label class="block font-bold text-gray-700 mb-1">{{ l('Building / Location', 'Gedung / Lokasi') }}</label>
              <input
                v-model="form.building"
                type="text"
                :placeholder="l('e.g. BWS Corporate Learning Center', 'Contoh: BWS Corporate Learning Center')"
                class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 mb-1">{{ l('Floor', 'Lantai') }}</label>
              <input
                v-model="form.floor"
                type="text"
                :placeholder="l('e.g. 8th Floor', 'Contoh: Lt. 8')"
                class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1">{{ l('Capacity (Seats / Participants)', 'Kapasitas (Kursi / Peserta)') }}</label>
            <input
              v-model.number="form.capacity"
              type="number"
              min="1"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1">{{ l('Facilities & Equipment', 'Fasilitas & Kelengkapan') }}</label>
            <textarea
              v-model="form.facilities"
              rows="2"
              :placeholder="l('e.g. HD Projector, Audio System, Wi-Fi 500 Mbps, Central AC, Smart Board', 'Contoh: Proyektor HD, Sistem Audio, Wi-Fi 500 Mbps, AC Sentral, Papan Pintar')"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              type="checkbox"
              id="is_active_loc"
              v-model="form.is_active"
              class="rounded text-[#0067AC] focus:ring-[#0067AC] h-4 w-4 border-gray-300 cursor-pointer"
            />
            <label for="is_active_loc" class="font-bold text-gray-700 cursor-pointer">{{ l('Room Active & Ready for Use', 'Ruangan Aktif & Siap Digunakan') }}</label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-semibold cursor-pointer"
            >
              {{ l('Cancel', 'Batal') }}
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-5 py-2 bg-[#0067AC] text-white rounded-xl hover:bg-[#005288] font-bold flex items-center gap-1.5 shadow-sm transition cursor-pointer"
            >
              <Save class="w-3.5 h-3.5" />
              {{ isSubmitting ? l('Saving...', 'Menyimpan...') : l('Save Room', 'Simpan Ruangan') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
    >
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center space-y-4">
        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center">
          <Trash2 class="w-6 h-6" />
        </div>
        <div>
          <h3 class="font-extrabold text-base text-gray-900">{{ l('Delete Room Master?', 'Hapus Data Ruangan?') }}</h3>
          <p class="text-xs text-gray-500 mt-1">
            {{ l('Room', 'Ruangan') }} <span class="font-bold text-gray-800">"{{ itemToDelete?.name }}"</span> {{ l('will be permanently deleted from Master Data.', 'akan dihapus dari Data Master.') }}
          </p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2">
          <button
            @click="showDeleteModal = false"
            class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-semibold text-xs cursor-pointer"
          >
            {{ l('Cancel', 'Batal') }}
          </button>
          <button
            @click="handleDelete"
            :disabled="isDeleting"
            class="px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 font-bold text-xs flex items-center gap-1.5 shadow-sm transition cursor-pointer"
          >
            {{ isDeleting ? l('Deleting...', 'Menghapus...') : l('Yes, Delete', 'Ya, Hapus') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
