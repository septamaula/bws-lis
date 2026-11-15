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
  Calendar
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

const navigationModules = [
  { id: 'dashboard', name: 'Dashboard LIS' },
  { id: 'tna', name: 'TNA & Plan' },
  { id: 'program', name: 'Program Kerja' },
  { id: 'class', name: 'Jadwal Kelas' },
  { id: 'participants', name: 'Peserta ELAPSE' },
  { id: 'eval', name: 'Evaluasi & Nilai' },
  { id: 'task', name: 'Penugasan' },
  { id: 'transaction', name: 'Transaksi & Reimburse' },
  { id: 'certification', name: 'Sertifikasi Wajib' },
  { id: 'reporting', name: 'Laporan & Audit' }
]

const currentTabTitle = computed(() => {
  switch (activeTab.value) {
    case 'dashboard': return 'Executive & Operational Dashboard'
    case 'tna': return 'TNA & Annual Training Plan'
    case 'program': return 'Program Pelatihan & Work Plan'
    case 'class': return 'Class Implementation & Schedule'
    case 'participants': return 'Peserta & Activity Tracking (ELAPSE)'
    case 'eval': return 'Evaluasi & Nilai Peserta (ELAPSE)'
    case 'task': return 'Penugasan & Task Management'
    case 'transaction': return 'Transaksi Keuangan & Reimbursement'
    case 'certification': return 'Sertifikasi Wajib & Ujian (Risk, SKSP, Tresuri)'
    case 'reporting': return 'Reporting Center & Audit Trail'
    default: return 'Dashboard'
  }
})

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
  if (kpis.value.pending_checker_approvals > 0) {
    flashSuccess.value = `Terdapat ${kpis.value.pending_checker_approvals} pengajuan membutuhkan otorisasi Checker (Dual-Control).`
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
</script>

<template>
  <div class="min-h-screen bg-[#f4f6f5] text-gray-900 flex flex-col font-sans selection:bg-[#157f4f] selection:text-white">

    <!-- Full-Screen Layout Wrapper (Identical to Mockup) -->
    <div class="w-full min-h-screen px-4 py-3 md:px-6 md:py-4 flex flex-col gap-5">

      <!-- ========================================================== -->
      <!-- CLEAN TOP NAVBAR (STREAMLINED & BALANCED - FROM MOCKUP)    -->
      <!-- ========================================================== -->
      <header class="bg-white rounded-full px-5 py-2.5 flex items-center justify-between shadow-sm sticky top-2 z-40 border border-gray-100">
        
        <!-- Left: Brand Logo -->
        <div class="flex items-center gap-3 cursor-pointer shrink-0" @click="activeTab = 'dashboard'">
          <div class="w-9 h-9 rounded-full bg-[#157f4f] flex items-center justify-center text-white font-bold text-lg relative shrink-0 shadow-sm">
            <span>W</span>
            <span class="w-2 h-2 bg-emerald-300 rounded-full absolute bottom-1 right-1 border-2 border-white"></span>
          </div>
          <div class="leading-tight">
            <span class="font-extrabold text-base tracking-tight text-gray-900 block">
              BWS <span class="text-[#157f4f]">LIS</span>
            </span>
            <span class="text-[10px] text-gray-400 font-medium block -mt-0.5">Learning System</span>
          </div>
        </div>

        <!-- Center: Global Search Bar -->
        <div class="flex-1 max-w-md mx-4 lg:mx-8 relative hidden md:block">
          <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="globalSearch"
            type="text"
            placeholder="Cari TNA, Program, Kelas, Peserta..."
            class="w-full bg-[#f4f6f5] hover:bg-gray-100 focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-transparent focus:border-[#157f4f] focus:outline-none transition"
          />
        </div>

        <!-- Right: Role Switcher + Notification + Profile -->
        <div class="flex items-center gap-2.5 shrink-0">
          
          <!-- Refresh Button -->
          <button
            @click="fetchDashboard"
            class="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-emerald-50 hover:text-[#157f4f] text-gray-500 flex items-center justify-center transition"
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
              <ShieldCheck class="w-3.5 h-3.5 text-[#157f4f] shrink-0" />
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
                :class="auth.user?.role_name === r ? 'bg-emerald-50 text-[#157f4f] font-bold' : 'text-gray-700 hover:bg-gray-50'"
              >
                <span>{{ r }}</span>
                <span v-if="auth.user?.role_name === r" class="w-1.5 h-1.5 rounded-full bg-[#157f4f]"></span>
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
              class="w-2 h-2 rounded-full bg-[#157f4f] absolute top-1.5 right-1.5"
            ></span>
          </button>

          <!-- Profile Pill -->
          <div class="relative">
            <div
              @click="showProfileDropdown = !showProfileDropdown"
              class="flex items-center gap-2 pl-1 cursor-pointer group"
              title="Lihat Profil & Matriks Role"
            >
              <div class="w-8 h-8 rounded-full bg-emerald-100 text-[#157f4f] font-bold text-xs flex items-center justify-center ring-2 ring-[#157f4f]/30">
                {{ getInitials(auth.user?.name) }}
              </div>
              <div class="hidden sm:block text-left leading-tight">
                <p class="text-xs font-bold text-gray-900 group-hover:text-[#157f4f] transition">
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
                <p class="text-[10px] text-emerald-700 font-semibold mt-1">NIK: {{ auth.user?.nik || 'BWS-2026-001' }}</p>
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
          :class="activeTab === m.id ? 'bg-[#157f4f] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
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
                  :class="activeTab === 'dashboard' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
                  title="Executive & Operational Dashboard"
                >
                  <LayoutGrid class="w-4 h-4" />
                </button>
                <div class="absolute left-full top-0 pl-3 hidden group-hover:block z-50">
                  <div class="bg-white rounded-2xl p-2.5 shadow-xl border border-gray-100 w-60">
                    <div class="px-3 py-1.5 border-b border-gray-100 mb-1.5">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-[#157f4f]">Dashboard LIS</span>
                    </div>
                    <ul class="space-y-1 text-xs font-medium text-gray-600">
                      <li>
                        <button
                          @click="activeTab = 'dashboard'"
                          class="w-full text-left flex items-center justify-between px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'dashboard' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
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
                  :class="activeTab === 'tna' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          @click="activeTab = 'tna'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'tna' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <FolderKanban class="w-3.5 h-3.5 text-gray-400" />
                          Daftar TNA & Pagu Budget
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-tna')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
                        >
                          <FileUp class="w-3.5 h-3.5 text-gray-400" />
                          Upload Approval & Input Budget
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
                  :class="activeTab === 'program' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          :class="activeTab === 'program' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <BookOpenCheck class="w-3.5 h-3.5 text-gray-400" />
                          Approved Program & Budget
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-class')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
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
                  :class="activeTab === 'class' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          :class="activeTab === 'class' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <CalendarClock class="w-3.5 h-3.5 text-gray-400" />
                          Jadwal Kelas & 6 Upload PIC
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-class')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
                        >
                          <MailCheck class="w-3.5 h-3.5 text-gray-400" />
                          Buat Kelas & Send Invitation
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Participants, Evaluation & ELAPSE -->
              <div class="relative group">
                <button
                  @click="activeTab = 'participants'"
                  id="side-participants"
                  class="side-btn w-10 h-10 rounded-full flex items-center justify-center transition"
                  :class="(activeTab === 'participants' || activeTab === 'eval') ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
                  title="Participants & Evaluation (ELAPSE)"
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
                          :class="activeTab === 'participants' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <UserCheck class="w-3.5 h-3.5 text-gray-400" />
                          Peserta, Pre/Post Test & Eval
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'eval'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl transition"
                          :class="activeTab === 'eval' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <Activity class="w-3.5 h-3.5 text-gray-400" />
                          Activity (ELAPSE) Review & Nilai
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
                  :class="activeTab === 'task' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          :class="activeTab === 'task' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <ListTodo class="w-3.5 h-3.5 text-gray-400" />
                          Monitoring & Approval Task
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'task'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
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
                  :class="activeTab === 'transaction' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          :class="activeTab === 'transaction' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <Receipt class="w-3.5 h-3.5 text-gray-400" />
                          Reimburse & Vendor Payment
                        </button>
                      </li>
                      <li>
                        <button
                          @click="openModal('modal-transaction')"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
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
                  :class="activeTab === 'certification' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          :class="activeTab === 'certification' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <ShieldAlert class="w-3.5 h-3.5 text-gray-400" />
                          Target vs Realisasi (Risk, SKSP, Tresuri)
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'certification'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
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
                  :class="activeTab === 'reporting' ? 'bg-[#157f4f] text-white shadow-sm' : 'text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white'"
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
                          :class="activeTab === 'reporting' ? 'bg-[#157f4f]/10 text-[#157f4f] font-semibold' : 'hover:bg-[#f4f6f5] hover:text-[#157f4f]'"
                        >
                          <FileCheck2 class="w-3.5 h-3.5 text-gray-400" />
                          All Training Info (Class/Yearly/Overall)
                        </button>
                      </li>
                      <li>
                        <button
                          @click="activeTab = 'reporting'"
                          class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-[#f4f6f5] hover:text-[#157f4f] transition"
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

          <!-- Pill Group 3: Auth & Role Matrix -->
          <div class="bg-white rounded-full p-2 flex flex-col gap-2 shadow-sm items-center mt-3 border border-gray-100">
            <div class="relative group">
              <button
                @click="showRoleDropdown = !showRoleDropdown"
                id="side-login"
                class="side-btn w-10 h-10 rounded-full text-gray-500 group-hover:bg-[#157f4f] group-hover:text-white flex items-center justify-center transition"
                title="SSO Login & Profil Peran"
              >
                <KeyRound class="w-4 h-4" />
              </button>
              <div class="absolute left-full top-1/2 -translate-y-1/2 pl-3 hidden group-hover:block z-50">
                <div class="bg-gray-900 text-white text-xs font-semibold px-3 py-1.5 rounded-xl shadow-lg whitespace-nowrap">
                  SSO Login & Profil Peran
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
                Portal Operasional Terpadu Learning & Development — Bank Woori Saudara
              </p>
            </div>

            <!-- Contextual Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
              <button
                @click="activeTab = 'tna'"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-700 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Calendar class="w-3.5 h-3.5 text-[#157f4f]" />
                <span>Tahun Buku: 2026</span>
              </button>
              <button
                v-if="canCreateClass"
                @click="showClassModal = true"
                class="bg-white px-3.5 py-2 rounded-full text-xs font-semibold text-gray-800 flex items-center gap-1.5 shadow-sm hover:bg-gray-50 border border-gray-100 transition"
              >
                <Send class="w-3.5 h-3.5 text-[#157f4f]" />
                <span>Send Class Invitation</span>
              </button>
              <button
                @click="showVoucherModal = true"
                class="bg-[#157f4f] text-white px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm hover:bg-emerald-800 transition"
              >
                <Receipt class="w-3.5 h-3.5" />
                <span>+ Klaim / Reimburse</span>
              </button>
            </div>
          </div>

          <!-- Alerts Banner -->
          <div v-if="flashSuccess" class="w-full">
            <div class="bg-emerald-50 border border-emerald-200 px-4 py-2.5 rounded-2xl text-xs text-[#157f4f] flex items-center justify-between shadow-2xs">
              <div class="flex items-center space-x-2">
                <CheckCircle class="w-4 h-4 text-[#157f4f] shrink-0" />
                <span>{{ flashSuccess }}</span>
              </div>
              <button @click="flashSuccess = ''" class="text-emerald-700 hover:text-emerald-900 font-bold">&times;</button>
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

          <!-- Dual Control Checker Priority Banner -->
          <div
            v-if="isCheckerRole && kpis.pending_checker_approvals > 0"
            class="w-full"
          >
            <div class="bg-amber-50 border border-amber-200/80 p-4 rounded-3xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-amber-900 shadow-2xs">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-amber-200/60 text-amber-800 flex items-center justify-center shrink-0">
                  <Scale class="w-4 h-4" />
                </div>
                <div>
                  <span class="font-bold text-amber-950 uppercase tracking-wide">Antrean Dual-Control Checker Aktif</span>
                  <p class="text-amber-800 text-[11px]">
                    Terdapat {{ kpis.pending_checker_approvals }} pengajuan (Kelas & Transaksi Keuangan) yang membutuhkan otorisasi Anda sebagai Checker.
                  </p>
                </div>
              </div>
              <button
                @click="activeTab = 'class'"
                class="px-4 py-1.5 rounded-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition shadow-2xs shrink-0"
              >
                Tinjau Antrean Checker
              </button>
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
            />

            <TnaModule
              v-else-if="activeTab === 'tna'"
              :tna-proposals="tnaProposals"
              :can-manage-tna="canManageTna"
              @open-tna-modal="showTnaModal = true"
            />

            <ProgramModule
              v-else-if="activeTab === 'program'"
              :training-programs="trainingPrograms"
              @open-program-modal="showClassModal = true"
            />

            <ClassModule
              v-else-if="activeTab === 'class'"
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
