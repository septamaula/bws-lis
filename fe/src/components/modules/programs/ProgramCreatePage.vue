<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
  GraduationCap,
  Clock,
  Compass,
  Award,
  Video,
  MapPin,
  HelpCircle,
  ShieldCheck,
  Check,
  UserCheck
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  editProgramId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['back', 'created'])
const auth = useAuthStore()

const isEditMode = computed(() => !!props.editProgramId)
const editingProgram = ref(null)
const loadingInit = ref(true)
const isSubmitting = ref(false)
const feedback = ref({ type: '', message: '' })

// Master / Reference Data State
const tnaProposals = ref([])
const categories = ref([])
const fiscalYears = ref([])

// Form State
const form = ref({
  title: '',
  category: 'Operasional & Layanan Perbankan',
  tna_proposal_id: '',
  tna_item_id: '',
  department: auth.user?.department || 'Information Technology',
  year: 2026,
  execution_quarter: 'Q2',
  target_audience: 'Officer & Senior Staff Operasional Cabang & Kantor Pusat',
  target_participants_count: 25,
  participant_prerequisites: 'Masa kerja minimum 1 tahun di Bank Woori Saudara dan memiliki pemahaman proses bisnis perbankan.',
  method: 'Hybrid', // In-Class, Virtual, Hybrid, E-Learning
  location: 'BWS Learning Center - R. Diklat 101',
  duration_hours: 16,
  passing_grade: 75,
  is_mandatory: false,
  description: '',
  approval_type: 'WORKFLOW', // 'DOCUMENT' or 'WORKFLOW'
  approval_doc_number: '',
  approval_doc_date: '',
  approval_doc_signer: 'Kepala Divisi Human Capital',
  budget_items: [],
  participants: []
})

// File Attachment State for Document Approval
const selectedDocFile = ref(null)
const docFileInputRef = ref(null)
const existingDocFile = ref(null)

const budgetItemTypes = [
  { value: 'SPEAKER', label: 'Honorarium Pembicara / Instruktur', badgeColor: '#0067AC' },
  { value: 'TRANSPORT', label: 'Transportasi & Perjalanan Dinas', badgeColor: '#003876' },
  { value: 'ACCOMMODATION', label: 'Akomodasi / Penginapan Hotel', badgeColor: '#4F46E5' },
  { value: 'LUNCH', label: 'Konsumsi: Makan Siang (Lunch)', badgeColor: '#059669' },
  { value: 'SNACK', label: 'Konsumsi: Snack & Coffee Break', badgeColor: '#D97706' },
  { value: 'MATERIALS', label: 'Modul, ATK & Training Kit', badgeColor: '#7C3AED' },
  { value: 'VENUE', label: 'Sewa Ruangan / Hall & Audio Visual', badgeColor: '#0284C7' },
  { value: 'CERTIFICATION', label: 'Biaya Ujian & Sertifikasi Resmi', badgeColor: '#DC2626' },
  { value: 'OTHER', label: 'Dana Taktis / Kontinjensi / Lainnya', badgeColor: '#4B5563' },
]

const quarters = ['Q1', 'Q2', 'Q3', 'Q4', 'Q1 - Q2', 'Q3 - Q4', 'Fleksibel']
const trainingMethods = [
  { value: 'Hybrid', label: 'Hybrid Learning (Tatap Muka & Virtual)', desc: 'Kombinasi kelas offline BWS Learning Center dan Zoom/Teams' },
  { value: 'In-Class', label: 'In-Class (Tatap Muka Penuh)', desc: 'Pelatihan tatap muka langsung di ruang kelas atau hotel' },
  { value: 'Virtual', label: 'Virtual Online Penuh', desc: 'Sesi webinar interaktif via video conference BWS LIS' },
  { value: 'E-Learning', label: 'E-Learning Self-Paced', desc: 'Pembelajaran mandiri berbasis modul video & kuis portal LIS' },
]

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

// Computeds for Selected TNA
const selectedTna = computed(() => {
  if (!form.value.tna_proposal_id) return null
  return tnaProposals.value.find(p => p.id === Number(form.value.tna_proposal_id)) || null
})

const availableTnaItems = computed(() => {
  return selectedTna.value?.items || []
})

const selectedTnaItem = computed(() => {
  if (!form.value.tna_item_id) return null
  return availableTnaItems.value.find(i => i.id === Number(form.value.tna_item_id)) || null
})

// Live Budget Computations
const totalBudgetCalculated = computed(() => {
  return form.value.budget_items.reduce((sum, item) => {
    const q = Number(item.quantity) || 0
    const p = Number(item.unit_price) || 0
    return sum + (q * p)
  }, 0)
})

const costPerParticipant = computed(() => {
  const pax = Number(form.value.target_participants_count) || 1
  return pax > 0 ? Math.round(totalBudgetCalculated.value / pax) : 0
})

// Watch TNA selection to auto-fill suggestions
watch(() => form.value.tna_proposal_id, (newId) => {
  if (!newId) {
    form.value.tna_item_id = ''
    return
  }
  const tna = tnaProposals.value.find(p => p.id === Number(newId))
  if (tna) {
    form.value.department = tna.department || form.value.department
    form.value.year = tna.year || form.value.year
    if (tna.items && tna.items.length > 0 && !form.value.tna_item_id) {
      form.value.tna_item_id = tna.items[0].id
      onTnaItemChanged(tna.items[0].id)
    }
  }
})

function onTnaItemChanged(itemId) {
  const item = availableTnaItems.value.find(i => i.id === Number(itemId))
  if (item) {
    if (!form.value.title || form.value.title === '') {
      form.value.title = item.sub_category_name || item.recommended_program || ''
    }
    form.value.category = item.category_name || item.job_family || form.value.category
    form.value.execution_quarter = item.execution_quarter || form.value.execution_quarter
    form.value.target_participants_count = item.target_participants_count || form.value.target_participants_count
    if (item.target_audience) {
      form.value.target_audience = item.target_audience
    }
  }
}

