<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  CheckCircle2,
  Clock,
  AlertCircle,
  FileText,
  Building2,
  Calendar,
  Wallet,
  Users,
  ShieldCheck,
  Send,
  XCircle,
  RotateCcw,
  Printer,
  ChevronRight,
  TrendingUp,
  Award,
  Sparkles,
  Info,
  Download,
  Paperclip,
  FileCheck,
  Edit2
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  proposalId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['back', 'refresh-list', 'edit'])

const auth = useAuthStore()

const loading = ref(true)
const proposal = ref(null)
const flowSteps = ref([])
const currentStep = ref(null)
const canApprove = ref(false)
const isProposer = ref(false)

// Approval Form State
const approvalAction = ref('APPROVE') // 'APPROVE' | 'REJECT' | 'REQUEST_REVISION'
const approvalNotes = ref('')
const approvedBudget = ref(0)
const submittingApproval = ref(false)
const actionFeedback = ref({ type: '', message: '' })

const downloading = ref(false)

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

async function downloadAttachment() {
  if (!proposal.value?.id) return
  downloading.value = true
  try {
    const response = await apiClient.get(`/tna/${proposal.value.id}/attachment`, {
      responseType: 'blob'
    })
    
    // Check if JSON response (fallback or informative)
    if (response.data.type === 'application/json') {
      const text = await response.data.text()
      const json = JSON.parse(text)
      actionFeedback.value = {
        type: 'success',
        message: json.message || 'Status lampiran usulan TNA tercatat.'
      }
      return
    }

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', proposal.value.attachment_name || `Dokumen_Usulan_TNA_${proposal.value.proposal_code || 'BWS'}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: 'Gagal mengunduh berkas lampiran TNA: ' + (err.response?.data?.message || err.message)
    }
  } finally {
    downloading.value = false
  }
}

async function fetchProposalDetail() {
  loading.value = true
  actionFeedback.value = { type: '', message: '' }
  try {
    const { data } = await apiClient.get(`/tna/${props.proposalId}`)
    proposal.value = data.proposal
    flowSteps.value = data.flowSteps || []
    currentStep.value = data.currentStep
    canApprove.value = data.canApprove
    isProposer.value = data.isProposer
    approvedBudget.value = Number(data.proposal.total_budget_approved || data.proposal.total_budget_requested || 0)
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal memuat rincian usulan TNA.'
    }
  } finally {
    loading.value = false
  }
}

async function handleWorkflowSubmit() {
  if (!approvalNotes.value.trim()) {
    actionFeedback.value = { type: 'error', message: 'Wajib memberikan catatan/justifikasi untuk keputusan otorisasi.' }
    return
  }

  submittingApproval.value = true
  actionFeedback.value = { type: '', message: '' }

  try {
    const payload = {
      decision: approvalAction.value,
      notes: approvalNotes.value.trim(),
      approved_budget: approvalAction.value === 'APPROVE' ? approvedBudget.value : null
    }

    const { data } = await apiClient.post(`/tna/${props.proposalId}/workflow-action`, payload)
    actionFeedback.value = { type: 'success', message: data.message }
    approvalNotes.value = ''
    await fetchProposalDetail()
    emit('refresh-list')
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal mengeksekusi otorisasi TNA.'
    }
  } finally {
    submittingApproval.value = false
  }
}

function handlePrint() {
  window.print()
}

const statusBadgeClass = computed(() => {
  if (!proposal.value) return 'bg-gray-100 text-gray-700'
  switch (proposal.value.status) {
    case 'APPROVED':
      return 'bg-emerald-50 text-emerald-800 border-emerald-200'
    case 'REJECTED':
      return 'bg-rose-50 text-rose-800 border-rose-200'
    case 'REVISED':
      return 'bg-amber-50 text-amber-800 border-amber-200'
    case 'CHECKER_REVIEW':
      return 'bg-[#EBF4FC] text-[#0067AC] border-blue-200'
    default:
      return 'bg-sky-50 text-sky-800 border-sky-200'
  }
})

