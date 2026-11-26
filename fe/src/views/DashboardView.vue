<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/client'
import { useI18n } from '@/i18n'
import {
  LayoutGrid,
  Compass,
  Layers,
  Presentation,
  Users,
  ClipboardCheck,
  Wallet,
  Award,
  BarChart3,
  KeyRound,
  Search,
  ShieldCheck,
  ShieldAlert,
  Bell,
  RefreshCw,
  ChevronDown,
  ChevronRight,
  LogOut,
  PieChart,
  FileSpreadsheet,
  FolderKanban,
  FileUp,
  BookOpenCheck,
  Send,
  CalendarClock,
  MailCheck,
  UserCheck,
  Activity,
  ListTodo,
  UploadCloud,
  Receipt,
  FilePlus2,
  CalendarPlus,
  FileCheck2,
  Stamp,
  CheckCircle,
  AlertTriangle,
  Scale,
  Calendar,
  Sliders,
  Plus,
  Download,
  FolderTree,
  CalendarRange,
  Workflow,
  Users2,
  FileText
} from 'lucide-vue-next'

// Import Modular Components
import OverviewModule from '@/components/modules/OverviewModule.vue'
import TnaModule from '@/components/modules/TnaModule.vue'
import ProgramModule from '@/components/modules/ProgramModule.vue'
import ClassModule from '@/components/modules/ClassModule.vue'
import ParticipantsModule from '@/components/modules/ParticipantsModule.vue'
import EvalModule from '@/components/modules/EvalModule.vue'
import TaskModule from '@/components/modules/TaskModule.vue'
import MemoModule from '@/components/modules/MemoModule.vue'
import TransactionModule from '@/components/modules/TransactionModule.vue'
import CertificationModule from '@/components/modules/CertificationModule.vue'
import ReportingModule from '@/components/modules/ReportingModule.vue'
import UserRbacModule from '@/components/modules/UserRbacModule.vue'
import TnaCreatePage from '@/components/modules/tna/TnaCreatePage.vue'
import TnaDetailPage from '@/components/modules/tna/TnaDetailPage.vue'
import MultiApprovalSettingModule from '@/components/modules/settings/MultiApprovalSettingModule.vue'
import CategoryMasterModule from '@/components/modules/CategoryMasterModule.vue'
import YearMasterModule from '@/components/modules/YearMasterModule.vue'
import FacilitatorMasterModule from '@/components/modules/FacilitatorMasterModule.vue'
import BiCategoryMasterModule from '@/components/modules/BiCategoryMasterModule.vue'
import LocationMasterModule from '@/components/modules/LocationMasterModule.vue'
import BusinessProcessFlowchartModule from '@/components/modules/BusinessProcessFlowchartModule.vue'

// Import Modals
import TnaModal from '@/components/modals/TnaModal.vue'
import ClassModal from '@/components/modals/ClassModal.vue'
import VoucherModal from '@/components/modals/VoucherModal.vue'
import CertificateModal from '@/components/modals/CertificateModal.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { l } = useI18n()

// Data State fetched from Backend REST API
const loading = ref(true)
const kpis = ref({})
const tnaProposals = ref([])
const trainingPrograms = ref([])
const trainingClasses = ref([])
const enrollments = ref([])
const financials = ref([])
const auditLogs = ref([])
const availableRoles = ref([])

// Alert Banner & Toast State
const flashSuccess = ref('')
const flashError = ref('')
const globalSearch = ref('')

// Navigation Tabs State (10 Modules matching Mockup)
const activeTab = ref('dashboard')
const rbacSubTab = ref('users')
const activeFiscalYear = ref(2026)

// TNA, Program, and Class View State: 'list' | 'create' | 'detail' | 'edit'
const tnaViewMode = ref('list')
const programViewMode = ref('list')
const classViewMode = ref('list')
const selectedTnaProposalId = ref(null)
const programModuleRef = ref(null)
const classModuleRef = ref(null)
const memoModuleRef = ref(null)

const isSubViewActive = computed(() => {
  if (activeTab.value === 'tna' && ['create', 'edit', 'detail'].includes(tnaViewMode.value)) return true
  if (activeTab.value === 'program' && programViewMode.value !== 'list') return true
  if (activeTab.value === 'class' && classViewMode.value !== 'list') return true
  if (activeTab.value === 'memo' && memoModuleRef.value?.viewMode !== 'list') return true
  return false
})

let isSyncingFromRoute = false

function syncStateFromRoute() {
  isSyncingFromRoute = true
  const tab = route.meta?.tab || 'dashboard'
  activeTab.value = tab

  if (tab === 'tna') {
    const view = route.meta?.view || 'list'
    tnaViewMode.value = view
    if ((view === 'detail' || view === 'edit') && route.params.id) {
      selectedTnaProposalId.value = Number(route.params.id)
    } else if (view === 'list' || view === 'create') {
      selectedTnaProposalId.value = null
    }
  } else if (tab === 'program') {
    const viewParam = route.params.view
    programViewMode.value = viewParam === 'bi-categories' ? 'bi_categories' : (viewParam || 'list')
  } else if (tab === 'class') {
    classViewMode.value = route.params.view || 'list'
  } else if (tab === 'rbac') {
    const sub = route.params.subTab || 'users'
    rbacSubTab.value = sub === 'approval-settings' ? 'approval_settings' : sub
  }

  nextTick(() => {
    isSyncingFromRoute = false
  })
}

function buildUrlFromState() {
  const tab = activeTab.value
  if (tab === 'dashboard') return '/dashboard'
  if (tab === 'tna') {
    if (tnaViewMode.value === 'create') return '/tna/create'
    if (tnaViewMode.value === 'categories') return '/tna/categories'
    if (tnaViewMode.value === 'years') return '/tna/years'
    if (tnaViewMode.value === 'detail' && selectedTnaProposalId.value) return `/tna/${selectedTnaProposalId.value}`
    if (tnaViewMode.value === 'edit' && selectedTnaProposalId.value) return `/tna/${selectedTnaProposalId.value}/edit`
    return '/tna'
  }
  if (tab === 'program') {
    if (programViewMode.value === 'create') return '/program/create'
    if (programViewMode.value === 'facilitator') return '/program/facilitator'
    if (programViewMode.value === 'bi_categories') return '/program/bi-categories'
    if (programViewMode.value === 'detail') return '/program/detail'
    if (programViewMode.value === 'edit') return '/program/edit'
    return '/program'
  }
  if (tab === 'class') {
    if (classViewMode.value === 'create') return '/class/create'
    if (classViewMode.value === 'locations') return '/class/locations'
    if (classViewMode.value === 'detail') return '/class/detail'
    if (classViewMode.value === 'edit') return '/class/edit'
    return '/class'
  }
  if (tab === 'rbac') {
    const sub = rbacSubTab.value === 'approval_settings' ? 'approval-settings' : rbacSubTab.value
    return sub && sub !== 'users' ? `/rbac/${sub}` : '/rbac'
  }
  return `/${tab}`
}

watch(() => route.fullPath, () => {
  syncStateFromRoute()
}, { immediate: true })

watch(activeTab, (newTab, oldTab) => {
  if (isSyncingFromRoute) return
  if (oldTab === 'tna' && newTab !== 'tna') {
    tnaViewMode.value = 'list'
  }
  if (oldTab === 'program' && newTab !== 'program') {
    programViewMode.value = 'list'
  }
  if (oldTab === 'class' && newTab !== 'class') {
    classViewMode.value = 'list'
  }
  if (newTab === 'rbac' && ['categories', 'years'].includes(rbacSubTab.value)) {
    rbacSubTab.value = 'users'
  }
})