// Budget Table Row Management
function addBudgetItemRow() {
  form.value.budget_items.push({
    item_type: 'SPEAKER',
    description: '',
    quantity: 1,
    unit: 'Paket',
    unit_price: 10000000,
    notes: ''
  })
}

function removeBudgetItemRow(index) {
  if (form.value.budget_items.length <= 1) {
    feedback.value = { type: 'error', message: 'Minimal harus terdapat 1 komponen biaya pada RAB.' }
    return
  }
  form.value.budget_items.splice(index, 1)
}

function populateStandardRabTemplate() {
  const pax = Number(form.value.target_participants_count) || 25
  form.value.budget_items = [
    {
      item_type: 'SPEAKER',
      description: 'Honorarium Narasumber Eksternal / Praktisi Senior Perbankan (2 Hari)',
      quantity: 2,
      unit: 'Hari',
      unit_price: 8000000,
      notes: 'Sertifikasi kompetensi resmi'
    },
    {
      item_type: 'TRANSPORT',
      description: 'Transportasi & Tiket Penerbangan / Taksi Bandara Instruktur',
      quantity: 1,
      unit: 'Paket',
      unit_price: 6500000,
      notes: 'PP Jakarta - Lokasi Pelatihan'
    },
    {
      item_type: 'LUNCH',
      description: `Makan Siang Prasmanan / Bento Box Peserta & Panitia (${pax} Orang x 2 Hari)`,
      quantity: pax * 2,
      unit: 'Pax',
      unit_price: 85000,
      notes: 'Menu higienis standar korporat'
    },
    {
      item_type: 'SNACK',
      description: `Coffee Break Pagi & Sore (${pax} Orang x 4 Sesi)`,
      quantity: pax * 4,
      unit: 'Pax',
      unit_price: 45000,
      notes: 'Termasuk aneka kue & coffee/tea'
    },
    {
      item_type: 'MATERIALS',
      description: `Modul Hardcopy, Binder, ATK Eksklusif & Training Kit BWS (${pax} Paket)`,
      quantity: pax,
      unit: 'Paket',
      unit_price: 350000,
      notes: 'Lanyard, blocknote & tas'
    },
    {
      item_type: 'CERTIFICATION',
      description: `Voucher Ujian Resmi Lembaga Sertifikasi Kompetensi (${pax} Peserta)`,
      quantity: pax,
      unit: 'Peserta',
      unit_price: 1250000,
      notes: 'Sertifikat kelulusan standar OJK'
    },
    {
      item_type: 'OTHER',
      description: 'Dana Taktis & Kontinjensi Operasional Kelas (5%)',
      quantity: 1,
      unit: 'Paket',
      unit_price: 4500000,
      notes: 'Biaya darurat / tak terduga'
    }
  ]

  feedback.value = {
    type: 'success',
    message: `Template RAB standar BWS LIS berhasil dimuat (${form.value.budget_items.length} komponen biaya terstruktur).`
  }
}

// Participants Management
function addParticipantRow() {
  form.value.participants.push({
    nik: '',
    name: '',
    department: form.value.department,
    branch: 'Kantor Cabang Utama BWS',
    position: 'Staff / Officer',
    email: ''
  })
}

function removeParticipantRow(index) {
  form.value.participants.splice(index, 1)
}

function populateSampleParticipants() {
  form.value.participants = [
    { nik: 'BWS-2024-010', name: 'Rangga Prawira', department: form.value.department, branch: 'Kantor Pusat BWS - Sudirman', position: 'Senior Officer', email: 'rangga.p@bankwoorisaudara.com' },
    { nik: 'BWS-2024-011', name: 'Dewi Lestari', department: form.value.department, branch: 'KC Bandung Asia Afrika', position: 'Assistant Manager', email: 'dewi.l@bankwoorisaudara.com' },
    { nik: 'BWS-2024-012', name: 'Fikri Haikal', department: form.value.department, branch: 'KC Surabaya Darmo', position: 'Credit Analyst Officer', email: 'fikri.h@bankwoorisaudara.com' },
    { nik: 'BWS-2024-013', name: 'Nadia Safitri', department: form.value.department, branch: 'KC Medan Putri Hijau', position: 'Compliance Specialist', email: 'nadia.s@bankwoorisaudara.com' },
    { nik: 'BWS-2024-014', name: 'Budi Santoso', department: form.value.department, branch: 'KC Semarang Pandanaran', position: 'Operations Supervisor', email: 'budi.s@bankwoorisaudara.com' },
  ]
}

// File Attachment for Approval by Document
function onDocFileSelected(event) {
  const file = event.target.files[0]
  if (!file) return
  if (file.size > 15 * 1024 * 1024) {
    feedback.value = { type: 'error', message: 'Ukuran berkas dokumen maksimal adalah 15 MB.' }
    return
  }
  selectedDocFile.value = file
  feedback.value = { type: '', message: '' }
}

function removeDocAttachment() {
  selectedDocFile.value = null
  if (docFileInputRef.value) {
    docFileInputRef.value.value = ''
  }
}