const canEditProposal = computed(() => {
  if (!proposal.value) return false
  const role = auth.user?.role_name
  const isSuperAdmin = (role === 'Super Admin')
  const isProposer = (auth.user?.id === proposal.value.proposed_by)

  // Status REVISED atau DRAFT boleh diedit oleh Pengusul, Dept Head, atau Super Admin
  if (proposal.value.status === 'REVISED' || proposal.value.status === 'DRAFT') {
    return true
  }
  // Sebelum disetujui final, Super Admin & Pengusul berhak mengedit
  if (proposal.value.status !== 'APPROVED' && (isSuperAdmin || isProposer)) {
    return true
  }
  return false
})

onMounted(() => {
  fetchProposalDetail()
})
</script>

<template>
  <div class="space-y-6">

    <!-- Top Navigation Bar & Action -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-2 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <button
          @click="emit('back')"
          class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 flex items-center justify-center transition shadow-2xs"
          title="Kembali ke Daftar TNA"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400 font-medium">TNA & Annual Training Plan</span>
            <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
            <span class="text-xs font-bold text-[#0067AC]">Rincian Dokumen Usulan</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-extrabold text-[#002855] tracking-tight">
            {{ proposal?.proposal_code || 'Memuat Dokumen...' }}
          </h1>
        </div>
      </div>

      <div class="flex items-center gap-2 self-end sm:self-auto">
        <span
          v-if="proposal"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border"
          :class="statusBadgeClass"
        >
          <span class="w-2 h-2 rounded-full" :class="proposal.status === 'APPROVED' ? 'bg-emerald-500' : proposal.status === 'REJECTED' ? 'bg-rose-500' : 'bg-[#0067AC] animate-pulse'"></span>
          {{ proposal.approval_status_badge || proposal.status }}
        </span>

        <button
          v-if="canEditProposal"
          @click="emit('edit', proposal.id)"
          class="bg-[#0067AC] hover:bg-[#00528c] text-white px-3.5 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs"
          title="Perbaiki atau edit rincian kebutuhan dan sasaran usulan ini"
        >
          <Edit2 class="w-3.5 h-3.5" />
          <span>{{ proposal.status === 'REVISED' ? 'Perbaiki & Edit Usulan' : 'Edit Usulan' }}</span>
        </button>

        <button
          @click="handlePrint"
          class="bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 px-3.5 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs"
        >
          <Printer class="w-3.5 h-3.5 text-gray-500" />
          <span>Cetak Dokumen</span>
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="bg-white rounded-3xl p-12 text-center border border-gray-100 space-y-3">
      <div class="w-10 h-10 border-4 border-[#0067AC] border-t-transparent rounded-full animate-spin mx-auto"></div>
      <p class="text-xs text-gray-500 font-medium">Memuat data rincian TNA dan alur multi-approval...</p>
    </div>

    <div v-else-if="proposal" class="space-y-6">

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
          <button @click="actionFeedback.message = ''" class="font-bold">&times;</button>
        </div>
      </div>

      <!-- Revision Required Notice Banner -->
      <div v-if="proposal.status === 'REVISED'" class="w-full">
        <div class="p-5 rounded-3xl bg-amber-50/90 border-2 border-amber-300 text-amber-950 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
          <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-2xl bg-amber-200 text-amber-900 flex items-center justify-center shrink-0 font-bold shadow-2xs">
              <RotateCcw class="w-5 h-5 text-amber-800" />
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-extrabold uppercase tracking-wider text-amber-800">Usulan Memerlukan Revisi Pengusul</span>
                <span class="text-[10px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">Revisi Ke-{{ proposal.revision_count || 1 }}</span>
              </div>
              <p class="text-xs text-amber-950 font-semibold mt-1">
                Catatan Otorisator: <span class="italic font-bold text-amber-900">"{{ proposal.rejection_notes || 'Silakan sesuaikan alokasi pagu atau rincian materi diklat sesuai arahan review.' }}"</span>
              </p>
              <p class="text-[11px] text-amber-800/90 mt-0.5">
                Anda dapat menambah, mengubah, atau menghapus baris rincian kebutuhan diklat, memperbarui anggaran, dan mengunggah dokumen baru, lalu mengajukan ulang ke tahapan Multi-Approval.
              </p>
            </div>
          </div>

          <button
            @click="emit('edit', proposal.id)"
            class="px-5 py-2.5 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md transition flex items-center gap-2 shrink-0 self-end md:self-auto cursor-pointer"
          >
            <Edit2 class="w-4 h-4" />
            <span>Perbaiki & Edit Rincian Usulan</span>
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
            <p class="text-[11px] text-gray-400 font-medium">Pagu Diusulkan</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900 font-mono">{{ formatRupiah(proposal.total_budget_requested) }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <CheckCircle2 class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Pagu Disetujui</p>
            <p class="text-base sm:text-lg font-extrabold text-[#0067AC] font-mono">
              {{ proposal.total_budget_approved ? formatRupiah(proposal.total_budget_approved) : 'Menunggu Otorisasi' }}
            </p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <Award class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Total Program Diklat</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900">{{ proposal.items?.length || 0 }} Kebutuhan Skill</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-2xs border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Target Peserta Didik</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900">
              {{ proposal.items?.reduce((sum, i) => sum + (i.target_participants_count || 0), 0) }} Pegawai BWS
            </p>
          </div>
        </div>
      </div>

      <!-- Information & Business Objective Card -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <Building2 class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">Informasi Usulan & Sasaran Strategis Perbankan</h2>
          </div>
          <span class="text-xs font-mono font-semibold text-gray-500">Tahun Buku: {{ proposal.year }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">Divisi / Unit Pemohon</span>
            <span class="font-bold text-gray-900 text-sm block">{{ proposal.department }}</span>
            <span class="text-gray-500 block">Pengusul: {{ proposal.proposer?.name || 'Dept Head' }} ({{ proposal.proposer?.nik || 'NIK N/A' }})</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">Target Periode & Prioritas</span>
            <div class="flex items-center gap-2">
              <span class="font-bold text-gray-900">{{ proposal.target_quarter }}</span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                :class="proposal.priority === 'Critical' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800'"
              >
                {{ proposal.priority }}
              </span>
            </div>
            <span class="text-gray-500 block">Tgl Pengajuan: {{ proposal.created_at?.substring(0, 10) }}</span>
          </div>

          <div class="p-3.5 rounded-2xl bg-[#F8FAFC] border border-gray-100 space-y-1">
            <span class="text-gray-400 font-medium block">Status Revisi Dokumen</span>
            <span class="font-bold text-gray-900 block">Revisi Ke-{{ proposal.revision_count || 0 }}</span>
            <span class="text-gray-500 block" v-if="proposal.rejection_notes">
              Catatan: "{{ proposal.rejection_notes }}"
            </span>
            <span class="text-gray-400 block" v-else>Tidak ada catatan revisi</span>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-[#EBF4FC]/40 border border-blue-100/60 text-xs space-y-1">
          <span class="font-bold text-[#002855] uppercase tracking-wider text-[10px] block">Latar Belakang & Sasaran Strategis Bank:</span>
          <p class="text-gray-700 leading-relaxed">
            {{ proposal.strategic_objective || 'Tidak ada sasaran strategis yang dicantumkan.' }}
          </p>
        </div>

        <!-- Dokumen Pendukung / Lampiran TOR / Memo Divisi -->
        <div class="p-4 rounded-2xl bg-white border border-gray-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs shadow-2xs">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center shrink-0">
              <Paperclip class="w-5 h-5" />
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Dokumen Lampiran Pendukung (TOR / Memo Divisi)</span>
              <p class="font-bold text-gray-900 text-xs mt-0.5">
                {{ proposal.attachment_name || 'Tidak ada dokumen pendukung terlampir' }}
              </p>
              <span class="text-[11px] text-gray-500 font-mono">
                {{ proposal.attachment_name ? (proposal.attachment_size ? 'Ukuran: ' + formatFileSize(proposal.attachment_size) : 'Format: Digital File') : 'Usulan berbasis input form langsung tanpa lampiran fisik' }}
              </span>
            </div>
          </div>
          <button
            v-if="proposal.attachment_name"
            @click="downloadAttachment"
            :disabled="downloading"
            class="px-4 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 self-end sm:self-auto disabled:opacity-50"
          >
            <Download class="w-3.5 h-3.5" :class="{ 'animate-bounce': downloading }" />
            <span>{{ downloading ? 'Mengunduh...' : 'Unduh Lampiran' }}</span>
          </button>
          <div v-else class="text-[11px] text-gray-400 italic flex items-center gap-1.5">
            <FileCheck class="w-3.5 h-3.5 text-gray-400" />
            <span>Usulan Tersimpan Lengkap</span>
          </div>
        </div>
      </div>

      <!-- TABEL RINCIAN KEBUTUHAN DIKLAT (TNA ITEMS) -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <FileText class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">Rincian Item Kebutuhan Diklat & Anggaran Tahunan</h2>
          </div>
          <div class="flex items-center gap-2">
            <button
              v-if="canEditProposal"
              @click="emit('edit', proposal.id)"
              class="px-3 py-1 rounded-full bg-[#EBF4FC] hover:bg-[#d5e9f8] text-[#0067AC] font-bold text-xs flex items-center gap-1.5 transition border border-blue-200 shadow-2xs"
              title="Perbaiki atau edit rincian kebutuhan dan anggaran"
            >
              <Edit2 class="w-3 h-3" />
              <span>Edit Rincian</span>
            </button>
            <span class="text-xs font-semibold text-gray-500 font-mono">{{ proposal.items?.length || 0 }} Kebutuhan Diklat Terdaftar</span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left border-collapse">
            <thead class="bg-[#F8FAFC] text-gray-700 font-bold border-b border-gray-200">
              <tr>
                <th class="py-3 px-3 text-center w-12">No</th>
                <th class="py-3 px-4">Kategori Induk</th>
                <th class="py-3 px-4">Sub-Kategori Diklat</th>
                <th class="py-3 px-3 text-center w-28">Kuartal Target</th>
                <th class="py-3 px-3 text-center w-32">Target Peserta</th>
                <th class="py-3 px-4 text-right w-44">Anggaran Usulan (Rp)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, idx) in proposal.items" :key="item.id" class="hover:bg-gray-50/60 transition">
                <td class="py-3.5 px-3 text-center font-bold text-gray-400 font-mono">{{ idx + 1 }}</td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: item.category?.color || '#0067AC' }"></span>
                    <span class="font-bold text-gray-900">{{ item.category_name || item.category?.name || item.job_family || 'Operations & Branch' }}</span>
                  </div>
                  <span class="text-[10px] text-gray-400 block font-mono mt-0.5 ml-4">
                    {{ item.category?.code || 'CAT-' + (idx + 1) }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-extrabold text-[#002855] text-xs block">
                    {{ item.sub_category_name || item.sub_category?.name || item.recommended_program }}
                  </span>
                  <span v-if="item.sub_category?.target_competency || item.competency_gap" class="text-[11px] text-gray-500 block mt-0.5">
                    {{ item.sub_category?.target_competency || item.competency_gap }}
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span class="px-2.5 py-1 rounded-full bg-[#EBF4FC] text-[#0067AC] text-[11px] font-bold inline-block">
                    {{ item.execution_quarter || proposal.target_quarter || 'Q1' }}
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center font-bold text-gray-900 font-mono">
                  {{ item.target_participants_count || 1 }} Pegawai
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-[#002855] text-sm">
                  {{ formatRupiah(item.budget || item.estimated_cost) }}
                </td>
              </tr>
              <tr v-if="!proposal.items || proposal.items.length === 0">
                <td colspan="6" class="py-8 text-center text-gray-400">
                  Belum ada baris kebutuhan diklat yang didaftarkan.
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-[#F8FAFC] border-t-2 border-gray-200 font-bold text-xs">
              <tr>
                <td colspan="4" class="py-3.5 px-4 text-gray-900 font-extrabold text-right">
                  Total Akumulasi Anggaran Usulan TNA (Setahun)
                </td>
                <td class="py-3.5 px-3 text-center text-gray-900 font-mono">
                  {{ proposal.items?.reduce((s, i) => s + (i.target_participants_count || 0), 0) }} Pegawai
                </td>
                <td class="py-3.5 px-4 text-right font-mono text-base text-[#0067AC] font-black">
                  {{ formatRupiah(proposal.total_budget_requested) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- MULTI-LEVEL APPROVAL STEPPER TRACKER -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-2xs space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-4 h-4 text-[#0067AC]" />
            <h2 class="text-sm font-bold text-gray-900">Alur Multi-Approval Berjenjang (Standar OJK & BWS LIS)</h2>
          </div>
          <span class="text-[11px] font-semibold text-gray-400">Total: {{ flowSteps.length }} Tahapan Pengesahan</span>
        </div>

        <!-- Visual Stepper Progress Bar -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div
            v-for="(step, idx) in flowSteps"
            :key="step.id"
            class="relative p-4 rounded-2xl border transition-all"
            :class="[
              proposal.status === 'APPROVED' || (proposal.current_step_order > step.step_order)
                ? 'bg-emerald-50/60 border-emerald-200'
                : proposal.current_step_order === step.step_order
                ? 'bg-[#EBF4FC] border-[#0067AC] ring-2 ring-[#0067AC]/10'
                : 'bg-gray-50/60 border-gray-200 opacity-60'
            ]"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full"
                :class="[
                  proposal.status === 'APPROVED' || (proposal.current_step_order > step.step_order)
                    ? 'bg-emerald-100 text-emerald-800'
                    : proposal.current_step_order === step.step_order
                    ? 'bg-[#0067AC] text-white'
                    : 'bg-gray-200 text-gray-600'
                ]"
              >
                Step {{ step.step_order }}
              </span>
              <span class="text-[11px] font-medium text-gray-400">SLA: {{ step.sla_days }} Hari</span>
            </div>

            <h3 class="text-xs font-bold text-gray-900 mb-1 leading-snug">{{ step.step_name }}</h3>
            <p class="text-[11px] text-gray-500 font-medium">Peran: {{ step.role_name }}</p>

            <div class="mt-3 pt-2 border-t border-gray-200/60 flex items-center gap-1.5 text-[11px]">
              <template v-if="proposal.status === 'APPROVED' || (proposal.current_step_order > step.step_order)">
                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600" />
                <span class="font-bold text-emerald-700">Telah Disetujui</span>
              </template>
              <template v-else-if="proposal.current_step_order === step.step_order">
                <Clock class="w-3.5 h-3.5 text-[#0067AC] animate-spin" />
                <span class="font-bold text-[#0067AC]">Menunggu Otorisasi</span>
              </template>
              <template v-else>
                <Clock class="w-3.5 h-3.5 text-gray-400" />
                <span class="text-gray-400">Tahap Berikutnya</span>
              </template>
            </div>
          </div>
        </div>

        <!-- Approval History Logs Table -->
        <div class="mt-4 pt-3 border-t border-gray-100 space-y-2">
          <span class="text-xs font-bold text-gray-700 block">Rekam Jejak Audit Otorisasi (Audit Trail Log):</span>
          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
              <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                <tr>
                  <th class="py-2 px-3">Waktu</th>
                  <th class="py-2 px-3">Tahapan</th>
                  <th class="py-2 px-3">Pejabat Otorisator</th>
                  <th class="py-2 px-3">Peran (Role)</th>
                  <th class="py-2 px-3 text-center">Keputusan</th>
                  <th class="py-2 px-3 text-right">Pagu Otorisasi</th>
                  <th class="py-2 px-3">Catatan / Komentar</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="h in proposal.approval_histories" :key="h.id" class="hover:bg-gray-50/60">
                  <td class="py-2 px-3 font-mono text-[11px] text-gray-500">{{ h.action_at ? h.action_at.substring(0, 16).replace('T', ' ') : '-' }}</td>
                  <td class="py-2 px-3 font-semibold text-gray-800">{{ h.step_name }}</td>
                  <td class="py-2 px-3 font-medium text-gray-900">{{ h.approver_name }} ({{ h.approver_nik || 'NIK' }})</td>
                  <td class="py-2 px-3 text-gray-600">{{ h.role_name }}</td>
                  <td class="py-2 px-3 text-center">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="h.action === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : h.action === 'REJECTED' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800'"
                    >
                      {{ h.action }}
                    </span>
                  </td>
                  <td class="py-2 px-3 text-right font-mono font-bold text-[#0067AC]">
                    {{ h.approved_amount ? formatRupiah(h.approved_amount) : '-' }}
                  </td>
                  <td class="py-2 px-3 text-gray-600 italic">"{{ h.notes || '-' }}"</td>
                </tr>
                <tr v-if="!proposal.approval_histories || proposal.approval_histories.length === 0">
                  <td colspan="7" class="py-4 text-center text-gray-400">Belum ada riwayat otorisasi untuk usulan ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- CONTEXTUAL APPROVAL ACTION PANEL (Active when user has authority) -->
        <div
          v-if="canApprove"
          class="p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-[#002855]/5 via-[#0067AC]/5 to-[#00A0E9]/5 border border-[#0067AC]/30 space-y-4 shadow-sm"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-[#002855]">
              <Sparkles class="w-4 h-4 text-[#0067AC]" />
              <span class="font-extrabold text-sm">Formulir Otorisasi Anda — {{ currentStep?.step_name || 'Otorisasi' }}</span>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-[#0067AC] text-white">
              Role: {{ auth.user?.role_name }}
            </span>
          </div>

          <p class="text-xs text-gray-600">
            Sebagai pejabat berwenang, silakan tinjau kelayakan usulan, sesuaikan rekomendasi pagu anggaran, dan berikan keputusan otorisasi.
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <button
              type="button"
              @click="approvalAction = 'APPROVE'"
              class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition"
              :class="approvalAction === 'APPROVE' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-emerald-50 hover:text-emerald-700'"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Setujui Usulan (Approve)</span>
            </button>

            <button
              type="button"
              @click="approvalAction = 'REQUEST_REVISION'"
              class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition"
              :class="approvalAction === 'REQUEST_REVISION' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-amber-50 hover:text-amber-700'"
            >
              <RotateCcw class="w-4 h-4" />
              <span>Minta Revisi Pengusul</span>
            </button>

            <button
              type="button"
              @click="approvalAction = 'REJECT'"
              class="p-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition"
              :class="approvalAction === 'REJECT' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-rose-50 hover:text-rose-700'"
            >
              <XCircle class="w-4 h-4" />
              <span>Tolak Usulan (Reject)</span>
            </button>
          </div>

          <!-- Pagu Adjustment (Only for APPROVE) -->
          <div v-if="approvalAction === 'APPROVE'" class="space-y-1 text-xs">
            <div class="flex items-center justify-between">
              <label class="font-bold text-gray-700">Pagu Anggaran Disetujui (Rupiah)</label>
              <span class="text-gray-400 font-mono">Pagu Awal: {{ formatRupiah(proposal.total_budget_requested) }}</span>
            </div>
            <input
              type="number"
              v-model.number="approvedBudget"
              min="0"
              class="w-full bg-white rounded-xl px-3.5 py-2.5 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition font-mono font-bold"
            />
          </div>

          <!-- Catatan / Review Notes -->
          <div class="space-y-1 text-xs">
            <label class="font-bold text-gray-700">Catatan Otorisasi / Justifikasi Kepatuhan Perbankan *</label>
            <textarea
              v-model="approvalNotes"
              rows="3"
              required
              class="w-full bg-white rounded-xl p-3 text-xs text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:outline-none transition"
              placeholder="Contoh: Kurikulum dan pagu biaya telah diverifikasi sesuai SLA BWS L&D 2026. Disetujui untuk diteruskan..."
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="handleWorkflowSubmit"
              :disabled="submittingApproval"
              class="px-6 py-2.5 rounded-full text-white font-bold text-xs shadow-md transition flex items-center gap-2 disabled:opacity-50"
              :class="approvalAction === 'APPROVE' ? 'bg-emerald-600 hover:bg-emerald-700' : approvalAction === 'REJECT' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'"
            >
              <Send class="w-3.5 h-3.5" />
              <span>{{ submittingApproval ? 'Memproses Otorisasi...' : 'Kirim Keputusan Otorisasi' }}</span>
            </button>
          </div>
        </div>

      </div>

    </div>

  </div>
</template>
