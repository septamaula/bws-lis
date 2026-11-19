<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/client'
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
  CalendarRange
} from 'lucide-vue-next'

// Import Modular Components
import OverviewModule from '@/components/modules/OverviewModule.vue'
import TnaModule from '@/components/modules/TnaModule.vue'
import ProgramModule from '@/components/modules/ProgramModule.vue'
import ClassModule from '@/components/modules/ClassModule.vue'
import ParticipantsModule from '@/components/modules/ParticipantsModule.vue'
import EvalModule from '@/components/modules/EvalModule.vue'
import TaskModule from '@/components/modules/TaskModule.vue'
import TransactionModule from '@/components/modules/TransactionModule.vue'
import CertificationModule from '@/components/modules/CertificationModule.vue'
import ReportingModule from '@/components/modules/ReportingModule.vue'
import UserRbacModule from '@/components/modules/UserRbacModule.vue'
import TnaCreatePage from '@/components/modules/tna/TnaCreatePage.vue'
import TnaDetailPage from '@/components/modules/tna/TnaDetailPage.vue'
import MultiApprovalSettingModule from '@/components/modules/settings/MultiApprovalSettingModule.vue'
import CategoryMasterModule from '@/components/modules/CategoryMasterModule.vue'
import YearMasterModule from '@/components/modules/YearMasterModule.vue'

// Import Modals
import TnaModal from '@/components/modals/TnaModal.vue'
import ClassModal from '@/components/modals/ClassModal.vue'
import VoucherModal from '@/components/modals/VoucherModal.vue'
import CertificateModal from '@/components/modals/CertificateModal.vue'

const router = useRouter()
const auth = useAuthStore()

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

// TNA View State: 'list' | 'create' | 'detail' | 'edit'
const tnaViewMode = ref('list')
const selectedTnaProposalId = ref(null)
const programModuleRef = ref(null)
const classModuleRef = ref(null)

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

function handleOpenClassCreateFromProgram(programId) {
  activeTab.value = 'class'
  setTimeout(() => {
    classModuleRef.value?.openCreate(programId)
  }, 100)
}

const navigationModules = [
  { id: 'dashboard', name: 'Dashboard LIS' },
  { id: 'tna', name: 'TNA & Plan' },
  { id: 'program', name: 'Program Kerja' },
  { id: 'class', name: 'Jadwal Kelas' },
  { id: 'participants', name: 'Peserta Diklat' },
  { id: 'eval', name: 'Evaluasi & Nilai' },
  { id: 'task', name: 'Penugasan' },
  { id: 'transaction', name: 'Transaksi & Reimburse' },
  { id: 'certification', name: 'Sertifikasi Wajib' },
  { id: 'reporting', name: 'Laporan & Audit' },
  { id: 'rbac', name: 'Kelola User & RBAC' }
]

const currentTabTitle = computed(() => {
  switch (activeTab.value) {
    case 'dashboard': return 'Executive & Operational Dashboard'
    case 'tna': return 'TNA & Annual Training Plan'
    case 'program': return 'Program Pelatihan & Work Plan'
    case 'class': return 'Class Implementation & Schedule'
    case 'participants': return 'Peserta & Activity Tracking'
    case 'eval': return 'Evaluasi & Nilai Peserta'
    case 'task': return 'Penugasan & Task Management'
    case 'transaction': return 'Transaksi Keuangan & Reimbursement'
    case 'certification': return 'Sertifikasi Wajib & Lisensi Profesi'
    case 'reporting': return 'Reporting Center & Audit Trail'
    case 'rbac': return 'Manajemen Pengguna & Matriks Hak Akses (RBAC)'
    default: return 'Dashboard'
  }
})

