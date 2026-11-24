<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Users2,
  Plus,
  Edit2,
  Trash2,
  Search,
  CheckCircle2,
  AlertCircle,
  Building2,
  Award,
  BookOpen,
  Phone,
  Mail,
  MapPin,
  X,
  Save,
  ArrowLeft,
  Briefcase
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import SubpageHeader from '@/components/SubpageHeader.vue'

const emit = defineEmits(['back'])

const loading = ref(true)
const feedbackMsg = ref('')
const feedbackType = ref('success')

const facilitators = ref([])
const stats = ref({
  total: 0,
  active: 0,
  external: 0,
  certification: 0,
  internal: 0
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
  type: 'External Vendor',
  contact_person: '',
  phone: '',
  email: '',
  specialization: '',
  address: '',
  is_active: true
})

// Delete modal
const showDeleteModal = ref(false)
const itemToDelete = ref(null)
const isDeleting = ref(false)

const typeOptions = [
  'External Vendor',
  'Lembaga Sertifikasi',
  'Internal Trainer',
  'Institusi / Akademisi'
]

async function fetchData() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/facilitators')
    facilitators.value = data.facilitators || []
    if (data.stats) {
      stats.value = data.stats
    }
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal memuat data facilitator.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})

const filteredList = computed(() => {
  return facilitators.value.filter(item => {
    const matchType = selectedType.value === 'ALL' || item.type === selectedType.value
    if (!matchType) return false
    if (!searchQuery.value.trim()) return true
    const q = searchQuery.value.toLowerCase()
    return (
      item.name?.toLowerCase().includes(q) ||
      item.code?.toLowerCase().includes(q) ||
      item.specialization?.toLowerCase().includes(q) ||
      item.contact_person?.toLowerCase().includes(q)
    )
  })
})

function openCreateModal() {
  editingItem.value = null
  const count = facilitators.value.length + 1
  form.value = {
    code: 'FAC-' + count.toString().padStart(3, '0'),
    name: '',
    type: 'External Vendor',
    contact_person: '',
    phone: '',
    email: '',
    specialization: '',
    address: '',
    is_active: true
  }
  showModal.value = true
}

function openEditModal(item) {
  editingItem.value = item
  form.value = {
    code: item.code || '',
    name: item.name || '',
    type: item.type || 'External Vendor',
    contact_person: item.contact_person || '',
    phone: item.phone || '',
    email: item.email || '',
    specialization: item.specialization || '',
    address: item.address || '',
    is_active: !!item.is_active
  }
  showModal.value = true
}

