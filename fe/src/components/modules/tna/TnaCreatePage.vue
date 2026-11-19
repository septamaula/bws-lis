<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  Plus,
  Trash2,
  Building2,
  Calendar,
  Wallet,
  Users,
  Send,
  Save,
  AlertCircle,
  CheckCircle2,
  ChevronRight,
  Sparkles,
  FileUp,
  FileCheck2,
  Paperclip,
  X,
  FileText,
  Layers,
  RotateCcw
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  editProposalId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['back', 'created'])
const auth = useAuthStore()

const isEditMode = computed(() => !!props.editProposalId)
const editingProposal = ref(null)
const existingAttachment = ref(null)

const departments = [
  'Information Technology',
  'Commercial & Corporate Credit',
  'Risk Management & Compliance',
  'Branch Operations & Service Quality',
  'Treasury & International Banking',
  'Human Capital',
  'Digital Banking & Product Development',
  'Consumer & Retail Banking',
  'Internal Audit'
]

const quarters = ['Q1', 'Q2', 'Q3', 'Q4', 'Q1 - Q2', 'All Quarters (Q1-Q4)']

// Master Data State
const loadingMasters = ref(true)
const categories = ref([])
const subCategories = ref([])
const fiscalYears = ref([])

// Form State
const form = ref({
  department: auth.user?.department || 'Information Technology',
  year: 2026,
  priority: 'High',
  target_quarter: 'Q1 - Q2 2026',
  strategic_objective: 'Akselerasi kapabilitas digital perbankan, kepatuhan regulasi OJK & BI, serta mitigasi risiko operasional cabang.',
  items: []
})

// File Attachment State
const selectedFile = ref(null)
const fileInputRef = ref(null)
const uploadProgress = ref(false)

const isSubmitting = ref(false)
const feedback = ref({ type: '', message: '' })

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

// Fetch Master Data on Mount
async function loadMasterData() {
  loadingMasters.value = true
  try {
    const [resCats, resSubs, resYears] = await Promise.all([
      apiClient.get('/categories?with_subs=1'),
      apiClient.get('/sub-categories'),
      apiClient.get('/fiscal-years')
    ])
    categories.value = resCats.data.categories || []
    subCategories.value = resSubs.data.subCategories || []
    fiscalYears.value = resYears.data.years || []

    if (resYears.data.active_year) {
      form.value.year = resYears.data.active_year
    }

    if (isEditMode.value) {
      // Muat data usulan yang sudah ada untuk diedit/direvisi
      const { data } = await apiClient.get(`/tna/${props.editProposalId}`)
      const p = data.proposal
      editingProposal.value = p

      form.value.department = p.department || auth.user?.department || 'Information Technology'
      form.value.year = p.year || 2026
      form.value.priority = p.priority || 'High'
      form.value.target_quarter = p.target_quarter || `Q1 - Q4 ${p.year}`
      form.value.strategic_objective = p.strategic_objective || ''

      if (p.attachment_name) {
        existingAttachment.value = {
          name: p.attachment_name,
          size: p.attachment_size,
          path: p.attachment_path
        }
      }

      if (p.items && p.items.length > 0) {
        form.value.items = p.items.map(it => ({
          category_id: it.category_id || '',
          sub_category_id: it.sub_category_id || '',
          execution_quarter: it.execution_quarter || 'Q1',
          target_participants_count: it.target_participants_count || 10,
          budget: Number(it.budget || it.estimated_cost) || 0
        }))
      } else {
        addItemRow()
      }
    } else {
      // Initialize with all categories & subcategories so user gets complete 1-year data immediately!
      if (form.value.items.length === 0 && categories.value.length > 0) {
        populateAllCategoriesAndSubCategories()
      }
    }
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: 'Gagal memuat master kategori, tahun, atau data usulan.'
    }
  } finally {
    loadingMasters.value = false
  }
}

onMounted(() => {
  loadMasterData()
})