const currentTabSubtitle = computed(() => {
  switch (activeTab.value) {
    case 'dashboard': return 'Portal Operasional Terpadu Learning & Development — Bank Woori Saudara'
    case 'tna': return 'Perencanaan Kebutuhan Diklat & Usulan Anggaran Kompetensi Divisi Bank Woori Saudara'
    case 'program': return 'Katalog Program Kerja, Kurikulum Diklat, dan Sertifikasi Wajib Regulasi OJK / BI'
    case 'class': return 'Manajemen Batch Pelatihan, Alokasi Ruangan, dan Otorisasi Maker-Checker'
    case 'participants': return 'Database Peserta Diklat, Kehadiran, dan Monitoring Progres Pelatihan LIS'
    case 'eval': return 'Rekapitulasi Skor Pre-Test & Post-Test, Efektivitas Peningkatan Kompetensi Pegawai'
    case 'task': return 'Monitoring Pengumpulan Action Learning Project & Penilaian Tugas Mandiri Peserta'
    case 'transaction': return 'Buku Transaksi Realisasi Anggaran, Klaim Biaya Diklat, dan Otorisasi Maker-Checker'
    case 'certification': return 'Kepatuhan Lisensi Perbankan OJK & Bank Indonesia (Manajemen Risiko, SKSP, Tresuri)'
    case 'reporting': return 'Pusat Unduh Laporan Realisasi Diklat, Sign-Off Berjenjang, dan Rekam Jejak Audit OJK'
    case 'rbac': return 'Konfigurasi Akun Pegawai, Tingkat Peran Jabatan, dan Matriks Kontrol Hak Akses'
    default: return 'Portal Operasional Terpadu Learning & Development — Bank Woori Saudara'
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
    flashError.value = 'Gagal mengunduh file log audit trail.'
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
    flashError.value = err.response?.data?.message || 'Gagal memuat data operasional.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboard()
})

// Role Switcher Dropdown
const showRoleDropdown = ref(false)
const showProfileDropdown = ref(false)

async function handleSwitchRole(roleName) {
  showRoleDropdown.value = false
  showProfileDropdown.value = false
  flashError.value = ''
  try {
    const res = await auth.switchRole(roleName)
    flashSuccess.value = res.message
    await fetchDashboard()
  } catch (err) {
    flashError.value = err.response?.data?.message || 'Gagal mengganti peran.'
  }
}

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}

// Notification handler
function handleNotificationClick() {
  if (totalPendingApprovalsForUser.value > 0) {
    flashSuccess.value = `Terdapat ${totalPendingApprovalsForUser.value} pengajuan operasional yang membutuhkan tindakan otorisasi Multi-Approval Anda.`
  } else if (kpis.value.pending_checker_approvals > 0) {
    flashSuccess.value = `Terdapat ${kpis.value.pending_checker_approvals} total antrean approval pada sistem.`
  } else {
    flashSuccess.value = 'Semua antrean operasional saat ini dalam status sinkron & aman (OJK Compliant).'
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
      checker_notes: 'Disetujui Checker melalui Dashboard Operasional.'
    })
    flashSuccess.value = data.message
    await fetchDashboard()
  } catch (err) {
    flashError.value = err.response?.data?.message || 'Otorisasi batch ditolak.'
  }
}

