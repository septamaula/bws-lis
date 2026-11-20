<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  Plus,
  Trash2,
  Calendar,
  Wallet,
  Users,
  Send,
  Save,
  AlertCircle,
  CheckCircle2,
  ChevronRight,
  FileUp,
  FileCheck2,
  Paperclip,
  X,
  FileText,
  Layers,
  MapPin,
  Clock,
  ShieldCheck,
  Building2,
  HelpCircle
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
const loadingInit = ref(true)
const isSubmitting = ref(false)
const feedback = ref({ type: '', message: '' })

// Master data
const tnaProposals = ref([])
const categories = ref([])

// Form state matching Excel wireframe
const form = ref({
  program_code: '',
  title: '',
  category: 'Operasional & Layanan Perbankan',
  location: 'Ruang 1 - BWS Learning Center',
  facilitator: 'PT Mitra Konsultan Perbankan',
  duration_text: '2 Hari (16 Jam)',
  start_date: '2026-03-10',
  end_date: '2026-03-11',
  program_type: 'In-House Training',
  unit_initiator: 'Divisi Operasional & Layanan',
  activity_type: 'Pelatihan',
  bi_category: 'Operasional Perbankan & Sistem Pembayaran',
  year: 2026,
  notes: '',
  approval_scenario: 'WITH_BUDGET', // 'WITHOUT_BUDGET' | 'WITH_BUDGET' | 'WITH_APPROVAL_DOC'

  // 10 Budget breakdown items
  budget_fee_facilitator: 15000000,
  budget_meals: 4500000,
  budget_hotel: 6000000,
  budget_meeting_package: 3500000,
  budget_transportation: 2000000,
  budget_business_trip: 0,
  budget_reward: 0,
  budget_material: 2500000,
  budget_internet: 1000000,
  budget_miscellaneous: 1500000,

  doc1_remarks: 'RAB & Kerangka Acuan Kerja (TOR)',
  approval_doc_signer: 'Learning & Development Div. Head'
})

// File uploads
const doc1File = ref(null)
const doc2File = ref(null)
const approvalDocFile = ref(null)
const existingDoc1 = ref(null)
const existingDoc2 = ref(null)

const doc1InputRef = ref(null)
const doc2InputRef = ref(null)
const approvalDocInputRef = ref(null)

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

// 10 Budget Total Calculation
const totalBudgetCalculated = computed(() => {
  const b = form.value
  return (
    Number(b.budget_fee_facilitator || 0) +
    Number(b.budget_meals || 0) +
    Number(b.budget_hotel || 0) +
    Number(b.budget_meeting_package || 0) +
    Number(b.budget_transportation || 0) +
    Number(b.budget_business_trip || 0) +
    Number(b.budget_reward || 0) +
    Number(b.budget_material || 0) +
    Number(b.budget_internet || 0) +
    Number(b.budget_miscellaneous || 0)
  )
})

// Options list
const unitOptions = [
  'Divisi Operasional & Layanan',
  'Divisi Kredit Komersial & SME',
  'Divisi Manajemen Risiko & Kepatuhan',
  'Divisi Teknologi Informasi',
  'Divisi Human Capital & Learning',
  'Divisi Treasury & Perbankan Internasional',
  'Divisi Digital Banking & Produk'
]

const programTypeOptions = [
  'In-House Training',
  'Public Training / External Vendor',
  'Program Sertifikasi Profesi',
  'Workshop / FGD Spesialis',
  'E-Learning & Virtual Webinar'
]

const activityTypeOptions = [
  'Pelatihan',
  'Workshop',
  'Sertifikasi',
  'Seminar / Konferensi',
  'Coaching & Mentoring'
]

const biCategoryOptions = [
  'Operasional Perbankan & Sistem Pembayaran (SKNBI/FAST)',
  'Kredit, Pembiayaan Komersial & Analisis Risiko',
  'Manajemen Risiko Perbankan (BSMR)',
  'Anti Pencucian Uang & Pencegahan Terorisme (APU-PPT)',
  'Teknologi Informasi, Keamanan Siber & POJK 29/2022',
  'Kepatuhan, Regulasi OJK & Perlindungan Konsumen',
  'Kepemimpinan, Budaya Kerja & Human Capital'
]