// Realistic Annual Planning Spec covering all 20 subcategories across Q1 - Q4
const annualSubCategorySpecs = {
  // Operasional & Layanan Perbankan
  'SUB-OPS-001': { quarter: 'Q1', participants: 25, budget: 65000000 },
  'SUB-OPS-002': { quarter: 'Q2', participants: 18, budget: 45000000 },
  'SUB-OPS-003': { quarter: 'Q3', participants: 12, budget: 55000000 },
  'SUB-OPS-004': { quarter: 'Q4', participants: 10, budget: 70000000 },

  // Kredit & Pembiayaan Komersial
  'SUB-CRD-001': { quarter: 'Q1', participants: 20, budget: 85000000 },
  'SUB-CRD-002': { quarter: 'Q2', participants: 15, budget: 60000000 },
  'SUB-CRD-003': { quarter: 'Q3', participants: 12, budget: 50000000 },
  'SUB-CRD-004': { quarter: 'Q4', participants: 30, budget: 75000000 },

  // Manajemen Risiko, Kepatuhan & Regulasi OJK
  'SUB-RSK-001': { quarter: 'Q1', participants: 16, budget: 95000000 },
  'SUB-RSK-002': { quarter: 'Q2', participants: 40, budget: 40000000 },
  'SUB-RSK-003': { quarter: 'Q3', participants: 15, budget: 55000000 },
  'SUB-RSK-004': { quarter: 'Q4', participants: 35, budget: 45000000 },

  // Teknologi Informasi & Keamanan Siber
  'SUB-IT-001': { quarter: 'Q1', participants: 20, budget: 110000000 },
  'SUB-IT-002': { quarter: 'Q2', participants: 12, budget: 65000000 },
  'SUB-IT-003': { quarter: 'Q3', participants: 15, budget: 80000000 },
  'SUB-IT-004': { quarter: 'Q4', participants: 18, budget: 70000000 },

  // Kepemimpinan, Manajerial & Budaya Kerja
  'SUB-LDR-001': { quarter: 'Q1', participants: 20, budget: 150000000 },
  'SUB-LDR-002': { quarter: 'Q2', participants: 15, budget: 90000000 },
  'SUB-LDR-003': { quarter: 'Q3', participants: 25, budget: 60000000 },
  'SUB-LDR-004': { quarter: 'Q4', participants: 50, budget: 50000000 },
}

function populateAllCategoriesAndSubCategories() {
  if (categories.value.length === 0 || subCategories.value.length === 0) return

  const quarters = ['Q1', 'Q2', 'Q3', 'Q4']
  const generatedItems = []

  categories.value.forEach((cat) => {
    const subs = getSubCategoriesForCategory(cat.id)
    subs.forEach((sub, subIdx) => {
      const spec = annualSubCategorySpecs[sub.code] || {
        quarter: quarters[subIdx % 4],
        participants: 15,
        budget: 45000000
      }
      generatedItems.push({
        category_id: cat.id,
        sub_category_id: sub.id,
        execution_quarter: spec.quarter,
        target_participants_count: spec.participants,
        budget: spec.budget
      })
    })
  })

  form.value.items = generatedItems
  if (!form.value.strategic_objective || form.value.strategic_objective.length < 20) {
    form.value.strategic_objective = 'Program Pelatihan Terpadu Tahunan Bank Woori Saudara 2026: Akselerasi Kapabilitas Digital Perbankan, Mitigasi Risiko Kredit Komersial, Kepatuhan Regulasi Mandatori OJK & BI (BSMR, APU-PPT, SKSP, UU PDP), Keamanan Siber POJK 29/2022, serta Penguatan Kepemimpinan dan Integritas SDM Cabang.'
  }
  feedback.value = {
    type: 'success',
    message: `Berhasil memuat contoh lengkap seluruh kategori dan sub-kategori (${generatedItems.length} program pelatihan setahun).`
  }
}

function clearAllRows() {
  form.value.items = []
  addItemRow()
}

// Filter Sub-Categories per Row
function getSubCategoriesForCategory(categoryId) {
  if (!categoryId) return []
  return subCategories.value.filter(s => s.category_id === Number(categoryId))
}

function getCategoryColor(categoryId) {
  const cat = categories.value.find(c => c.id === Number(categoryId))
  return cat?.color || '#0067AC'
}

function onCategoryChange(item) {
  const available = getSubCategoriesForCategory(item.category_id)
  item.sub_category_id = available[0]?.id || ''
}