async function handleApproveVoucher(voucher) {
  flashError.value = ''
  try {
    const { data } = await apiClient.post(`/transactions/${voucher.id}/approve`, {
      decision: 'APPROVED',
      checker_notes: 'Otorisasi pencairan disetujui Checker.'
    })
    flashSuccess.value = data.message
    await fetchDashboard()
  } catch (err) {
    flashError.value = err.response?.data?.message || 'Otorisasi voucher ditolak.'
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
      <header class="bg-white rounded-full px-5 py-2.5 flex items-center justify-between shadow-sm sticky top-2 z-40 border border-gray-100">
        
        <!-- Left: Official BWS Logo + LIS Information -->
        <div class="flex items-center gap-3.5 cursor-pointer shrink-0 group" @click="activeTab = 'dashboard'">
          <!-- Official Bank Woori Saudara Vector Logo -->
          <svg class="h-8 sm:h-9 w-auto" viewBox="0 0 290 80" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <radialGradient id="bwsGlobeHeader" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#00A0E9"/>
                <stop offset="65%" stop-color="#0067AC"/>
                <stop offset="100%" stop-color="#002855"/>
              </radialGradient>
              <linearGradient id="horizonGlowHeader" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#00A0E9" stop-opacity="0.3"/>
                <stop offset="20%" stop-color="#FFFFFF"/>
                <stop offset="80%" stop-color="#FFFFFF"/>
                <stop offset="100%" stop-color="#00A0E9" stop-opacity="0.3"/>
              </linearGradient>
            </defs>
            <circle cx="40" cy="40" r="36" fill="url(#bwsGlobeHeader)"/>
            <path d="M 6 40 A 34 34 0 0 1 74 40 Z" fill="#0067AC"/>
            <path d="M 6 40 Q 40 18 74 40 Q 40 30 6 40 Z" fill="url(#horizonGlowHeader)"/>
            <path d="M 6 40 Q 40 30 74 40 A 34 34 0 0 1 6 40 Z" fill="#0055A5"/>
            <text x="86" y="47" font-family="'Plus Jakarta Sans', sans-serif" font-weight="900" font-style="italic" font-size="44" fill="#0067AC" letter-spacing="-1">BWS</text>
            <text x="88" y="65" font-family="'Plus Jakarta Sans', sans-serif" font-weight="800" font-style="italic" font-size="11.5" fill="#0067AC" letter-spacing="0.5">BANK WOORI SAUDARA</text>
          </svg>

          <!-- Divider -->
          <div class="h-7 w-px bg-slate-200 hidden sm:block"></div>

          <!-- Right: LIS Information -->
          <div class="leading-tight">
            <div class="flex items-center gap-1.5">
              <span class="font-extrabold text-sm sm:text-base tracking-tight text-[#002855] group-hover:text-[#0067AC] transition">
                LIS
              </span>
              <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC] border border-[#00A0E9]/30 uppercase tracking-wider hidden sm:inline-block">
                PORTAL
              </span>
            </div>
            <span class="text-[10px] text-slate-500 font-medium block">Learning Integrated System</span>
          </div>
        </div>

        <!-- Center: Global Search Bar -->
        <div class="flex-1 max-w-md mx-4 lg:mx-8 relative hidden md:block">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="globalSearch"
            type="text"
            placeholder="Cari TNA, Program, Kelas, Peserta..."
            class="w-full bg-[#f4f6f5] hover:bg-gray-100 focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition"
          />
        </div>

        <!-- Right: Role Switcher + Notification + Profile -->
        <div class="flex items-center gap-2.5 shrink-0">
          
          <!-- Refresh Button -->
          <button
            @click="fetchDashboard"
            class="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-blue-50 hover:text-[#0067AC] text-gray-500 flex items-center justify-center transition"
            title="Segarkan Data"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Role Switcher Pill -->
          <div class="relative">
            <button
              @click="showRoleDropdown = !showRoleDropdown"
              class="flex items-center gap-1.5 bg-[#f4f6f5] hover:bg-gray-100 px-3 py-1.5 rounded-full border border-gray-200/80 transition text-xs font-semibold text-gray-700"
              title="Ganti Peran Pengguna"
            >
              <ShieldCheck class="w-3.5 h-3.5 text-[#0067AC] shrink-0" />
              <span class="truncate max-w-[130px]">{{ auth.user?.role_name || 'Manager L&D' }}</span>
              <ChevronDown class="w-3 h-3 text-gray-400" />
            </button>

            <!-- Role Dropdown -->
            <div
              v-if="showRoleDropdown"
              class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded-2xl shadow-xl p-2 z-50 space-y-1 animate-scaleIn"
            >
              <div class="px-3 py-1.5 border-b border-gray-100">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Peran Perbankan (8 Role)</span>
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
            title="Notifikasi"
          >
            <Bell class="w-4 h-4" />
            <span
              v-if="kpis.pending_checker_approvals > 0"
              class="w-2 h-2 rounded-full bg-[#0067AC] absolute top-1.5 right-1.5"
            ></span>
          </button>

          <!-- Profile Pill -->
          <div class="relative">
            <div
              @click="showProfileDropdown = !showProfileDropdown"
              class="flex items-center gap-2 pl-1 cursor-pointer group"
              title="Lihat Profil & Matriks Role"
            >
              <div class="w-8 h-8 rounded-full bg-[#EBF4FC] text-[#0067AC] font-bold text-xs flex items-center justify-center ring-2 ring-[#0067AC]/30">
                {{ getInitials(auth.user?.name) }}
              </div>
              <div class="hidden sm:block text-left leading-tight">
                <p class="text-xs font-bold text-gray-900 group-hover:text-[#0067AC] transition">
                  {{ auth.user?.name || 'Budi Santoso' }}
                </p>
                <p class="text-[10px] text-gray-400 font-medium">
                  {{ auth.user?.role_name || 'Manager L&D' }}
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
                <span>Keluar dari Portal</span>
              </button>
            </div>
          </div>

          <!-- Quick Logout Button -->
          <button
            @click="handleLogout"
            class="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-rose-50 hover:text-rose-600 text-gray-500 flex items-center justify-center transition"
            title="Keluar dari portal"
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
                  title="Executive & Operational Dashboard"
                >
                  <LayoutGrid class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-60">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">Dashboard LIS</span>
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
                            Executive & Process Flow
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
                          All Training Report
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
                  title="TNA / Training Plan"
                >
                  <Compass class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">TNA / Training Plan</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'tna'; tnaViewMode = 'list'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'tna' && tnaViewMode === 'list' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <FolderKanban class="w-3.5 h-3.5 text-gray-400" />
                          Daftar TNA & Pagu Budget
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'tna'; tnaViewMode = 'create'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'tna' && tnaViewMode === 'create' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <Plus class="w-3.5 h-3.5 text-gray-400" />
                          + Buat Usulan TNA Baru
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Program L&D -->
              <div class="relative group">
                <button
                  @click="activeTab = 'program'"
                  id="side-program"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'program' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  title="Program Pelatihan"
                >
                  <Layers class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Program Pelatihan</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'program'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'program' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <BookOpenCheck class="w-3.5 h-3.5 text-gray-400" />
                          Approved Program & Budget
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-class')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <Send class="w-3.5 h-3.5 text-gray-400" />
                          Siap Dibuatkan Kelas
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Class Implementation -->
              <div class="relative group">
                <button
                  @click="activeTab = 'class'"
                  id="side-class"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'class' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  title="Class & Implementation"
                >
                  <Presentation class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Class & Implementation</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'class'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'class' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <CalendarClock class="w-3.5 h-3.5 text-gray-400" />
                          Jadwal Kelas & 6 Upload PIC
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-class')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <MailCheck class="w-3.5 h-3.5 text-gray-400" />
                          Buat Kelas & Send Invitation
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
                  title="Participants & Evaluation"
                >
                  <Users class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Participants & Eval</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'participants'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'participants' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <UserCheck class="w-3.5 h-3.5 text-gray-400" />
                          Peserta, Pre/Post Test & Eval
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'eval'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'eval' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <Activity class="w-3.5 h-3.5 text-gray-400" />
                          Activity Review & Evaluasi Nilai
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
                  title="Task (Class/Independent)"
                >
                  <ClipboardCheck class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Task (Class/Indep.)</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'task'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'task' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <ListTodo class="w-3.5 h-3.5 text-gray-400" />
                          Monitoring & Approval Task
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'task'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <UploadCloud class="w-3.5 h-3.5 text-gray-400" />
                          Buat Task / Upload Tugas Peserta
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
                  title="Transaction & Reimbursement"
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
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Transaction & Payment</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'transaction'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'transaction' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <Receipt class="w-3.5 h-3.5 text-gray-400" />
                          Reimburse & Vendor Payment
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-transaction')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <FilePlus2 class="w-3.5 h-3.5 text-gray-400" />
                          Ajukan Klaim / Reimburse Baru
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Certification (Risk, SKSP, Tresuri) -->
              <div class="relative group">
                <button
                  @click="activeTab = 'certification'"
                  id="side-certification"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="activeTab === 'certification' ? 'bg-[#0067AC] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#0067AC] group-hover:text-white'"
                  title="Certification & Mandatory Exam"
                >
                  <Award class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Certification & Exam</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'certification'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'certification' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <ShieldAlert class="w-3.5 h-3.5 text-gray-400" />
                          Target vs Realisasi (Risk, SKSP, Tresuri)
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'certification'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <CalendarPlus class="w-3.5 h-3.5 text-gray-400" />
                          Update Realisasi Bulanan & Ujian
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
                  title="Reporting Center & Sign-Off"
                >
                  <BarChart3 class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-gray-800">Reporting Center</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'reporting'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'reporting' ? 'bg-[#0067AC]/10 text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                        >
                          <FileCheck2 class="w-3.5 h-3.5 text-gray-400" />
                          All Training Info (Class/Yearly/Overall)
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'reporting'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#0067AC] transition"
                        >
                          <Stamp class="w-3.5 h-3.5 text-gray-400" />
                          Sign-Off Approved (1) & (2)
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
                title="Kelola Pengguna & Matriks RBAC"
              >
                <KeyRound class="w-4 h-4" />
              </button>
              <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-64">
                  <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5 flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#0067AC]">Kelola User & RBAC</span>
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
                          Daftar Pengguna (User)
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
                          Master Karyawan (SDM)
                        </span>
                        <ChevronRight class="w-3.5 h-3.5" />
                      </button>
                    </li>
                    <li>
                      <button
                        @click="activeTab = 'rbac'; rbacSubTab = 'categories'"
                        class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                        :class="activeTab === 'rbac' && rbacSubTab === 'categories' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                      >
                        <span class="flex items-center gap-2">
                          <FolderTree class="w-3.5 h-3.5" />
                          Master Kategori & Sub
                        </span>
                        <ChevronRight class="w-3.5 h-3.5" />
                      </button>
                    </li>
                    <li>
                      <button
                        @click="activeTab = 'rbac'; rbacSubTab = 'years'"
                        class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                        :class="activeTab === 'rbac' && rbacSubTab === 'years' ? 'bg-[#EBF4FC] text-[#0067AC] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#0067AC]'"
                      >
                        <span class="flex items-center gap-2">
                          <CalendarRange class="w-3.5 h-3.5" />
                          Master Tahun Anggaran
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
                          Setting Multi-Approval
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
          <div id="page-subheader" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 px-1">
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
                @click="activeTab = 'rbac'; rbacSubTab = 'years'"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-700 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
                title="Klik untuk Kelola Master Tahun Anggaran"
              >
                <Calendar class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Tahun Buku: {{ activeFiscalYear }}</span>
              </button>
              <button
                v-if="canCreateClass"
                @click="showClassModal = true"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-800 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Send class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Send Class Invitation</span>
              </button>
              <button
                @click="showVoucherModal = true"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition"
              >
                <Receipt class="w-3.5 h-3.5" />
                <span>+ Klaim / Reimburse</span>
              </button>
            </div>

            <!-- 2. TNA Module Header Actions -->
            <div v-else-if="activeTab === 'tna'" class="flex items-center gap-2">
              <button
                @click="activeTab = 'rbac'; rbacSubTab = 'years'"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-700 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
                title="Kelola Master Tahun Anggaran"
              >
                <Calendar class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Tahun Buku: {{ activeFiscalYear }}</span>
              </button>
              <button
                v-if="canManageTna && tnaViewMode === 'list'"
                @click="tnaViewMode = 'create'"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>+ Usulan TNA Baru</span>
              </button>
              <button
                v-else-if="tnaViewMode === 'edit'"
                @click="tnaViewMode = 'detail'"
                class="bg-white text-gray-700 px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-200 transition"
              >
                <ArrowLeft class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Kembali ke Rincian Dokumen</span>
              </button>
              <button
                v-else-if="tnaViewMode !== 'list'"
                @click="tnaViewMode = 'list'"
                class="bg-white text-gray-700 px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-200 transition"
              >
                <FolderKanban class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Daftar Usulan TNA</span>
              </button>
            </div>

            <!-- 3. Program Kerja -->
            <div v-else-if="activeTab === 'program'" class="flex items-center gap-2">
              <button
                @click="programModuleRef?.openCreate()"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>+ Buat Program Baru</span>
              </button>
            </div>

            <!-- 4. Class Implementation -->
            <div v-else-if="activeTab === 'class'" class="flex items-center gap-2">
              <button
                v-if="canCreateClass"
                @click="showClassModal = true"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-800 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Send class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Send Class Invitation</span>
              </button>
              <button
                v-if="canCreateClass"
                @click="classModuleRef?.openCreate()"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>+ Buat Batch Kelas (Maker)</span>
              </button>
            </div>

            <!-- 5. Transaksi Keuangan -->
            <div v-else-if="activeTab === 'transaction'" class="flex items-center gap-2">
              <button
                @click="showVoucherModal = true"
                class="bg-[#0067AC] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-[#00528c] transition"
              >
                <Receipt class="w-3.5 h-3.5" />
                <span>+ Buat Voucher / Klaim</span>
              </button>
            </div>

            <!-- 6. Reporting & Audit -->
            <div v-else-if="activeTab === 'reporting'" class="flex items-center gap-2">
              <button
                @click="handleDownloadAuditCsv"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-800 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Download class="w-3.5 h-3.5 text-[#0067AC]" />
                <span>Unduh Audit CSV</span>
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
                <span>Kelola Pengguna</span>
              </button>

              <button
                @click="rbacSubTab = 'karyawan'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'karyawan' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <UserCheck class="w-3.5 h-3.5" />
                <span>Master Karyawan</span>
              </button>

              <button
                @click="rbacSubTab = 'categories'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'categories' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <FolderTree class="w-3.5 h-3.5" />
                <span>Master Kategori & Sub</span>
              </button>

              <button
                @click="rbacSubTab = 'years'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'years' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <CalendarRange class="w-3.5 h-3.5" />
                <span>Master Tahun</span>
              </button>

              <button
                @click="rbacSubTab = 'levels'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'levels' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <ShieldCheck class="w-3.5 h-3.5" />
                <span>Tingkat Peran</span>
              </button>

              <button
                @click="rbacSubTab = 'matrix'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'matrix' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <Sliders class="w-3.5 h-3.5" />
                <span>Matriks Hak Akses (RBAC)</span>
              </button>

              <button
                @click="rbacSubTab = 'approvals'"
                class="px-4 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shrink-0"
                :class="rbacSubTab === 'approvals' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
              >
                <FileCheck2 class="w-3.5 h-3.5" />
                <span>Setting Multi-Approval</span>
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

          <!-- Pusat Otorisasi & Multi-Approval Priority Banner (ONLY ON DASHBOARD/ANALYTIC TAB) -->
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
                    <span class="font-bold text-sm tracking-wide uppercase text-white">Antrean Multi-Approval & Task Otorisasi</span>
                    <span class="text-[10px] bg-amber-400 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                      {{ totalPendingApprovalsForUser }} Menunggu Tindakan
                    </span>
                  </div>
                  <p class="text-blue-100/90 text-xs mt-0.5">
                    Terdapat berkas yang membutuhkan otorisasi Anda berdasarkan matriks kewenangan peran <span class="font-semibold text-amber-300">[{{ auth.user?.role_name }}]</span>.
                  </p>
                  
                  <!-- Breakdown Pills per Module -->
                  <div class="flex items-center gap-2 mt-2.5 flex-wrap">
                    <button
                      v-if="pendingTnaForUser.length > 0"
                      @click="activeTab = 'tna'; tnaViewMode = 'list'"
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-semibold border border-white/20 transition cursor-pointer"
                    >
                      <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                      {{ pendingTnaForUser.length }} Usulan TNA
                    </button>
                    <button
                      v-if="pendingClassesForUser.length > 0"
                      @click="activeTab = 'class'"
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-semibold border border-white/20 transition cursor-pointer"
                    >
                      <span class="w-2 h-2 rounded-full bg-sky-300"></span>
                      {{ pendingClassesForUser.length }} Batch Kelas
                    </button>
                    <button
                      v-if="pendingFinancialsForUser.length > 0"
                      @click="activeTab = 'transaction'"
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 hover:bg-white/25 text-white text-[11px] font-semibold border border-white/20 transition cursor-pointer"
                    >
                      <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                      {{ pendingFinancialsForUser.length }} Transaksi Keuangan
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
                  title="Konfigurasi Alur Multi-Approval"
                >
                  <Sliders class="w-3.5 h-3.5 text-blue-200" />
                  <span>Setting Alur</span>
                </button>
                <button
                  @click="pendingTnaForUser.length > 0 ? (activeTab = 'tna', tnaViewMode = 'list') : (pendingClassesForUser.length > 0 ? activeTab = 'class' : activeTab = 'transaction')"
                  class="px-4 py-2 rounded-xl bg-white hover:bg-blue-50 text-[#002855] font-bold text-xs transition shadow-sm flex items-center gap-1.5 cursor-pointer"
                >
                  <span>Tinjau Antrean Approval</span>
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
              <TnaModule
                v-else
                :tna-proposals="tnaProposals"
                :can-manage-tna="canManageTna"
                @create-tna="tnaViewMode = 'create'"
                @view-tna-detail="openTnaDetail"
              />
            </template>

            <ProgramModule
              v-else-if="activeTab === 'program'"
              ref="programModuleRef"
              :training-programs="trainingPrograms"
              @open-class-create="handleOpenClassCreateFromProgram"
            />

            <ClassModule
              v-else-if="activeTab === 'class'"
              ref="classModuleRef"
              :training-classes="trainingClasses"
              :is-checker-role="isCheckerRole"
              :can-create-class="canCreateClass"
              @open-class-modal="showClassModal = true"
              @approve-class="handleApproveClass"
            />

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

            <!-- User RBAC, Master Data & Multi-Approval Settings -->
            <template v-else-if="activeTab === 'rbac'">
              <CategoryMasterModule
                v-if="rbacSubTab === 'categories'"
              />
              <YearMasterModule
                v-else-if="rbacSubTab === 'years'"
              />
              <MultiApprovalSettingModule
                v-else-if="rbacSubTab === 'approvals' || rbacSubTab === 'approval_settings'"
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
