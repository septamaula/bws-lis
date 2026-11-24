<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  Plus,
  Trash2,
  Calendar,
  Wallet,
  Save,
  AlertCircle,
  CheckCircle2,
  ChevronRight,
  FileUp,
  Paperclip,
  X,
  FileText,
  Layers,
  RotateCcw,
  ShieldCheck,
  Building2,
  ChevronDown,
  FileSpreadsheet,
  Download,
  Upload
} from 'lucide-vue-next'
import * as XLSX from 'xlsx'
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

// Master Data State
const loadingMasters = ref(true)
const categories = ref([])
const fiscalYears = ref([])

const selectedFiscalYear = computed(() => {
  return fiscalYears.value.find(y => Number(y.year) === Number(form.value.year))
})

const isYearAlreadyRegistered = computed(() => {
  if (isEditMode.value) return false
  return (selectedFiscalYear.value?.tna_proposals_count || 0) > 0
})

// Form State matching Excel wireframe
const form = ref({
  year: 2026,
  tna_status: 'Aktif',
  minimum_regulator_investment: 1100000000,
  investment_projection: 0,
  strategic_objective: 'Akselerasi kapabilitas digital perbankan, kepatuhan regulasi OJK & BI, serta mitigasi risiko operasional.',
  department: 'Human Capital & Corporate Academy (Bank-Wide Plan)',
  items: []
})

// File Attachment State
const selectedFile = ref(null)
const fileInputRef = ref(null)

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

// Total Calculation
const totalInvestment = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (Number(item.budget) || 0), 0)
})

// Import Excel State & Methods
const showImportModal = ref(false)
const importFile = ref(null)
const importFileInputRef = ref(null)
const isParsingExcel = ref(false)
const importError = ref('')
const parsedItems = ref([])
const importMode = ref('replace') // 'replace' | 'append'

const parsedItemsTotal = computed(() => {
  return parsedItems.value.reduce((sum, item) => sum + (Number(item.budget) || 0), 0)
})

function openImportModal() {
  importFile.value = null
  importError.value = ''
  parsedItems.value = []
  importMode.value = 'replace'
  showImportModal.value = true
}

function downloadTemplate() {
  const wsData = [
    ['No', 'Kategori Pelatihan', 'Judul Program Pelatihan', 'Pagu Investasi (Rp)', 'Estimasi Realisasi (Rp)'],
    [1, 'Operasional & Layanan Perbankan', 'Frontliner, Teller & Service Excellence', 65000000, 0],
    [2, 'Operasional & Layanan Perbankan', 'Back Office, Kliring & Sistem Pembayaran (SKNBI/FAST)', 55000000, 0],
    [3, 'Operasional & Layanan Perbankan', 'Trade Finance & Transaksi Ekspor-Impor', 65000000, 0],
    [4, 'Kredit & Pembiayaan Komersial', 'Analisis Kelayakan Kredit & Cashflow Modelling', 95000000, 0],
    [5, 'Kredit & Pembiayaan Komersial', 'Mitigasi Risiko Kredit & Pencegahan NPL', 70000000, 0],
    [6, 'Manajemen Risiko, Kepatuhan & Regulasi OJK', 'Sertifikasi Manajemen Risiko Perbankan (BSMR 1 - 4)', 115000000, 0],
    [7, 'Manajemen Risiko, Kepatuhan & Regulasi OJK', 'APU-PPT (Anti Pencucian Uang & Pencegahan Terorisme)', 60000000, 0],
    [8, 'Teknologi Informasi & Keamanan Siber', 'Keamanan Siber Perbankan & Kepatuhan POJK 29/2022', 110000000, 0],
    [9, 'Teknologi Informasi & Keamanan Siber', 'Digital Banking, API Open Banking & SNAP BI', 85000000, 0],
    [10, 'Kepemimpinan, Manajerial & Budaya Kerja', 'Officer Development Program (ODP) Corporate Academy', 110000000, 0],
    [11, 'Kepemimpinan, Manajerial & Budaya Kerja', 'Branch Manager Leadership & P&L Business Mastery', 75000000, 0]
  ]

  const ws = XLSX.utils.aoa_to_sheet(wsData)
  ws['!cols'] = [
    { wch: 6 },
    { wch: 45 },
    { wch: 55 },
    { wch: 22 },
    { wch: 22 }
  ]

  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Rincian Program TNA')

  XLSX.writeFile(wb, 'Template_Usulan_Program_TNA_BWS.xlsx')
}

function handleImportFileChange(e) {
  const file = e.target?.files?.[0]
  if (file) processExcelFile(file)
}

function handleFileDrop(e) {
  const file = e.dataTransfer?.files?.[0]
  if (file) processExcelFile(file)
}