// Table Row Actions (Multiple Add Table)
function addItemRow() {
  const defaultCat = categories.value[0]
  const defaultSub = defaultCat ? getSubCategoriesForCategory(defaultCat.id)[0] : null
  form.value.items.push({
    category_id: defaultCat?.id || '',
    sub_category_id: defaultSub?.id || '',
    execution_quarter: 'Q2',
    target_participants_count: 10,
    budget: 35000000
  })
}

function removeItemRow(index) {
  if (form.value.items.length <= 1) {
    feedback.value = { type: 'error', message: 'Minimal harus terdapat 1 baris kebutuhan diklat.' }
    return
  }
  form.value.items.splice(index, 1)
}

// File Attachment Handlers
function onFileSelected(event) {
  const file = event.target.files[0]
  if (!file) return
  if (file.size > 15 * 1024 * 1024) {
    feedback.value = { type: 'error', message: 'Ukuran file maksimal adalah 15 MB.' }
    return
  }
  selectedFile.value = file
  feedback.value = { type: '', message: '' }
}

function removeAttachment() {
  selectedFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

// Computeds
const totalParticipants = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (Number(item.target_participants_count) || 0), 0)
})

const totalBudgetRequested = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (Number(item.budget) || 0), 0)
})

// Submit Action
async function submitForm(isSubmit) {
  if (!form.value.department || !form.value.strategic_objective.trim()) {
    feedback.value = { type: 'error', message: 'Divisi dan Sasaran Strategis Bank wajib diisi.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }

  for (let i = 0; i < form.value.items.length; i++) {
    const item = form.value.items[i]
    if (!item.category_id || !item.sub_category_id) {
      feedback.value = { type: 'error', message: `Baris #${i + 1}: Kategori dan Sub-Kategori wajib dipilih.` }
      return
    }
    if (!item.budget || Number(item.budget) <= 0) {
      feedback.value = { type: 'error', message: `Baris #${i + 1}: Anggaran usulan harus lebih dari 0.` }
      return
    }
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const formData = new FormData()
    formData.append('department', form.value.department)
    formData.append('year', form.value.year)
    formData.append('priority', form.value.priority)
    formData.append('target_quarter', form.value.target_quarter || `Q1 - Q4 ${form.value.year}`)
    formData.append('strategic_objective', form.value.strategic_objective)
    formData.append('is_submit', isSubmit ? '1' : '0')

    if (selectedFile.value) {
      formData.append('attachment', selectedFile.value)
      formData.append('attachment_name', selectedFile.value.name)
      formData.append('attachment_size', selectedFile.value.size)
    } else if (existingAttachment.value) {
      formData.append('attachment_name', existingAttachment.value.name)
      formData.append('attachment_size', existingAttachment.value.size)
    }

    form.value.items.forEach((item, index) => {
      formData.append(`items[${index}][category_id]`, item.category_id)
      formData.append(`items[${index}][sub_category_id]`, item.sub_category_id)
      formData.append(`items[${index}][execution_quarter]`, item.execution_quarter)
      formData.append(`items[${index}][target_participants_count]`, item.target_participants_count || 1)
      formData.append(`items[${index}][budget]`, item.budget)
    })

    const targetUrl = isEditMode.value ? `/tna/${props.editProposalId}` : '/tna'
    const { data } = await apiClient.post(targetUrl, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    const code = data.proposal?.proposal_code || editingProposal.value?.proposal_code || 'TNA'
    let msg = ''
    if (isEditMode.value) {
      msg = isSubmit
        ? `Usulan TNA (${code}) berhasil diperbaiki dan diajukan ulang ke Alur Multi-Approval.`
        : `Perubahan usulan TNA (${code}) berhasil disimpan sebagai Draft.`
    } else {
      msg = isSubmit
        ? `Usulan TNA (${code}) berhasil diajukan ke Alur Multi-Approval.`
        : `DRAFT Usulan TNA (${code}) berhasil disimpan.`
    }

    emit('created', msg)
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menyimpan usulan TNA.'
    }
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-6xl mx-auto pb-24">

    <!-- Top Action Navigation Bar -->
    <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-4">
      <div class="flex items-center gap-3">
        <button
          @click="emit('back')"
          class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center text-gray-700 shadow-2xs border border-gray-200 transition"
          :title="isEditMode ? 'Kembali ke Rincian Dokumen' : 'Kembali ke Daftar TNA'"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span
              class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
              :class="isEditMode ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-[#EBF4FC] text-[#0067AC]'"
            >
              {{ isEditMode ? 'Mode Perbaikan / Revisi Usulan' : 'Formulir TNA Tahunan' }}
            </span>
            <span class="text-xs text-gray-400">• Standar Learning BWS & POJK</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-extrabold text-[#002855]">
            {{ isEditMode ? `Perbaiki & Sesuaikan Usulan — ${editingProposal?.proposal_code || 'TNA'}` : 'Pengajuan Rencana Kebutuhan Diklat (TNA)' }}
          </h1>
        </div>
      </div>

      <!-- Quick Back Link -->
      <button
        @click="emit('back')"
        class="text-xs font-semibold text-gray-500 hover:text-[#0067AC] transition flex items-center gap-1"
      >
        <span>{{ isEditMode ? 'Kembali ke Rincian' : 'Batal & Kembali' }}</span>
      </button>
    </div>

    <!-- Revision Instructions Banner if in Edit Mode and has notes -->
    <div v-if="isEditMode && editingProposal?.rejection_notes" class="transition-all">
      <div class="p-4 sm:p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-950 flex items-start gap-3.5 shadow-xs">
        <div class="w-9 h-9 rounded-xl bg-amber-200 text-amber-900 flex items-center justify-center shrink-0 font-bold">
          <RotateCcw class="w-4 h-4 text-amber-800" />
        </div>
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <span class="text-xs font-extrabold uppercase tracking-wider text-amber-900">Catatan Otorisator untuk Revisi:</span>
            <span class="text-[10px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">Revisi Ke-{{ editingProposal.revision_count || 1 }}</span>
          </div>
          <p class="text-xs font-bold text-amber-950 italic">"{{ editingProposal.rejection_notes }}"</p>
          <p class="text-[11px] text-amber-800">
            Anda dapat menyesuaikan alokasi kuartal, target peserta, pagu biaya, atau menambah/menghapus baris kebutuhan diklat pada tabel rincian di bawah. Setelah siap, klik <strong>"Kirim Ulang ke Multi-Approval"</strong>.
          </p>
        </div>
      </div>
    </div>

    <!-- Alert / Feedback Banner -->
    <div v-if="feedback.message" class="transition-all">
      <div
        class="p-4 rounded-2xl flex items-center justify-between text-xs font-semibold shadow-2xs border"
        :class="feedback.type === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-blue-50 text-[#0067AC] border-blue-200'"
      >
        <div class="flex items-center gap-2">
          <AlertCircle v-if="feedback.type === 'error'" class="w-4 h-4 shrink-0 text-rose-600" />
          <CheckCircle2 v-else class="w-4 h-4 shrink-0 text-[#0067AC]" />
          <span>{{ feedback.message }}</span>
        </div>
        <button @click="feedback.message = ''" class="font-bold text-sm">&times;</button>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 1: DATA STRATEGIS & TAHUN DARI MASTER                  -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
      <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
        <Building2 class="w-4 h-4 text-[#0067AC]" />
        <h2 class="text-sm font-bold text-gray-900">1. Data Usulan & Sasaran Strategis Perbankan</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
        
        <!-- Unit Divisi Pengusul -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Unit Kerja / Divisi Pengusul *</label>
          <select
            v-model="form.department"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-medium"
          >
            <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
          </select>
        </div>

        <!-- Tahun Anggaran (Dinamis dari Master Tahun) -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700 flex items-center justify-between">
            <span>Tahun Anggaran Diklat *</span>
            <span class="text-[10px] text-[#0067AC] font-semibold">(Master Tahun)</span>
          </label>
          <select
            v-model="form.year"
            class="w-full bg-[#EBF4FC] rounded-xl px-3 py-2.5 text-xs text-[#002855] border border-blue-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-extrabold"
          >
            <option v-for="y in fiscalYears" :key="y.id" :value="y.year">
              Tahun {{ y.year }} ({{ y.status }})
            </option>
          </select>
        </div>

        <!-- Prioritas Usulan -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Tingkat Prioritas Usulan *</label>
          <select
            v-model="form.priority"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-medium"
          >
            <option value="Critical">Critical (Mandatori OJK / BI / Core Risk)</option>
            <option value="High">High (Target Sasaran Strategis Bisnis)</option>
            <option value="Medium">Medium (Penguatan Kompetensi Reguler)</option>
            <option value="Low">Low (Pengembangan Tambahan)</option>
          </select>
        </div>

        <!-- Target Kuartal -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Rencana Periode Pelaksanaan *</label>
          <select
            v-model="form.target_quarter"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-medium"
          >
            <option v-for="q in quarters" :key="q" :value="q">{{ q }}</option>
          </select>
        </div>
      </div>

      <!-- Sasaran Strategis Bank -->
      <div class="space-y-1.5 text-xs">
        <div class="flex items-center justify-between">
          <label class="font-bold text-gray-700">Latar Belakang & Sasaran Strategis Perbankan (Business Justification) *</label>
          <span class="text-gray-400 font-medium">Acuan verifikasi HCBP & sign-off Kadiv HC</span>
        </div>
        <textarea
          v-model="form.strategic_objective"
          rows="3"
          class="w-full bg-[#F8FAFC] rounded-2xl p-3.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition leading-relaxed font-medium"
          placeholder="Jelaskan dasar kebutuhan divisi, keselarasan dengan Key Performance Indicators (KPI), pemenuhan audit OJK/BI, atau kebutuhan transformasi digital..."
        ></textarea>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 2: RINCIAN KEBUTUHAN DIKLAT (BENTUK TABEL MULTIPLE ADD)-->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
      
      <!-- Section Header with Add Button -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
        <div>
          <div class="flex items-center gap-2">
            <Wallet class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">2. Rincian Kebutuhan Pelatihan & Alokasi Anggaran</h2>
          </div>
          <p class="text-xs text-gray-400">Pilih Kategori Induk dan Sub-Kategori materi yang diusulkan beserta alokasi anggarannya.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <button
            type="button"
            @click="populateAllCategoriesAndSubCategories"
            class="bg-[#EBF4FC] hover:bg-blue-100 text-[#0067AC] border border-[#0067AC]/30 px-3.5 py-2 rounded-full text-xs font-bold flex items-center gap-1.5 transition shadow-2xs cursor-pointer"
            title="Muat seluruh 20 program dari 5 Kategori secara lengkap sepanjang tahun (Q1 - Q4)"
          >
            <Sparkles class="w-3.5 h-3.5 text-[#0067AC]" />
            <span>+ Muat Seluruh Kategori & Sub (1 Tahun Lengkap)</span>
          </button>

          <button
            type="button"
            @click="addItemRow"
            class="bg-[#0067AC] hover:bg-[#00528c] text-white px-4 py-2 rounded-full text-xs font-bold flex items-center gap-1.5 transition shadow-2xs shrink-0 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>+ Tambah Baris</span>
          </button>
        </div>
      </div>

      <!-- Multiple Add Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-200">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
              <th class="py-3 px-3 w-10 text-center">No</th>
              <th class="py-3 px-3 w-64">Kategori Induk *</th>
              <th class="py-3 px-3">Sub-Kategori Diklat *</th>
              <th class="py-3 px-3 w-28 text-center">Kuartal</th>
              <th class="py-3 px-3 w-32 text-center">Target Peserta</th>
              <th class="py-3 px-3 w-48">Anggaran Usulan (Rp) *</th>
              <th class="py-3 px-3 w-14 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="(item, index) in form.items"
              :key="index"
              class="hover:bg-blue-50/20 transition group"
            >
              <!-- Number -->
              <td class="py-3 px-3 text-center font-bold text-gray-400">
                {{ index + 1 }}
              </td>

              <!-- Kategori Induk Dropdown with Color Pill Indicator -->
              <td class="py-3 px-3">
                <div class="flex items-center gap-1.5">
                  <span
                    class="w-2.5 h-2.5 rounded-full shrink-0"
                    :style="{ backgroundColor: getCategoryColor(item.category_id) }"
                    :title="categories.find(c => c.id === Number(item.category_id))?.name"
                  ></span>
                  <select
                    v-model="item.category_id"
                    @change="onCategoryChange(item)"
                    class="w-full bg-[#F8FAFC] rounded-xl px-2.5 py-2 text-xs font-bold text-[#002855] border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition"
                  >
                    <option value="" disabled>Pilih Kategori...</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </option>
                  </select>
                </div>
              </td>

              <!-- Sub-Kategori Dropdown (Filtered dynamically) -->
              <td class="py-3 px-3">
                <select
                  v-model="item.sub_category_id"
                  class="w-full bg-[#F8FAFC] rounded-xl px-2.5 py-2 text-xs font-medium text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition"
                >
                  <option value="" disabled>Pilih Sub-Kategori...</option>
                  <option
                    v-for="sub in getSubCategoriesForCategory(item.category_id)"
                    :key="sub.id"
                    :value="sub.id"
                  >
                    {{ sub.name }}
                  </option>
                </select>
              </td>

              <!-- Kuartal -->
              <td class="py-3 px-3 text-center">
                <select
                  v-model="item.execution_quarter"
                  class="w-full bg-[#F8FAFC] rounded-xl px-2 py-2 text-xs font-semibold text-center text-gray-800 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                >
                  <option value="Q1">Q1</option>
                  <option value="Q2">Q2</option>
                  <option value="Q3">Q3</option>
                  <option value="Q4">Q4</option>
                </select>
              </td>

              <!-- Target Peserta -->
              <td class="py-3 px-3 text-center">
                <div class="relative">
                  <input
                    v-model.number="item.target_participants_count"
                    type="number"
                    min="1"
                    placeholder="10"
                    class="w-full bg-[#F8FAFC] rounded-xl px-2 py-2 text-xs font-bold text-center text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                  />
                  <span class="text-[10px] text-gray-400 block mt-0.5 text-center font-medium">Pegawai</span>
                </div>
              </td>

              <!-- Anggaran Usulan (Rp) with Live Formatted Indonesian Rupiah -->
              <td class="py-3 px-3">
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-[11px]">Rp</span>
                  <input
                    v-model.number="item.budget"
                    type="number"
                    step="500000"
                    placeholder="50000000"
                    class="w-full bg-[#F8FAFC] rounded-xl pl-9 pr-3 py-2 text-xs font-mono font-extrabold text-[#0067AC] border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition text-right"
                  />
                </div>
                <div class="text-[10px] font-mono font-bold text-gray-500 text-right mt-1">
                  {{ formatRupiah(item.budget) }}
                </div>
              </td>

              <!-- Aksi Hapus -->
              <td class="py-3 px-3 text-center">
                <button
                  type="button"
                  @click="removeItemRow(index)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                  title="Hapus Baris Ini"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </td>
            </tr>

            <!-- Empty Row Fallback -->
            <tr v-if="form.items.length === 0">
              <td colspan="7" class="py-8 text-center text-gray-400 italic">
                Belum ada baris kebutuhan diklat. Silakan klik tombol "+ Tambah Baris Kebutuhan" di atas.
              </td>
            </tr>
          </tbody>

          <!-- Table Footer Summary -->
          <tfoot class="bg-[#F8FAFC] border-t-2 border-gray-200 font-bold">
            <tr>
              <td colspan="4" class="py-3 px-4 text-right text-gray-600 uppercase text-[11px] tracking-wide">
                Total Akumulasi Pagu Diusulkan:
              </td>
              <td class="py-3 px-3 text-center text-[#002855]">
                {{ totalParticipants }} Pegawai
              </td>
              <td class="py-3 px-3 text-right font-mono text-sm text-[#0067AC] font-black">
                {{ formatRupiah(totalBudgetRequested) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 3: UPLOAD DOKUMEN PENDUKUNG (JUSTIFIKASI / TOR / MEMO) -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div class="flex items-center gap-2">
          <FileUp class="w-4 h-4 text-[#0067AC]" />
          <h2 class="text-sm font-bold text-gray-900">3. Dokumen Lampiran Pendukung (TOR / Memo Divisi)</h2>
        </div>
        <span class="text-[11px] text-gray-400">Format PDF, DOCX, XLSX (Maks. 15 MB)</span>
      </div>

      <!-- Existing File Indicator when Editing -->
      <div
        v-if="existingAttachment && !selectedFile"
        class="flex items-center justify-between p-3.5 rounded-2xl bg-[#EBF4FC]/70 border border-blue-200"
      >
        <div class="flex items-center gap-2.5">
          <Paperclip class="w-4 h-4 text-[#0067AC]" />
          <div>
            <span class="text-[10px] font-bold uppercase text-gray-500 block">Lampiran Saat Ini (Tersimpan)</span>
            <span class="text-xs font-bold text-gray-900">{{ existingAttachment.name }}</span>
            <span class="text-[10px] text-gray-400 font-mono ml-2">({{ formatFileSize(existingAttachment.size) }})</span>
          </div>
        </div>
        <span class="text-[11px] text-[#0067AC] font-semibold italic">Pilih file baru di bawah jika ingin mengganti lampiran</span>
      </div>

      <!-- File Upload Dropzone -->
      <div
        v-if="!selectedFile"
        @click="$refs.fileInputRef.click()"
        class="border-2 border-dashed border-gray-200 hover:border-[#0067AC] bg-[#F8FAFC] hover:bg-blue-50/30 rounded-2xl p-6 text-center cursor-pointer transition group"
      >
        <input
          ref="fileInputRef"
          type="file"
          class="hidden"
          accept=".pdf,.doc,.docx,.xls,.xlsx,.zip"
          @change="onFileSelected"
        />
        <div class="flex flex-col items-center gap-2">
          <div class="w-12 h-12 rounded-2xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center group-hover:scale-105 transition">
            <Paperclip class="w-6 h-6" />
          </div>
          <div>
            <p class="text-xs font-bold text-gray-900">
              Klik untuk memilih dokumen atau seret file ke sini
            </p>
            <p class="text-[11px] text-gray-400 mt-0.5">
              Lampirkan Memo Pengusulan Kadiv, Kerangka Acuan Kerja (TOR), atau Rincian Silabus Pelatihan.
            </p>
          </div>
        </div>
      </div>

      <!-- File Attached Badge Preview -->
      <div
        v-else
        class="flex items-center justify-between p-4 rounded-2xl bg-[#EBF4FC] border border-blue-200"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-[#0067AC] text-white flex items-center justify-center font-bold">
            <FileText class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-bold text-[#002855]">{{ selectedFile.name }}</p>
            <p class="text-[11px] text-[#0067AC] font-medium">
              {{ formatFileSize(selectedFile.size) }} • Dokumen Pendukung Terlampir
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="removeAttachment"
          class="text-gray-400 hover:text-rose-600 p-2 rounded-lg hover:bg-white transition"
          title="Hapus Dokumen"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- STICKY BOTTOM ACTION BAR                                      -->
    <!-- ============================================================= -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 shadow-xl px-4 py-3 sm:px-8">
      <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        
        <!-- Live Summary Metrics -->
        <div class="flex items-center gap-5 sm:gap-8 text-left">
          <div>
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider block">Total Usulan</span>
            <span class="text-sm font-extrabold text-[#002855]">{{ form.items.length }} Mata Diklat</span>
          </div>
          <div class="h-6 w-px bg-gray-200"></div>
          <div>
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider block">Total Peserta</span>
            <span class="text-sm font-extrabold text-[#002855]">{{ totalParticipants }} Pegawai BWS</span>
          </div>
          <div class="h-6 w-px bg-gray-200"></div>
          <div>
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider block">Total Anggaran</span>
            <span class="text-base font-black text-[#0067AC] font-mono">{{ formatRupiah(totalBudgetRequested) }}</span>
          </div>
        </div>

        <!-- Submission Buttons -->
        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
          <button
            type="button"
            :disabled="isSubmitting"
            @click="submitForm(false)"
            class="flex-1 sm:flex-none px-4 py-2.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <Save class="w-3.5 h-3.5" />
            <span>{{ isEditMode ? 'Simpan Perubahan Draft' : 'Simpan DRAFT' }}</span>
          </button>

          <button
            type="button"
            :disabled="isSubmitting"
            @click="submitForm(true)"
            class="flex-1 sm:flex-none px-6 py-2.5 rounded-full bg-[#002855] hover:bg-[#003876] text-white text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <Send class="w-3.5 h-3.5" />
            <span>{{ isSubmitting ? 'Memproses...' : (isEditMode ? 'Kirim Ulang ke Multi-Approval' : 'Ajukan ke Alur Multi-Approval') }}</span>
          </button>
        </div>

      </div>
    </div>

  </div>
</template>