// Load References on Mount
async function loadReferences() {
  loadingInit.value = true
  try {
    const [resTna, resCats, resYears] = await Promise.all([
      apiClient.get('/tna?year=ALL'),
      apiClient.get('/categories'),
      apiClient.get('/fiscal-years')
    ])

    tnaProposals.value = resTna.data.proposals || []
    categories.value = resCats.data.categories || []
    fiscalYears.value = resYears.data.years || []

    if (isEditMode.value) {
      const { data } = await apiClient.get(`/programs/${props.editProgramId}`)
      const p = data.program
      editingProgram.value = p

      form.value.title = p.title || ''
      form.value.category = p.category || 'Operasional & Layanan Perbankan'
      form.value.tna_proposal_id = p.tna_proposal_id || ''
      form.value.tna_item_id = p.tna_item_id || ''
      form.value.department = p.department || auth.user?.department || 'Information Technology'
      form.value.year = p.year || 2026
      form.value.execution_quarter = p.execution_quarter || 'Q1'
      form.value.target_audience = p.target_audience || ''
      form.value.target_participants_count = p.target_participants_count || 20
      form.value.participant_prerequisites = p.participant_prerequisites || ''
      form.value.method = p.method || 'Hybrid'
      form.value.location = p.location || 'BWS Learning Center'
      form.value.duration_hours = p.duration_hours || 16
      form.value.passing_grade = p.passing_grade || 75
      form.value.is_mandatory = !!p.is_mandatory
      form.value.description = p.description || ''
      form.value.approval_type = p.approval_type || 'WORKFLOW'
      form.value.approval_doc_number = p.approval_doc_number || ''
      form.value.approval_doc_date = p.approval_doc_date ? p.approval_doc_date.substring(0, 10) : ''
      form.value.approval_doc_signer = p.approval_doc_signer || ''

      if (p.approval_doc_name) {
        existingDocFile.value = {
          name: p.approval_doc_name,
          size: p.approval_doc_size,
          path: p.approval_doc_path
        }
      }

      if (p.budget_items && p.budget_items.length > 0) {
        form.value.budget_items = p.budget_items.map(b => ({
          item_type: b.item_type || 'OTHER',
          description: b.description || '',
          quantity: Number(b.quantity) || 1,
          unit: b.unit || 'Paket',
          unit_price: Number(b.unit_price) || 0,
          notes: b.notes || ''
        }))
      } else {
        populateStandardRabTemplate()
      }

      if (p.participants && p.participants.length > 0) {
        form.value.participants = p.participants.map(part => ({
          nik: part.nik || '',
          name: part.name || '',
          department: part.department || '',
          branch: part.branch || '',
          position: part.position || '',
          email: part.email || ''
        }))
      }
    } else {
      if (tnaProposals.value.length > 0) {
        const approvedTna = tnaProposals.value.find(t => t.status === 'APPROVED') || tnaProposals.value[0]
        form.value.tna_proposal_id = approvedTna.id
        form.value.department = approvedTna.department
        if (approvedTna.items && approvedTna.items.length > 0) {
          form.value.tna_item_id = approvedTna.items[0].id
          onTnaItemChanged(approvedTna.items[0].id)
        }
      }
      populateStandardRabTemplate()
      populateSampleParticipants()
    }
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: 'Gagal memuat master data atau referensi TNA. Pastikan koneksi backend aktif.'
    }
  } finally {
    loadingInit.value = false
  }
}

onMounted(() => {
  loadReferences()
})

