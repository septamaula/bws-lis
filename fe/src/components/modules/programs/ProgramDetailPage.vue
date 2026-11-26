<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  ChevronRight,
  Edit2,
  Printer,
  Calendar,
  Building2,
  CheckCircle2,
  XCircle,
  RotateCcw,
  Clock,
  Wallet,
  Users,
  Award,
  BookOpen,
  FileText,
  FileCheck2,
  Download,
  Send,
  Sparkles,
  Paperclip,
  MapPin,
  GraduationCap,
  ShieldCheck,
  Compass,
  FileCheck,
  Plus
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/i18n'

const { l, locale } = useI18n()

const props = defineProps({
  programId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['back', 'refresh-list', 'edit', 'open-class-create'])
const auth = useAuthStore()

const loading = ref(true)
const program = ref(null)
const flowSteps = ref([])

// Workflow Action State
const approvalAction = ref('APPROVE')
const approvalNotes = ref('')
const submittingApproval = ref(false)
const actionFeedback = ref({ type: '', message: '' })
const downloadingDoc = ref(false)

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

function formatDateDisplay(d) {
  if (!d) return '-'
  try {
    const date = new Date(d)
    if (isNaN(date.getTime())) return d
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-US' : 'id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }).format(date)
  } catch {
    return d
  }
}

async function fetchProgramDetail() {
  loading.value = true
  try {
    const { data } = await apiClient.get(`/programs/${props.programId}`)
    program.value = data.program
    flowSteps.value = data.flow_steps || []
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: l('Failed to load training program details.', 'Gagal memuat rincian data program kerja pelatihan.')
    }
  } finally {
    loading.value = false
  }
}

// Hak Akses Otorisasi
const currentStep = computed(() => {
  if (!program.value || !flowSteps.value.length) return null
  return flowSteps.value.find(s => s.step_order === program.value.current_step_order)
})

const canApprove = computed(() => {
  if (!program.value || program.value.approval_type !== 'WORKFLOW') return false
  if (program.value.status === 'APPROVED' || program.value.status === 'REJECTED') return false
  const role = auth.user?.role_name
  if (role === 'Super Admin' || auth.user?.level?.is_admin) return true
  return currentStep.value && currentStep.value.role_name === role
})

const canEditProgram = computed(() => {
  if (!program.value) return false
  const role = auth.user?.role_name
  const isSuperAdmin = (role === 'Super Admin' || auth.user?.level?.is_admin)
  const isCreator = (auth.user?.id === program.value.created_by)

  if (program.value.status === 'REVISED' || program.value.status === 'DRAFT') {
    return true
  }
  if (program.value.status !== 'APPROVED' && (isSuperAdmin || isCreator)) {
    return true
  }
  return false
})

async function handleWorkflowSubmit() {
  if (!approvalNotes.value.trim()) {
    actionFeedback.value = { type: 'error', message: l('Authorization notes are required.', 'Catatan otorisasi wajib diisi.') }
    return
  }

  submittingApproval.value = true
  actionFeedback.value = { type: '', message: '' }

  try {
    const payload = {
      decision: approvalAction.value,
      notes: approvalNotes.value.trim()
    }
    await apiClient.post(`/programs/${props.programId}/workflow-action`, payload)
    actionFeedback.value = { type: 'success', message: l('Program authorization decision has been recorded.', 'Keputusan otorisasi program berhasil diproses.') }
    approvalNotes.value = ''
    await fetchProgramDetail()
    emit('refresh-list')
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to execute program authorization.', 'Gagal mengeksekusi otorisasi program.')
    }
  } finally {
    submittingApproval.value = false
  }
}

async function downloadDocument() {
  downloadingDoc.value = true
  try {
    const response = await apiClient.get(`/programs/${props.programId}/document`, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: response.headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', program.value?.approval_doc_name || l('Program_Approval_Document.pdf', 'Dokumen_Pengesahan_Program.pdf'))
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: l('Failed to download authorization document file.', 'Gagal mengunduh berkas dokumen pengesahan.')
    }
  } finally {
    downloadingDoc.value = false
  }
}

function formatScheduleRange(start, end) {
  if (!start) return '-'
  try {
    const s = new Date(start)
    if (isNaN(s.getTime())) return start.substring(0, 10)
    const months = locale.value === 'en'
      ? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      : ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    
    if (!end || start.substring(0, 10) === end.substring(0, 10)) {
      const d = String(s.getDate()).padStart(2, '0')
      return `${d} ${months[s.getMonth()]} ${s.getFullYear()}`
    }
    
    const e = new Date(end)
    if (isNaN(e.getTime())) return `${start.substring(0, 10)} - ${end.substring(0, 10)}`

    const sd = String(s.getDate()).padStart(2, '0')
    const ed = String(e.getDate()).padStart(2, '0')

    // Same month and year: e.g. 22 - 24 Nov 2026
    if (s.getMonth() === e.getMonth() && s.getFullYear() === e.getFullYear()) {
      return `${sd} - ${ed} ${months[s.getMonth()]} ${s.getFullYear()}`
    }
    // Same year, different month: e.g. 28 Nov - 02 Des 2026
    if (s.getFullYear() === e.getFullYear()) {
      return `${sd} ${months[s.getMonth()]} - ${ed} ${months[e.getMonth()]} ${s.getFullYear()}`
    }
    // Different year: e.g. 28 Des 2026 - 02 Jan 2027
    return `${sd} ${months[s.getMonth()]} ${s.getFullYear()} - ${ed} ${months[e.getMonth()]} ${e.getFullYear()}`
  } catch (err) {
    return `${start?.substring(0, 10)} - ${end?.substring(0, 10)}`
  }
}