watch(rbacSubTab, (val) => {
  if (['categories', 'years'].includes(val)) {
    rbacSubTab.value = 'users'
  }
})

watch([activeTab, tnaViewMode, programViewMode, classViewMode, rbacSubTab, selectedTnaProposalId], () => {
  if (isSyncingFromRoute) return
  const targetPath = buildUrlFromState()
  if (route.path !== targetPath) {
    router.push(targetPath)
  }
})

function openTnaDetail(proposalId) {
  selectedTnaProposalId.value = proposalId
  tnaViewMode.value = 'detail'
}

function openTnaEdit(proposalId) {
  selectedTnaProposalId.value = proposalId
  tnaViewMode.value = 'edit'
}

function handleTnaCreated(message) {
  flashSuccess.value = message
  if (tnaViewMode.value === 'edit') {
    tnaViewMode.value = 'detail'
  } else {
    tnaViewMode.value = 'list'
  }
  fetchDashboard()
}

function handleCreateProgram() {
  if (programModuleRef.value?.openCreate) {
    programModuleRef.value.openCreate()
  } else {
    programViewMode.value = 'create'
  }
}

function handleOpenClassCreateFromProgram(programId) {
  activeTab.value = 'class'
  setTimeout(() => {
    classModuleRef.value?.openCreate(programId)
  }, 100)
}

const navigationModules = computed(() => [
  { id: 'dashboard', name: l('LIS Dashboard', 'Dasbor LIS') },
  { id: 'tna', name: l('TNA & Plan', 'TNA & Perencanaan') },
  { id: 'program', name: l('Training Programs', 'Program Kerja') },
  { id: 'class', name: l('Class Schedule', 'Jadwal Kelas') },
  { id: 'participants', name: l('Participants', 'Peserta Pelatihan') },
  { id: 'eval', name: l('Evaluation & Scores', 'Evaluasi & Nilai') },
  { id: 'task', name: l('Tasks', 'Penugasan') },
  { id: 'memo', name: l('Memos', 'Memo Kedinasan') },
  { id: 'transaction', name: l('Transactions & Claims', 'Transaksi & Klaim Biaya') },
  { id: 'certification', name: l('Mandatory Certifications', 'Sertifikasi Wajib') },
  { id: 'reporting', name: l('Reports & Audit', 'Laporan & Audit') },
  { id: 'rbac', name: l('Users & RBAC', 'Pengguna & RBAC') }
])

const currentTabTitle = computed(() => {
  switch (activeTab.value) {
    case 'dashboard': return l('Executive & Operational Dashboard', 'Dasbor Eksekutif & Operasional')
    case 'tna':
      if (tnaViewMode.value === 'categories') return l('Training Categories Master (TNA)', 'Master Kategori Pelatihan (TNA)')
      if (tnaViewMode.value === 'years') return l('Fiscal Years & TNA Status Master', 'Master Tahun Anggaran & Status TNA')
      return l('TNA Directory', 'Daftar TNA')
    case 'program':
      if (programViewMode.value === 'facilitator') return l('Institutions & Facilitators', 'Lembaga & Fasilitator')
      if (programViewMode.value === 'bi_categories') return l('BI / OJK Regulatory Categories', 'Kategori Regulasi BI / OJK')
      return l('Program Directory', 'Daftar Program')
    case 'class':
      if (classViewMode.value === 'locations') return l('Training Rooms & Locations', 'Ruangan & Lokasi Pelatihan')
      return l('Class Directory', 'Daftar Kelas')
    case 'participants': return l('Participants & Activity Tracking', 'Peserta & Pelacakan Aktivitas')
    case 'eval': return l('Participant Evaluation & Scores', 'Evaluasi & Nilai Peserta')
    case 'task': return l('Assignments & Task Management', 'Penugasan & Manajemen Tugas')
    case 'memo': return l('Memo Directory', 'Daftar Memo')
    case 'transaction': return l('Financial Transactions & Reimbursements', 'Transaksi Keuangan & Penggantian Biaya')
    case 'certification': return l('Mandatory Certifications & Licenses', 'Sertifikasi Wajib & Lisensi Profesi')
    case 'reporting': return l('Reporting Center & Audit Trail', 'Pusat Pelaporan & Jejak Audit')
    case 'rbac':
      if (rbacSubTab.value === 'flowchart') return l('Business Process Flowchart', 'Bagan Alir Proses Bisnis')
      return l('User Management & Access Control Matrix (RBAC)', 'Manajemen Pengguna & Matriks Hak Akses (RBAC)')
    default: return l('Dashboard', 'Dasbor')
  }
})

const currentTabSubtitle = computed(() => {
  switch (activeTab.value) {
    case 'dashboard': return l('Integrated Learning & Development Operations Portal — Bank Woori Saudara', 'Portal Operasional Terpadu Pembelajaran & Pengembangan — Bank Woori Saudara')
    case 'tna':
      if (tnaViewMode.value === 'categories') return l('Manage competency clusters, training subjects, and banking curriculum standards', 'Kelola rumpun kompetensi, mata pelatihan, dan standar kurikulum perbankan')
      if (tnaViewMode.value === 'years') return l('Manage fiscal budget years, regulatory ceilings, and active cycle statuses', 'Kelola tahun buku anggaran, batas pagu regulator, dan status periode aktif')
      return l('Division Competency Needs Analysis & Annual Training Budget Proposals — Bank Woori Saudara', 'Perencanaan Kebutuhan Pelatihan & Usulan Anggaran Kompetensi Divisi — Bank Woori Saudara')
    case 'program':
      if (programViewMode.value === 'facilitator') return l('Manage training partner institutions, external vendors, and internal instructors', 'Kelola basis data mitra lembaga pelatihan, vendor eksternal, institusi, dan instruktur')
      if (programViewMode.value === 'bi_categories') return l('Manage Bank Indonesia & OJK regulatory compliance classifications', 'Kelola klasifikasi kepatuhan regulasi Bank Indonesia & Otoritas Jasa Keuangan')
      return l('Work Plan Catalog, Training Curriculum, and OJK / BI Mandatory Certifications', 'Katalog Program Kerja, Kurikulum Pelatihan, dan Sertifikasi Wajib Regulasi OJK / BI')
    case 'class': return l('Training batch execution catalog, facilitators, schedules, and operational monitoring', 'Katalog pelaksanaan angkatan kelas pelatihan BWS, fasilitator, jadwal, dan pemantauan kelas')
    case 'participants': return l('Training participant roster, attendance records, and learning progress tracking', 'Basis data peserta pelatihan, kehadiran, dan pemantauan progres pembelajaran LIS')
    case 'eval': return l('Pre-test & post-test score summary and employee competency improvement analytics', 'Rekapitulasi nilai pra-tes & pasca-tes serta analisis peningkatan kompetensi pegawai')
    case 'task': return l('Action learning project submissions and independent assignment grading', 'Pemantauan pengumpulan proyek pembelajaran aksi & penilaian tugas mandiri peserta')
    case 'memo': return l('Internal assignment letters and external L&D correspondence numbering log', 'Pencatatan nomor surat kedinasan, penugasan internal, dan korespondensi eksternal Divisi L&D')
    case 'transaction': return l('Budget realization ledger, expense claims, and Dual-Control Maker-Checker authorizations', 'Buku transaksi realisasi anggaran, klaim biaya pelatihan, dan otorisasi kendali ganda')
    case 'certification': return l('OJK & Bank Indonesia banking license compliance (Risk Management, Payment Systems, Treasury)', 'Kepatuhan lisensi perbankan OJK & Bank Indonesia (Manajemen Risiko, Sistem Pembayaran, Tresuri)')
    case 'reporting': return l('Training realization report center, tiered sign-off, and OJK audit trail logs', 'Pusat unduh laporan realisasi pelatihan, pengesahan berjenjang, dan rekam jejak audit OJK')
    case 'rbac':
      if (rbacSubTab.value === 'flowchart') return l('End-to-end business process documentation: TNA, Classes, Evaluations, Realization & Reporting', 'Dokumentasi alur proses bisnis menyeluruh: TNA & Anggaran, Kelas, Evaluasi, Realisasi & Laporan')
      return l('Employee account configuration, role hierarchy levels, and access permission matrix', 'Konfigurasi akun pegawai, tingkat peran jabatan, dan matriks kendali hak akses')
    default: return l('Integrated Learning & Development Operations Portal — Bank Woori Saudara', 'Portal Operasional Terpadu Pembelajaran & Pengembangan — Bank Woori Saudara')
  }
})

