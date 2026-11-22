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
  ChevronDown
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

// Master Data State
const loadingMasters = ref(true)
const categories = ref([])
const subCategories = ref([])
const fiscalYears = ref([])

// Form State matching Excel wireframe
const form = ref({
  year: 2026,
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

function getSubCategoriesForCategory(categoryName) {
  if (!categoryName) return []
  const cat = categories.value.find(c => c.name === categoryName)
  return cat?.sub_categories || []
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
      apiClient.get('/categories?with_subs=1'),
      apiClient.get('/fiscal-years')
    ])
    categories.value = resCats.data.categories || []
    fiscalYears.value = resYears.data.years || []

    if (resYears.data.active_year && !isEditMode.value) {
      form.value.year = resYears.data.active_year
    }

    if (isEditMode.value) {
      const { data } = await apiClient.get(`/tna/${props.editProposalId}`)
      const p = data.proposal
      editingProposal.value = p

      form.value.year = p.year || 2026
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
          
          <!-- Field 1: Year -->
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Year (Tahun Anggaran)</span>
              <span class="text-[11px] text-[#0067AC] font-semibold">Wajib</span>
            </label>
            <div class="relative">
              <input
                v-model.number="form.year"
                type="number"
                min="2020"
                max="2040"
                placeholder="2026"
                :disabled="isEditMode"
                class="w-full bg-white text-xs font-bold text-gray-900 rounded-xl px-4 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs font-mono disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
            </div>
            <p class="text-[10px] text-gray-400 mt-1">Satu tahun anggaran hanya diperkenankan 1 dokumen TNA Induk.</p>
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
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Rp</span>
              <input
                v-model.number="form.minimum_regulator_investment"
                type="number"
                step="1000000"
                placeholder="1000000000"
                class="w-full bg-white text-xs font-bold text-gray-900 rounded-xl pl-9 pr-4 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs font-mono"
              />
            </div>
            <p class="text-[10px] text-gray-400 mt-1 font-mono">
              Terbaca: {{ formatRupiah(form.minimum_regulator_investment) }} (Ketentuan Alokasi OJK / Regulasi Bank)
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
                @click="loadPreset20Programs"
                title="Muat contoh lengkap 20 program dari 5 kategori"
              >
                Muat Template 20 Program
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

                <!-- Training Program (with Sub Categories autocomplete suggestions) -->
                <td class="py-2.5 px-3">
                  <input
                    v-model="item.recommended_program"
                    type="text"
                    :list="'subcat-suggestions-' + idx"
                    placeholder="Judul / nama program pelatihan..."
                    class="w-full bg-white text-xs text-gray-800 rounded-lg px-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition shadow-2xs placeholder:text-gray-300"
                  />
                  <datalist :id="'subcat-suggestions-' + idx">
                    <option
                      v-for="sub in getSubCategoriesForCategory(item.category_name)"
                      :key="sub.id"
                      :value="sub.name"
                    >
                      {{ sub.code ? `[${sub.code}] ` : '' }}{{ sub.target_competency ? `(${sub.target_competency})` : '' }}
                    </option>
                  </datalist>
                </td>

                <!-- Investment (Projection) -->
                <td class="py-2.5 px-3 text-right">
                  <div class="relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-gray-400">Rp</span>
                    <input
                      v-model.number="item.budget"
                      type="number"
                      step="1000000"
                      class="w-full bg-white text-xs font-bold text-right text-gray-900 rounded-lg pl-8 pr-3 py-1.5 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition font-mono shadow-2xs"
                    />
                  </div>
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

  </div>
</template>