// Submission Handler
async function submitForm(isSubmit) {
  if (!form.value.title.trim()) {
    feedback.value = { type: 'error', message: 'Nama Program Pelatihan wajib diisi.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }

  if (form.value.approval_type === 'DOCUMENT' && isSubmit) {
    if (!form.value.approval_doc_number.trim() || !form.value.approval_doc_signer.trim()) {
      feedback.value = { type: 'error', message: 'Nomor Dokumen/Memo dan Pejabat Pengesah wajib diisi untuk Approval by Document.' }
      window.scrollTo({ top: 0, behavior: 'smooth' })
      return
    }
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const formData = new FormData()
    formData.append('title', form.value.title)
    formData.append('category', form.value.category)
    if (form.value.tna_proposal_id) formData.append('tna_proposal_id', form.value.tna_proposal_id)
    if (form.value.tna_item_id) formData.append('tna_item_id', form.value.tna_item_id)
    formData.append('department', form.value.department)
    formData.append('year', form.value.year)
    formData.append('execution_quarter', form.value.execution_quarter)
    formData.append('target_audience', form.value.target_audience)
    formData.append('target_participants_count', form.value.target_participants_count || 20)
    formData.append('participant_prerequisites', form.value.participant_prerequisites)
    formData.append('method', form.value.method)
    formData.append('location', form.value.location)
    formData.append('duration_hours', form.value.duration_hours || 16)
    formData.append('passing_grade', form.value.passing_grade || 75)
    formData.append('is_mandatory', form.value.is_mandatory ? '1' : '0')
    formData.append('description', form.value.description)
    formData.append('approval_type', form.value.approval_type)
    formData.append('approval_doc_number', form.value.approval_doc_number)
    if (form.value.approval_doc_date) formData.append('approval_doc_date', form.value.approval_doc_date)
    formData.append('approval_doc_signer', form.value.approval_doc_signer)
    formData.append('is_submit', isSubmit ? '1' : '0')

    if (selectedDocFile.value) {
      formData.append('document', selectedDocFile.value)
      formData.append('approval_doc_name', selectedDocFile.value.name)
      formData.append('approval_doc_size', selectedDocFile.value.size)
    } else if (existingDocFile.value) {
      formData.append('approval_doc_name', existingDocFile.value.name)
      formData.append('approval_doc_size', existingDocFile.value.size)
    }

    form.value.budget_items.forEach((item, index) => {
      formData.append(`budget_items[${index}][item_type]`, item.item_type)
      formData.append(`budget_items[${index}][description]`, item.description)
      formData.append(`budget_items[${index}][quantity]`, item.quantity)
      formData.append(`budget_items[${index}][unit]`, item.unit)
      formData.append(`budget_items[${index}][unit_price]`, item.unit_price)
      const sub = (Number(item.quantity) || 0) * (Number(item.unit_price) || 0)
      formData.append(`budget_items[${index}][subtotal]`, sub)
      formData.append(`budget_items[${index}][notes]`, item.notes || '')
    })

    form.value.participants.forEach((p, index) => {
      if (p.name && p.name.trim()) {
        formData.append(`participants[${index}][nik]`, p.nik || '')
        formData.append(`participants[${index}][name]`, p.name)
        formData.append(`participants[${index}][department]`, p.department || form.value.department)
        formData.append(`participants[${index}][branch]`, p.branch || '')
        formData.append(`participants[${index}][position]`, p.position || '')
        formData.append(`participants[${index}][email]`, p.email || '')
      }
    })

    const targetUrl = isEditMode.value ? `/programs/${props.editProgramId}` : '/programs'
    const { data } = await apiClient.post(targetUrl, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    const code = data.program?.program_code || 'PRG'
    let msg = ''
    if (isEditMode.value) {
      msg = isSubmit
        ? `Program kerja (${code}) berhasil diperbarui dan diajukan ke otorisasi.`
        : `Draft program kerja (${code}) berhasil disimpan.`
    } else {
      msg = isSubmit
        ? (form.value.approval_type === 'DOCUMENT'
            ? `Program kerja (${code}) resmi disahkan via Dokumen Pendukung.`
            : `Program kerja (${code}) berhasil diajukan ke Alur Multi-Approval (Step 1 HCBP).`)
        : `DRAFT Program kerja (${code}) berhasil disimpan.`
    }

    emit('created', msg)
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menyimpan program kerja pelatihan.'
    }
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-6xl mx-auto pb-28">

    <!-- Top Action Navigation Bar -->
    <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-4">
      <div class="flex items-center gap-3">
        <button
          @click="emit('back')"
          class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center text-gray-700 shadow-2xs border border-gray-200 transition cursor-pointer"
          :title="isEditMode ? 'Kembali ke Rincian' : 'Kembali ke Daftar Program'"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span
              class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
              :class="isEditMode ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-[#EBF4FC] text-[#0067AC]'"
            >
              {{ isEditMode ? 'Mode Perbaikan Program' : 'Program Kerja Mandiri' }}
            </span>
            <span class="text-xs text-gray-400">• Standar Pelatihan Bank BWS & OJK</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-extrabold text-[#002855]">
            {{ isEditMode ? `Perbaiki Program Kerja — ${editingProgram?.program_code || ''}` : 'Penyusunan Program Kerja & Rencana Anggaran (RAB) Diklat' }}
          </h1>
        </div>
      </div>

      <button
        @click="emit('back')"
        class="text-xs font-semibold text-gray-500 hover:text-[#0067AC] transition flex items-center gap-1 cursor-pointer"
      >
        <span>Batal & Kembali</span>
      </button>
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
        <button @click="feedback.message = ''" class="font-bold text-sm cursor-pointer">&times;</button>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 1: KAITAN USULAN TNA (1 TNA -> BANYAK PROGRAM)         -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div class="flex items-center gap-2">
          <Compass class="w-4 h-4 text-[#0067AC]" />
          <h2 class="text-sm font-bold text-gray-900">1. Kaitan Dokumen Usulan TNA (1 TNA &rarr; Banyak Program)</h2>
        </div>
        <span class="text-[11px] font-semibold text-[#0067AC] bg-[#EBF4FC] px-2.5 py-1 rounded-full">
          Integrasi Rencana Induk BWS
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        
        <!-- Dropdown Pilih Dokumen TNA -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700 flex items-center justify-between">
            <span>Pilih Dokumen TNA Tahunan Acuan *</span>
            <span class="text-[10px] text-gray-400">1 TNA mencakup banyak program</span>
          </label>
          <select
            v-model="form.tna_proposal_id"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold"
          >
            <option value="">-- Tanpa Kaitan TNA (Program Insidental) --</option>
            <option v-for="tna in tnaProposals" :key="tna.id" :value="tna.id">
              {{ tna.proposal_code }} — {{ tna.department }} (Tahun {{ tna.year }} - {{ tna.status }})
            </option>
          </select>
        </div>

        <!-- Dropdown Pilih Kebutuhan Diklat Spesifik TNA -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700 flex items-center justify-between">
            <span>Spesifikasi Mata Diklat TNA (Sub-Kategori)</span>
            <span class="text-[10px] text-[#0067AC] font-semibold" v-if="availableTnaItems.length > 0">
              {{ availableTnaItems.length }} Mata Diklat Tersedia
            </span>
          </label>
          <select
            v-model="form.tna_item_id"
            @change="onTnaItemChanged(form.tna_item_id)"
            :disabled="!form.tna_proposal_id || availableTnaItems.length === 0"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold disabled:opacity-50"
          >
            <option value="">-- Pilih Mata Diklat yang Diturunkan --</option>
            <option v-for="item in availableTnaItems" :key="item.id" :value="item.id">
              {{ item.sub_category_name || item.recommended_program }} [{{ item.execution_quarter || 'Q1' }} - {{ formatRupiah(item.budget) }}]
            </option>
          </select>
        </div>

      </div>

      <!-- Live TNA Context Preview Card -->
      <div v-if="selectedTna" class="p-4 rounded-2xl bg-[#EBF4FC]/50 border border-blue-200/80 text-xs space-y-2">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 border-b border-blue-100/80 pb-2">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#0067AC] animate-pulse"></span>
            <span class="font-bold text-[#002855]">Rujukan TNA: {{ selectedTna.proposal_code }}</span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-[#0067AC] font-bold">Status: {{ selectedTna.status }}</span>
          </div>
          <span class="font-mono text-xs font-bold text-[#0067AC]">
            Alokasi Pagu TNA Divisi: {{ formatRupiah(selectedTna.total_budget_requested) }}
          </span>
        </div>
        <p class="text-gray-600 text-[11px] leading-relaxed">
          <strong class="text-[#002855]">Sasaran Strategis TNA:</strong> {{ selectedTna.strategic_objective }}
        </p>
        <div v-if="selectedTnaItem" class="pt-1.5 flex items-center gap-3 text-[11px] font-semibold text-gray-700">
          <span>Kebutuhan: <span class="text-[#0067AC] font-bold">{{ selectedTnaItem.sub_category_name }}</span></span>
          <span>• Kuartal: <span class="text-[#002855] font-bold">{{ selectedTnaItem.execution_quarter }}</span></span>
          <span>• Pagu Item: <span class="text-emerald-700 font-mono font-bold">{{ formatRupiah(selectedTnaItem.budget) }}</span></span>
        </div>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 2: INFORMASI DASAR & SPESIFIKASI PROGRAM               -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
      <div class="flex items-center gap-2 border-b border-gray-100 pb-3">
        <BookOpen class="w-4 h-4 text-[#0067AC]" />
        <h2 class="text-sm font-bold text-gray-900">2. Informasi Dasar & Kurikulum Program Pelatihan</h2>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
        
        <!-- Judul Program -->
        <div class="sm:col-span-2 space-y-1.5">
          <label class="block font-bold text-gray-700">Nama / Judul Program Pelatihan *</label>
          <input
            type="text"
            v-model="form.title"
            required
            placeholder="Contoh: Comprehensive Commercial Credit Analysis & Risk Mitigation 2026"
            class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold"
          />
        </div>

        <!-- Kategori Diklat -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Kategori Diklat *</label>
          <select
            v-model="form.category"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold"
          >
            <option v-for="c in categories" :key="c.id" :value="c.name">{{ c.name }}</option>
            <option value="Operasional & Layanan Perbankan">Operasional & Layanan Perbankan</option>
            <option value="Kredit & Pembiayaan Komersial">Kredit & Pembiayaan Komersial</option>
            <option value="Manajemen Risiko, Kepatuhan & Regulasi OJK">Manajemen Risiko, Kepatuhan & Regulasi OJK</option>
            <option value="Teknologi Informasi & Keamanan Siber">Teknologi Informasi & Keamanan Siber</option>
            <option value="Kepemimpinan, Manajerial & Budaya Kerja">Kepemimpinan, Manajerial & Budaya Kerja</option>
          </select>
        </div>

        <!-- Metode Pelatihan -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Metode Penyelenggaraan *</label>
          <select
            v-model="form.method"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold"
          >
            <option v-for="m in trainingMethods" :key="m.value" :value="m.value">
              {{ m.label }}
            </option>
          </select>
        </div>

        <!-- Durasi & Kuartal -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Durasi Pelatihan (Jam)</label>
          <div class="relative">
            <input
              type="number"
              v-model.number="form.duration_hours"
              min="1"
              max="200"
              class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold"
            />
            <span class="absolute right-3.5 top-2.5 text-gray-400 font-medium">Jam Pelajaran</span>
          </div>
        </div>

        <!-- Target Kuartal Eksekusi -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Target Kuartal Pelaksanaan</label>
          <select
            v-model="form.execution_quarter"
            class="w-full bg-[#F8FAFC] rounded-xl px-3 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold"
          >
            <option v-for="q in quarters" :key="q" :value="q">{{ q }}</option>
          </select>
        </div>

        <!-- Lokasi / Ruang Kelas -->
        <div class="sm:col-span-2 space-y-1.5">
          <label class="block font-bold text-gray-700">Lokasi / Fasilitas Pelatihan *</label>
          <input
            type="text"
            v-model="form.location"
            placeholder="Contoh: BWS Learning Center - R. Diklat 101 atau Hotel Grand Mercure Jakarta"
            class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-medium"
          />
        </div>

        <!-- Passing Grade Kelulusan -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Passing Grade Minimum (%)</label>
          <div class="relative">
            <input
              type="number"
              v-model.number="form.passing_grade"
              min="50"
              max="100"
              class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-semibold font-mono"
            />
            <span class="absolute right-3.5 top-2.5 text-gray-400 font-medium">% Nilai Kelulusan</span>
          </div>
        </div>

        <!-- Sifat Diklat Mandatori OJK -->
        <div class="sm:col-span-3 pt-1">
          <label class="flex items-center gap-2.5 cursor-pointer">
            <input
              type="checkbox"
              v-model="form.is_mandatory"
              class="w-4 h-4 text-[#0067AC] rounded border-gray-300 focus:ring-[#0067AC]"
            />
            <span class="font-bold text-gray-800 text-xs">
              Program Wajib Regulasi Otoritas Jasa Keuangan (OJK) / Bank Indonesia (BI)
            </span>
          </label>
        </div>

        <!-- Deskripsi & Silabus -->
        <div class="sm:col-span-3 space-y-1.5">
          <label class="block font-bold text-gray-700">Deskripsi Silabus & Output Kompetensi *</label>
          <textarea
            rows="3"
            v-model="form.description"
            placeholder="Uraikan garis besar materi, studi kasus perbankan, dan standar kompetensi yang diharapkan..."
            class="w-full bg-[#F8FAFC] rounded-xl p-3 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition"
          ></textarea>
        </div>

      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 3: INFORMASI & TARGET PESERTA DIDIK                     -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div class="flex items-center gap-2">
          <Users class="w-4 h-4 text-[#0067AC]" />
          <h2 class="text-sm font-bold text-gray-900">3. Informasi & Target Peserta Didik</h2>
        </div>
        <span class="text-[11px] font-bold text-gray-500 font-mono">
          Kuota: {{ form.target_participants_count }} Pegawai BWS
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
        
        <!-- Target Kuota Peserta -->
        <div class="space-y-1.5">
          <label class="block font-bold text-gray-700">Target Kuota Peserta (Pegawai) *</label>
          <input
            type="number"
            v-model.number="form.target_participants_count"
            min="1"
            class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-bold font-mono"
          />
        </div>

        <!-- Target Audience / Level Jabatan -->
        <div class="sm:col-span-2 space-y-1.5">
          <label class="block font-bold text-gray-700">Target Level Jabatan & Kategori Peserta *</label>
          <input
            type="text"
            v-model="form.target_audience"
            placeholder="Contoh: Staff, Officer, Assistant Manager, Relationship Manager Cabang"
            class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-medium"
          />
        </div>

        <!-- Prasyarat Peserta -->
        <div class="sm:col-span-3 space-y-1.5">
          <label class="block font-bold text-gray-700">Kriteria & Prasyarat Keikutsertaan Peserta</label>
          <input
            type="text"
            v-model="form.participant_prerequisites"
            placeholder="Contoh: Lulus evaluasi dasar operasional dan memiliki rekomendasi dari Pemimpin Cabang"
            class="w-full bg-[#F8FAFC] rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none transition font-medium"
          />
        </div>

      </div>

      <!-- Tabel Daftar Nominasi Peserta (Multiple Add) -->
      <div class="space-y-3 pt-2">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-xs font-bold text-gray-900">Daftar Nominasi Pegawai Terdaftar (Opsional / Awal)</h3>
            <p class="text-[11px] text-gray-400">Dapat diisi saat penyusunan program atau dilengkapi saat batch kelas dibuka.</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="populateSampleParticipants"
              class="px-3 py-1.5 rounded-full bg-blue-50 text-[#0067AC] hover:bg-blue-100 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
            >
              <UserCheck class="w-3.5 h-3.5" />
              <span>+ Muat Contoh Karyawan BWS</span>
            </button>
            <button
              type="button"
              @click="addParticipantRow"
              class="px-3 py-1.5 rounded-full bg-[#0067AC] text-white hover:bg-[#00528c] text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>+ Tambah Peserta</span>
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
              <tr>
                <th class="py-2.5 px-3 text-center w-12">No</th>
                <th class="py-2.5 px-3 w-36">NIK Pegawai</th>
                <th class="py-2.5 px-3">Nama Lengkap</th>
                <th class="py-2.5 px-3">Unit / Kantor Cabang</th>
                <th class="py-2.5 px-3">Jabatan</th>
                <th class="py-2.5 px-2 text-center w-12">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(p, idx) in form.participants" :key="idx" class="hover:bg-gray-50/50">
                <td class="py-2 px-3 text-center font-bold text-gray-400 font-mono">{{ idx + 1 }}</td>
                <td class="py-2 px-3">
                  <input
                    type="text"
                    v-model="p.nik"
                    placeholder="BWS-2024-xxx"
                    class="w-full bg-white rounded-lg px-2.5 py-1.5 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none font-mono"
                  />
                </td>
                <td class="py-2 px-3">
                  <input
                    type="text"
                    v-model="p.name"
                    placeholder="Nama Pegawai"
                    class="w-full bg-white rounded-lg px-2.5 py-1.5 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none font-semibold text-gray-900"
                  />
                </td>
                <td class="py-2 px-3">
                  <input
                    type="text"
                    v-model="p.branch"
                    placeholder="Cabang / Unit Kerja"
                    class="w-full bg-white rounded-lg px-2.5 py-1.5 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none text-gray-700"
                  />
                </td>
                <td class="py-2 px-3">
                  <input
                    type="text"
                    v-model="p.position"
                    placeholder="Jabatan"
                    class="w-full bg-white rounded-lg px-2.5 py-1.5 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none text-gray-700"
                  />
                </td>
                <td class="py-2 px-2 text-center">
                  <button
                    type="button"
                    @click="removeParticipantRow(idx)"
                    class="text-gray-400 hover:text-rose-600 p-1 rounded transition cursor-pointer"
                    title="Hapus Peserta"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="form.participants.length === 0">
                <td colspan="6" class="py-4 text-center text-gray-400 text-xs">
                  Belum ada peserta yang dinominasikan. Klik "+ Tambah Peserta" atau "+ Muat Contoh Karyawan BWS".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 4: RINCIAN ANGGARAN BIAYA (RAB) KOMPREHENSIF           -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
        <div>
          <div class="flex items-center gap-2">
            <Wallet class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">4. Rancangan Anggaran Biaya (RAB) Program Pelatihan</h2>
          </div>
          <p class="text-[11px] text-gray-400 mt-0.5">
            Breakdown komponen: Pembicara, Transportasi, Akomodasi, Makan Siang, Snack, Modul/ATK, Venue, Sertifikasi, dll.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="populateStandardRabTemplate"
            class="px-3.5 py-1.5 rounded-full bg-blue-50 hover:bg-blue-100 text-[#0067AC] font-bold text-xs transition flex items-center gap-1.5 cursor-pointer shadow-2xs border border-blue-200"
          >
            <Sparkles class="w-3.5 h-3.5" />
            <span>+ Muat Template RAB Standar BWS</span>
          </button>
          <button
            type="button"
            @click="addBudgetItemRow"
            class="px-3.5 py-1.5 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-bold text-xs transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>+ Tambah Komponen Biaya</span>
          </button>
        </div>
      </div>

      <!-- Tabel Multiple Add RAB -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left border-collapse">
          <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
            <tr>
              <th class="py-3 px-3 text-center w-10">No</th>
              <th class="py-3 px-3 w-56">Komponen / Peruntukan</th>
              <th class="py-3 px-3">Uraian / Deskripsi Rincian</th>
              <th class="py-3 px-2 text-center w-24">Vol (Qty)</th>
              <th class="py-3 px-2 text-center w-24">Satuan</th>
              <th class="py-3 px-3 text-right w-36">Harga Satuan (Rp)</th>
              <th class="py-3 px-3 text-right w-40">Subtotal Biaya (Rp)</th>
              <th class="py-3 px-2 text-center w-12">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, idx) in form.budget_items" :key="idx" class="hover:bg-gray-50/60 transition">
              <td class="py-2.5 px-3 text-center font-bold text-gray-400 font-mono">{{ idx + 1 }}</td>
              
              <!-- Komponen Type Dropdown -->
              <td class="py-2.5 px-3">
                <select
                  v-model="item.item_type"
                  class="w-full bg-white rounded-xl px-2.5 py-1.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none font-bold"
                >
                  <option v-for="t in budgetItemTypes" :key="t.value" :value="t.value">
                    {{ t.label }}
                  </option>
                </select>
              </td>

              <!-- Deskripsi Uraian -->
              <td class="py-2.5 px-3">
                <input
                  type="text"
                  v-model="item.description"
                  placeholder="Keterangan rincian peruntukan biaya..."
                  class="w-full bg-white rounded-xl px-2.5 py-1.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none font-medium"
                />
              </td>

              <!-- Quantity -->
              <td class="py-2.5 px-2 text-center">
                <input
                  type="number"
                  v-model.number="item.quantity"
                  min="0.1"
                  step="any"
                  class="w-full bg-white rounded-xl px-2 py-1.5 text-xs text-center font-bold text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none font-mono"
                />
              </td>

              <!-- Satuan -->
              <td class="py-2.5 px-2 text-center">
                <select
                  v-model="item.unit"
                  class="w-full bg-white rounded-xl px-1.5 py-1.5 text-xs text-center font-semibold text-gray-700 border border-gray-200 focus:border-[#0067AC] focus:outline-none"
                >
                  <option value="Pax">Pax</option>
                  <option value="Hari">Hari</option>
                  <option value="Orang">Orang</option>
                  <option value="Sesi">Sesi</option>
                  <option value="Paket">Paket</option>
                  <option value="Peserta">Peserta</option>
                  <option value="Bulan">Bulan</option>
                </select>
              </td>

              <!-- Unit Price -->
              <td class="py-2.5 px-3 text-right">
                <input
                  type="number"
                  v-model.number="item.unit_price"
                  min="0"
                  step="1000"
                  class="w-full bg-white rounded-xl px-2.5 py-1.5 text-xs text-right font-bold text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none font-mono"
                />
                <span class="text-[10px] text-gray-400 block font-mono mt-0.5">
                  {{ formatRupiah(item.unit_price) }}
                </span>
              </td>

              <!-- Subtotal (Calculated) -->
              <td class="py-2.5 px-3 text-right font-mono font-extrabold text-[#002855] text-xs">
                {{ formatRupiah((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)) }}
              </td>

              <!-- Delete Button -->
              <td class="py-2.5 px-2 text-center">
                <button
                  type="button"
                  @click="removeBudgetItemRow(idx)"
                  class="text-gray-400 hover:text-rose-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer"
                  title="Hapus Baris Biaya"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </td>
            </tr>
            <tr v-if="form.budget_items.length === 0">
              <td colspan="8" class="py-6 text-center text-gray-400">
                Belum ada rincian biaya. Klik "+ Muat Template RAB Standar BWS" untuk mengisi otomatis.
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-[#F8FAFC] border-t-2 border-gray-200 font-bold text-xs">
            <tr>
              <td colspan="5" class="py-3 px-4 text-right text-gray-700 font-extrabold">
                Total Akumulasi Anggaran Program Pelatihan (RAB):
              </td>
              <td colspan="2" class="py-3 px-4 text-right font-mono text-base font-black text-[#0067AC]">
                {{ formatRupiah(totalBudgetCalculated) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Cost Summary KPI Strip -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-[#EBF4FC]/40 border border-blue-100 text-xs">
        <div>
          <span class="text-gray-400 font-medium block">Total RAB Program:</span>
          <span class="font-extrabold text-[#002855] text-sm font-mono">{{ formatRupiah(totalBudgetCalculated) }}</span>
        </div>
        <div>
          <span class="text-gray-400 font-medium block">Estimasi Biaya per Peserta (Cost per Pax):</span>
          <span class="font-extrabold text-[#0067AC] text-sm font-mono">
            {{ formatRupiah(costPerParticipant) }} / Pegawai
          </span>
        </div>
        <div>
          <span class="text-gray-400 font-medium block">Alokasi Pagu TNA Terhubung:</span>
          <span class="font-extrabold text-emerald-800 text-sm font-mono">
            {{ selectedTnaItem ? formatRupiah(selectedTnaItem.budget) : (selectedTna ? formatRupiah(selectedTna.total_budget_requested) : 'Tidak terhubung TNA') }}
          </span>
        </div>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- BAGIAN 5: METODE PENGESAHAN / OTORISASI (DUAL-APPROVAL)       -->
    <!-- ============================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
      <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div class="flex items-center gap-2">
          <ShieldCheck class="w-4 h-4 text-[#0067AC]" />
          <h2 class="text-sm font-bold text-gray-900">5. Metode Pengesahan & Otorisasi Program Pelatihan</h2>
        </div>
        <span class="text-[11px] font-semibold text-gray-400">Pilih salah satu metode otorisasi</span>
      </div>

      <!-- 2 Pilihan Kartu Metode Approval -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Pilihan A: Approval by Document -->
        <label
          class="relative p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
          :class="form.approval_type === 'DOCUMENT' ? 'border-[#0067AC] bg-[#EBF4FC]/40 ring-2 ring-[#0067AC]/10' : 'border-gray-200 hover:border-gray-300 bg-white'"
        >
          <div class="flex items-start gap-3">
            <input
              type="radio"
              name="approval_type"
              value="DOCUMENT"
              v-model="form.approval_type"
              class="mt-1 text-[#0067AC] focus:ring-[#0067AC]"
            />
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-xs text-[#002855]">Approval via Dokumen Resmi</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Langsung APPROVED</span>
              </div>
              <p class="text-[11px] text-gray-500 mt-1">
                Persetujuan sudah tersedia secara offline berupa <strong>Memo Internal, Nota Dinas, atau Surat Keputusan (SK) Direksi</strong>. Cukup masukkan nomor surat dan lampirkan berkas fisik.
              </p>
            </div>
          </div>
          <div class="mt-3 pt-2 border-t border-gray-100 text-[11px] text-[#0067AC] font-semibold flex items-center gap-1">
            <Check class="w-3.5 h-3.5" />
            <span>Dokumen diunggah &amp; status program langsung disahkan</span>
          </div>
        </label>

        <!-- Pilihan B: Persetujuan Berjenjang (Multi-Approval Workflow) -->
        <label
          class="relative p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
          :class="form.approval_type === 'WORKFLOW' ? 'border-[#0067AC] bg-[#EBF4FC]/40 ring-2 ring-[#0067AC]/10' : 'border-gray-200 hover:border-gray-300 bg-white'"
        >
          <div class="flex items-start gap-3">
            <input
              type="radio"
              name="approval_type"
              value="WORKFLOW"
              v-model="form.approval_type"
              class="mt-1 text-[#0067AC] focus:ring-[#0067AC]"
            />
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-xs text-[#002855]">Persetujuan Berjenjang Online (Workflow)</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-[#0067AC] font-bold">Multi-Approval</span>
              </div>
              <p class="text-[11px] text-gray-500 mt-1">
                Mengikuti tahapan otorisasi berjenjang online di portal BWS LIS:
                <strong>Step 1 (HCBP) &rarr; Step 2 (HC Learning Head) &rarr; Step 3 (Kadiv HC)</strong>.
              </p>
            </div>
          </div>
          <div class="mt-3 pt-2 border-t border-gray-100 text-[11px] text-gray-500 font-semibold flex items-center gap-1">
            <Clock class="w-3.5 h-3.5 text-[#0067AC]" />
            <span>Mengalir berjenjang ke otorisator berwenang</span>
          </div>
        </label>

      </div>

      <!-- Detail Input jika Memilih Approval by Document -->
      <div v-if="form.approval_type === 'DOCUMENT'" class="p-5 rounded-2xl bg-gray-50/80 border border-gray-200 space-y-4">
        <div class="flex items-center gap-2 text-[#002855]">
          <FileCheck2 class="w-4 h-4 text-[#0067AC]" />
          <h3 class="text-xs font-bold uppercase tracking-wider">Identitas & Berkas Dokumen Persetujuan</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
          <div class="space-y-1">
            <label class="font-bold text-gray-700">Nomor Dokumen / Memo Pengesahan *</label>
            <input
              type="text"
              v-model="form.approval_doc_number"
              placeholder="Contoh: MEMO/HC/2026/088/BWS"
              class="w-full bg-white rounded-xl px-3 py-2 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none font-semibold font-mono"
            />
          </div>

          <div class="space-y-1">
            <label class="font-bold text-gray-700">Tanggal Pengesahan Dokumen *</label>
            <input
              type="date"
              v-model="form.approval_doc_date"
              class="w-full bg-white rounded-xl px-3 py-2 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none font-semibold"
            />
          </div>

          <div class="space-y-1">
            <label class="font-bold text-gray-700">Pejabat Penandatangan / Pengesah *</label>
            <input
              type="text"
              v-model="form.approval_doc_signer"
              placeholder="Contoh: Direktur Human Capital / Kadiv HC"
              class="w-full bg-white rounded-xl px-3 py-2 text-xs border border-gray-200 focus:border-[#0067AC] focus:outline-none font-semibold"
            />
          </div>
        </div>

        <!-- File Upload Dropzone Dokumen Persetujuan -->
        <div class="space-y-2 pt-1">
          <label class="font-bold text-gray-700 text-xs block">Unggah Berkas Scan Dokumen / SK Persetujuan (PDF / DOCX)</label>
          
          <!-- Existing File Indicator when Editing -->
          <div
            v-if="existingDocFile && !selectedDocFile"
            class="flex items-center justify-between p-3.5 rounded-2xl bg-[#EBF4FC] border border-blue-200"
          >
            <div class="flex items-center gap-2.5">
              <Paperclip class="w-4 h-4 text-[#0067AC]" />
              <div>
                <span class="text-[10px] font-bold uppercase text-gray-500 block">Lampiran Dokumen Pengesahan Tersimpan</span>
                <span class="text-xs font-bold text-gray-900">{{ existingDocFile.name }}</span>
                <span class="text-[10px] text-gray-400 font-mono ml-2">({{ formatFileSize(existingDocFile.size) }})</span>
              </div>
            </div>
            <span class="text-[11px] text-[#0067AC] font-semibold italic">Pilih file baru di bawah jika ingin mengganti</span>
          </div>

          <!-- File Upload Dropzone -->
          <div
            v-if="!selectedDocFile"
            @click="$refs.docFileInputRef.click()"
            class="border-2 border-dashed border-gray-200 hover:border-[#0067AC] bg-white hover:bg-blue-50/20 rounded-2xl p-5 text-center cursor-pointer transition group"
          >
            <input
              ref="docFileInputRef"
              type="file"
              class="hidden"
              accept=".pdf,.doc,.docx,.xls,.xlsx"
              @change="onDocFileSelected"
            />
            <div class="flex flex-col items-center gap-1.5">
              <div class="w-10 h-10 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center">
                <Paperclip class="w-5 h-5" />
              </div>
              <p class="text-xs font-bold text-gray-800">Klik untuk memilih berkas dokumen persetujuan</p>
              <p class="text-[10px] text-gray-400">Format PDF, DOCX (Maksimal 15 MB)</p>
            </div>
          </div>

          <!-- Attached File Preview -->
          <div
            v-else
            class="flex items-center justify-between p-3.5 rounded-2xl bg-[#EBF4FC] border border-blue-200"
          >
            <div class="flex items-center gap-2.5">
              <FileCheck2 class="w-5 h-5 text-[#0067AC]" />
              <div>
                <p class="text-xs font-bold text-[#002855]">{{ selectedDocFile.name }}</p>
                <p class="text-[10px] text-[#0067AC] font-medium">{{ formatFileSize(selectedDocFile.size) }} • Berkas Dokumen Siap Diunggah</p>
              </div>
            </div>
            <button
              type="button"
              @click="removeDocAttachment"
              class="text-gray-400 hover:text-rose-600 p-1.5 rounded-lg hover:bg-white transition cursor-pointer"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

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
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider block">Total Anggaran (RAB)</span>
            <span class="text-base font-black text-[#0067AC] font-mono">{{ formatRupiah(totalBudgetCalculated) }}</span>
          </div>
          <div class="h-6 w-px bg-gray-200"></div>
          <div>
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider block">Target Peserta</span>
            <span class="text-sm font-extrabold text-[#002855]">{{ form.target_participants_count }} Pegawai</span>
          </div>
          <div class="h-6 w-px bg-gray-200"></div>
          <div>
            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider block">Metode Pengesahan</span>
            <span class="text-xs font-bold text-gray-800">
              {{ form.approval_type === 'DOCUMENT' ? 'Dokumen Memo / SK' : 'Multi-Approval Online' }}
            </span>
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
            <span>Simpan DRAFT</span>
          </button>

          <button
            type="button"
            :disabled="isSubmitting"
            @click="submitForm(true)"
            class="flex-1 sm:flex-none px-6 py-2.5 rounded-full bg-[#002855] hover:bg-[#003876] text-white text-xs font-bold transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <Send class="w-3.5 h-3.5" />
            <span>
              {{ isSubmitting ? 'Memproses...' : (form.approval_type === 'DOCUMENT' ? 'Sahkan via Dokumen' : 'Ajukan ke Multi-Approval') }}
            </span>
          </button>
        </div>

      </div>
    </div>

  </div>
</template>