function parseTrainer(str) {
  if (!str) return { name: l('BWS Trainer Team', 'Tim Pengajar BWS'), role: '' }
  const match = str.match(/^(.*?)\s*\((.*?)\)$/)
  if (match) {
    return { name: match[1].trim(), role: match[2].trim() }
  }
  return { name: str, role: '' }
}

function handlePrint() {
  window.print()
}

const statusBadgeClass = computed(() => {
  if (!program.value) return 'bg-gray-100 text-gray-700'
  switch (program.value.status) {
    case 'APPROVED':
      return 'bg-emerald-50 text-emerald-800 border-emerald-200'
    case 'REJECTED':
      return 'bg-rose-50 text-rose-800 border-rose-200'
    case 'REVISED':
      return 'bg-amber-50 text-amber-800 border-amber-200'
    default:
      return 'bg-[#EBF4FC] text-[#0067AC] border-blue-200'
  }
})

const standardBudgetSum = computed(() => {
  if (!program.value) return 0
  return (
    Number(program.value.budget_fee_facilitator || 0) +
    Number(program.value.budget_meals || 0) +
    Number(program.value.budget_hotel || 0) +
    Number(program.value.budget_meeting_package || 0) +
    Number(program.value.budget_transportation || 0) +
    Number(program.value.budget_business_trip || 0) +
    Number(program.value.budget_reward || 0) +
    Number(program.value.budget_material || 0) +
    Number(program.value.budget_internet || 0) +
    Number(program.value.budget_miscellaneous || 0)
  )
})

const budgetItemTypeBadge = computed(() => ({
  SPEAKER: { label: l('Speaker / Trainer', 'Pembicara / Pengajar'), class: 'bg-blue-100 text-[#0067AC]' },
  TRANSPORT: { label: l('Transportation', 'Transportasi'), class: 'bg-indigo-100 text-indigo-800' },
  ACCOMMODATION: { label: l('Accommodation / Hotel', 'Penginapan / Hotel'), class: 'bg-purple-100 text-purple-800' },
  LUNCH: { label: l('Lunch', 'Makan Siang'), class: 'bg-emerald-100 text-emerald-800' },
  SNACK: { label: l('Coffee Break & Snack', 'Kudapan & Rehat Kopi'), class: 'bg-amber-100 text-amber-800' },
  MATERIALS: { label: l('Modules & Stationery', 'Modul & ATK'), class: 'bg-fuchsia-100 text-fuchsia-800' },
  VENUE: { label: l('Room / Venue Rental', 'Sewa Ruangan / Tempat'), class: 'bg-sky-100 text-sky-800' },
  CERTIFICATION: { label: l('Certification Exam', 'Ujian Sertifikasi'), class: 'bg-rose-100 text-rose-800' },
  OTHER: { label: l('Contingency / Others', 'Kontinjensi / Lainnya'), class: 'bg-gray-100 text-gray-700' },
}))

onMounted(() => {
  fetchProgramDetail()
})
</script>