async function processExcelFile(file) {
  importFile.value = file
  importError.value = ''
  parsedItems.value = []
  isParsingExcel.value = true

  try {
    const data = await file.arrayBuffer()
    const wb = XLSX.read(data, { type: 'array' })

    // Look for target sheet
    let targetSheet = wb.SheetNames[0]
    for (const name of wb.SheetNames) {
      const lower = name.toLowerCase()
      if (name === 'Rincian Program TNA' || lower.includes('tna') || lower.includes('program') || lower.includes('usulan')) {
        targetSheet = name
        break
      }
    }

    const sheet = wb.Sheets[targetSheet]
    if (!sheet) throw new Error('Sheet data tidak ditemukan di file Excel.')

    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 })
    if (!rows || rows.length === 0) throw new Error('File Excel tidak memiliki baris data.')

    // Find header row
    let headerIdx = -1
    let colCat = -1, colProg = -1, colBudget = -1, colReal = -1

    for (let r = 0; r < Math.min(rows.length, 40); r++) {
      const row = rows[r] || []
      for (let c = 0; c < row.length; c++) {
        const val = String(row[c] || '').toLowerCase().trim()
        if (val.includes('kategori') || val.includes('category')) colCat = c
        if (val.includes('program') || val.includes('training') || val.includes('judul')) colProg = c
        if (val.includes('pagu') || val.includes('investasi') || val.includes('investment') || val.includes('budget') || val.includes('biaya')) colBudget = c
        if (val.includes('realis') || val.includes('realiz')) colReal = c
      }
      if (colProg !== -1 && colBudget !== -1) {
        headerIdx = r
        break
      }
    }

    // Fallback if no explicit header found: assume col 1=Category, col 2=Program, col 3=Budget, col 4=Realization
    if (headerIdx === -1) {
      headerIdx = 0
      colCat = 1
      colProg = 2
      colBudget = 3
      colReal = 4
    }

    const items = []
    let currentCategory = categories.value[0]?.name || 'Operasional & Layanan Perbankan'

    for (let r = headerIdx + 1; r < rows.length; r++) {
      const row = rows[r] || []
      if (!row || row.length === 0) continue

      const progVal = colProg !== -1 ? String(row[colProg] || '').trim() : ''
      if (!progVal || progVal.toLowerCase() === 'total' || progVal.toLowerCase().startsWith('subtotal')) continue

      const catVal = colCat !== -1 ? String(row[colCat] || '').trim() : ''
      if (catVal && catVal.toLowerCase() !== 'total' && !catVal.toLowerCase().startsWith('subtotal')) {
        currentCategory = catVal
      }

      // Match category from master
      const matchedCat = categories.value.find(c =>
        c.name.toLowerCase() === currentCategory.toLowerCase() ||
        c.name.toLowerCase().includes(currentCategory.toLowerCase()) ||
        currentCategory.toLowerCase().includes(c.name.toLowerCase())
      )

      const rawBudget = colBudget !== -1 ? row[colBudget] : 0
      const numBudget = Number(String(rawBudget).replace(/[^0-9.-]+/g, '')) || 0

      const rawReal = colReal !== -1 ? row[colReal] : 0
      const numReal = Number(String(rawReal).replace(/[^0-9.-]+/g, '')) || 0

      items.push({
        category_id: matchedCat?.id || null,
        category_name: matchedCat?.name || currentCategory,
        recommended_program: progVal,
        budget: numBudget,
        realization_amount: numReal
      })
    }

    if (items.length === 0) {
      throw new Error('Tidak ada baris program yang dapat dibaca. Pastikan kolom "Kategori", "Program Pelatihan", dan "Pagu Investasi" terisi.')
    }

    parsedItems.value = items
  } catch (err) {
    importError.value = err.message || 'Gagal memproses file Excel.'
    parsedItems.value = []
  } finally {
    isParsingExcel.value = false
  }
}

function applyImportedItems() {
  if (parsedItems.value.length === 0) return

  if (importMode.value === 'replace') {
    form.value.items = [...parsedItems.value]
  } else {
    form.value.items = [...form.value.items, ...parsedItems.value]
  }

  feedback.value = {
    type: 'success',
    message: `Berhasil mengimpor ${parsedItems.value.length} program pelatihan ke tabel TNA.`
  }

  showImportModal.value = false
}