async function handleDownloadAuditCsv() {
  try {
    const response = await apiClient.get('/audit/export', { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8;' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bws_audit_trail_${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    flashError.value = l('Failed to download audit trail log file.', 'Gagal mengunduh berkas log jejak audit.')
  }
}

async function fetchDashboard() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/dashboard')
    kpis.value = data.kpis
    tnaProposals.value = data.tnaProposals
    trainingPrograms.value = data.trainingPrograms
    trainingClasses.value = data.trainingClasses
    enrollments.value = data.enrollments
    financials.value = data.financials
    auditLogs.value = data.auditLogs
    availableRoles.value = data.availableRoles

    // Fetch active fiscal year
    try {
      const { data: yearData } = await apiClient.get('/fiscal-years')
      if (yearData.active_year) {
        activeFiscalYear.value = yearData.active_year
      }
    } catch (_) {}
  } catch (err) {
    flashError.value = err.response?.data?.message || l('Failed to load operational dashboard data.', 'Gagal memuat data operasional dasbor.')
  } finally {
    loading.value = false
  }
}

// Role Switcher & Profile Dropdown
const showRoleDropdown = ref(false)
const showProfileDropdown = ref(false)
const roleDropdownRef = ref(null)
const profileDropdownRef = ref(null)

function handleOutsideClick(e) {
  if (roleDropdownRef.value && !roleDropdownRef.value.contains(e.target)) {
    showRoleDropdown.value = false
  }
  if (profileDropdownRef.value && !profileDropdownRef.value.contains(e.target)) {
    showProfileDropdown.value = false
  }
}

onMounted(() => {
  fetchDashboard()
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
})

async function handleSwitchRole(roleName) {
  showRoleDropdown.value = false
  showProfileDropdown.value = false
  flashError.value = ''
  try {
    const res = await auth.switchRole(roleName)
    flashSuccess.value = res.message
    await fetchDashboard()
  } catch (err) {
    flashError.value = err.response?.data?.message || l('Failed to switch user role.', 'Gagal mengganti peran pengguna.')
  }
}

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}

// Notification handler
function handleNotificationClick() {
  if (totalPendingApprovalsForUser.value > 0) {
    flashSuccess.value = l(
      'You have {count} operational submissions awaiting your Multi-Approval action.',
      'Terdapat {count} pengajuan operasional yang membutuhkan tindakan otorisasi berjenjang Anda.',
      { count: totalPendingApprovalsForUser.value }
    )
  } else if (kpis.value.pending_checker_approvals > 0) {
    flashSuccess.value = l(
      'There are {count} pending approval items in the system queue.',
      'Terdapat {count} total antrean persetujuan pada sistem.',
      { count: kpis.value.pending_checker_approvals }
    )
  } else {
    flashSuccess.value = l(
      'All operational queues are currently synchronized and OJK compliant.',
      'Semua antrean operasional saat ini dalam status sinkron dan sesuai ketentuan OJK.'
    )
  }
}

// Modals State
const showTnaModal = ref(false)
const showClassModal = ref(false)
const showVoucherModal = ref(false)
const showCertModal = ref(false)
const activeCertData = ref({})

function openModal(name) {
  if (name === 'modal-class') showClassModal.value = true
  else if (name === 'modal-transaction') showVoucherModal.value = true
  else if (name === 'modal-tna') showTnaModal.value = true
  else if (name === 'modal-certification') activeTab.value = 'certification'
  else if (name === 'modal-report') activeTab.value = 'reporting'
  else if (name === 'modal-task') activeTab.value = 'task'
}

function handleViewCertificate(cert) {
  activeCertData.value = cert
  showCertModal.value = true
}

function handleModalSubmitted(message) {
  flashSuccess.value = message
  fetchDashboard()
}

// Dual-Control Approvals via API
async function handleApproveClass(cls) {
  flashError.value = ''
  try {
    const { data } = await apiClient.post(`/classes/${cls.id}/approve`, {
      decision: 'APPROVED',
      checker_notes: l('Approved by Checker via Operations Dashboard.', 'Disetujui oleh Pemeriksa melalui Dasbor Operasional.')
    })
    flashSuccess.value = data.message
    await fetchDashboard()
  } catch (err) {
    flashError.value = err.response?.data?.message || l('Batch authorization failed.', 'Otorisasi angkatan kelas ditolak.')
  }
}

async function handleApproveVoucher(voucher) {
  flashError.value = ''
  try {
    const { data } = await apiClient.post(`/transactions/${voucher.id}/approve`, {
      decision: 'APPROVED',
      checker_notes: l('Disbursement approved by Checker.', 'Otorisasi pencairan disetujui Pemeriksa.')
    })
    flashSuccess.value = data.message
    await fetchDashboard()
  } catch (err) {
    flashError.value = err.response?.data?.message || l('Voucher authorization failed.', 'Otorisasi voucher ditolak.')
  }
}

function getInitials(name) {
  if (!name) return 'BW'
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
}

const isCheckerRole = computed(() => {
  return auth.user?.role_name === 'HC Learning Head (Checker)' || auth.user?.role_name === 'Super Admin'
})

const canCreateClass = computed(() => {
  return auth.user?.role_name === 'HC Training (Maker)' || auth.user?.role_name === 'Super Admin'
})

const canManageTna = computed(() => {
  return auth.user?.role_name === 'Dept Head' || auth.user?.role_name === 'HCBP' || auth.user?.role_name === 'Super Admin'
})

// Dynamic Multi-Approval Queue breakdown by User Role
const pendingTnaForUser = computed(() => {
  const role = auth.user?.role_name
  if (role === 'Super Admin') {
    return tnaProposals.value.filter(t => ['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW'].includes(t.status))
  } else if (role === 'HCBP') {
    return tnaProposals.value.filter(t => t.status === 'SUBMITTED')
  } else if (role === 'HC Learning Head (Checker)') {
    return tnaProposals.value.filter(t => t.status === 'CHECKER_REVIEW')
  } else if (role === 'Executive') {
    return tnaProposals.value.filter(t => t.status === 'APPROVER_REVIEW')
  } else if (role === 'Dept Head') {
    return tnaProposals.value.filter(t => t.status === 'REVISION')
  }
  return []
})