<template>
  <div class="space-y-6">

    <!-- Standard Subpage Header (Navigation & Actions) -->
    <SubpageHeader
      :back-label="$l('Back to Program List', 'Kembali ke Daftar Program')"
      :title="program?.title || $l('Training Program Details', 'Rincian Program Pelatihan')"
      :badge="program?.program_code || $l('Work Program', 'Program Kerja')"
      :subtitle="program ? $l(`${program.category || ''} • Initiator: ${program.unit_initiator || program.department || 'HC'} • Year ${program.year || 2026}`, `${program.category || ''} • Inisiator: ${program.unit_initiator || program.department || 'HC'} • Tahun ${program.year || 2026}`) : ''"
      :status-badge="program?.status === 'APPROVED' ? $l('Final Approved', 'Disetujui Final') : program?.status === 'REJECTED' ? $l('Rejected', 'Ditolak') : program?.status === 'REVISED' ? $l('Needs Revision', 'Perlu Revisi') : $l('Pending Review', 'Menunggu Peninjauan')"
      :status-class="statusBadgeClass"
      @back="emit('back')"
    >
      <template #actions>
        <button
          v-if="canEditProgram"
          @click="emit('edit', program.id)"
          class="bg-[#0067AC] hover:bg-[#00528c] text-white px-3.5 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs cursor-pointer shrink-0 whitespace-nowrap"
          :title="$l('Revise or edit program specifications and budget plan (RAB)', 'Perbaiki atau ubah spesifikasi dan RAB program ini')"
        >
          <Edit2 class="w-3.5 h-3.5" />
          <span>{{ program.status === 'REVISED' ? $l('Revise Program', 'Perbaiki Program') : $l('Edit Program', 'Ubah Program') }}</span>
        </button>

        <button
          @click="handlePrint"
          class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 px-3.5 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs cursor-pointer shrink-0 whitespace-nowrap"
        >
          <Printer class="w-3.5 h-3.5 text-gray-500" />
          <span>{{ $l('Print Document', 'Cetak Dokumen') }}</span>
        </button>
      </template>
    </SubpageHeader>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="bg-white rounded-3xl p-12 text-center border border-gray-100 space-y-3">
      <div class="w-10 h-10 border-4 border-[#0067AC] border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-gray-500 font-medium">{{ $l('Loading work program details, budget plan (RAB), and authorization...', 'Memuat data rincian program kerja, RAB, dan otorisasi...') }}</p>
    </div>

    <div v-else-if="program" class="space-y-6">

      <!-- Alert Feedback -->
      <div v-if="actionFeedback.message" class="w-full">
        <div
          class="p-4 rounded-2xl text-xs flex items-center justify-between border"
          :class="actionFeedback.type === 'success' ? 'bg-[#EBF4FC] border-blue-200 text-[#0067AC]' : 'bg-rose-50 border-rose-200 text-rose-700'"
        >
          <div class="flex items-center gap-2">
            <CheckCircle2 v-if="actionFeedback.type === 'success'" class="w-4 h-4 shrink-0" />
            <AlertCircle v-else class="w-4 h-4 shrink-0" />
            <span class="font-semibold">{{ actionFeedback.message }}</span>
          </div>
          <button @click="actionFeedback.message = ''" class="font-bold cursor-pointer">&times;</button>
        </div>
      </div>

      <!-- Revision Required Notice Banner -->
      <div v-if="program.status === 'REVISED'" class="w-full">
        <div class="p-5 rounded-3xl bg-amber-50/90 border-2 border-amber-300 text-amber-950 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
          <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-2xl bg-amber-200 text-amber-900 flex items-center justify-center shrink-0 font-bold">
              <RotateCcw class="w-5 h-5 text-amber-800" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold uppercase tracking-wider text-amber-900">{{ $l('Program Requires Revision', 'Program Memerlukan Perbaikan / Revisi') }}</span>
              </div>
              <p class="text-xs text-amber-950 font-semibold mt-1">
                {{ $l('Authorizer Notes:', 'Catatan Otorisator:') }} <span class="italic font-bold text-amber-900">"{{ program.rejection_notes || $l('Please adjust the budget allocation (RAB) or participant quota.', 'Silakan sesuaikan alokasi biaya RAB atau kuota peserta.') }}"</span>
              </p>
            </div>
          </div>

          <button
            @click="emit('edit', program.id)"
            class="px-5 py-2.5 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md transition flex items-center gap-2 shrink-0 self-end md:self-auto cursor-pointer"
          >
            <Edit2 class="w-4 h-4" />
            <span>{{ $l('Revise Specifications & Budget', 'Perbaiki Spesifikasi & RAB') }}</span>
          </button>
        </div>
      </div>

      <!-- 4-Metric Statistics Cards Strip -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
            <Wallet class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Total Budget (RAB)', 'Total Anggaran (RAB)') }}</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900 font-mono">{{ formatRupiah(program.total_budget) }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Target Participants', 'Target Peserta') }}</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900">{{ program.target_participants_count }} {{ $l('Employees', 'Pegawai') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Training Duration', 'Durasi Pelatihan') }}</p>
            <p class="text-base sm:text-lg font-extrabold text-[#0067AC]">{{ program.duration_hours }} {{ $l('Lesson Hours', 'Jam Pelajaran') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
            <GraduationCap class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">{{ $l('Number of Classes', 'Jumlah Kelas') }}</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900 font-mono">{{ program.classes?.length || 0 }} {{ $l('Classes', 'Kelas') }}</p>
          </div>
        </div>
      </div>

      <!-- Kaitan Usulan Dokumen TNA Card -->
      <div v-if="program.tna_proposal" class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <Compass class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Linked TNA Document (Training Needs Analysis)', 'Kaitan Dokumen Analisis Kebutuhan Pelatihan (TNA)') }}</h2>
          </div>
          <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#EBF4FC] text-[#0067AC]">
            {{ $l('1 TNA → Many Work Programs', '1 TNA → Banyak Program Kerja') }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('TNA Document Code', 'Nomor Kode Dokumen TNA') }}</span>
            <span class="font-extrabold text-[#002855] text-sm block">{{ program.tna_proposal.proposal_code }}</span>
            <span class="text-gray-500 block">{{ $l('Requesting Unit:', 'Unit Pemohon:') }} {{ program.tna_proposal.department }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('TNA Status & Fiscal Year', 'Status & Tahun Buku TNA') }}</span>
            <span class="font-bold text-gray-900 text-sm block">{{ $l(`Fiscal Year ${program.tna_proposal.year}`, `Tahun Anggaran ${program.tna_proposal.year}`) }}</span>
            <span class="text-emerald-700 font-semibold block">{{ $l('TNA Status:', 'Status TNA:') }} {{ program.tna_proposal.status }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Approved Division TNA Ceiling', 'Pagu TNA Divisi Disetujui') }}</span>
            <span class="font-mono font-extrabold text-[#0067AC] text-sm block">
              {{ formatRupiah(program.tna_proposal.total_budget_requested) }}
            </span>
            <span class="text-gray-500 block">{{ $l('Program Budget Allocation:', 'Alokasi Biaya Program:') }} {{ formatRupiah(program.total_budget) }}</span>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-[#EBF4FC]/40 border border-blue-100/60 text-xs space-y-1">
          <span class="font-bold text-[#002855] uppercase tracking-wider text-[10px] block">{{ $l('TNA Background & Strategic Objectives:', 'Latar Belakang & Sasaran Strategis TNA:') }}</span>
          <p class="text-gray-700 leading-relaxed">{{ program.tna_proposal.strategic_objective }}</p>
        </div>
      </div>

      <!-- Informasi Spesifikasi Program Pelatihan Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <BookOpen class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Training Delivery & Curriculum Specifications', 'Spesifikasi Penyelenggaraan & Kurikulum Diklat') }}</h2>
          </div>
          <span
            class="text-[11px] font-bold px-2.5 py-0.5 rounded-full"
            :class="program.is_mandatory ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-blue-100 text-[#0067AC]'"
          >
            {{ program.is_mandatory ? $l('Mandatory (OJK / BI Regulation)', 'Wajib Regulasi OJK / BI') : $l('Elective / Development', 'Pilihan / Pengembangan') }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Category & Requesting Unit', 'Kategori & Unit Pemohon') }}</span>
            <span class="font-bold text-gray-900 block">{{ program.category }}</span>
            <span class="text-gray-500 block">{{ $l('Unit:', 'Unit:') }} {{ program.unit_initiator || program.department }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Period & Duration', 'Periode & Durasi') }}</span>
            <span class="font-bold text-[#002855] block">
              {{ program.start_date ? formatDateDisplay(program.start_date) : '-' }} {{ $l('to', 's/d') }} {{ program.end_date ? formatDateDisplay(program.end_date) : '-' }}
            </span>
            <span class="text-gray-500 block">{{ $l('Duration:', 'Durasi:') }} {{ program.duration_text || (program.duration_hours + ' ' + $l('Hours', 'Jam')) }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Location & Facilitator', 'Lokasi & Fasilitator') }}</span>
            <span class="font-bold text-gray-900 block">{{ program.location || 'BWS Learning Center' }}</span>
            <span class="text-gray-500 block">{{ $l('Facilitator:', 'Fasilitator:') }} {{ program.facilitator || '-' }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('BI Category & Type', 'Kategori BI & Tipe') }}</span>
            <span class="font-bold text-[#0067AC] block">{{ program.bi_category || $l('Banking Regulation', 'Regulasi Perbankan') }}</span>
            <span class="text-gray-500 block">{{ $l('Type:', 'Tipe:') }} {{ program.activity_type || program.program_type || $l('Training', 'Pelatihan') }}</span>
          </div>
        </div>

        <div v-if="program.notes" class="p-4 rounded-2xl bg-blue-50/50 border border-blue-100 text-xs space-y-1">
          <span class="font-bold text-[#0067AC] uppercase tracking-wider text-[10px] block">{{ $l('Notes / Disposition:', 'Catatan / Disposisi:') }}</span>
          <p class="text-gray-700 leading-relaxed">{{ program.notes }}</p>
        </div>

        <div v-if="program.description" class="p-4 rounded-2xl bg-gray-50 border border-gray-100 text-xs space-y-1">
          <span class="font-bold text-gray-700 uppercase tracking-wider text-[10px] block">{{ $l('Description & Material Syllabus:', 'Deskripsi & Silabus Materi:') }}</span>
          <p class="text-gray-700 leading-relaxed">{{ program.description }}</p>
        </div>
      </div>

      <!-- BUDGET BREAKDOWN (RAB) -->
      <div v-if="standardBudgetSum > 0 || program.total_budget > 0" class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <Wallet class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Budget Plan Breakdown (RAB)', 'Rencana Anggaran Biaya (RAB)') }}</h2>
          </div>
          <span class="text-xs font-mono font-bold text-[#0067AC]">
            {{ (program.budget_items && program.budget_items.length > 0) ? $l('Standard Subtotal: ', 'Subtotal Standar: ') : $l('Total: ', 'Total: ') }}{{ formatRupiah(standardBudgetSum) }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div class="space-y-2 bg-[#F8FAFC] p-4 rounded-2xl border border-gray-100">
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Facilitator Fee:', 'Honorarium Fasilitator:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_fee_facilitator) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Meals & Refreshments:', 'Konsumsi & Hidangan:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_meals) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Hotel / Accommodation:', 'Penginapan / Hotel:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_hotel) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Meeting Package:', 'Paket Ruang Pertemuan:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_meeting_package) }}</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-gray-600 font-medium">{{ $l('Transportation:', 'Transportasi:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_transportation) }}</span>
            </div>
          </div>

          <div class="space-y-2 bg-[#F8FAFC] p-4 rounded-2xl border border-gray-100">
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Business Trip Allowance:', 'Uang Harian Perjalanan Dinas:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_business_trip) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Participant Rewards:', 'Apresiasi & Penghargaan:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_reward) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Training Materials:', 'Modul & Perlengkapan Materi:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_material) }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-gray-200/60">
              <span class="text-gray-600 font-medium">{{ $l('Internet & Connectivity:', 'Koneksi Internet:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_internet) }}</span>
            </div>
            <div class="flex justify-between py-1">
              <span class="text-gray-600 font-medium">{{ $l('Miscellaneous:', 'Biaya Lain-Lain:') }}</span>
              <span class="font-mono font-bold text-gray-900">{{ formatRupiah(program.budget_miscellaneous) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- TABEL RINCIAN BIAYA TAMBAHAN -->
      <div v-if="program.budget_items && program.budget_items.length > 0" class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <Wallet class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Additional Cost Components', 'Rincian Komponen Biaya Tambahan') }}</h2>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="canEditProgram"
              @click="emit('edit', program.id)"
              class="px-3 py-1 rounded-full bg-[#EBF4FC] hover:bg-[#d5e9f8] text-[#0067AC] font-bold text-xs flex items-center gap-1.5 transition border border-blue-200 cursor-pointer"
            >
              <Edit2 class="w-3 h-3" />
              <span>{{ $l('Edit Budget', 'Ubah RAB') }}</span>
            </button>
            <span class="text-xs font-semibold text-gray-500 font-mono">{{ program.budget_items.length }} {{ $l('Components', 'Komponen') }}</span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
              <tr>
                <th class="py-3 px-4 text-center w-14">No</th>
                <th class="py-3 px-4">{{ $l('Additional Cost Description', 'Uraian Biaya Tambahan') }}</th>
                <th class="py-3 px-4 text-right w-56">{{ $l('Amount (IDR)', 'Nominal (Rp)') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, idx) in program.budget_items" :key="item.id" class="hover:bg-gray-50/60 transition">
                <td class="py-3.5 px-4 text-center font-bold text-gray-400 font-mono">{{ idx + 1 }}</td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-gray-900 block text-xs">{{ item.description }}</span>
                  <span v-if="item.notes" class="text-[11px] text-gray-400 italic block mt-0.5">{{ item.notes }}</span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-[#002855] text-xs">
                  {{ formatRupiah(item.subtotal || item.unit_price) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-[#F8FAFC] border-t-2 border-gray-200 font-bold text-xs">
              <tr>
                <td colspan="2" class="py-3.5 px-4 text-gray-800 font-extrabold text-right">
                  {{ $l('Additional Cost Subtotal', 'Subtotal Biaya Tambahan') }}
                </td>
                <td class="py-3.5 px-4 text-right font-mono text-sm text-[#0067AC] font-black">
                  {{ formatRupiah(program.budget_items.reduce((s, i) => s + Number(i.subtotal || i.unit_price || 0), 0)) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- TABEL DAFTAR NOMINASI PESERTA -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <Users class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Registered BWS Employee Nominations', 'Daftar Nominasi Pegawai BWS Terdaftar') }}</h2>
          </div>
          <span class="text-xs font-semibold text-gray-500 font-mono">{{ program.participants?.length || 0 }} {{ $l('Registered Employees', 'Pegawai Terdaftar') }}</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
              <tr>
                <th class="py-2.5 px-3 text-center w-12">No</th>
                <th class="py-2.5 px-3 w-36">{{ $l('Employee NIK', 'NIK Pegawai') }}</th>
                <th class="py-2.5 px-4">{{ $l('Full Name', 'Nama Lengkap') }}</th>
                <th class="py-2.5 px-3">{{ $l('Unit / Branch Office', 'Unit / Kantor Cabang') }}</th>
                <th class="py-2.5 px-3">{{ $l('Position', 'Jabatan') }}</th>
                <th class="py-2.5 px-3 text-center w-28">{{ $l('Status', 'Status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(p, idx) in program.participants" :key="p.id" class="hover:bg-gray-50/50">
                <td class="py-2.5 px-3 text-center font-bold text-gray-400 font-mono">{{ idx + 1 }}</td>
                <td class="py-2.5 px-3 font-mono font-bold text-[#0067AC]">{{ p.nik || '-' }}</td>
                <td class="py-2.5 px-4 font-bold text-gray-900">{{ p.name }}</td>
                <td class="py-2.5 px-3 text-gray-700">{{ p.branch || p.department }}</td>
                <td class="py-2.5 px-3 text-gray-600">{{ p.position }}</td>
                <td class="py-2.5 px-3 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#0067AC]">
                    {{ p.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="!program.participants || program.participants.length === 0">
                <td colspan="6" class="py-6 text-center text-gray-400">
                  {{ $l('Participant nominations have not been added to this program yet.', 'Daftar nominasi peserta belum diisi pada program ini.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECTION: BATCH KELAS PELATIHAN -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2.5">
            <Calendar class="w-4 h-4 text-[#0067AC]" />
            <div>
              <h2 class="text-sm font-bold text-gray-900">{{ $l('Training Class Batches', 'Angkatan Kelas Pelatihan') }}</h2>
              <p class="text-[11px] text-gray-500">{{ $l('Execution schedule of class batches for this training program', 'Jadwal pelaksanaan angkatan kelas dari program pelatihan ini') }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-gray-500 font-mono">{{ program.classes?.length || 0 }} {{ $l('Scheduled Batches', 'Angkatan Terjadwal') }}</span>
            <AppButton
              type="button"
              variant="primary"
              pill
              icon="plus"
              @click="emit('open-class-create', program.id)"
            >
              {{ $l('Open Class Batch', 'Buka Angkatan Kelas') }}
            </AppButton>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
              <tr>
                <th class="py-3 px-3 w-12 text-center">No</th>
                <th class="py-3 px-4 min-w-[240px]">{{ $l('Batch Code & Name', 'Kode & Nama Angkatan') }}</th>
                <th class="py-3 px-4 min-w-[170px] whitespace-nowrap">{{ $l('Execution Schedule', 'Jadwal Pelaksanaan') }}</th>
                <th class="py-3 px-4 min-w-[200px]">{{ $l('Method & Location', 'Metode & Lokasi') }}</th>
                <th class="py-3 px-4 min-w-[200px]">{{ $l('Instructor / Trainer', 'Pengajar / Instruktur') }}</th>
                <th class="py-3 px-4 w-28 text-center whitespace-nowrap">{{ $l('Participants', 'Peserta') }}</th>
                <th class="py-3 px-4 w-36 text-center whitespace-nowrap">{{ $l('Status', 'Status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(cls, idx) in program.classes" :key="cls.id" class="hover:bg-gray-50/60 transition">
                <td class="py-3.5 px-3 text-center font-bold text-gray-400 font-mono text-xs align-middle">{{ idx + 1 }}</td>
                <td class="py-3.5 px-4 min-w-[240px] align-middle">
                  <p class="font-bold text-gray-900 text-xs leading-snug">{{ cls.batch_name }}</p>
                  <p class="font-mono text-[11px] font-semibold text-[#0067AC] mt-0.5">{{ cls.class_code }}</p>
                </td>
                <td class="py-3.5 px-4 text-gray-700 whitespace-nowrap align-middle">
                  <p class="font-bold text-gray-900 text-xs">{{ formatScheduleRange(cls.start_date, cls.end_date) }}</p>
                  <p class="text-[11px] text-gray-500 font-mono flex items-center gap-1 mt-0.5 whitespace-nowrap">
                    <Clock class="w-3 h-3 text-gray-400 shrink-0" />
                    <span>{{ cls.daily_schedule || '08:30 - 16:30 WIB' }}</span>
                  </p>
                </td>
                <td class="py-3.5 px-4 min-w-[200px] align-middle">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span
                      class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                      :class="cls.method === 'In-Classroom' ? 'bg-blue-50 text-[#0067AC] border border-blue-100' : cls.method === 'Virtual' ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'"
                    >
                      {{ cls.method || 'In-Classroom' }}
                    </span>
                  </div>
                  <p class="text-[11px] text-gray-600 leading-snug" :title="cls.location_or_url">
                    {{ cls.location_or_url || 'BWS Corporate Learning Center' }}
                  </p>
                </td>
                <td class="py-3.5 px-4 min-w-[200px] align-middle">
                  <p class="font-bold text-xs text-gray-900 leading-snug">{{ parseTrainer(cls.trainer_name).name }}</p>
                  <p v-if="parseTrainer(cls.trainer_name).role" class="text-[11px] text-gray-500 mt-0.5 leading-tight">
                    {{ parseTrainer(cls.trainer_name).role }}
                  </p>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap align-middle">
                  <span class="font-bold font-mono text-xs text-[#0067AC]">{{ cls.enrollments_count || 0 }}</span>
                  <span class="text-gray-400 font-mono text-xs"> / {{ cls.max_quota || 25 }}</span>
                  <span class="text-[11px] text-gray-500 ml-1 font-medium">{{ $l('Participants', 'Peserta') }}</span>
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap align-middle">
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border"
                    :class="cls.dual_control_status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : cls.dual_control_status === 'PENDING_CHECKER' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-gray-50 text-gray-700 border-gray-200'"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full shrink-0"
                      :class="cls.dual_control_status === 'APPROVED' ? 'bg-emerald-500' : cls.dual_control_status === 'PENDING_CHECKER' ? 'bg-amber-500' : 'bg-gray-400'"
                    ></span>
                    <span>{{ cls.dual_control_status === 'APPROVED' ? $l('Authorized', 'Terotorisasi') : cls.dual_control_status === 'PENDING_CHECKER' ? $l('Pending Checker', 'Menunggu Verifikator') : cls.dual_control_status }}</span>
                  </span>
                </td>
              </tr>
              <tr v-if="!program.classes || program.classes.length === 0">
                <td colspan="7" class="py-8 text-center text-gray-400">
                  {{ $l('No class batches have been opened for this program yet. Click "Open Class Batch" to add the first batch.', 'Belum ada angkatan kelas yang dibuka untuk program ini. Klik "Buka Angkatan Kelas" untuk membuka angkatan pertama.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PANEL OTORISASI: APPROVAL BY DOCUMENT vs MULTI-APPROVAL WORKFLOW -->
      
      <!-- KASUS A: APPROVAL BY DOCUMENT -->
      <div v-if="program.approval_type === 'DOCUMENT'" class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <FileCheck2 class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Official Approval Document (Approval by Document)', 'Dokumen Persetujuan Resmi (Persetujuan via Dokumen)') }}</h2>
          </div>
          <span class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 text-emerald-800">
            {{ $l('Approved via Document (Memo/Decree)', 'Disahkan via Dokumen (Memo/SK)') }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Document / Memo Number', 'Nomor Dokumen / Memo') }}</span>
            <span class="font-extrabold text-[#002855] text-sm block font-mono">{{ program.approval_doc_number || 'MEMO/BWS/2026' }}</span>
            <span class="text-gray-500 block">{{ $l('Approval Date:', 'Tgl Pengesahan:') }} {{ program.approval_doc_date ? program.approval_doc_date.substring(0, 10) : '-' }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Signing / Authorizing Official', 'Pejabat Penandatangan / Pengesah') }}</span>
            <span class="font-bold text-gray-900 text-sm block">{{ program.approval_doc_signer || $l('Board / Division Head', 'Direksi / Kepala Divisi') }}</span>
            <span class="text-emerald-700 font-semibold block">{{ $l('Valid Document Authority', 'Otoritas Dokumen Sah') }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">{{ $l('Attached Document File', 'Berkas Dokumen Lampiran') }}</span>
            <span class="font-bold text-gray-900 block truncate">{{ program.approval_doc_name || $l('Approval_Document.pdf', 'Dokumen_Pengesahan.pdf') }}</span>
            <span class="text-gray-400 font-mono block">{{ program.approval_doc_size ? formatFileSize(program.approval_doc_size) : $l('PDF File', 'Berkas PDF') }}</span>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end">
          <button
            @click="downloadDocument"
            :disabled="downloadingDoc"
            class="px-5 py-2.5 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-bold text-xs shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <Download class="w-4 h-4" :class="{ 'animate-bounce': downloadingDoc }" />
            <span>{{ downloadingDoc ? $l('Downloading Document...', 'Mengunduh Dokumen...') : $l('Download Authorization Document', 'Unduh Berkas Dokumen Pengesahan') }}</span>
          </button>
        </div>
      </div>

      <!-- KASUS B: PERSATUJUAN BERJENJANG (MULTI-APPROVAL WORKFLOW) -->
      <div v-else class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">{{ $l('Hierarchical Multi-Approval Workflow (OJK & BWS LIS Standard)', 'Alur Multi-Persetujuan Berjenjang (Standar OJK & BWS LIS)') }}</h2>
          </div>
          <span class="text-[11px] font-semibold text-gray-400">{{ $l(`Total: ${flowSteps.length} Approval Stages`, `Total: ${flowSteps.length} Tahapan Pengesahan`) }}</span>
        </div>

        <!-- Stepper Progress Bar -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div
            v-for="step in flowSteps"
            :key="step.id"
            class="relative p-4 rounded-2xl border transition-all"
            :class="[
              program.status === 'APPROVED' || (program.current_step_order > step.step_order)
                ? 'bg-emerald-50/60 border-emerald-200'
                : program.current_step_order === step.step_order
                ? 'bg-[#EBF4FC] border-[#0067AC] ring-2 ring-[#0067AC]/10'
                : 'bg-gray-50/60 border-gray-200 opacity-60'
            ]"
          >
            <div class="flex items-center justify-between mb-2">
              <span
                class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full"
                :class="[
                  program.status === 'APPROVED' || (program.current_step_order > step.step_order)
                    ? 'bg-emerald-100 text-emerald-800'
                    : program.current_step_order === step.step_order
                    ? 'bg-[#0067AC] text-white'
                    : 'bg-gray-200 text-gray-600'
                ]"
              >
                {{ $l('Step', 'Tahap') }} {{ step.step_order }}
              </span>
              <span class="text-[11px] font-medium text-gray-400">SLA: {{ step.sla_days }} {{ $l(step.sla_days === 1 ? 'Day' : 'Days', 'Hari') }}</span>
            </div>

            <h3 class="text-xs font-bold text-gray-900 mb-1 leading-snug">{{ step.step_name }}</h3>
            <p class="text-[11px] text-gray-500 font-medium">{{ $l('Role:', 'Peran:') }} {{ step.role_name }}</p>

            <div class="mt-3 pt-2 border-t border-gray-200/60 flex items-center gap-1.5 text-[11px]">
              <template v-if="program.status === 'APPROVED' || (program.current_step_order > step.step_order)">
                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                <span class="font-bold text-emerald-700">{{ $l('Approved', 'Telah Disetujui') }}</span>
              </template>
              <template v-else-if="program.current_step_order === step.step_order">
                <Clock class="w-3.5 h-3.5 text-[#0067AC] animate-spin" />
                <span class="font-bold text-[#0067AC]">{{ $l('Pending Authorization', 'Menunggu Otorisasi') }}</span>
              </template>
              <template v-else>
                <Clock class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-400">{{ $l('Next Stage', 'Tahap Berikutnya') }}</span>
              </template>
            </div>
          </div>
        </div>

        <!-- Approval History Logs Table -->
        <div class="mt-4 pt-3 border-t border-gray-100 space-y-2">
          <span class="text-xs font-bold text-gray-700 block">{{ $l('Authorization Audit Trail Log:', 'Rekam Jejak Audit Otorisasi:') }}</span>
          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
              <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                <tr>
                  <th class="py-2 px-3">{{ $l('Timestamp', 'Waktu') }}</th>
                  <th class="py-2 px-3">{{ $l('Stage', 'Tahapan') }}</th>
                  <th class="py-2 px-3">{{ $l('Authorizing Official', 'Pejabat Otorisator') }}</th>
                  <th class="py-2 px-3">{{ $l('Role', 'Peran') }}</th>
                  <th class="py-2 px-3 text-center">{{ $l('Decision', 'Keputusan') }}</th>
                  <th class="py-2 px-3">{{ $l('Notes / Comments', 'Catatan / Komentar') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="h in program.approval_histories" :key="h.id" class="hover:bg-gray-50/60">
                  <td class="py-2 px-3 font-mono text-[11px] text-gray-500">{{ h.action_at ? h.action_at.substring(0, 16).replace('T', ' ') : '-' }}</td>
                  <td class="py-2 px-3 font-semibold text-gray-800">{{ h.step_name }}</td>
                  <td class="py-2 px-3 font-medium text-gray-900">{{ h.approver_name }}</td>
                  <td class="py-2 px-3 text-gray-600">{{ h.role_name }}</td>
                  <td class="py-2 px-3 text-center">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="h.action === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : h.action === 'REJECTED' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800'"
                    >
                      {{ h.action }}
                    </span>
                  </td>
                  <td class="py-2 px-3 text-gray-600 italic">"{{ h.notes || '-' }}"</td>
                </tr>
                <tr v-if="!program.approval_histories || program.approval_histories.length === 0">
                  <td colspan="6" class="py-4 text-center text-gray-400">{{ $l('No authorization history recorded for this program yet.', 'Belum ada riwayat otorisasi untuk program ini.') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- CONTEXTUAL APPROVAL ACTION PANEL -->
        <div
          v-if="canApprove"
          class="p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-[#002855]/5 via-[#0067AC]/5 to-[#00A0E9]/5 border border-[#0067AC]/30 space-y-4 shadow-sm"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-[#002855]">
              <Sparkles class="w-4 h-4 text-[#0067AC]" />
              <span class="font-extrabold text-sm">{{ $l('Your Authorization Form', 'Formulir Otorisasi Anda') }} — {{ currentStep?.step_name || $l('Authorization', 'Otorisasi') }}</span>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#0067AC] text-white">
              {{ $l('Role:', 'Peran:') }} {{ auth.user?.role_name }}
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <button
              type="button"
              @click="approvalAction = 'APPROVE'"
              class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
              :class="approvalAction === 'APPROVE' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-emerald-50'"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>{{ $l('Approve Program', 'Setujui Program') }}</span>
            </button>

            <button
              type="button"
              @click="approvalAction = 'REQUEST_REVISION'"
              class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
              :class="approvalAction === 'REQUEST_REVISION' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-amber-50'"
            >
              <RotateCcw class="w-4 h-4" />
              <span>{{ $l('Request Revision', 'Minta Revisi Program') }}</span>
            </button>

            <button
              type="button"
              @click="approvalAction = 'REJECT'"
              class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition cursor-pointer"
              :class="approvalAction === 'REJECT' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-rose-50'"
            >
              <XCircle class="w-4 h-4" />
              <span>{{ $l('Reject Program', 'Tolak Program') }}</span>
            </button>
          </div>

          <div class="space-y-1 text-xs">
            <label class="font-bold text-gray-700">{{ $l('Authorization Notes / Budget (RAB) & Curriculum Evaluation *', 'Catatan Otorisasi / Evaluasi RAB & Kurikulum *') }}</label>
            <textarea
              v-model="approvalNotes"
              rows="3"
              required
              class="w-full bg-white rounded-xl p-3 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition"
              :placeholder="$l('Provide justification or authorization notes...', 'Berikan justifikasi atau catatan otorisasi...')"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="handleWorkflowSubmit"
              :disabled="submittingApproval"
              class="px-6 py-2.5 rounded-full text-white font-bold text-xs shadow-md transition flex items-center gap-2 disabled:opacity-50 cursor-pointer"
              :class="approvalAction === 'APPROVE' ? 'bg-emerald-600 hover:bg-emerald-700' : approvalAction === 'REJECT' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
            >
              <Send class="w-3.5 h-3.5" />
              <span>{{ submittingApproval ? $l('Processing Authorization...', 'Memproses Otorisasi...') : $l('Submit Authorization Decision', 'Kirim Keputusan Otorisasi') }}</span>
            </button>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>