function onFileSelect(e) {
  const file = e.target.files[0]
  if (!file) return

  const allowedTypes = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel'
  ]

  if (!allowedTypes.includes(file.type) && !file.name.match(/\.(pdf|docx|doc|xlsx|xls)$/i)) {
    feedback.value = {
      type: 'error',
      message: 'Format berkas tidak didukung. Harap unggah dokumen PDF, Word, atau Excel.'
    }
    return
  }

  if (file.size > 15 * 1024 * 1024) {
    feedback.value = {
      type: 'error',
      message: 'Ukuran file melebihi batas maksimal 15 MB.'
    }
    return
  }

  selectedFile.value = file
  feedback.value = { type: '', message: '' }
}

function removeFile() {
  selectedFile.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const itemColumns = [
  { key: 'category_name', label: 'Category', width: '32%' },
  { key: 'recommended_program', label: 'Training Program' },
  { key: 'budget', label: 'Investment (Projection)', align: 'right', width: '220px' },
  { key: 'action', label: 'Aksi', align: 'center', width: '60px' }
]

function addItemRow() {
  const firstCat = categories.value[0]
  form.value.items.push({
    category_id: firstCat?.id || null,
    category_name: firstCat?.name || 'Operasional & Layanan Perbankan',
    recommended_program: '',
    budget: 50000000,
    realization_amount: 0
  })
}

function onCategoryChange(item) {
  const matched = categories.value.find(c => c.name === item.category_name)
  if (matched) {
    item.category_id = matched.id
  }
}

function removeItemRow(index) {
  if (form.value.items.length <= 1) {
    feedback.value = {
      type: 'error',
      message: 'TNA harus memiliki minimal satu program pelatihan.'
    }
    return
  }
  form.value.items.splice(index, 1)
}

// Preset: Muat Contoh Lengkap 20 Program (Total Pagu: Rp 1.485.000.000)
function loadPreset20Programs() {
  form.value.items = [
    // Operasional & Layanan Perbankan (Subtotal: Rp 260.000.000)
    { category_name: 'Operasional & Layanan Perbankan', recommended_program: 'Frontliner, Teller & Service Excellence', budget: 65000000, realization_amount: 0 },
    { category_name: 'Operasional & Layanan Perbankan', recommended_program: 'Back Office, Kliring & Sistem Pembayaran (SKNBI/FAST)', budget: 55000000, realization_amount: 0 },
    { category_name: 'Operasional & Layanan Perbankan', recommended_program: 'Trade Finance & Transaksi Ekspor-Impor', budget: 65000000, realization_amount: 0 },
    { category_name: 'Operasional & Layanan Perbankan', recommended_program: 'Treasury Operations & Pasar Uang', budget: 75000000, realization_amount: 0 },
    // Kredit & Pembiayaan Komersial (Subtotal: Rp 310.000.000)
    { category_name: 'Kredit & Pembiayaan Komersial', recommended_program: 'Analisis Kelayakan Kredit & Cashflow Modelling', budget: 95000000, realization_amount: 0 },
    { category_name: 'Kredit & Pembiayaan Komersial', recommended_program: 'Mitigasi Risiko Kredit & Pencegahan NPL', budget: 70000000, realization_amount: 0 },
    { category_name: 'Kredit & Pembiayaan Komersial', recommended_program: 'Restrukturisasi Kredit & Penyelamatan Aset (Remedial)', budget: 60000000, realization_amount: 0 },
    { category_name: 'Kredit & Pembiayaan Komersial', recommended_program: 'Kredit Konsumer, KPR & Pembiayaan Pensiunan BWS', budget: 85000000, realization_amount: 0 },
    // Manajemen Risiko, Kepatuhan & Regulasi OJK (Subtotal: Rp 290.000.000)
    { category_name: 'Manajemen Risiko, Kepatuhan & Regulasi OJK', recommended_program: 'Sertifikasi Manajemen Risiko Perbankan (BSMR 1 - 4)', budget: 115000000, realization_amount: 0 },
    { category_name: 'Manajemen Risiko, Kepatuhan & Regulasi OJK', recommended_program: 'APU-PPT (Anti Pencucian Uang & Pencegahan Terorisme)', budget: 60000000, realization_amount: 0 },
    { category_name: 'Manajemen Risiko, Kepatuhan & Regulasi OJK', recommended_program: 'Audit Internal Perbankan & Investigasi Anti-Fraud', budget: 65000000, realization_amount: 0 },
    { category_name: 'Manajemen Risiko, Kepatuhan & Regulasi OJK', recommended_program: 'Perlindungan Konsumen & Kerahasiaan Data (UU PDP)', budget: 50000000, realization_amount: 0 },
    // Teknologi Informasi & Keamanan Siber (Subtotal: Rp 350.000.000)
    { category_name: 'Teknologi Informasi & Keamanan Siber', recommended_program: 'Keamanan Siber Perbankan & Kepatuhan POJK 29/2022', budget: 110000000, realization_amount: 0 },
    { category_name: 'Teknologi Informasi & Keamanan Siber', recommended_program: 'Core Banking Administration & Database Tuning', budget: 75000000, realization_amount: 0 },
    { category_name: 'Teknologi Informasi & Keamanan Siber', recommended_program: 'Digital Banking, API Open Banking & SNAP BI', budget: 85000000, realization_amount: 0 },
    { category_name: 'Teknologi Informasi & Keamanan Siber', recommended_program: 'IT Disaster Recovery & Business Continuity (BCM)', budget: 80000000, realization_amount: 0 },
    // Kepemimpinan, Manajerial & Budaya Kerja (Subtotal: Rp 270.000.000)
    { category_name: 'Kepemimpinan, Manajerial & Budaya Kerja', recommended_program: 'Officer Development Program (ODP) Corporate Academy', budget: 110000000, realization_amount: 0 },
    { category_name: 'Kepemimpinan, Manajerial & Budaya Kerja', recommended_program: 'Branch Manager Leadership & P&L Business Mastery', budget: 75000000, realization_amount: 0 },
    { category_name: 'Kepemimpinan, Manajerial & Budaya Kerja', recommended_program: 'Supervisory Coaching, Mentoring & People Development', budget: 50000000, realization_amount: 0 },
    { category_name: 'Kepemimpinan, Manajerial & Budaya Kerja', recommended_program: 'Budaya Kerja Perbankan BWS & Integrity Excellence', budget: 35000000, realization_amount: 0 }
  ]

  // Link category_id automatically from loaded master categories
  form.value.items.forEach(item => {
    const matched = categories.value.find(c => c.name === item.category_name)
    if (matched) {
      item.category_id = matched.id
    }
  })
}

// Fetch Master Data on Mount
async function loadMasterData() {
  loadingMasters.value = true
  try {
    const [resCats, resYears] = await Promise.all([
      apiClient.get('/categories'),
      apiClient.get('/fiscal-years')
    ])
    categories.value = resCats.data.categories || []
    fiscalYears.value = resYears.data.years || []

    if (!isEditMode.value) {
      const activeFy = fiscalYears.value.find(y => y.is_active)
      const availableFy = fiscalYears.value.find(y => (y.tna_proposals_count || 0) === 0 && y.status !== 'TUTUP_BUKU')
      if (activeFy && (activeFy.tna_proposals_count || 0) === 0) {
        form.value.year = activeFy.year
      } else if (availableFy) {
        form.value.year = availableFy.year
      } else if (resYears.data.active_year) {
        form.value.year = resYears.data.active_year
      }
    }

    if (isEditMode.value) {
      const { data } = await apiClient.get(`/tna/${props.editProposalId}`)
      const p = data.proposal
      editingProposal.value = p

      form.value.year = p.year || 2026
      form.value.tna_status = p.tna_status || 'Aktif'
      form.value.minimum_regulator_investment = p.minimum_regulator_investment || 1000000000
      form.value.strategic_objective = p.strategic_objective || ''
      form.value.department = p.department || 'Human Capital & Corporate Academy'

      if (p.attachment_name) {
        existingAttachment.value = {
          name: p.attachment_name,
          size: p.attachment_size,
          path: p.attachment_path
        }
      }

      if (p.items && p.items.length > 0) {
        form.value.items = p.items.map(it => ({
          category_id: it.category_id || null,
          category_name: it.category_name || it.category?.name || 'Operasional & Layanan Perbankan',
          recommended_program: it.recommended_program || it.sub_category_name || 'Program Pelatihan',
          budget: Number(it.budget || it.estimated_cost) || 0,
          realization_amount: Number(it.realization_amount) || 0
        }))
      } else {
        addItemRow()
      }
    } else {
      loadPreset20Programs()
    }
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: 'Gagal memuat master data kategori atau rincian usulan.'
    }
  } finally {
    loadingMasters.value = false
  }
}