function onDoc1Change(e) {
  const f = e.target.files[0]
  if (f) doc1File.value = f
}

function onDoc2Change(e) {
  const f = e.target.files[0]
  if (f) doc2File.value = f
}

function onApprovalDocChange(e) {
  const f = e.target.files[0]
  if (f) approvalDocFile.value = f
}

async function loadData() {
  loadingInit.value = true
  try {
    const [resCats, resTna] = await Promise.all([
      apiClient.get('/categories'),
      apiClient.get('/tna')
    ])
    categories.value = resCats.data.categories || []
    tnaProposals.value = resTna.data.proposals || []

    if (isEditMode.value) {
      const { data } = await apiClient.get(`/programs/${props.editProgramId}`)
      const p = data.program
      form.value.program_code = p.program_code || ''
      form.value.title = p.title || ''
      form.value.category = p.category || 'Operasional & Layanan Perbankan'
      form.value.location = p.location || 'Ruang 1'
      form.value.facilitator = p.facilitator || ''
      form.value.duration_text = p.duration_text || (p.duration_hours ? `${p.duration_hours} Jam` : '')
      form.value.start_date = p.start_date ? p.start_date.substring(0, 10) : ''
      form.value.end_date = p.end_date ? p.end_date.substring(0, 10) : ''
      form.value.program_type = p.program_type || 'In-House Training'
      form.value.unit_initiator = p.unit_initiator || p.department || 'Divisi Operasional & Layanan'
      form.value.activity_type = p.activity_type || 'Pelatihan'
      form.value.bi_category = p.bi_category || 'Operasional Perbankan'
      form.value.year = p.year || 2026
      form.value.notes = p.notes || ''
      form.value.approval_scenario = p.approval_scenario || (p.total_budget > 0 ? 'WITH_BUDGET' : 'WITHOUT_BUDGET')

      form.value.budget_fee_facilitator = Number(p.budget_fee_facilitator || 0)
      form.value.budget_meals = Number(p.budget_meals || 0)
      form.value.budget_hotel = Number(p.budget_hotel || 0)
      form.value.budget_meeting_package = Number(p.budget_meeting_package || 0)
      form.value.budget_transportation = Number(p.budget_transportation || 0)
      form.value.budget_business_trip = Number(p.budget_business_trip || 0)
      form.value.budget_reward = Number(p.budget_reward || 0)
      form.value.budget_material = Number(p.budget_material || 0)
      form.value.budget_internet = Number(p.budget_internet || 0)
      form.value.budget_miscellaneous = Number(p.budget_miscellaneous || 0)

      if (p.doc1_name) existingDoc1.value = p.doc1_name
      if (p.doc2_name) existingDoc2.value = p.doc2_name
    } else {
      // Auto-generate program code suggestion
      const randNum = Math.floor(100 + Math.random() * 900)
      form.value.program_code = `D26_${randNum}`
    }
  } catch (err) {
    feedback.value = { type: 'error', message: 'Gagal memuat data program.' }
  } finally {
    loadingInit.value = false
  }
}

onMounted(() => {
  loadData()
})

