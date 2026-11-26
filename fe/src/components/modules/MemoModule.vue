<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '@/api/client'
import {
  FileText,
  Plus,
  Search,
  Trash2,
  Edit3,
  Download,
  UploadCloud,
  CheckCircle2,
  AlertCircle,
  X,
  ArrowLeft,
  Calendar,
  User,
  Paperclip,
  Building,
  ExternalLink,
  FileSpreadsheet,
  FileCheck2,
  ChevronRight,
  Filter,
  Eye,
  RotateCcw,
  Save
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import SubpageHeader from '@/components/SubpageHeader.vue'
import { useI18n } from '@/i18n'

const { l, t, locale } = useI18n()

const emit = defineEmits(['back'])

// State: 'list' | 'create' | 'edit'
const viewMode = ref('list')
const loading = ref(false)
const isSubmitting = ref(false)
const feedback = ref({ type: '', message: '' })

// Delete Confirmation Modal State
const showDeleteModal = ref(false)
const memoToDelete = ref(null)
const isDeleting = ref(false)

// Memos Data
const memos = ref([])
const filterCategory = ref('') // '' | 'INTERNAL' | 'EKSTERNAL'

// Form State
const defaultForm = () => ({
  id: null,
  category: 'INTERNAL',
  memo_date: new Date().toISOString().slice(0, 10),
  memo_number: '',
  pic: '',
  receiver: '',
  subject: '',
  description: '',
  document_name: '',
  document_file: null
})

const form = ref(defaultForm())
const isEditMode = computed(() => !!form.value.id)

// Fetch Memos from REST API
async function fetchMemos() {
  loading.value = true
  try {
    const res = await apiClient.get('/memos')
    memos.value = res.data?.data || []
  } catch (err) {
    showNotice('error', err.response?.data?.message || l('Failed to load memo data.', 'Gagal memuat data memo.'))
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMemos()
})

// Statistics
const totalInternal = computed(() => memos.value.filter(m => m.category === 'INTERNAL').length)
const totalEksternal = computed(() => memos.value.filter(m => m.category === 'EKSTERNAL').length)

// Filtered memo list for the single DataTable
const formattedMemos = computed(() => {
  let list = memos.value.map(m => ({
    ...m,
    receiver_display: m.category === 'EKSTERNAL' ? (m.receiver || '-') : l('L&D Division Internal Unit', 'Unit Internal Divisi L&D')
  }))

  if (filterCategory.value) {
    list = list.filter(m => m.category === filterCategory.value)
  }

  return list
})

// Columns matching SS2 Excel Wireframe for Single Unified Grid
const memoColumns = computed(() => [
  { key: 'memo_date', label: l('Date', 'Tanggal'), sortable: true, width: '120px', align: 'center' },
  { key: 'category', label: l('Category', 'Kategori'), sortable: true, width: '110px', align: 'center' },
  { key: 'memo_number', label: l('Memo Number', 'Nomor Memo'), sortable: true, width: '190px' },
  { key: 'pic', label: l('PIC', 'Penanggung Jawab'), sortable: true, width: '140px' },
  { key: 'receiver_display', label: l('Recipient / Vendor', 'Penerima / Vendor'), sortable: true, width: '180px' },
  { key: 'subject', label: l('Subject', 'Perihal'), sortable: true },
  { key: 'document_name', label: l('Document', 'Dokumen'), width: '130px', align: 'center' }
])

function formatDateShort(val) {
  if (!val) return '-'
  try {
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    const day = String(d.getDate()).padStart(2, '0')
    const months = locale.value === 'en'
      ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      : ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    const month = months[d.getMonth()]
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
  } catch {
    return val
  }
}

// Navigation / Form Triggers
function openCreateMemo(category = 'INTERNAL') {
  form.value = defaultForm()
  form.value.category = category
  const randNum = String(Math.floor(100 + Math.random() * 900))
  if (category === 'INTERNAL') {
    form.value.memo_number = `${randNum}/MEMO-LD.DIV/II/2026`
    form.value.pic = 'Aditya Nugraha (HC Training)'
  } else {
    form.value.memo_number = `${randNum}/BWS-L&D/LSPP/II/2026`
    form.value.pic = 'Maya Wulandari (HC Learning Head)'
    form.value.receiver = l('LSPP (Banking Professional Certification Body)', 'LSPP (Lembaga Sertifikasi Profesi Perbankan)')
  }
  feedback.value = { type: '', message: '' }
  viewMode.value = 'create'
}

function openEditMemo(memo) {
  form.value = {
    id: memo.id,
    category: memo.category,
    memo_date: memo.memo_date ? memo.memo_date.slice(0, 10) : new Date().toISOString().slice(0, 10),
    memo_number: memo.memo_number,
    pic: memo.pic,
    receiver: memo.receiver || '',
    subject: memo.subject,
    description: memo.description || '',
    document_name: memo.document_name || '',
    document_file: null
  }
  feedback.value = { type: '', message: '' }
  viewMode.value = 'edit'
}

function handleFileChange(e) {
  const file = e.target.files?.[0]
  if (file) {
    form.value.document_file = file
    form.value.document_name = file.name
  }
}

function removeFile() {
  form.value.document_file = null
  form.value.document_name = ''
}

// Submit Form (Create / Update)
async function submitMemoForm() {
  if (!form.value.memo_number || !form.value.pic || !form.value.subject) {
    showNotice('error', l('Please complete Memo Number, PIC, and Subject.', 'Mohon lengkapi Nomor Memo, Penanggung Jawab, dan Perihal.'))
    return
  }

  if (form.value.category === 'EKSTERNAL' && !form.value.receiver) {
    showNotice('error', l('External Recipient is required for External Memos.', 'Penerima Eksternal wajib diisi untuk Memo Eksternal.'))
    return
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const formData = new FormData()
    formData.append('category', form.value.category)
    formData.append('memo_date', form.value.memo_date)
    formData.append('memo_number', form.value.memo_number)
    formData.append('pic', form.value.pic)
    formData.append('subject', form.value.subject)
    if (form.value.receiver) formData.append('receiver', form.value.receiver)
    if (form.value.description) formData.append('description', form.value.description)
    if (form.value.document_file) formData.append('document', form.value.document_file)

    if (isEditMode.value) {
      formData.append('_method', 'PUT')
      await apiClient.post(`/memos/${form.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      showNotice('success', l('Memo document has been updated.', 'Dokumen Memo berhasil diperbarui.'))
      await fetchMemos()
      // Tetap di halaman edit sesuai pola form BWS
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      await apiClient.post('/memos', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      showNotice('success', l('Memo document has been created.', 'Dokumen Memo berhasil dibuat.'))
      await fetchMemos()
      viewMode.value = 'list'
    }
  } catch (err) {
    showNotice('error', err.response?.data?.message || l('Failed to save memo document.', 'Gagal menyimpan dokumen memo.'))
  } finally {
    isSubmitting.value = false
  }
}

function confirmDelete(memo) {
  memoToDelete.value = memo
  showDeleteModal.value = true
}

// Delete Memo
async function executeDelete() {
  if (!memoToDelete.value) return
  isDeleting.value = true

  try {
    await apiClient.delete(`/memos/${memoToDelete.value.id}`)
    showNotice('success', l(`Memo ${memoToDelete.value.memo_number} has been deleted.`, `Memo ${memoToDelete.value.memo_number} berhasil dihapus.`))
    showDeleteModal.value = false
    memoToDelete.value = null
    await fetchMemos()
  } catch (err) {
    showNotice('error', err.response?.data?.message || l('Failed to delete memo.', 'Gagal menghapus memo.'))
  } finally {
    isDeleting.value = false
  }
}

// Download or Preview document
function downloadMemoDocument(memo) {
  if (!memo.document_path) {
    alert(l(
      `Sample file: ${memo.document_name || 'Memo.pdf'}\nPlease use the edit form to upload the actual PDF/Word file.`,
      `Berkas contoh: ${memo.document_name || 'Memo.pdf'}\nSilakan gunakan formulir ubah untuk mengunggah berkas PDF/Word asli.`
    ))
    return
  }
  const url = `${apiClient.defaults.baseURL}/memos/${memo.id}/document`
  window.open(url, '_blank')
}

function showNotice(type, message) {
  feedback.value = { type, message }
  setTimeout(() => {
    if (feedback.value.message === message) {
      feedback.value = { type: '', message: '' }
    }
  }, 5000)
}

defineExpose({
  openCreateMemo,
  fetchMemos,
  viewMode
})
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-16">

    <!-- Feedback Banner Toast -->
    <div
      v-if="feedback.message"
      class="p-4 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-2xs animate-fadeIn border transition"
      :class="feedback.type === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
    >
      <div class="flex items-center gap-2">
        <AlertCircle v-if="feedback.type === 'error'" class="w-4 h-4 text-rose-600 shrink-0" />
        <CheckCircle2 v-else class="w-4 h-4 text-emerald-600 shrink-0" />
        <span>{{ feedback.message }}</span>
      </div>
      <button @click="feedback = { type: '', message: '' }" class="hover:opacity-75 font-bold p-1 cursor-pointer">
        ✕
      </button>
    </div>

    <!-- ========================================================== -->
    <!-- MODE 1: SINGLE UNIFIED GRID VIEW (CLEAN LIKE TNA)          -->
    <!-- ========================================================== -->
    <template v-if="viewMode === 'list'">

      <!-- Quick KPI Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-2xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ $l('Total Memo Archive', 'Total Arsip Memo') }}</span>
            <div class="text-2xl font-extrabold text-gray-900 mt-1">{{ memos.length }} {{ $l(memos.length === 1 ? 'Document' : 'Documents', 'Surat') }}</div>
            <span class="text-[10px] text-gray-500">{{ $l('Internal & External', 'Internal & Eksternal') }}</span>
          </div>
          <div class="w-10 h-10 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
            <FileText class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-2xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ $l('Internal Memos', 'Memo Internal') }}</span>
            <div class="text-2xl font-extrabold text-blue-700 mt-1">{{ totalInternal }} {{ $l(totalInternal === 1 ? 'Document' : 'Documents', 'Surat') }}</div>
            <span class="text-[10px] text-gray-500">Format: MEMO-LD.DIV</span>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
            <Building class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-2xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ $l('External Memos', 'Memo Eksternal') }}</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1">{{ totalEksternal }} {{ $l(totalEksternal === 1 ? 'Document' : 'Documents', 'Surat') }}</div>
            <span class="text-[10px] text-gray-500">{{ $l('Vendor / Certification Body', 'Vendor / Lembaga Sertifikasi') }}</span>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
            <ExternalLink class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Category Filter Strip (Single Grid with Internal / External Filter) -->
      <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 p-1 bg-gray-100 rounded-2xl text-xs font-semibold">
          <button
            type="button"
            @click="filterCategory = ''"
            class="px-4 py-1.5 rounded-xl transition cursor-pointer"
            :class="filterCategory === '' ? 'bg-white text-[#0067AC] shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'"
          >
            {{ $l('All Memos', 'Semua Memo') }} ({{ memos.length }})
          </button>
          <button
            type="button"
            @click="filterCategory = 'INTERNAL'"
            class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
            :class="filterCategory === 'INTERNAL' ? 'bg-white text-[#0067AC] shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'"
          >
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>{{ $l('Internal Memos', 'Memo Internal') }} ({{ totalInternal }})</span>
          </button>
          <button
            type="button"
            @click="filterCategory = 'EKSTERNAL'"
            class="px-4 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
            :class="filterCategory === 'EKSTERNAL' ? 'bg-white text-emerald-700 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>{{ $l('External Memos', 'Memo Eksternal') }} ({{ totalEksternal }})</span>
          </button>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openCreateMemo('INTERNAL')"
            class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-[#0067AC] text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{{ $l('Internal Memo', 'Memo Internal') }}</span>
          </button>
          <button
            type="button"
            @click="openCreateMemo('EKSTERNAL')"
            class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{{ $l('External Memo', 'Memo Eksternal') }}</span>
          </button>
        </div>
      </div>

      <!-- Single Unified DataTable for Memos -->
      <DataTable
        :data="formattedMemos"
        :columns="memoColumns"
        date-key="memo_date"
        export-filename="bws_official_memos"
        :default-per-page="10"
      >
        <!-- Tanggal -->
        <template #cell-memo_date="{ row }">
          <span class="font-mono text-xs text-gray-700 font-medium">
            {{ formatDateShort(row.memo_date) }}
          </span>
        </template>

        <!-- Kategori -->
        <template #cell-category="{ row }">
          <span
            class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
            :class="row.category === 'INTERNAL' ? 'bg-blue-50 text-[#0067AC] border border-blue-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'"
          >
            {{ row.category === 'INTERNAL' ? $l('INTERNAL', 'INTERNAL') : $l('EXTERNAL', 'EKSTERNAL') }}
          </span>
        </template>

        <!-- Nomor Memo -->
        <template #cell-memo_number="{ row }">
          <span class="font-mono font-bold text-xs text-[#0067AC]">
            {{ row.memo_number }}
          </span>
        </template>

        <!-- PIC -->
        <template #cell-pic="{ row }">
          <span class="font-semibold text-xs text-gray-800">
            {{ row.pic }}
          </span>
        </template>

        <!-- Receiver / Penerima -->
        <template #cell-receiver_display="{ row }">
          <span class="text-xs font-medium" :class="row.category === 'EKSTERNAL' ? 'text-gray-900 font-bold' : 'text-gray-400 italic'">
            {{ row.receiver_display }}
          </span>
        </template>

        <!-- Perihal / Subject -->
        <template #cell-subject="{ row }">
          <div class="max-w-md">
            <p class="font-bold text-xs text-gray-900 truncate" :title="row.subject">
              {{ row.subject }}
            </p>
            <p v-if="row.description" class="text-[10px] text-gray-400 truncate mt-0.5" :title="row.description">
              {{ row.description }}
            </p>
          </div>
        </template>

        <!-- Dokumen -->
        <template #cell-document_name="{ row }">
          <button
            v-if="row.document_name || row.document_path"
            type="button"
            @click="downloadMemoDocument(row)"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-blue-50 text-gray-700 hover:text-[#0067AC] transition cursor-pointer text-xs"
            :title="$l(`Download File: ${row.document_name || 'Memo.pdf'}`, `Unduh Berkas: ${row.document_name || 'Memo.pdf'}`)"
          >
            <Paperclip class="w-3.5 h-3.5 text-blue-500" />
            <span class="truncate max-w-[90px] text-[11px] font-medium">{{ row.document_name || $l('Download', 'Unduh') }}</span>
          </button>
          <span v-else class="text-gray-300 text-xs italic">-</span>
        </template>

        <!-- Action Column (Edit / Hapus) -->
        <template #action="{ row }">
          <div class="flex items-center justify-end gap-1.5">
            <button
              type="button"
              @click="openEditMemo(row)"
              class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
              :title="$l('Edit Memo', 'Ubah Memo')"
            >
              <Edit3 class="w-3.5 h-3.5" />
              <span>{{ $t('common.edit') }}</span>
            </button>
            <button
              type="button"
              @click="confirmDelete(row)"
              class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-semibold flex items-center gap-1 transition cursor-pointer"
              :title="$l('Delete Memo', 'Hapus Memo')"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>{{ $t('common.delete') }}</span>
            </button>
          </div>
        </template>
      </DataTable>

    </template>

    <!-- ========================================================== -->
    <!-- MODE 2: FORM CREATE / EDIT MEMO                            -->
    <!-- ========================================================== -->
    <template v-else-if="viewMode === 'create' || viewMode === 'edit'">

      <SubpageHeader
        :back-label="$l('Back to Memo List', 'Kembali ke Daftar Memo')"
        :title="isEditMode ? $l(`Edit Memo: ${form.memo_number}`, `Ubah Memo: ${form.memo_number}`) : $l('Create New Memo Document', 'Buat Dokumen Memo Baru')"
        :subtitle="$l('Official correspondence numbering, internal assignment memos, and L&D Division external communications', 'Pencatatan nomor surat kedinasan, memo penugasan internal, dan korespondensi eksternal Divisi L&D')"
        :badge="form.category === 'INTERNAL' ? $l('Internal Memo', 'Memo Internal') : $l('External Memo', 'Memo Eksternal')"
        @back="viewMode = 'list'"
      />

      <form @submit.prevent="submitMemoForm" class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-2xs space-y-6">

        <!-- Section 1: Kategori Surat Memo -->
        <div class="p-4 rounded-2xl bg-blue-50/50 border border-blue-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div>
            <h4 class="font-extrabold text-sm text-[#0067AC]">{{ $l('Official Memo Correspondence Category', 'Kategori Surat Kedinasan Memo') }}</h4>
            <p class="text-xs text-gray-500">{{ $l('Select the type of correspondence to record', 'Pilih jenis korespondensi yang akan dicatat') }}</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="form.category = 'INTERNAL'"
              class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="form.category === 'INTERNAL' ? 'bg-[#0067AC] text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
            >
              {{ $l('Internal Memo', 'Memo Internal') }}
            </button>
            <button
              type="button"
              @click="form.category = 'EKSTERNAL'"
              class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="form.category === 'EKSTERNAL' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
            >
              {{ $l('External Memo', 'Memo Eksternal') }}
            </button>
          </div>
        </div>

        <!-- Section 2: Input Data Surat -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
          <!-- Tanggal Memo -->
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">{{ $l('Memo Date *', 'Tanggal Memo *') }}</label>
            <input
              v-model="form.memo_date"
              type="date"
              required
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
            />
          </div>

          <!-- Nomor Memo -->
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">{{ $l('Memo Number *', 'Nomor Surat Memo *') }}</label>
            <input
              v-model="form.memo_number"
              type="text"
              required
              :placeholder="$l('Example: 001/MEMO-LD.DIV/II/2026', 'Contoh: 001/MEMO-LD.DIV/II/2026')"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-gray-900 focus:outline-none focus:border-[#0067AC]"
            />
          </div>

          <!-- PIC Pembuat -->
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">{{ $l('Creator PIC / Person in Charge *', 'Pembuat / Penanggung Jawab *') }}</label>
            <input
              v-model="form.pic"
              type="text"
              required
              :placeholder="$l('PIC name (e.g. Aditya Nugraha)', 'Nama penanggung jawab (mis. Aditya Nugraha)')"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
            />
          </div>

          <!-- Receiver (Khusus Eksternal) -->
          <div v-if="form.category === 'EKSTERNAL'">
            <label class="block font-bold text-emerald-800 mb-1.5">{{ $l('External Recipient / Institution *', 'Penerima / Lembaga Eksternal *') }}</label>
            <input
              v-model="form.receiver"
              type="text"
              required
              :placeholder="$l('Vendor / Certification Body name (e.g. LSPP, LPPI, Partner)', 'Nama Vendor / Lembaga Sertifikasi (mis. LSPP, LPPI, Mitra Konsultan)')"
              class="w-full bg-emerald-50/40 border border-emerald-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-emerald-600"
            />
          </div>

          <div v-else>
            <label class="block font-bold text-gray-400 mb-1.5">{{ $l('Destination Unit / Recipient', 'Unit Tujuan / Penerima') }}</label>
            <input
              type="text"
              disabled
              :value="$l('Internal L&D Division / Bank Woori Saudara', 'Unit Internal Divisi L&D / Bank Woori Saudara')"
              class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-500 cursor-not-allowed"
            />
          </div>

          <!-- Perihal / Subject -->
          <div class="sm:col-span-2">
            <label class="block font-bold text-gray-700 mb-1.5">{{ $l('Memo Subject *', 'Perihal Surat Memo *') }}</label>
            <input
              v-model="form.subject"
              type="text"
              required
              :placeholder="$l('Letter subject (e.g. Risk Certification Level 1 Training Proposal)', 'Perihal surat (mis. Usulan Pelaksanaan Diklat Sertifikasi Risiko Level 1)')"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 focus:outline-none focus:border-[#0067AC]"
            />
          </div>

          <!-- Keterangan Tambahan -->
          <div class="sm:col-span-2">
            <label class="block font-bold text-gray-700 mb-1.5">{{ $l('Additional Notes / Content Summary', 'Keterangan Tambahan / Ringkasan Isi') }}</label>
            <textarea
              v-model="form.description"
              rows="3"
              :placeholder="$l('Summary of memo points or execution instructions...', 'Ringkasan poin memo atau instruksi pelaksanaan...')"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-medium text-gray-800 focus:outline-none focus:border-[#0067AC]"
            ></textarea>
          </div>

          <!-- Upload Berkas Dokumen -->
          <div class="sm:col-span-2">
            <label class="block font-bold text-gray-700 mb-1.5">{{ $l('Memo Document File (PDF, Word, Scan)', 'Berkas Dokumen Memo (PDF, Word, Pindai)') }}</label>
            <div class="flex items-center gap-3">
              <label class="px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold cursor-pointer transition flex items-center gap-2">
                <UploadCloud class="w-4 h-4 text-[#0067AC]" />
                <span>{{ $l('Choose File...', 'Pilih Berkas...') }}</span>
                <input type="file" @change="handleFileChange" accept=".pdf,.doc,.docx,.jpg,.png" class="hidden" />
              </label>
              <div v-if="form.document_name" class="flex items-center gap-2 bg-blue-50 text-[#0067AC] px-3 py-1.5 rounded-xl font-mono text-xs">
                <Paperclip class="w-3.5 h-3.5" />
                <span>{{ form.document_name }}</span>
                <button type="button" @click="removeFile" class="text-rose-500 hover:text-rose-700 font-bold ml-1">✕</button>
              </div>
              <span v-else class="text-gray-400 text-xs italic">{{ $l('No file selected', 'Belum ada berkas dipilih') }}</span>
            </div>
          </div>
        </div>

        <!-- Submit & Batal -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
          <button
            type="button"
            @click="viewMode = 'list'"
            class="px-5 py-2.5 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 transition cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-[#0067AC] hover:bg-[#00528c] shadow-sm transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <Save class="w-4 h-4" />
            <span>{{ isSubmitting ? $l('Saving...', 'Menyimpan...') : (isEditMode ? $l('Save Memo Changes', 'Simpan Perubahan Memo') : $l('Save Memo Document', 'Simpan Dokumen Memo')) }}</span>
          </button>
        </div>

      </form>

    </template>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModal && memoToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs transition-opacity"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 transform transition-all animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3 text-rose-600 mb-4">
          <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center shrink-0">
            <Trash2 class="w-6 h-6 text-rose-600" />
          </div>
          <div>
            <h3 class="font-bold text-gray-900 text-base">{{ $l('Delete Memo Document', 'Hapus Dokumen Memo') }}</h3>
            <p class="text-xs text-gray-500">{{ $l('Confirm memo archive deletion', 'Konfirmasi penghapusan arsip surat memo') }}</p>
          </div>
        </div>

        <div class="bg-gray-50 rounded-2xl p-4 mb-4 border border-gray-100 space-y-2 text-xs">
          <div class="flex justify-between items-center">
            <span class="text-gray-400">{{ $l('Memo Number:', 'Nomor Memo:') }}</span>
            <span class="font-mono font-bold text-[#0067AC]">{{ memoToDelete.memo_number }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-gray-400">{{ $l('Category:', 'Kategori:') }}</span>
            <span class="font-bold" :class="memoToDelete.category === 'INTERNAL' ? 'text-blue-700' : 'text-emerald-700'">{{ memoToDelete.category === 'INTERNAL' ? $l('INTERNAL', 'INTERNAL') : $l('EXTERNAL', 'EKSTERNAL') }}</span>
          </div>
          <div class="flex justify-between items-start">
            <span class="text-gray-400">{{ $l('Subject:', 'Perihal:') }}</span>
            <span class="font-bold text-gray-800 text-right max-w-[200px] truncate">{{ memoToDelete.subject }}</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
          <button
            type="button"
            @click="showDeleteModal = false; memoToDelete = null"
            :disabled="isDeleting"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 transition cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="button"
            @click="executeDelete"
            :disabled="isDeleting"
            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <span v-if="isDeleting">{{ $l('Deleting...', 'Menghapus...') }}</span>
            <span v-else>{{ $l('Yes, Delete Memo', 'Ya, Hapus Memo') }}</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