onMounted(() => {
  loadMasterData()
})

async function submitForm() {
  if (!form.value.year) {
    feedback.value = { type: 'error', message: 'Tahun Anggaran wajib diisi.' }
    return
  }

  if (!isEditMode.value && isYearAlreadyRegistered.value) {
    feedback.value = {
      type: 'error',
      message: `Tahun Anggaran ${form.value.year} sudah memiliki dokumen TNA terdaftar. Dalam 1 tahun anggaran hanya terdapat 1 dokumen TNA Induk.`
    }
    return
  }

  if (form.value.items.length === 0) {
    feedback.value = { type: 'error', message: 'Minimal 1 baris program pelatihan harus dimasukkan.' }
    return
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const payloadItems = form.value.items.map(i => {
      const matchedCat = categories.value.find(c => c.name === i.category_name)
      return {
        category_id: i.category_id || matchedCat?.id || null,
        category_name: i.category_name,
        recommended_program: i.recommended_program,
        budget: Number(i.budget) || 0,
        realization_amount: Number(i.realization_amount) || 0
      }
    })

    const formData = new FormData()
    formData.append('year', form.value.year)
    formData.append('tna_status', form.value.tna_status || 'Aktif')
    formData.append('minimum_regulator_investment', form.value.minimum_regulator_investment || 0)
    formData.append('investment_projection', totalInvestment.value)
    formData.append('strategic_objective', form.value.strategic_objective)
    formData.append('department', form.value.department)
    formData.append('is_submit', '1')

    if (selectedFile.value) {
      formData.append('attachment', selectedFile.value)
    }

    payloadItems.forEach((it, idx) => {
      if (it.category_id) {
        formData.append(`items[${idx}][category_id]`, it.category_id)
      }
      formData.append(`items[${idx}][category_name]`, it.category_name)
      formData.append(`items[${idx}][recommended_program]`, it.recommended_program)
      formData.append(`items[${idx}][budget]`, it.budget)
      formData.append(`items[${idx}][realization_amount]`, it.realization_amount)
    })

    let res
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
      res = await apiClient.post(`/tna/${props.editProposalId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      res = await apiClient.post('/tna', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }

    emit('created', res.data.message || 'Dokumen TNA berhasil disimpan.')
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menyimpan usulan TNA.'
    feedback.value = { type: 'error', message: msg }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-12">

    <!-- Standard Subpage Header (Navigation) -->
    <SubpageHeader
      back-label="Kembali ke List TNA"
      :title="isEditMode ? `Edit Usulan TNA - Tahun Anggaran ${form.year}` : 'Form Usulan TNA Baru (Rencana Tahunan)'"
      subtitle="Perencanaan kebutuhan diklat dan alokasi pagu investasi bank-wide"
      :badge="isEditMode ? 'Mode Edit' : '1 Dokumen per Tahun'"
      @back="emit('back')"
    />

    <!-- Alert / Feedback Banner -->
    <div
      v-if="feedback.message"
      class="p-4 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-xs animate-fadeIn"
      :class="feedback.type === 'error' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
    >
      <div class="flex items-center gap-2">
        <AlertCircle v-if="feedback.type === 'error'" class="w-4 h-4 text-rose-600" />
        <CheckCircle2 v-else class="w-4 h-4 text-emerald-600" />
        <span>{{ feedback.message }}</span>
      </div>
      <button @click="feedback = { type: '', message: '' }" class="hover:opacity-75">
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- MAIN FORM CONTAINER: Create/Edit TNA -->
    <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm overflow-hidden">

      <!-- Form Body -->
      <div class="p-6 md:p-8 flex flex-col gap-6">

        <!-- Top 4 Fields matching Excel Mockup -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-gray-50/70 p-5 rounded-2xl border border-gray-200">
          
          <!-- Field 1: Year & Status TNA -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                <span>Year (Tahun)</span>
                <span class="text-[10px] text-[#0067AC] font-semibold">Wajib</span>
              </label>
              <div class="relative">
                <select
                  v-model.number="form.year"
                  :disabled="isEditMode || loadingMasters"
                  class="w-full bg-white text-xs font-bold text-gray-900 rounded-xl px-3 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs appearance-none pr-8 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                  <option v-if="loadingMasters" :value="form.year" disabled>Memuat...</option>
                  <option
                    v-for="fy in fiscalYears"
                    :key="fy.id"
                    :value="fy.year"
                  >
                    {{ fy.year }} &mdash; {{ fy.status === 'AKTIF' ? 'Aktif' : 'Tutup' }}
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400">
                  <ChevronDown class="w-4 h-4" />
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                <span>Status Siklus TNA</span>
                <span class="text-[10px] font-bold" :class="form.tna_status === 'Aktif' ? 'text-emerald-600' : 'text-gray-500'">
                  {{ form.tna_status }}
                </span>
              </label>
              <select
                v-model="form.tna_status"
                class="w-full bg-white text-xs font-bold text-gray-900 rounded-xl px-3 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs cursor-pointer"
              >
                <option value="Aktif">Aktif (Dapat Dirujuk Program)</option>
                <option value="Closed">Closed (Siklus Selesai / Arsip)</option>
              </select>
            </div>
          </div>

          <!-- Field 2: Upload Document -->
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Upload Document</span>
              <span class="text-[11px] text-gray-400 font-normal">TOR / Memo Pendukung (PDF, DOCX)</span>
            </label>
            <div class="flex items-center gap-2">
              <input
                ref="fileInputRef"
                type="file"
                @change="onFileSelect"
                class="hidden"
                accept=".pdf,.docx,.doc,.xlsx,.xls"
              />
              <button
                type="button"
                @click="fileInputRef?.click()"
                class="px-4 py-2 rounded-xl bg-white hover:bg-gray-100 border border-gray-300 text-gray-700 font-bold text-xs shadow-2xs transition flex items-center gap-1.5 cursor-pointer shrink-0"
              >
                <FileUp class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Browse File</span>
              </button>

              <div class="flex-1 min-w-0">
                <div v-if="selectedFile" class="flex items-center justify-between bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-200 text-xs text-[#0067AC]">
                  <span class="truncate font-semibold text-[11px]">{{ selectedFile.name }}</span>
                  <button @click="removeFile" type="button" class="text-rose-500 hover:text-rose-700 ml-1">
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>
                <div v-else-if="existingAttachment" class="flex items-center justify-between bg-gray-100 px-3 py-1.5 rounded-xl text-xs text-gray-700">
                  <span class="truncate font-semibold text-[11px]">{{ existingAttachment.name }}</span>
                  <span class="text-[10px] text-gray-400 shrink-0">(Tersimpan)</span>
                </div>
                <span v-else class="text-xs text-gray-400 italic">Belum ada file dipilih</span>
              </div>
            </div>
          </div>

          <!-- Field 3: Minimum Investment Projection Based on Regulator Statement -->
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Minimum Investment Projection Based on Regulator Statement</span>
            </label>
            <AppNumberInput
              v-model="form.minimum_regulator_investment"
              prefix="Rp"
              placeholder="1.000.000.000"
            />
            <p class="text-[10px] text-gray-400 mt-1 font-mono">
              Ketentuan Alokasi OJK / Regulasi Bank
            </p>
          </div>

          <!-- Field 4: Investment (Projection) Total -->
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Investment (Projection) - Total Usulan</span>
              <span class="text-[10px] text-emerald-600 font-bold">Kalkulasi Otomatis</span>
            </label>
            <div class="bg-blue-50/80 px-4 py-2.5 rounded-xl border border-blue-200 flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500">Total Proyeksi:</span>
              <span class="text-base font-extrabold text-[#0067AC] font-mono">
                {{ formatRupiah(totalInvestment) }}
              </span>
            </div>
            <p class="text-[10px] text-gray-400 mt-1">
              Akumulasi total dari {{ form.items.length }} program pelatihan di bawah.
            </p>
          </div>

        </div>

        <!-- Section 2: Items Table (Matching Excel Mockup) -->
        <div class="flex flex-col gap-3">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">Rincian Program Pelatihan & Anggaran</h3>
              <p class="text-[11px] text-gray-500">Tentukan kategori diklat, judul program, dan pagu proyeksi investasi</p>
            </div>

            <div class="flex items-center gap-2">
              <AppButton
                type="button"
                variant="outline"
                size="sm"
                @click="openImportModal"
                title="Import data program pelatihan dari file Excel/CSV"
              >
                <span class="flex items-center gap-1.5">
                  <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600" />
                  Import Excel
                </span>
              </AppButton>
              <AppButton
                type="button"
                variant="primary"
                size="sm"
                icon="plus"
                @click="addItemRow"
              >
                Tambah Baris
              </AppButton>
            </div>
          </div>

          <!-- Standard Detail Table for Form Items -->
          <DetailTable :columns="itemColumns" show-numbering>
            <template #tbody>
              <tr
                v-for="(item, idx) in form.items"
                :key="idx"
                class="hover:bg-blue-50/30 transition"
              >
                <!-- No -->
                <td class="py-2.5 px-3 text-center text-gray-400 font-bold font-mono text-[11px]">
                  {{ idx + 1 }}
                </td>

                <!-- Category (Master Data Select) -->
                <td class="py-2.5 px-3">
                  <div class="relative">
                    <select
                      v-model="item.category_name"
                      @change="onCategoryChange(item)"
                      class="w-full bg-white text-xs font-semibold text-gray-800 rounded-lg pl-3 pr-8 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shadow-2xs cursor-pointer appearance-none truncate"
                    >
                      <option value="" disabled>-- Pilih Kategori Diklat --</option>
                      <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.name"
                      >
                        {{ cat.name }}
                      </option>
                      <!-- Fallback jika ada kategori legacy/kustom di luar master data -->
                      <option
                        v-if="item.category_name && !categories.some(c => c.name === item.category_name)"
                        :value="item.category_name"
                      >
                        {{ item.category_name }}
                      </option>
                    </select>
                    <ChevronDown class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
                </td>

                <!-- Training Program -->
                <td class="py-2.5 px-3">
                  <input
                    v-model="item.recommended_program"
                    type="text"
                    placeholder="Judul / nama program pelatihan..."
                    class="w-full bg-white text-xs text-gray-800 rounded-lg px-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shadow-2xs placeholder:text-gray-300"
                  />
                </td>

                <!-- Investment (Projection) -->
                <td class="py-2.5 px-3 text-right">
                  <AppNumberInput
                    v-model="item.budget"
                    prefix="Rp"
                    size="sm"
                    align="right"
                    placeholder="0"
                  />
                </td>

                <!-- Action -->
                <td class="py-2.5 px-3 text-center">
                  <button
                    type="button"
                    @click="removeItemRow(idx)"
                    class="p-1 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    title="Hapus baris"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>

              <tr v-if="form.items.length === 0">
                <td colspan="5" class="py-8 text-center text-gray-400">
                  Belum ada baris program. Klik tombol "Tambah Baris" di atas.
                </td>
              </tr>
            </template>

            <!-- Table Footer -->
            <template #tfoot>
              <tr class="bg-[#f8faf9] border-t-2 border-gray-300 font-extrabold text-xs">
                <td colspan="3" class="py-3.5 px-4 text-right text-gray-800 uppercase tracking-wider">
                  Total Investment (Projection):
                </td>
                <td class="py-3.5 px-4 text-right font-mono text-sm text-[#0067AC]">
                  {{ formatRupiah(totalInvestment) }}
                </td>
                <td></td>
              </tr>
            </template>
          </DetailTable>
        </div>

        <!-- Action Buttons: Save & Cancel (Matching Excel Mockup) -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="emit('back')"
            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-100 font-bold text-xs transition cursor-pointer"
          >
            Cancel
          </button>

          <button
            type="button"
            @click="submitForm"
            :disabled="isSubmitting"
            class="px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <Save class="w-4 h-4" />
            <span>{{ isSubmitting ? 'Menyimpan...' : 'Save' }}</span>
          </button>
        </div>

      </div>

    </div>

    <!-- ============================================================= -->
    <!-- MODAL: IMPORT EXCEL & DOWNLOAD TEMPLATE                       -->
    <!-- ============================================================= -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fadeIn"
      @click.self="showImportModal = false"
    >
      <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-emerald-50/40 via-white to-white">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-100 shadow-2xs">
              <FileSpreadsheet class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-extrabold text-sm text-gray-900">Import Program Pelatihan & Anggaran TNA</h3>
              <p class="text-[11px] text-gray-500">Unduh template resmi atau unggah file spreadsheet untuk mengisi tabel program</p>
            </div>
          </div>
          <button
            type="button"
            @click="showImportModal = false"
            class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition cursor-pointer"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 overflow-y-auto space-y-5 text-xs">
          <!-- Step 1: Download Template Card -->
          <div class="p-4 rounded-2xl bg-gradient-to-r from-[#0067AC]/5 via-blue-50/40 to-slate-50 border border-blue-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3.5">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full bg-[#0067AC] text-white text-[10px] font-bold uppercase tracking-wider">Step 1</span>
                <span class="font-bold text-gray-900">Unduh Format Template Resmi BWS</span>
              </div>
              <p class="text-[11px] text-gray-600 leading-relaxed">
                Gunakan format template standar dengan kolom Kategori Pelatihan, Program, dan Pagu Investasi agar data terbaca otomatis.
              </p>
            </div>
            <button
              type="button"
              @click="downloadTemplate"
              class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-blue-200 text-[#0067AC] hover:bg-blue-50 font-bold text-xs transition shadow-2xs hover:shadow-xs shrink-0 cursor-pointer"
            >
              <Download class="w-4 h-4 text-[#0067AC]" />
              <span>Download Template (.xlsx)</span>
            </button>
          </div>

          <!-- Step 2: Upload File Area -->
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-full bg-gray-800 text-white text-[10px] font-bold uppercase tracking-wider">Step 2</span>
              <span class="font-bold text-gray-900">Unggah File Spreadsheet yang Sudah Diisi</span>
            </div>

            <div
              @dragover.prevent
              @drop.prevent="handleFileDrop"
              @click="importFileInputRef?.click()"
              class="border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition flex flex-col items-center justify-center gap-2"
              :class="importFile ? 'border-emerald-300 bg-emerald-50/30' : 'border-gray-200 hover:border-[#0067AC] bg-gray-50/50 hover:bg-blue-50/20'"
            >
              <input
                ref="importFileInputRef"
                type="file"
                accept=".xlsx, .xls, .csv"
                class="hidden"
                @change="handleImportFileChange"
              />

              <div
                class="w-12 h-12 rounded-2xl flex items-center justify-center"
                :class="importFile ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100/70 text-[#0067AC]'"
              >
                <FileSpreadsheet v-if="importFile" class="w-6 h-6" />
                <Upload v-else class="w-6 h-6" />
              </div>

              <div v-if="importFile" class="space-y-1">
                <p class="font-bold text-gray-900 text-xs">{{ importFile.name }}</p>
                <p class="text-[11px] text-gray-500 font-mono">{{ formatFileSize(importFile.size) }}</p>
                <p class="text-[10px] text-emerald-700 font-semibold flex items-center justify-center gap-1">
                  <CheckCircle2 class="w-3.5 h-3.5" /> File berhasil diproses. Klik untuk mengganti file.
                </p>
              </div>
              <div v-else class="space-y-1">
                <p class="font-bold text-gray-700 text-xs">Tarik & lepaskan file Excel di sini, atau <span class="text-[#0067AC] underline font-semibold">Pilih File</span></p>
                <p class="text-[10px] text-gray-400">Mendukung format .xlsx, .xls, dan .csv</p>
              </div>
            </div>
          </div>

          <!-- Loading Spinner during parse -->
          <div v-if="isParsingExcel" class="p-3 text-center text-xs text-gray-500 animate-pulse">
            Sedang membaca dan memetakan baris program pelatihan...
          </div>

          <!-- Error Alert -->
          <div v-if="importError" class="p-3.5 rounded-xl bg-rose-50 text-rose-800 border border-rose-200 text-xs flex items-center gap-2">
            <AlertCircle class="w-4 h-4 text-rose-600 shrink-0" />
            <span>{{ importError }}</span>
          </div>

          <!-- Step 3: Parsed Preview -->
          <div v-if="parsedItems.length > 0" class="space-y-3 pt-1 border-t border-gray-100">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-bold uppercase tracking-wider">Hasil Baca</span>
                <span class="font-bold text-gray-900">{{ parsedItems.length }} Program Pelatihan Terdeteksi</span>
              </div>
              <span class="font-mono font-bold text-xs text-[#0067AC]">
                Total: {{ formatRupiah(parsedItemsTotal) }}
              </span>
            </div>

            <!-- Mode selector: Replace or Append -->
            <div class="grid grid-cols-2 gap-2 bg-gray-100 p-1 rounded-xl text-xs font-semibold">
              <button
                type="button"
                @click="importMode = 'replace'"
                class="py-1.5 px-3 rounded-lg transition text-center cursor-pointer"
                :class="importMode === 'replace' ? 'bg-white text-[#0067AC] shadow-2xs font-bold' : 'text-gray-500 hover:text-gray-800'"
              >
                Ganti Seluruh Tabel (Replace)
              </button>
              <button
                type="button"
                @click="importMode = 'append'"
                class="py-1.5 px-3 rounded-lg transition text-center cursor-pointer"
                :class="importMode === 'append' ? 'bg-white text-[#0067AC] shadow-2xs font-bold' : 'text-gray-500 hover:text-gray-800'"
              >
                Tambahkan ke Tabel (Append)
              </button>
            </div>

            <!-- Preview Table -->
            <div class="overflow-x-auto rounded-xl border border-gray-200 max-h-48 overflow-y-auto">
              <table class="w-full text-left border-collapse text-[11px]">
                <thead class="sticky top-0 bg-[#F8FAFC] border-b border-gray-200 text-gray-500 font-bold uppercase tracking-wider text-[10px]">
                  <tr>
                    <th class="py-2 px-3 w-10 text-center">No</th>
                    <th class="py-2 px-3">Kategori</th>
                    <th class="py-2 px-3">Program Pelatihan</th>
                    <th class="py-2 px-3 text-right">Pagu</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="(p, pIdx) in parsedItems" :key="pIdx" class="hover:bg-blue-50/30">
                    <td class="py-2 px-3 text-center text-gray-400 font-mono">{{ pIdx + 1 }}</td>
                    <td class="py-2 px-3 text-gray-700 font-medium truncate max-w-[140px]">{{ p.category_name }}</td>
                    <td class="py-2 px-3 text-gray-900 font-medium">{{ p.recommended_program }}</td>
                    <td class="py-2 px-3 text-right font-mono text-gray-800 font-semibold">{{ formatRupiah(p.budget) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
          <button
            type="button"
            @click="showImportModal = false"
            class="px-4 py-2 rounded-xl text-gray-600 hover:bg-gray-200/60 font-bold text-xs transition cursor-pointer"
          >
            Batal
          </button>

          <button
            type="button"
            @click="applyImportedItems"
            :disabled="parsedItems.length === 0"
            class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white font-bold text-xs shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Terapkan ke Tabel TNA ({{ parsedItems.length }} Program)</span>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>