async function submitProgram() {
  if (!form.value.title) {
    feedback.value = { type: 'error', message: 'Program Name (Nama Program) wajib diisi.' }
    return
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const formData = new FormData()
    formData.append('program_code', form.value.program_code)
    formData.append('title', form.value.title)
    formData.append('category', form.value.category)
    formData.append('location', form.value.location)
    formData.append('facilitator', form.value.facilitator)
    formData.append('duration_text', form.value.duration_text)
    formData.append('start_date', form.value.start_date)
    formData.append('end_date', form.value.end_date)
    formData.append('program_type', form.value.program_type)
    formData.append('unit_initiator', form.value.unit_initiator)
    formData.append('activity_type', form.value.activity_type)
    formData.append('bi_category', form.value.bi_category)
    formData.append('year', form.value.year)
    formData.append('notes', form.value.notes || '')
    formData.append('approval_scenario', form.value.approval_scenario)
    formData.append('is_submit', '1')

    // 10 Budget items
    formData.append('budget_fee_facilitator', form.value.budget_fee_facilitator || 0)
    formData.append('budget_meals', form.value.budget_meals || 0)
    formData.append('budget_hotel', form.value.budget_hotel || 0)
    formData.append('budget_meeting_package', form.value.budget_meeting_package || 0)
    formData.append('budget_transportation', form.value.budget_transportation || 0)
    formData.append('budget_business_trip', form.value.budget_business_trip || 0)
    formData.append('budget_reward', form.value.budget_reward || 0)
    formData.append('budget_material', form.value.budget_material || 0)
    formData.append('budget_internet', form.value.budget_internet || 0)
    formData.append('budget_miscellaneous', form.value.budget_miscellaneous || 0)

    if (doc1File.value) formData.append('doc1', doc1File.value)
    if (form.value.doc1_remarks) formData.append('doc1_remarks', form.value.doc1_remarks)
    if (doc2File.value) formData.append('doc2', doc2File.value)
    if (approvalDocFile.value) formData.append('document', approvalDocFile.value)

    let res
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
      res = await apiClient.post(`/programs/${props.editProgramId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      res = await apiClient.post('/programs', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }

    emit('created', res.data.message || 'Program kerja berhasil disimpan.')
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menyimpan program kerja.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-16">

    <!-- Top Navigation Bar -->
    <div class="flex items-center justify-between">
      <button
        @click="emit('back')"
        class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-[#0067AC] transition cursor-pointer"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke Program List</span>
      </button>

      <div class="flex items-center gap-2 text-xs text-gray-400">
        <span>Program</span>
        <ChevronRight class="w-3.5 h-3.5" />
        <span class="text-gray-800 font-bold">{{ isEditMode ? 'Edit Program' : 'Create Program' }}</span>
      </div>
    </div>

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

    <!-- MAIN FORM: Create/View/Edit Program (Matching Excel Wireframe) -->
    <div class="bg-white rounded-3xl border border-gray-200/90 shadow-sm overflow-hidden">
      
      <!-- Card Header Bar -->
      <div class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <Layers class="w-5 h-5 text-white/90" />
          <h2 class="text-base font-extrabold tracking-wide uppercase">
            {{ isEditMode ? `Edit Program: ${form.program_code}` : 'Create/View/Edit Program' }}
          </h2>
        </div>
        <span class="text-xs bg-white/20 text-white font-bold px-3 py-1 rounded-full">
          Standar BWS Learning Hub
        </span>
      </div>

      <!-- Form Body -->
      <div class="p-6 md:p-8 flex flex-col gap-8">

        <!-- SECTION 1: PROGRAM INFORMATION (2 Columns matching Excel Mockup) -->
        <div>
          <h3 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#0067AC]"></span>
            <span>Informasi Pokok Program</span>
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
            
            <!-- Left Column: TNA Category, Program Name, Location, Facilitator, Duration -->
            <div class="flex flex-col gap-4">
              
              <!-- TNA Category -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  TNA Category
                </label>
                <input
                  v-model="form.category"
                  type="text"
                  placeholder="Category 1 / Operasional & Layanan Perbankan"
                  class="w-full bg-white text-xs font-semibold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
                />
              </div>

              <!-- Program Name -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                  <span>Program Name</span>
                  <span class="text-[10px] text-[#0067AC] font-bold">Wajib</span>
                </label>
                <input
                  v-model="form.title"
                  type="text"
                  placeholder="Nama Program Pelatihan (e.g. Program A / Service Excellence)"
                  class="w-full bg-white text-xs font-bold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
                />
              </div>

              <!-- Location -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Location
                </label>
                <input
                  v-model="form.location"
                  type="text"
                  placeholder="Ruang 1 / Ruang 2 / Virtual / Hotel"
                  class="w-full bg-white text-xs text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
                />
              </div>

              <!-- Facilitator -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Facilitator
                </label>
                <input
                  v-model="form.facilitator"
                  type="text"
                  placeholder="PT A / Lembaga Sertifikasi / Trainer Internal"
                  class="w-full bg-white text-xs text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
                />
              </div>

              <!-- Duration & Tanggal -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Duration
                  </label>
                  <input
                    v-model="form.duration_text"
                    type="text"
                    placeholder="2 Hari (16 Jam)"
                    class="w-full bg-white text-xs text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs font-mono"
                  />
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Periode Tgl
                  </label>
                  <div class="flex items-center gap-1">
                    <input
                      v-model="form.start_date"
                      type="date"
                      class="w-full bg-white text-[11px] font-mono text-gray-900 rounded-xl px-2 py-2 border border-gray-300 focus:outline-none"
                    />
                    <span class="text-gray-400 text-[10px]">-</span>
                    <input
                      v-model="form.end_date"
                      type="date"
                      class="w-full bg-white text-[11px] font-mono text-gray-900 rounded-xl px-2 py-2 border border-gray-300 focus:outline-none"
                    />
                  </div>
                </div>
              </div>

            </div>

            <!-- Right Column: Program ID, Program Type, Unit Initiator, Activity Type, BI Category -->
            <div class="flex flex-col gap-4">
              
              <!-- Program ID -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Program ID
                </label>
                <input
                  v-model="form.program_code"
                  type="text"
                  placeholder="D24_001"
                  class="w-full bg-white text-xs font-extrabold text-[#0067AC] rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs font-mono"
                />
              </div>

              <!-- Program Type -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Program Type
                </label>
                <select
                  v-model="form.program_type"
                  class="w-full bg-white text-xs font-semibold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs cursor-pointer"
                >
                  <option v-for="t in programTypeOptions" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>

              <!-- Unit Initiator -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Unit Initiator
                </label>
                <input
                  v-model="form.unit_initiator"
                  type="text"
                  placeholder="Divisi Operasional & Layanan / Unit Pengusul"
                  class="w-full bg-white text-xs font-semibold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
                />
              </div>

              <!-- Activity Type -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                  Activity Type
                </label>
                <select
                  v-model="form.activity_type"
                  class="w-full bg-white text-xs font-semibold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs cursor-pointer"
                >
                  <option v-for="a in activityTypeOptions" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>

              <!-- BI Category (Bank Indonesia / OJK Regulatory Category) -->
              <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                  <span>BI Category</span>
                  <span class="text-[10px] text-[#0067AC]">Regulasi BI / OJK</span>
                </label>
                <input
                  v-model="form.bi_category"
                  type="text"
                  placeholder="Kategori Regulasi BI / OJK"
                  class="w-full bg-white text-xs font-semibold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
                />
              </div>

            </div>

          </div>
        </div>

        <!-- SECTION 2: BUDGET (10 Components matching Excel Wireframe) -->
        <div class="bg-gray-50/70 p-6 rounded-2xl border border-gray-200">
          <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-200">
            <div>
              <h3 class="text-sm font-extrabold text-gray-900 flex items-center gap-2">
                <Wallet class="w-4 h-4 text-[#0067AC]" />
                <span>Budget (Rincian 10 Item Anggaran RAB)</span>
              </h3>
              <p class="text-[11px] text-gray-500">Isi komponen biaya pelaksanaan pelatihan sesuai RAB BWS</p>
            </div>
            <span class="text-xs font-bold text-[#0067AC]">[+] Add budget</span>
          </div>

          <!-- 10 Budget Items in 2 Columns -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3.5">
            
            <!-- Left 5 Items: Fee Facilitator, Meals, Hotel, Meeting Package, Transportation -->
            <div class="flex flex-col gap-3">
              
              <!-- 1. Fee Facilitator -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Fee Facilitator</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_fee_facilitator"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 2. Meals -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Meals</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_meals"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 3. Hotel -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Hotel</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_hotel"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 4. Meeting Package -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Meeting Package</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_meeting_package"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 5. Transportation -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Transportation</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_transportation"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

            </div>

            <!-- Right 5 Items: Business Trip, Reward, Material, Internet, Miscellaneous -->
            <div class="flex flex-col gap-3">
              
              <!-- 6. Business Trip Allowance -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Business Trip Allowance</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_business_trip"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 7. Reward -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Reward</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_reward"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 8. Material -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Material</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_material"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 9. Internet -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Internet</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_internet"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

              <!-- 10. Miscellaneous -->
              <div class="flex items-center justify-between gap-3">
                <label class="text-xs font-bold text-gray-700 w-44">Miscellaneous</label>
                <div class="relative flex-1">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                  <input
                    v-model.number="form.budget_miscellaneous"
                    type="number"
                    step="500000"
                    class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-xl pl-8 pr-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none font-mono shadow-2xs"
                  />
                </div>
              </div>

            </div>

          </div>

          <!-- Total Row matching Excel [Total 0] -->
          <div class="mt-5 pt-3 border-t border-gray-200 flex items-center justify-between">
            <span class="text-xs font-extrabold text-gray-800 uppercase tracking-wider">
              Total Budget (RAB)
            </span>
            <div class="px-5 py-2 rounded-xl bg-white border border-gray-300 font-extrabold text-sm text-[#0067AC] font-mono shadow-2xs">
              {{ formatRupiah(totalBudgetCalculated) }}
            </div>
          </div>
        </div>

        <!-- SECTION 3: UPLOAD DOKUMEN & REMARKS (Matching Excel Mockup) -->
        <div class="bg-gray-50/70 p-6 rounded-2xl border border-gray-200 flex flex-col gap-4">
          <h3 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider flex items-center gap-2">
            <FileText class="w-4 h-4 text-[#0067AC]" />
            <span>Upload Dokumen</span>
          </h3>

          <!-- Dokumen 1 + Remarks -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div class="flex items-center gap-3">
              <label class="text-xs font-bold text-gray-700 w-24">Dokumen 1</label>
              <input ref="doc1InputRef" type="file" @change="onDoc1Change" class="hidden" />
              <button
                type="button"
                @click="doc1InputRef?.click()"
                class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-gray-100 border border-gray-300 text-xs font-bold text-gray-700 transition shadow-2xs flex items-center gap-1 cursor-pointer shrink-0"
              >
                <FileUp class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>[Browse]</span>
              </button>
              <span class="text-xs text-gray-600 truncate font-semibold">
                {{ doc1File ? doc1File.name : (existingDoc1 || 'Belum ada file') }}
              </span>
            </div>

            <div class="flex items-center gap-3">
              <label class="text-xs font-bold text-gray-700 w-20">Remarks</label>
              <input
                v-model="form.doc1_remarks"
                type="text"
                placeholder="Keterangan dokumen (e.g. Memo Persetujuan / TOR)"
                class="flex-1 bg-white text-xs text-gray-900 rounded-xl px-3 py-1.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none"
              />
            </div>
          </div>

          <!-- Dokumen 2 -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div class="flex items-center gap-3">
              <label class="text-xs font-bold text-gray-700 w-24">Dokumen 2</label>
              <input ref="doc2InputRef" type="file" @change="onDoc2Change" class="hidden" />
              <button
                type="button"
                @click="doc2InputRef?.click()"
                class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-gray-100 border border-gray-300 text-xs font-bold text-gray-700 transition shadow-2xs flex items-center gap-1 cursor-pointer shrink-0"
              >
                <FileUp class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>[Browse]</span>
              </button>
              <span class="text-xs text-gray-600 truncate font-semibold">
                {{ doc2File ? doc2File.name : (existingDoc2 || 'Belum ada file') }}
              </span>
            </div>
          </div>
        </div>

        <!-- SECTION 4: NOTE (Matching Excel Mockup Big Box) -->
        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
            Note
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            placeholder="Catatan tambahan, urgensi pelaksanaan, atau disposisi persetujuan..."
            class="w-full bg-white text-xs text-gray-900 rounded-2xl p-3.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs leading-relaxed"
          ></textarea>
        </div>

        <!-- SECTION 5: APPROVAL SCENARIOS (Matching Excel Mockup 3 Tables) -->
        <div class="flex flex-col gap-4 bg-gray-50/70 p-6 rounded-2xl border border-gray-200">
          <div>
            <h3 class="text-xs font-extrabold text-gray-900 uppercase tracking-wider flex items-center gap-2">
              <ShieldCheck class="w-4 h-4 text-[#0067AC]" />
              <span>Alur Persetujuan & Disposisi</span>
            </h3>
            <p class="text-[11px] text-gray-500 mt-0.5">
              Pilih skenario alur persetujuan sesuai ketentuan tata kelola BWS
            </p>
          </div>

          <!-- Scenario Switcher Pills -->
          <div class="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              @click="form.approval_scenario = 'WITHOUT_BUDGET'"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border"
              :class="form.approval_scenario === 'WITHOUT_BUDGET' ? 'bg-[#0067AC] text-white border-[#0067AC] shadow-sm' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
            >
              Approved Without Budget
            </button>

            <button
              type="button"
              @click="form.approval_scenario = 'WITH_BUDGET'"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border"
              :class="form.approval_scenario === 'WITH_BUDGET' ? 'bg-[#0067AC] text-white border-[#0067AC] shadow-sm' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
            >
              Approved With Budget (EHR Berjenjang)
            </button>

            <button
              type="button"
              @click="form.approval_scenario = 'WITH_APPROVAL_DOC'"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border"
              :class="form.approval_scenario === 'WITH_APPROVAL_DOC' ? 'bg-[#0067AC] text-white border-[#0067AC] shadow-sm' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'"
            >
              Approved With Approval Document
            </button>
          </div>

          <!-- Table Variant 1: Approved Without Budget -->
          <div v-if="form.approval_scenario === 'WITHOUT_BUDGET'" class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="bg-gray-100 px-4 py-2 text-xs font-bold text-gray-700">Approved Without Budget</div>
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white font-bold text-[10px] uppercase tracking-wider">
                  <th class="py-2.5 px-3">Approved Level</th>
                  <th class="py-2.5 px-3">NIK</th>
                  <th class="py-2.5 px-3">Name</th>
                  <th class="py-2.5 px-3">Position</th>
                  <th class="py-2.5 px-3">Remarks/Disposition</th>
                  <th class="py-2.5 px-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr class="bg-white">
                  <td class="py-2.5 px-3 font-semibold text-gray-900">Learning & Development Division Head</td>
                  <td class="py-2.5 px-3 font-mono text-gray-500">990012</td>
                  <td class="py-2.5 px-3 font-bold text-gray-800">Budi Setiawan</td>
                  <td class="py-2.5 px-3 text-gray-600">Head of Learning & Development</td>
                  <td class="py-2.5 px-3 text-gray-500 italic">Disposisi Pelaksanaan Diklat</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0067AC] border border-blue-200">
                      Menunggu Disposisi
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table Variant 2: Approved With Budget (3 Levels Berjenjang) -->
          <div v-else-if="form.approval_scenario === 'WITH_BUDGET'" class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
            <div class="bg-gray-100 px-4 py-2 text-xs font-bold text-gray-700">Approved With Budget (3 Levels Berjenjang)</div>
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white font-bold text-[10px] uppercase tracking-wider">
                  <th class="py-2.5 px-3">Approved Level</th>
                  <th class="py-2.5 px-3">NIK</th>
                  <th class="py-2.5 px-3">Name</th>
                  <th class="py-2.5 px-3">Position</th>
                  <th class="py-2.5 px-3">Remarks/Disposition</th>
                  <th class="py-2.5 px-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 bg-white">
                <tr>
                  <td class="py-2.5 px-3 font-semibold text-gray-900">Level 1: Human Capital Div. Head</td>
                  <td class="py-2.5 px-3 font-mono text-gray-500">990008</td>
                  <td class="py-2.5 px-3 font-bold text-gray-800">Ahmad Fauzi</td>
                  <td class="py-2.5 px-3 text-gray-600">Division Head of Human Capital</td>
                  <td class="py-2.5 px-3 text-gray-500 italic">Persetujuan Otorisator 1</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0067AC] border border-blue-200">
                      Reviewer 1
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="py-2.5 px-3 font-semibold text-gray-900">Level 2: Learning & Development Div. Head</td>
                  <td class="py-2.5 px-3 font-mono text-gray-500">990012</td>
                  <td class="py-2.5 px-3 font-bold text-gray-800">Budi Setiawan</td>
                  <td class="py-2.5 px-3 text-gray-600">Head of Learning & Development</td>
                  <td class="py-2.5 px-3 text-gray-500 italic">Persetujuan Otorisator 2</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                      Reviewer 2
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="py-2.5 px-3 font-semibold text-gray-900">Level 3: People Development Dept. Head</td>
                  <td class="py-2.5 px-3 font-mono text-gray-500">990025</td>
                  <td class="py-2.5 px-3 font-bold text-gray-800">Siti Rahma</td>
                  <td class="py-2.5 px-3 text-gray-600">Dept Head People Development</td>
                  <td class="py-2.5 px-3 text-gray-500 italic">Verifikasi Teknis & Silabus</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-50 text-gray-600 border border-gray-200">
                      Reviewer 3
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table Variant 3: Approved With Approval Document -->
          <div v-else class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs flex flex-col gap-3 p-4 bg-white">
            <div class="text-xs font-bold text-gray-700">Approved With Approval Document</div>
            
            <div class="flex items-center gap-3">
              <label class="text-xs font-bold text-gray-700">Upload Document Persetujuan:</label>
              <input ref="approvalDocInputRef" type="file" @change="onApprovalDocChange" class="hidden" />
              <button
                type="button"
                @click="approvalDocInputRef?.click()"
                class="px-3 py-1.5 rounded-xl bg-white hover:bg-gray-100 border border-gray-300 text-xs font-bold text-gray-700 transition flex items-center gap-1 cursor-pointer"
              >
                <FileUp class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>[Browse Document]</span>
              </button>
              <span class="text-xs text-gray-600 font-semibold truncate">
                {{ approvalDocFile ? approvalDocFile.name : 'Belum ada dokumen diunggah' }}
              </span>
            </div>

            <table class="w-full text-left text-xs border-collapse border border-gray-200 rounded-lg overflow-hidden mt-1">
              <thead>
                <tr class="bg-gradient-to-r from-[#0067AC] to-[#003876] text-white font-bold text-[10px] uppercase tracking-wider">
                  <th class="py-2 px-3">Approved Level</th>
                  <th class="py-2 px-3">NIK</th>
                  <th class="py-2 px-3">Name</th>
                  <th class="py-2 px-3">Position</th>
                  <th class="py-2 px-3">Remarks/Disposition</th>
                  <th class="py-2 px-3 text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr class="bg-white">
                  <td class="py-2.5 px-3 font-semibold text-gray-900">Learning & Development Division Head</td>
                  <td class="py-2.5 px-3 font-mono text-gray-500">990012</td>
                  <td class="py-2.5 px-3 font-bold text-gray-800">Budi Setiawan</td>
                  <td class="py-2.5 px-3 text-gray-600">Head of Learning & Development</td>
                  <td class="py-2.5 px-3 text-gray-500 italic">Disposisi Dokumen Persetujuan</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      Disahkan Dokumen
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bottom Note in Excel Template -->
          <div class="text-[11px] text-gray-500 leading-relaxed bg-amber-50/60 p-3 rounded-xl border border-amber-200 text-amber-900">
            <strong>Catatan Alur:</strong><br />
            • Jika ada dokumen persetujuan, cukup melampirkan dokumen yang telah disetujui, dan Disposisi LD Div Head.<br />
            • Jika baru diajukan persetujuan seperti di EHR: Berjenjang (3 level).
          </div>
        </div>

        <!-- ACTION BUTTONS: SUBMIT & Cancel (Matching Excel Mockup) -->
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
            @click="submitProgram"
            :disabled="isSubmitting"
            class="px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer disabled:opacity-50 uppercase tracking-wider"
          >
            <Send class="w-4 h-4" />
            <span>{{ isSubmitting ? 'MEMPROSES...' : 'SUBMIT' }}</span>
          </button>
        </div>

      </div>

    </div>

  </div>
</template>