const pendingClassesForUser = computed(() => {
  const role = auth.user?.role_name
  if (role === 'Super Admin' || role === 'HC Learning Head (Checker)') {
    return trainingClasses.value.filter(c => c.dual_control_status === 'PENDING_CHECKER')
  }
  return []
})

const pendingFinancialsForUser = computed(() => {
  const role = auth.user?.role_name
  if (role === 'Super Admin' || role === 'HC Learning Head (Checker)') {
    return financials.value.filter(f => f.status === 'PENDING_APPROVAL')
  }
  return []
})

const totalPendingApprovalsForUser = computed(() => {
  return pendingTnaForUser.value.length + pendingClassesForUser.value.length + pendingFinancialsForUser.value.length
})
</script>

<template>
  <div class="min-h-screen bg-[#f4f6f5] text-gray-900 flex flex-col font-sans selection:bg-[#00A0E9] selection:text-white">

    <!-- Full-Screen Layout Wrapper -->
    <div class="w-full min-h-screen px-4 py-3 md:px-6 md:py-4 flex flex-col gap-5">

      <!-- ========================================================== -->
      <!-- CLEAN TOP NAVBAR (BWS CORPORATE BRANDING)                   -->
      <!-- ========================================================== -->
      <header class="bg-white rounded-full px-5 py-2.5 flex items-center justify-between shadow-sm sticky top-2 z-40 border border-gray-100 relative">
        
        <!-- BWS Signature Corporate Wave Accent (Supergraphic Pemanis - Ultra-Ringan 0ms Lag) -->
        <div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
          <div class="absolute right-0 top-0 bottom-0 w-72 md:w-96 select-none opacity-25">
            <svg class="w-full h-full object-cover" viewBox="0 0 380 60" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
              <!-- Curve 1: Sky Cyan / Sunrise Gradation tint -->
              <path d="M 60 60 C 140 60 190 8 380 0 L 380 60 Z" fill="#00A0E9" />
              <!-- Curve 2: BWS Main Royal Blue (Pantone 7462 PC #0067AC) -->
              <path d="M 140 60 C 210 60 250 16 380 6 L 380 60 Z" fill="#0067AC" />
            </svg>
          </div>
        </div>

        <!-- Left: Official BWS Logo + LIS Information -->
        <div class="flex items-center gap-3.5 cursor-pointer shrink-0 group relative z-10" @click="activeTab = 'dashboard'">
          <!-- Official Bank Woori Saudara Primary Logo (Resmi) -->
          <img src="/images/bws-logo-primary.png" alt="Bank Woori Saudara" class="h-8 sm:h-9 w-auto object-contain transition group-hover:scale-105" />

          <!-- Divider -->
          <div class="h-7 w-px bg-slate-200 hidden sm:block"></div>

          <!-- Right: LIS Information -->
          <div class="leading-tight">
            <span class="font-extrabold text-sm sm:text-base tracking-tight text-[#002855] group-hover:text-[#0067AC] transition block">
              LIS
            </span>
            <span class="text-[10px] text-slate-500 font-medium block">Learning Integrated System</span>
          </div>
        </div>

        <!-- Center: Global Search Bar -->
        <div class="flex-1 max-w-md mx-4 lg:mx-8 relative hidden md:block z-10">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="globalSearch"
            type="text"
            :placeholder="$l('Search TNA, Programs, Classes, Participants...', 'Cari TNA, Program, Kelas, Peserta...')"
            class="w-full bg-[#f4f6f5] hover:bg-gray-100 focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition shadow-inner"
          />
        </div>

        <!-- Right: Language Switcher + Refresh + Role Switcher + Notification + Profile -->
        <div class="flex items-center gap-2.5 shrink-0 relative z-10">
          
          <!-- Language Switcher (EN / ID) -->
          <LanguageSwitcher />

          <!-- Refresh Button -->
          <button
            @click="fetchDashboard"
            class="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-blue-50 hover:text-[#0067AC] text-gray-500 flex items-center justify-center transition"
            :title="$t('common.refresh')"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Role Switcher Pill -->
          <div ref="roleDropdownRef" class="relative">
            <button
              @click="showRoleDropdown = !showRoleDropdown"
              class="flex items-center gap-1.5 bg-[#f4f6f5] hover:bg-gray-100 px-3 py-1.5 rounded-full border border-gray-200/80 transition text-xs font-semibold text-gray-700"
              :title="$l('Switch User Role', 'Ganti Peran Pengguna')"
            >
              <ShieldCheck class="w-3.5 h-3.5 text-[#0067AC] shrink-0" />
              <span class="truncate max-w-[130px]">{{ auth.user?.role_name || 'L&D Manager' }}</span>
              <ChevronDown class="w-3 h-3 text-gray-400" />
            </button>

            <!-- Role Dropdown -->
            <div
              v-if="showRoleDropdown"
              class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-2xl shadow-xl p-2 z-50 space-y-1 animate-scaleIn"
            >
              <div class="px-3 py-1.5 border-b border-gray-100">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $l('Banking Roles (8 Roles)', 'Peran Perbankan (8 Peran)') }}</span>
              </div>
              <button
                v-for="r in availableRoles"
                :key="r"
                @click="handleSwitchRole(r)"
                class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between"
                :class="auth.user?.role_name === r ? 'bg-[#EBF4FC] text-[#0067AC] font-bold' : 'text-gray-700 hover:bg-gray-50'"
              >
                <span>{{ r }}</span>
                <span v-if="auth.user?.role_name === r" class="w-1.5 h-1.5 rounded-full bg-[#0067AC]"></span>
              </button>
            </div>
          </div>

          <!-- Notification Bell -->
          <button
            @click="handleNotificationClick"
            class="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-gray-200 flex items-center justify-center text-gray-600 transition relative"
            :title="$l('Notifications', 'Notifikasi')"
          >
            <Bell class="w-4 h-4" />
            <span
              v-if="kpis.pending_checker_approvals > 0"
              class="w-2 h-2 rounded-full bg-[#0067AC] absolute top-1.5 right-1.5"
            ></span>
          </button>

          <!-- Profile Pill -->
          <div ref="profileDropdownRef" class="relative">
            <div
              @click="showProfileDropdown = !showProfileDropdown"
              class="flex items-center gap-2 pl-1 cursor-pointer group"
              :title="$l('View Profile & Role Matrix', 'Lihat Profil & Matriks Peran')"
            >
              <div class="w-8 h-8 rounded-full bg-[#EBF4FC] text-[#0067AC] font-bold text-xs flex items-center justify-center ring-2 ring-[#0067AC]/30">
                {{ getInitials(auth.user?.name) }}
              </div>
              <div class="hidden sm:block text-left leading-tight">
                <p class="text-xs font-bold text-gray-900 group-hover:text-[#0067AC] transition">
                  {{ auth.user?.name || 'Budi Santoso' }}
                </p>
                <p class="text-[10px] text-gray-400 font-medium">
                  {{ auth.user?.role_name || 'L&D Manager' }}
                </p>
              </div>
              <ChevronDown class="w-3.5 h-3.5 text-gray-400 hidden sm:block" />
            </div>

            <!-- Profile Dropdown -->
            <div
              v-if="showProfileDropdown"
              class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-2xl shadow-xl p-2 z-50 space-y-1 animate-scaleIn"
            >
              <div class="px-3 py-2 border-b border-gray-100">
                <p class="text-xs font-bold text-gray-900">{{ auth.user?.name }}</p>
                <p class="text-[11px] text-gray-500 font-mono">{{ auth.user?.email }}</p>
                <p class="text-[10px] text-[#0067AC] font-semibold mt-1">NIK: {{ auth.user?.nik || 'BWS-2026-001' }}</p>
              </div>
              <button
                @click="handleLogout"
                class="w-full text-left px-3 py-2 rounded-xl text-xs text-rose-600 hover:bg-rose-50 font-semibold transition flex items-center gap-2"
              >
                <LogOut class="w-3.5 h-3.5" />
                <span>{{ $l('Sign Out', 'Keluar dari Portal') }}</span>
              </button>
            </div>
          </div>

          <!-- Quick Logout Button -->
          <button
            @click="handleLogout"
            class="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-rose-50 hover:text-rose-600 text-gray-500 flex items-center justify-center transition"
            :title="$l('Sign out of portal', 'Keluar dari portal')"
          >
            <LogOut class="w-3.5 h-3.5" />
          </button>

        </div>
      </header>

      <!-- Mobile Navigation Bar (Pill Bar for Small Screens) -->
      <div class="md:hidden overflow-x-auto flex items-center gap-1.5 p-1 bg-white rounded-2xl shadow-xs border border-gray-100">
        <button
          v-for="m in navigationModules"
          :key="m.id"
          @click="activeTab = m.id"
          class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition shrink-0"
          :class="activeTab === m.id ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
        >
          {{ m.name }}
        </button>
      </div>

      <!-- ========================================================== -->
      <!-- MAIN BODY: LEFT FLOATING SIDEBAR + FULL WIDTH CONTENT      -->
      <!-- ========================================================== -->
      <div class="flex-1 flex gap-5 items-start">

        <!-- Left Floating Pill Sidebar (10 Modul End-to-End + Hover Submenu) -->
        <aside class="hidden md:flex flex-col justify-between items-center sticky top-20 z-30 shrink-0">
          <div class="flex flex-col gap-3">

            <!-- Pill Group 1: Planning & Implementation (Modul 1 - 5) -->
            <div class="bg-white rounded-full p-2 flex flex-col gap-2 shadow-sm items-center border border-gray-100">

              <!-- Dashboard -->
              <div class="relative group">
                <button
                  @click="activeTab = 'dashboard'"
                  id="side-dashboard"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition shadow-xs"
                  :class="activeTab === 'dashboard' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Executive & Operational Dashboard', 'Dasbor Eksekutif & Operasional')"
                >
                  <LayoutGrid class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-60">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">{{ $l('LIS Dashboard', 'Dasbor LIS') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'dashboard'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'dashboard' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <PieChart class="w-3.5 h-3.5" />
                            {{ $l('Executive & Process Flow', 'Dasbor Eksekutif & Alur Proses') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5" />
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'reporting'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-gray-900 transition"
                        >
                          <FileSpreadsheet class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('All Training Reports', 'Semua Laporan Pelatihan') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- TNA / Training Plan -->
              <div class="relative group">
                <button
                  @click="activeTab = 'tna'"
                  id="side-tna"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'tna' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('TNA & Training Plan', 'TNA & Perencanaan Pelatihan')"
                >
                  <Compass class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5 flex items-center justify-between">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">{{ $l('TNA & Training Plan', 'TNA & Perencanaan') }}</span>
                      <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC]">{{ $l('Plan', 'Rencana') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'tna'; tnaViewMode = 'list'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'tna' && tnaViewMode === 'list' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <FolderKanban class="w-3.5 h-3.5" />
                            {{ $l('TNA Directory', 'Daftar TNA') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>

                      <!-- Section Header: Master Data -->
                      <li class="pt-2 pb-1 px-3 border-t border-gray-100 mt-1 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $l('Master Data', 'Data Master') }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'tna'; tnaViewMode = 'categories'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'tna' && tnaViewMode === 'categories' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <FolderTree class="w-3.5 h-3.5" />
                            {{ $l('Training Categories', 'Kategori Pelatihan') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'tna'; tnaViewMode = 'years'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'tna' && tnaViewMode === 'years' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <CalendarRange class="w-3.5 h-3.5" />
                            {{ $l('Fiscal Years', 'Tahun Anggaran') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Program L&D -->
              <div class="relative group">
                <button
                  @click="activeTab = 'program'; programViewMode = 'list'"
                  id="side-program"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'program' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Training Programs', 'Program Pelatihan')"
                >
                  <Layers class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5 flex items-center justify-between">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">{{ $l('Training Programs', 'Program Pelatihan') }}</span>
                      <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC]">{{ $l('Work Plan', 'Rencana Kerja') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'program'; programViewMode = 'list'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'program' && programViewMode === 'list' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <BookOpenCheck class="w-3.5 h-3.5" />
                            {{ $l('Program Directory', 'Daftar Program') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>

                      <!-- Section Header: Master Data -->
                      <li class="pt-2 pb-1 px-3 border-t border-gray-100 mt-1 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $l('Master Data', 'Data Master') }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'program'; programViewMode = 'facilitator'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'program' && programViewMode === 'facilitator' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <Users2 class="w-3.5 h-3.5" />
                            {{ $l('Facilitators & Partners', 'Fasilitator & Lembaga') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'program'; programViewMode = 'bi_categories'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'program' && programViewMode === 'bi_categories' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <Scale class="w-3.5 h-3.5" />
                            {{ $l('BI Categories', 'Kategori Regulasi BI') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Class Implementation -->
              <div class="relative group">
                <button
                  @click="activeTab = 'class'; classViewMode = 'list'"
                  id="side-class"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'class' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Class & Implementation', 'Kelas & Pelaksanaan')"
                >
                  <Presentation class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5 flex items-center justify-between">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">{{ $l('Class & Implementation', 'Kelas & Pelaksanaan') }}</span>
                      <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC]">{{ $l('Batch', 'Angkatan') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'class'; classViewMode = 'list'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'class' && classViewMode === 'list' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <Presentation class="w-3.5 h-3.5" />
                            {{ $l('Class Directory', 'Daftar Kelas') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'class'; classViewMode = 'create'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'class' && classViewMode === 'create' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <Plus class="w-3.5 h-3.5" />
                            {{ $l('Create New Class', 'Buat Kelas Baru') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>

                      <!-- Section Header: Master Data -->
                      <li class="pt-2 pb-1 px-3 border-t border-gray-100 mt-1 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ $l('Master Data', 'Data Master') }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'class'; classViewMode = 'locations'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'class' && classViewMode === 'locations' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <span class="flex items-center gap-2">
                            <MapPin class="w-3.5 h-3.5" />
                            {{ $l('Rooms & Locations', 'Ruangan & Lokasi') }}
                          </span>
                          <ChevronRight class="w-3.5 h-3.5 text-gray-400" />
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Participants, Evaluation & Tracking -->
              <div class="relative group">
                <button
                  @click="activeTab = 'participants'"
                  id="side-participants"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="(activeTab === 'participants' || activeTab === 'eval') ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Participants & Evaluation', 'Peserta & Evaluasi')"
                >
                  <Users class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">{{ $l('Participants & Evaluation', 'Peserta & Evaluasi') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'participants'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'participants' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <UserCheck class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Participants & Assessments', 'Peserta & Ujian Pelatihan') }}
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'eval'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'eval' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <Activity class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Evaluation & Score Analytics', 'Evaluasi & Rekap Nilai') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

            </div>

            <!-- Pill Group 2: Task, Transaction, Certification, Reporting -->
            <div class="bg-white rounded-full p-2 flex flex-col gap-2 shadow-sm items-center border border-gray-100">

              <!-- Task Management -->
              <div class="relative group">
                <button
                  @click="activeTab = 'task'"
                  id="side-task"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'task' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Task Management', 'Manajemen Penugasan')"
                >
                  <ClipboardCheck class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">{{ $l('Tasks & Assignments', 'Penugasan Peserta') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'task'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'task' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <ListTodo class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Task Monitoring & Approval', 'Pemantauan & Persetujuan Tugas') }}
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'task'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <UploadCloud class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Create Task / Submit Work', 'Buat Tugas / Unggah Hasil') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Memo (Internal & Eksternal) -->
              <div class="relative group">
                <button
                  @click="activeTab = 'memo'"
                  id="side-memo"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'memo' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Official Memos (Internal / External)', 'Memo Kedinasan (Internal / Eksternal)')"
                >
                  <FileText class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5 flex items-center justify-between">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">{{ $l('Official Memos', 'Memo Kedinasan') }}</span>
                      <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC]">{{ $l('Correspondence', 'Surat') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'memo'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'memo' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <FileSpreadsheet class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Memo Directory (Internal & External)', 'Daftar Memo (Internal & Eksternal)') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Transaction & Reimbursement -->
              <div class="relative group">
                <button
                  @click="activeTab = 'transaction'"
                  id="side-transaction"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition relative"
                  :class="activeTab === 'transaction' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Transactions & Reimbursements', 'Transaksi & Penggantian Biaya')"
                >
                  <Wallet class="w-4 h-4" />
                  <span
                    v-if="financials.length > 0"
                    class="w-2 h-2 rounded-full bg-amber-500 absolute top-2 right-2 ring-2 ring-white"
                  ></span>
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">{{ $l('Transactions & Payments', 'Transaksi & Pembayaran') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'transaction'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'transaction' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <Receipt class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Claims & Vendor Payments', 'Klaim & Pembayaran Vendor') }}
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-transaction')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <FilePlus2 class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Submit Expense Claim', 'Ajukan Klaim Biaya Baru') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Certification (Risk, SKSP, Treasury) -->
              <div class="relative group">
                <button
                  @click="activeTab = 'certification'"
                  id="side-certification"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'certification' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Certifications & Mandatory Exams', 'Sertifikasi & Ujian Wajib')"
                >
                  <Award class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">{{ $l('Certifications & Exams', 'Sertifikasi & Ujian') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'certification'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'certification' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <ShieldAlert class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Target vs. Realization (Risk, SKSP, Treasury)', 'Target vs Realisasi (Risiko, SKSP, Tresuri)') }}
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'certification'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <CalendarPlus class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Monthly Fulfillment & Exam Updates', 'Pembaruan Realisasi & Ujian') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Reporting Center -->
              <div class="relative group">
                <button
                  @click="activeTab = 'reporting'"
                  id="side-reporting"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'reporting' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  :title="$l('Reporting Center & Sign-Off', 'Pusat Pelaporan & Pengesahan')"
                >
                  <BarChart3 class="w-4 h-4" />
                </button>
                <div class="absolute left-full bottom-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">{{ $l('Reporting Center', 'Pusat Pelaporan') }}</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'reporting'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'reporting' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <FileCheck2 class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('All Training Reports (Class / Annual)', 'Semua Laporan Pelatihan (Kelas / Tahunan)') }}
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'reporting'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <Stamp class="w-3.5 h-3.5 text-gray-400" />
                          {{ $l('Tiered Sign-Off Approvals', 'Pengesahan Berjenjang') }}
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <!-- Pill Group 3: User & RBAC Management -->
          <div class="bg-white rounded-full p-2 flex flex-col gap-2 shadow-sm items-center mt-3 border border-gray-100">
            <div class="relative group">
              <button
                @click="activeTab = 'rbac'"
                id="side-rbac"
                class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                :class="activeTab === 'rbac' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                :title="$l('User & RBAC Matrix Management', 'Kelola Pengguna & Matriks RBAC')"
              >
                <KeyRound class="w-4 h-4" />
              </button>
              <div class="absolute left-full bottom-0 pl-3 hidden group-hover:block z-50">
                <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                  <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">{{ $l('Users & RBAC', 'Pengguna & RBAC') }}</span>
                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC]">LDMS</span>
                  </div>
                  <ul class="space-y-1 text-xs font-medium text-gray-600">
                    <li>
                      <button
                        @click="activeTab = 'rbac'; rbacSubTab = 'users'"
                        class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                        :class="activeTab === 'rbac' && rbacSubTab === 'users' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                      >
                        <span class="flex items-center gap-2">
                          <Users class="w-3.5 h-3.5" />
                          {{ $l('User Directory', 'Daftar Pengguna') }}
                        </span>
                        <ChevronRight class="w-3.5 h-3.5" />
                      </button>
                    </li>
                    <li>
                      <button
                        @click="activeTab = 'rbac'; rbacSubTab = 'karyawan'"
                        class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                        :class="activeTab === 'rbac' && rbacSubTab === 'karyawan' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                      >
                        <span class="flex items-center gap-2">
                          <UserCheck class="w-3.5 h-3.5" />
                          {{ $l('Employee Master (HR)', 'Master Karyawan (SDM)') }}
                        </span>
                        <ChevronRight class="w-3.5 h-3.5" />
                      </button>
                    </li>
                    <li>
                      <button
                        @click="activeTab = 'rbac'; rbacSubTab = 'approvals'"
                        class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                        :class="activeTab === 'rbac' && rbacSubTab === 'approvals' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                      >
                        <span class="flex items-center gap-2">
                          <Sliders class="w-3.5 h-3.5" />
                          {{ $l('Multi-Approval Settings', 'Pengaturan Multi-Persetujuan') }}
                        </span>
                        <ChevronRight class="w-3.5 h-3.5" />
                      </button>
                    </li>
                    <li>
                      <button
                        @click="activeTab = 'rbac'; rbacSubTab = 'flowchart'"
                        class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                        :class="activeTab === 'rbac' && rbacSubTab === 'flowchart' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                      >
                        <span class="flex items-center gap-2">
                          <Workflow class="w-3.5 h-3.5 text-[#0067AC]" />
                          {{ $l('Business Process Flowchart', 'Bagan Alir Proses Bisnis') }}
                        </span>
                        <ChevronRight class="w-3.5 h-3.5" />
                      </button>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

        </aside>

        <!-- ========================================================== -->
        <!-- RIGHT CONTENT AREA (FULL WIDTH, DYNAMIC VIEWS)             -->
        <!-- ========================================================== -->
        <div class="flex-1 min-w-0 flex flex-col gap-5">

          <!-- Page Sub-header (Title + Contextual Quick Actions) -->
          <div v-if="!isSubViewActive" id="page-subheader" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 px-1">
            <div>
              <h1 id="page-title" class="text-2xl md:text-3xl font-bold tracking-tight text-gray-900">
                {{ currentTabTitle }}
              </h1>
              <p id="page-subtitle" class="text-xs text-gray-400 mt-0.5">
                {{ currentTabSubtitle }}
              </p>
            </div>

            <!-- Contextual Quick Action Buttons / Sub-Tabs per Module -->
            <!-- 1. Executive Dashboard: General Fast Actions -->
            <div v-if="activeTab === 'dashboard'" class="flex flex-wrap items-center gap-2">
              <button
                @click="activeTab = 'tna'; tnaViewMode = 'years'"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-700 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
                :title="$l('Click to manage Fiscal Year master settings', 'Klik untuk mengelola Master Tahun Anggaran')"
              >
                <Calendar class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>{{ $l('Fiscal Year:', 'Tahun Buku:') }} {{ activeFiscalYear }}</span>
              </button>
              <button
                v-if="canCreateClass"
                @click="showClassModal = true"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-800 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Send class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>{{ $l('Send Class Invitation', 'Kirim Undangan Kelas') }}</span>
              </button>
              <button
                @click="showVoucherModal = true"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition"
              >
                <Receipt class="w-3.5 h-3.5" />
                <span>{{ $l('Submit Expense Claim', 'Klaim Biaya') }}</span>
              </button>
            </div>

            <!-- 2. TNA Module Header Actions -->
            <div v-else-if="activeTab === 'tna'" class="flex items-center gap-2">
              <button
                v-if="canManageTna && tnaViewMode === 'list'"
                @click="tnaViewMode = 'create'"
                class="bg-[#0067AC] hover:bg-[#00528c] text-white px-4 py-2 rounded-full text-xs font-bold flex items-center gap-1.5 shadow-xs transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $l('New TNA Proposal', 'Usulan TNA Baru') }}</span>
              </button>
            </div>

            <!-- 3. Training Programs -->
            <div v-else-if="activeTab === 'program'" class="flex items-center gap-2">
              <button
                v-if="programViewMode === 'list'"
                @click="handleCreateProgram"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $l('Create New Program', 'Buat Program Baru') }}</span>
              </button>
            </div>

            <!-- 4. Class Implementation -->
            <div v-else-if="activeTab === 'class'" class="flex items-center gap-2">
              <button
                v-if="canCreateClass && classViewMode === 'list'"
                @click="classModuleRef?.openCreate()"
                class="bg-[#0067AC] hover:bg-[#00528c] text-white px-4 py-2 rounded-full text-xs font-bold flex items-center gap-1.5 shadow-xs transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $l('Create New Class', 'Buat Kelas Baru') }}</span>
              </button>
            </div>

            <!-- Memo Module Header Action -->
            <div v-else-if="activeTab === 'memo'" class="flex items-center gap-2">
              <button
                v-if="memoModuleRef?.viewMode === 'list'"
                @click="memoModuleRef?.openCreateMemo('INTERNAL')"
                class="bg-[#0067AC] hover:bg-[#00528c] text-white px-4 py-2 rounded-full text-xs font-bold flex items-center gap-1.5 shadow-xs transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $l('Create New Memo', 'Buat Memo Baru') }}</span>
              </button>
            </div>

            <!-- 5. Financial Transactions -->
            <div v-else-if="activeTab === 'transaction'" class="flex items-center gap-2">
              <button
                @click="showVoucherModal = true"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition"
              >
                <Receipt class="w-3.5 h-3.5" />
                <span>{{ $l('Create Voucher / Claim', 'Buat Voucher / Klaim') }}</span>
              </button>
            </div>

            <!-- 6. Reporting & Audit -->
            <div v-else-if="activeTab === 'reporting'" class="flex items-center gap-2">
              <button
                @click="handleDownloadAuditCsv"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-800 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Download class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>{{ $l('Download Audit CSV', 'Unduh Log Audit CSV') }}</span>
              </button>
            </div>

            <!-- 7. RBAC Module Sub-Tabs -->
            <div v-else-if="activeTab === 'rbac'" class="flex items-center gap-1.5 p-1 bg-white border border-gray-100 rounded-full shadow-2xs self-start lg:self-auto overflow-x-auto">
              <button
                @click="rbacSubTab = 'users'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'users' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <Users class="w-3.5 h-3.5" />
                <span>{{ $l('Manage Users', 'Kelola Pengguna') }}</span>
              </button>

              <button
                @click="rbacSubTab = 'karyawan'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'karyawan' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <UserCheck class="w-3.5 h-3.5" />
                <span>{{ $l('Employee Master', 'Master Karyawan') }}</span>
              </button>

              <button
                @click="rbacSubTab = 'levels'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'levels' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <ShieldCheck class="w-3.5 h-3.5" />
                <span>{{ $l('Role Levels', 'Tingkat Peran') }}</span>
              </button>

              <button
                @click="rbacSubTab = 'matrix'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'matrix' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <Sliders class="w-3.5 h-3.5" />
                <span>{{ $l('Access Matrix (RBAC)', 'Matriks Hak Akses (RBAC)') }}</span>
              </button>

              <button
                @click="rbacSubTab = 'approvals'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'approvals' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <FileCheck2 class="w-3.5 h-3.5" />
                <span>{{ $l('Multi-Approval Rules', 'Pengaturan Persetujuan') }}</span>
              </button>

              <button
                @click="rbacSubTab = 'flowchart'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'flowchart' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <Workflow class="w-3.5 h-3.5" />
                <span>{{ $l('Process Flowchart', 'Bagan Proses Bisnis') }}</span>
              </button>
            </div>
          </div>

          <!-- Alerts Banner -->
          <div v-if="flashSuccess" class="w-full">
            <div class="bg-[#EBF4FC] border border-blue-200 px-4 py-2.5 rounded-2xl text-xs text-[#0067AC] flex items-center justify-between shadow-2xs">
              <div class="flex items-center space-x-2">
                <CheckCircle class="w-4 h-4 text-[#0067AC] shrink-0" />
                <span>{{ flashSuccess }}</span>
              </div>
              <button @click="flashSuccess = ''" class="text-[#0067AC] hover:text-blue-900 font-bold">&times;</button>
            </div>
          </div>

          <div v-if="flashError" class="w-full">
            <div class="bg-rose-50 border border-rose-200 px-4 py-2.5 rounded-2xl text-xs text-rose-700 flex items-center justify-between shadow-2xs">
              <div class="flex items-center space-x-2">
                <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0" />
                <span>{{ flashError }}</span>
              </div>
              <button @click="flashError = ''" class="text-rose-700 hover:text-rose-900 font-bold">&times;</button>
            </div>
          </div>

          <!-- Multi-Approval & Authorization Priority Banner -->
          <div
            v-if="activeTab === 'dashboard' && totalPendingApprovalsForUser > 0"
            class="w-full"
          >
            <div class="bg-gradient-to-r from-[#002855] via-[#003876] to-[#005088] border border-blue-900/30 p-4 sm:p-5 rounded-3xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-white shadow-sm">
              <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-white shrink-0 shadow-inner">
                  <ShieldCheck class="w-5 h-5 text-amber-300" />
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-sm tracking-wide uppercase text-white">{{ $l('Multi-Approval & Authorization Queue', 'Antrean Multi-Persetujuan & Otorisasi') }}</span>
                    <span class="text-[10px] bg-amber-400 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                      {{ $l('{count} Pending Action', '{count} Menunggu Tindakan', { count: totalPendingApprovalsForUser }) }}
                    </span>
                  </div>
                  <p class="text-blue-100/90 text-xs mt-0.5">
                    {{ $l('Submissions are awaiting your authorization based on the authority matrix for', 'Terdapat berkas yang membutuhkan otorisasi Anda berdasarkan matriks kewenangan peran') }} <span class="font-semibold text-amber-300">[{{ auth.user?.role_name }}]</span>.
                  </p>
                  
                  <!-- Breakdown Pills per Module -->
                  <div class="flex items-center gap-2 mt-2.5 flex-wrap">
                    <button
                      v-if="pendingTnaForUser.length > 0"
                      @click="activeTab = 'tna'; tnaViewMode = 'list'"
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-semibold border border-white/20 transition cursor-pointer"
                    >
                      <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                      {{ $l('{count} TNA Proposals', '{count} Usulan TNA', { count: pendingTnaForUser.length }) }}
                    </button>
                    <button
                      v-if="pendingClassesForUser.length > 0"
                      @click="activeTab = 'class'"
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-semibold border border-white/20 transition cursor-pointer"
                    >
                      <span class="w-2 h-2 rounded-full bg-sky-300"></span>
                      {{ $l('{count} Class Batches', '{count} Angkatan Kelas', { count: pendingClassesForUser.length }) }}
                    </button>
                    <button
                      v-if="pendingFinancialsForUser.length > 0"
                      @click="activeTab = 'transaction'"
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-semibold border border-white/20 transition cursor-pointer"
                    >
                      <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                      {{ $l('{count} Financial Transactions', '{count} Transaksi Keuangan', { count: pendingFinancialsForUser.length }) }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center gap-2 self-stretch md:self-auto justify-end shrink-0">
                <button
                  v-if="auth.user?.role_name === 'Super Admin'"
                  @click="activeTab = 'rbac'; rbacSubTab = 'approval_settings'"
                  class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-medium text-xs transition border border-white/20 flex items-center gap-1.5 cursor-pointer"
                  :title="$l('Configure Multi-Approval Workflows', 'Konfigurasi Alur Persetujuan Berjenjang')"
                >
                  <Sliders class="w-3.5 h-3.5 text-blue-200" />
                  <span>{{ $l('Workflow Settings', 'Pengaturan Alur') }}</span>
                </button>
                <button
                  @click="pendingTnaForUser.length > 0 ? (activeTab = 'tna', tnaViewMode = 'list') : (pendingClassesForUser.length > 0 ? activeTab = 'class' : activeTab = 'transaction')"
                  class="px-4 py-2 rounded-xl bg-white hover:bg-blue-50 text-[#002855] font-bold text-xs transition shadow-sm flex items-center gap-1.5 cursor-pointer"
                >
                  <span>{{ $l('Review Approval Queue', 'Tinjau Antrean Persetujuan') }}</span>
                  <ChevronRight class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Dynamic Module Components -->
          <main class="w-full pb-12">
            <OverviewModule
              v-if="activeTab === 'dashboard'"
              :kpis="kpis"
              :training-classes="trainingClasses"
              :financials="financials"
              :tna-proposals="tnaProposals"
              @switch-view="activeTab = $event"
              @open-modal="openModal"
              @view-tna-detail="openTnaDetail"
            />

            <!-- TNA Module (List, Full-Page Create, and Detail Rincian View) -->
            <template v-else-if="activeTab === 'tna'">
              <TnaDetailPage
                v-if="tnaViewMode === 'detail' && selectedTnaProposalId"
                :proposal-id="selectedTnaProposalId"
                @back="tnaViewMode = 'list'"
                @refresh-list="fetchDashboard"
                @edit="openTnaEdit"
              />
              <TnaCreatePage
                v-else-if="tnaViewMode === 'create' || tnaViewMode === 'edit'"
                :edit-proposal-id="tnaViewMode === 'edit' ? selectedTnaProposalId : null"
                @back="tnaViewMode = (tnaViewMode === 'edit' ? 'detail' : 'list')"
                @created="handleTnaCreated"
              />
              <CategoryMasterModule
                v-else-if="tnaViewMode === 'categories'"
              />
              <YearMasterModule
                v-else-if="tnaViewMode === 'years'"
              />
              <TnaModule
                v-else
                :tna-proposals="tnaProposals"
                :can-manage-tna="canManageTna"
                @create-tna="tnaViewMode = 'create'"
                @view-tna-detail="openTnaDetail"
                @edit-tna="openTnaEdit"
                @refresh-list="fetchDashboard"
              />
            </template>

            <template v-else-if="activeTab === 'program'">
              <FacilitatorMasterModule
                v-if="programViewMode === 'facilitator'"
                @back="programViewMode = 'list'"
              />
              <BiCategoryMasterModule
                v-else-if="programViewMode === 'bi_categories'"
                @back="programViewMode = 'list'"
              />
              <ProgramModule
                v-else
                ref="programModuleRef"
                v-model:view-mode="programViewMode"
                :training-programs="trainingPrograms"
                @open-class-create="handleOpenClassCreateFromProgram"
              />
            </template>

            <template v-else-if="activeTab === 'class'">
              <LocationMasterModule
                v-if="classViewMode === 'locations'"
                @back="classViewMode = 'list'"
              />
              <ClassModule
                v-else
                ref="classModuleRef"
                v-model:view-mode="classViewMode"
                :training-classes="trainingClasses"
                :is-checker-role="isCheckerRole"
                :can-create-class="canCreateClass"
                @open-class-modal="showClassModal = true"
                @approve-class="handleApproveClass"
              />
            </template>

            <ParticipantsModule
              v-else-if="activeTab === 'participants'"
              :enrollments="enrollments"
              @view-certificate="handleViewCertificate"
            />

            <EvalModule
              v-else-if="activeTab === 'eval'"
              :enrollments="enrollments"
            />

            <TaskModule
              v-else-if="activeTab === 'task'"
              :training-classes="trainingClasses"
            />

            <MemoModule
              v-else-if="activeTab === 'memo'"
              ref="memoModuleRef"
              @back="activeTab = 'dashboard'"
            />

            <TransactionModule
              v-else-if="activeTab === 'transaction'"
              :financials="financials"
              :is-checker-role="isCheckerRole"
              @open-voucher-modal="showVoucherModal = true"
              @approve-voucher="handleApproveVoucher"
            />

            <CertificationModule
              v-else-if="activeTab === 'certification'"
              :enrollments="enrollments"
              @view-certificate="handleViewCertificate"
            />

            <ReportingModule
              v-else-if="activeTab === 'reporting'"
              :audit-logs="auditLogs"
            />

            <!-- User RBAC & Multi-Approval Settings -->
            <template v-else-if="activeTab === 'rbac'">
              <MultiApprovalSettingModule
                v-if="rbacSubTab === 'approvals' || rbacSubTab === 'approval_settings'"
              />
              <BusinessProcessFlowchartModule
                v-else-if="rbacSubTab === 'flowchart'"
              />
              <UserRbacModule
                v-else
                :sub-tab="rbacSubTab"
                @update:sub-tab="rbacSubTab = $event"
              />
            </template>
          </main>

        </div>

      </div>

    </div>

    <!-- Modals -->
    <TnaModal
      :show="showTnaModal"
      @close="showTnaModal = false"
      @submitted="handleModalSubmitted"
    />

    <ClassModal
      :show="showClassModal"
      :programs="trainingPrograms"
      @close="showClassModal = false"
      @submitted="handleModalSubmitted"
    />

    <VoucherModal
      :show="showVoucherModal"
      :classes="trainingClasses"
      @close="showVoucherModal = false"
      @submitted="handleModalSubmitted"
    />

    <CertificateModal
      :show="showCertModal"
      :certificate-data="activeCertData"
      @close="showCertModal = false"
    />

  </div>
</template>

<style scoped>
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
.animate-scaleIn {
  animation: scaleIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