async function saveForm() {
  if (!form.value.name.trim()) {
    alert('Nama Lembaga / Facilitator wajib diisi.')
    return
  }
  isSubmitting.value = true
  try {
    if (editingItem.value) {
      await apiClient.put(`/facilitators/${editingItem.value.id}`, form.value)
      feedbackMsg.value = `Facilitator '${form.value.name}' berhasil diperbarui.`
    } else {
      await apiClient.post('/facilitators', form.value)
      feedbackMsg.value = `Facilitator '${form.value.name}' berhasil ditambahkan.`
    }
    feedbackType.value = 'success'
    showModal.value = false
    await fetchData()
    setTimeout(() => { feedbackMsg.value = '' }, 4000)
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan data facilitator.')
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
    await apiClient.delete(`/facilitators/${itemToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = `Facilitator '${itemToDelete.value.name}' berhasil dihapus.`
    showDeleteModal.value = false
    itemToDelete.value = null
    await fetchData()
    setTimeout(() => { feedbackMsg.value = '' }, 4000)
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus facilitator.')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-16">
    <!-- Subpage Navigation Header -->
    <SubpageHeader
      back-label="Kembali ke Program Pelatihan"
      title="Master Lembaga & Facilitator Pelatihan"
      subtitle="Database vendor pelatihan eksternal, lembaga sertifikasi, dan trainer internal Bank Woori Saudara"
      badge="Master Data Program"
      @back="emit('back')"
    >
      <template #actions>
        <button
          @click="openCreateModal"
          class="px-4 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00558F] text-white font-bold text-xs shadow-sm transition flex items-center gap-1.5 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Facilitator</span>
        </button>
      </template>
    </SubpageHeader>

    <!-- Feedback Message Alert -->
    <div
      v-if="feedbackMsg"
      class="p-4 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-xs transition animate-fadeIn"
      :class="feedbackType === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
    >
      <div class="flex items-center gap-2">
        <AlertCircle v-if="feedbackType === 'error'" class="w-4 h-4 text-rose-600" />
        <CheckCircle2 v-else class="w-4 h-4 text-emerald-600" />
        <span>{{ feedbackMsg }}</span>
      </div>
      <button @click="feedbackMsg = ''" class="hover:opacity-75">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Briefcase class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Facilitator</p>
          <p class="text-lg font-extrabold text-gray-900">{{ facilitators.length }} Rekanan</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <Building2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">External Vendor</p>
          <p class="text-lg font-extrabold text-purple-700">
            {{ facilitators.filter(f => f.type === 'External Vendor').length }} Vendor
          </p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <Award class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Lembaga Sertifikasi</p>
          <p class="text-lg font-extrabold text-amber-700">
            {{ facilitators.filter(f => f.type === 'Lembaga Sertifikasi').length }} Lembaga
          </p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <BookOpen class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Internal Academy</p>
          <p class="text-lg font-extrabold text-emerald-700">
            {{ facilitators.filter(f => f.type === 'Internal Trainer').length }} Trainer
          </p>
        </div>
      </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm overflow-hidden flex flex-col">
      <!-- Search & Filter Bar -->
      <div class="p-4 md:p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
        <div class="relative w-full md:w-80">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama, kode, atau spesialisasi..."
            class="w-full bg-white text-xs text-gray-800 rounded-xl pl-9 pr-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
          />
          <Search class="w-4 h-4 text-gray-400 absolute left-3 top-3" />
        </div>

        <div class="flex items-center gap-2 overflow-x-auto">
          <button
            @click="selectedType = 'ALL'"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
            :class="selectedType === 'ALL' ? 'bg-[#0067AC] text-white shadow-2xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
          >
            Semua Tipe
          </button>
          <button
            v-for="t in typeOptions"
            :key="t"
            @click="selectedType = t"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
            :class="selectedType === t ? 'bg-[#0067AC] text-white shadow-2xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
          >
            {{ t }}
          </button>
        </div>
      </div>

      <!-- Facilitators Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80 text-[11px] font-extrabold text-gray-600 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-24">Kode</th>
              <th class="py-3.5 px-4">Nama Facilitator / Provider</th>
              <th class="py-3.5 px-4 w-40">Tipe Rekanan</th>
              <th class="py-3.5 px-4">Kontak & PIC</th>
              <th class="py-3.5 px-4">Bidang Spesialisasi</th>
              <th class="py-3.5 px-4 w-24 text-center">Status</th>
              <th class="py-3.5 px-4 w-28 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
            <tr v-if="loading">
              <td colspan="7" class="py-12 text-center text-gray-400">
                Memuat data facilitator...
              </td>
            </tr>
            <tr v-else-if="filteredList.length === 0">
              <td colspan="7" class="py-12 text-center text-gray-400">
                Tidak ada data facilitator yang sesuai pencarian.
              </td>
            </tr>
            <tr
              v-for="item in filteredList"
              :key="item.id"
              class="hover:bg-blue-50/30 transition"
            >
              <td class="py-3.5 px-4 font-mono font-extrabold text-[#0067AC]">
                {{ item.code }}
              </td>
              <td class="py-3.5 px-4">
                <span class="font-extrabold text-gray-900 block">{{ item.name }}</span>
                <span v-if="item.address" class="text-[10px] text-gray-400 flex items-center gap-1 mt-0.5 truncate max-w-xs">
                  <MapPin class="w-2.5 h-2.5 shrink-0" />
                  {{ item.address }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold"
                  :class="[
                    item.type === 'External Vendor' ? 'bg-purple-50 text-purple-700 border border-purple-200' :
                    item.type === 'Lembaga Sertifikasi' ? 'bg-amber-50 text-amber-700 border border-amber-200' :
                    item.type === 'Internal Trainer' ? 'bg-blue-50 text-[#0067AC] border border-blue-200' :
                    'bg-emerald-50 text-emerald-700 border border-emerald-200'
                  ]"
                >
                  {{ item.type }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span class="font-bold text-gray-800 block">{{ item.contact_person || '-' }}</span>
                <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-0.5">
                  <span v-if="item.phone" class="flex items-center gap-0.5">
                    <Phone class="w-2.5 h-2.5" />
                    {{ item.phone }}
                  </span>
                  <span v-if="item.email" class="flex items-center gap-0.5">
                    <Mail class="w-2.5 h-2.5" />
                    {{ item.email }}
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <span class="text-gray-600 line-clamp-2 max-w-sm">
                  {{ item.specialization || '-' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full"
                  :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500 border border-gray-200'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="item.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                  <span>{{ item.is_active ? 'Aktif' : 'Non-Aktif' }}</span>
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="inline-flex items-center gap-1 justify-center bg-gray-50 p-1 rounded-xl border border-gray-200">
                  <button
                    @click="openEditModal(item)"
                    class="p-1 rounded-lg text-amber-600 hover:bg-amber-100 transition cursor-pointer"
                    title="Edit Facilitator"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="confirmDelete(item)"
                    class="p-1 rounded-lg text-rose-600 hover:bg-rose-100 transition cursor-pointer"
                    title="Hapus Facilitator"
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

    <!-- Create / Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 animate-scaleUp my-8">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
              <Briefcase class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">
                {{ editingItem ? 'Edit Data Facilitator' : 'Tambah Facilitator Baru' }}
              </h3>
              <p class="text-[11px] text-gray-400">Pengaturan mitra lembaga pelatihan & trainer</p>
            </div>
          </div>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveForm" class="flex flex-col gap-3.5 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Kode Rekanan</label>
              <input
                v-model="form.code"
                type="text"
                class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 font-mono font-bold text-[#0067AC] focus:border-[#0067AC] focus:outline-none"
                placeholder="FAC-001"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Tipe Rekanan</label>
              <select
                v-model="form.type"
                class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 font-semibold text-gray-800 focus:border-[#0067AC] focus:outline-none cursor-pointer"
              >
                <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">
              Nama Lembaga / Trainer <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 font-bold text-gray-900 focus:border-[#0067AC] focus:outline-none"
              placeholder="e.g. PT Mitra Konsultan Perbankan"
            />
          </div>

          <div class="grid grid-cols-3 gap-2.5">
            <div>
              <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Nama PIC</label>
              <input
                v-model="form.contact_person"
                type="text"
                class="w-full bg-white rounded-xl px-3 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
                placeholder="PIC / Koordinator"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">No. Telp / WA</label>
              <input
                v-model="form.phone"
                type="text"
                class="w-full bg-white rounded-xl px-3 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
                placeholder="+62 8..."
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Email Resmi</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full bg-white rounded-xl px-3 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
                placeholder="info@..."
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Bidang Spesialisasi Pelatihan</label>
            <input
              v-model="form.specialization"
              type="text"
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
              placeholder="e.g. Operasional Layanan, Credit Underwriting, Keamanan Siber"
            />
          </div>

          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Alamat Kantor / Domisili</label>
            <textarea
              v-model="form.address"
              rows="2"
              class="w-full bg-white rounded-xl px-3.5 py-2 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
              placeholder="Alamat lengkap gedung / kota..."
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input
              id="fac-active"
              v-model="form.is_active"
              type="checkbox"
              class="w-4 h-4 rounded text-[#0067AC] border-gray-300 focus:ring-[#0067AC] cursor-pointer"
            />
            <label for="fac-active" class="font-bold text-gray-700 cursor-pointer">
              Status Aktif (Tersedia untuk dirujuk pada Form Usulan Program)
            </label>
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
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Facilitator' }}</span>
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
            <h3 class="text-base font-extrabold text-gray-900">Hapus Facilitator</h3>
            <p class="text-xs text-gray-500">Konfirmasi Penghapusan Rekanan</p>
          </div>
        </div>

        <div class="bg-rose-50/70 p-3.5 rounded-2xl border border-rose-100 text-xs text-rose-800 leading-relaxed">
          Apakah Anda yakin ingin menghapus rekanan <strong>{{ itemToDelete?.name }}</strong> ({{ itemToDelete?.code }})?
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
