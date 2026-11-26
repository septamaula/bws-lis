<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  ArrowLeft,
  Calendar,
  Building2,
  Users,
  Send,
  Save,
  Plus,
  Trash2,
  MapPin,
  Clock,
  BookOpen,
  Award,
  Video,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Sparkles,
  ChevronRight,
  ShieldCheck,
  Scale,
  FileCheck2,
  Printer,
  RotateCcw,
  Star,
  TrendingUp,
  Download,
  Check,
  Search,
  HelpCircle
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from '@/i18n'

const { l, t } = useI18n()

const props = defineProps({
  classId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['back', 'refresh-list', 'open-program-detail'])
const auth = useAuthStore()

const loading = ref(true)
const trainingClass = ref(null)
const classStats = ref({})
const availableNominations = ref([])

// Active Sub-Tab: 'overview' | 'attendance' | 'evaluation' | 'roster'
const activeSubTab = ref('attendance')

// Feedback & Loading States
const actionFeedback = ref({ type: '', message: '' })
const isSavingAttendance = ref(false)
const isSavingScores = ref(false)
const isGeneratingCertificates = ref(false)

// Attendance Sub-Tab State
const selectedSessionId = ref(null)
const attendanceRecords = ref({}) // key: enrollment_id -> { status, check_in_time, notes }

// Evaluation Sub-Tab State
const scoreRecords = ref({}) // key: enrollment_id -> { pre_test_score, post_test_score, task_score, eval_instructor_rating, eval_notes }

// Add Participant Modal State
const showAddParticipantModal = ref(false)
const selectedNominationIds = ref([])

// Master Pegawai State & Selection
const masterPegawai = ref([])
const showPegawaiModal = ref(false)
const searchPegawai = ref('')
const filterPegawaiDept = ref('')
const selectedPegawaiIds = ref([])
const isEnrollingPegawai = ref(false)

async function fetchMasterPegawai() {
  try {
    const { data } = await apiClient.get('/karyawan')
    masterPegawai.value = data.karyawans || []
  } catch (err) {
    console.error('Failed to load master pegawai:', err)
  }
}

const uniquePegawaiDepts = computed(() => {
  const set = new Set()
  masterPegawai.value.forEach(k => {
    if (k.departemen) set.add(k.departemen)
  })
  return Array.from(set).sort()
})

const deptFilterOptions = computed(() => [
  { value: '', label: l('-- All Work Units --', '-- Seluruh Unit Kerja --') },
  ...uniquePegawaiDepts.value.map(d => ({ value: d, label: d }))
])

const filteredMasterPegawai = computed(() => {
  let list = masterPegawai.value
  if (filterPegawaiDept.value) {
    list = list.filter(k => k.departemen === filterPegawaiDept.value)
  }
  if (searchPegawai.value) {
    const q = searchPegawai.value.toLowerCase()
    list = list.filter(k =>
      (k.nik && k.nik.toLowerCase().includes(q)) ||
      (k.nama && k.nama.toLowerCase().includes(q)) ||
      (k.departemen && k.departemen.toLowerCase().includes(q)) ||
      (k.jabatan && k.jabatan.toLowerCase().includes(q)) ||
      (k.kantor_cabang && k.kantor_cabang.toLowerCase().includes(q))
    )
  }
  return list
})

function isPegawaiEnrolledInDetail(k) {
  return trainingClass.value?.enrollments?.some(e =>
    (e.nik && k.nik && e.nik.toLowerCase() === k.nik.toLowerCase()) ||
    (e.participant_name && k.nama && e.participant_name.toLowerCase() === k.nama.toLowerCase())
  )
}

function toggleSelectPegawai(k) {
  if (isPegawaiEnrolledInDetail(k)) return
  const id = k.karyawan_id
  const idx = selectedPegawaiIds.value.indexOf(id)
  if (idx >= 0) {
    selectedPegawaiIds.value.splice(idx, 1)
  } else {
    selectedPegawaiIds.value.push(id)
  }
}

function selectAllFilteredPegawai() {
  const available = filteredMasterPegawai.value.filter(k => !isPegawaiEnrolledInDetail(k))
  const allSelected = available.length > 0 && available.every(k => selectedPegawaiIds.value.includes(k.karyawan_id))
  if (allSelected) {
    selectedPegawaiIds.value = []
  } else {
    selectedPegawaiIds.value = available.map(k => k.karyawan_id)
  }
}

async function handleEnrollSelectedPegawai() {
  if (!selectedPegawaiIds.value.length) return
  isEnrollingPegawai.value = true
  try {
    const selected = masterPegawai.value.filter(k => selectedPegawaiIds.value.includes(k.karyawan_id))
    const payload = {
      participants: selected.map(k => ({
        user_id: k.user?.id || k.karyawan_id,
        nik: k.nik,
        name: k.nama,
        department: k.departemen,
        branch: k.kantor_cabang || 'Kantor Pusat Sudirman',
        position: k.jabatan || 'Staff'
      }))
    }
    await apiClient.post(`/classes/${props.classId}/enroll`, payload)
    showPegawaiModal.value = false
    selectedPegawaiIds.value = []
    actionFeedback.value = {
      type: 'success',
      message: l(
        `${selected.length} participants from Employee Master have been enrolled in this class batch.`,
        `${selected.length} peserta dari Master Pegawai berhasil didaftarkan ke angkatan kelas ini.`
      )
    }
    fetchClassDetail()
  } catch (err) {
    console.error('Failed to enroll pegawai:', err)
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to enroll participants from employee master.', 'Gagal mendaftarkan peserta dari master pegawai.')
    }
  } finally {
    isEnrollingPegawai.value = false
  }
}

async function fetchClassDetail() {
  loading.value = true
  try {
    const { data } = await apiClient.get(`/classes/${props.classId}`)
    trainingClass.value = data.class
    classStats.value = data.stats || {}
    availableNominations.value = data.available_program_participants || []

    // Select first session by default
    if (data.class?.sessions?.length && !selectedSessionId.value) {
      selectedSessionId.value = data.class.sessions[0].id
    }

    initAttendanceState()
    initScoreState()
  } catch (err) {
    console.error('Failed to load class detail:', err)
    actionFeedback.value = {
      type: 'error',
      message: l('Failed to load class batch details. Ensure the ID is valid.', 'Gagal memuat rincian angkatan kelas. Pastikan ID data valid.')
    }
  } finally {
    loading.value = false
  }
}

function initAttendanceState() {
  if (!trainingClass.value || !selectedSessionId.value) return
  const currentSessId = selectedSessionId.value
  const records = {}

  trainingClass.value.enrollments?.forEach(e => {
    const existingAtt = e.attendances?.find(a => a.class_session_id === currentSessId)
    records[e.id] = {
      status: existingAtt?.status || 'HADIR',
      check_in_time: existingAtt?.check_in_time || '08:15 WIB',
      notes: existingAtt?.notes || ''
    }
  })
  attendanceRecords.value = records
}

function initScoreState() {
  if (!trainingClass.value) return
  const records = {}
  trainingClass.value.enrollments?.forEach(e => {
    records[e.id] = {
      pre_test_score: e.pre_test_score !== null ? e.pre_test_score : '',
      post_test_score: e.post_test_score !== null ? e.post_test_score : '',
      task_score: e.task_score !== null ? e.task_score : '',
      eval_instructor_rating: e.eval_instructor_rating || 5,
      eval_notes: e.eval_notes || ''
    }
  })
  scoreRecords.value = records
}

function onSessionChange(sessId) {
  selectedSessionId.value = sessId
  initAttendanceState()
}

const currentSession = computed(() => {
  return trainingClass.value?.sessions?.find(s => s.id === selectedSessionId.value)
})

// Current Session Attendance Summary
const currentSessionStats = computed(() => {
  const records = Object.values(attendanceRecords.value)
  const total = records.length
  if (!total) return { total: 0, hadir: 0, izin: 0, sakit: 0, alpa: 0, pct: 0 }
  const hadir = records.filter(r => r.status === 'HADIR').length
  const izin = records.filter(r => r.status === 'IZIN').length
  const sakit = records.filter(r => r.status === 'SAKIT').length
  const alpa = records.filter(r => r.status === 'ALPA').length
  return {
    total,
    hadir,
    izin,
    sakit,
    alpa,
    pct: Math.round((hadir / total) * 100)
  }
})

function formatAttendanceShort(status) {
  if (status === 'HADIR') return l('PRS', 'HAD')
  if (status === 'IZIN') return l('EXC', 'IZN')
  if (status === 'SAKIT') return l('SCK', 'SKT')
  if (status === 'ALPA') return l('ABS', 'ALP')
  return status?.substring(0, 3) || '-'
}

// Quick Mark All Present for Selected Session
async function handleQuickMarkAllPresent() {
  if (!selectedSessionId.value) return
  isSavingAttendance.value = true
  actionFeedback.value = { type: '', message: '' }
  try {
    await apiClient.post(`/classes/${props.classId}/attendance/quick-all`, {
      session_id: selectedSessionId.value
    })
    actionFeedback.value = { type: 'success', message: l('All participants in this session have been marked as present.', 'Seluruh peserta sesi ini berhasil ditandai hadir.') }
    // Update local state
    Object.keys(attendanceRecords.value).forEach(k => {
      attendanceRecords.value[k].status = 'HADIR'
      attendanceRecords.value[k].check_in_time = '08:15 WIB'
    })
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to mark all participants present.', 'Gagal menandai seluruh hadir.')
    }
  } finally {
    isSavingAttendance.value = false
  }
}

// Save Current Session Attendance
async function handleSaveAttendance() {
  if (!selectedSessionId.value) return
  isSavingAttendance.value = true
  actionFeedback.value = { type: '', message: '' }
  try {
    const payload = {
      session_id: selectedSessionId.value,
      attendances: Object.entries(attendanceRecords.value).map(([enrollmentId, att]) => ({
        enrollment_id: parseInt(enrollmentId),
        status: att.status,
        check_in_time: att.check_in_time,
        notes: att.notes
      }))
    }
    await apiClient.post(`/classes/${props.classId}/attendance`, payload)
    actionFeedback.value = { type: 'success', message: l('Session attendance records have been saved.', 'Presensi sesi berhasil disimpan.') }
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to save attendance records.', 'Gagal menyimpan presensi.')
    }
  } finally {
    isSavingAttendance.value = false
  }
}

// Save Exam Scores & Evaluation
async function handleSaveScores() {
  isSavingScores.value = true
  actionFeedback.value = { type: '', message: '' }
  try {
    const payload = {
      scores: Object.entries(scoreRecords.value).map(([enrollmentId, s]) => ({
        enrollment_id: parseInt(enrollmentId),
        pre_test_score: s.pre_test_score !== '' ? parseFloat(s.pre_test_score) : null,
        post_test_score: s.post_test_score !== '' ? parseFloat(s.post_test_score) : null,
        task_score: s.task_score !== '' ? parseFloat(s.task_score) : null,
        eval_instructor_rating: s.eval_instructor_rating ? parseInt(s.eval_instructor_rating) : null,
        eval_notes: s.eval_notes
      }))
    }
    await apiClient.post(`/classes/${props.classId}/evaluation/scores`, payload)
    actionFeedback.value = { type: 'success', message: l('Exam scores and evaluations have been saved.', 'Nilai evaluasi berhasil disimpan.') }
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to save evaluation scores.', 'Gagal menyimpan nilai evaluasi.')
    }
  } finally {
    isSavingScores.value = false
  }
}

// Generate Certificates for Eligible Participants
async function handleGenerateCertificates() {
  isGeneratingCertificates.value = true
  actionFeedback.value = { type: '', message: '' }
  try {
    await apiClient.post(`/classes/${props.classId}/certificates/generate`)
    actionFeedback.value = { type: 'success', message: l('Eligible participant e-certificates have been issued.', 'E-sertifikat peserta yang memenuhi syarat berhasil diterbitkan.') }
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to issue certificates.', 'Gagal menerbitkan sertifikat.')
    }
  } finally {
    isGeneratingCertificates.value = false
  }
}

// Checker Approval
async function handleCheckerDecision(decision) {
  try {
    await apiClient.post(`/classes/${props.classId}/approve`, {
      decision: decision,
      checker_notes: l(
        `Authorized by Checker: ${auth.user?.name || 'HC Learning Head'}`,
        `Disetujui oleh Verifikator: ${auth.user?.name || 'HC Learning Head'}`
      )
    })
    actionFeedback.value = { type: 'success', message: l('Class batch authorization decision has been processed.', 'Keputusan otorisasi angkatan kelas berhasil diproses.') }
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to process class batch authorization.', 'Gagal memproses otorisasi angkatan kelas.')
    }
  }
}

// Enroll Selected Nominations
async function handleEnrollSelectedNominations() {
  if (!selectedNominationIds.value.length) return
  const participantsToEnroll = availableNominations.value
    .filter(n => selectedNominationIds.value.includes(n.id))
    .map(n => ({
      nik: n.nik,
      name: n.name,
      department: n.department,
      branch: n.branch,
      position: n.position
    }))

  try {
    await apiClient.post(`/classes/${props.classId}/enroll`, {
      participants: participantsToEnroll
    })
    actionFeedback.value = { type: 'success', message: l('Selected nominations have been enrolled into this class batch.', 'Peserta nominasi terpilih berhasil ditambahkan ke angkatan kelas.') }
    showAddParticipantModal.value = false
    selectedNominationIds.value = []
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to enroll participants.', 'Gagal menambahkan peserta.')
    }
  }
}

// Remove Participant
async function handleRemoveParticipant(enrollmentId) {
  if (!confirm(l('Are you sure you want to remove this participant from the class batch?', 'Apakah Anda yakin ingin menghapus peserta ini dari angkatan kelas?'))) return
  try {
    await apiClient.delete(`/classes/${props.classId}/enroll/${enrollmentId}`)
    actionFeedback.value = { type: 'success', message: l('Participant has been removed from this batch.', 'Peserta berhasil dihapus dari angkatan.') }
    await fetchClassDetail()
  } catch (err) {
    actionFeedback.value = {
      type: 'error',
      message: err.response?.data?.message || l('Failed to remove participant.', 'Gagal menghapus peserta.')
    }
  }
}

onMounted(() => {
  fetchClassDetail()
  fetchMasterPegawai()
})
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-16">

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-2xs">
      <div class="inline-block animate-spin w-8 h-8 border-4 border-[#0067AC] border-t-transparent rounded-full mb-3"></div>
      <p class="text-xs text-gray-500 font-semibold">{{ $l('Loading class batch operational data...', 'Memuat data operasional angkatan kelas...') }}</p>
    </div>

    <template v-else-if="trainingClass">
      
      <!-- Standard Subpage Header (Navigation & Actions) -->
      <SubpageHeader
        :back-label="$l('Back to Class List', 'Kembali ke Daftar Kelas')"
        :title="trainingClass.batch_name"
        :subtitle="$l(`Program: ${trainingClass.program?.title || ''} • ${trainingClass.program?.category || ''} • Method: ${trainingClass.method || ''}`, `Program: ${trainingClass.program?.title || ''} • ${trainingClass.program?.category || ''} • Metode: ${trainingClass.method || ''}`)"
        :badge="trainingClass.class_code"
        :status-badge="trainingClass.dual_control_status === 'APPROVED' ? $l('Authorized by Checker', 'Terotorisasi Verifikator') : trainingClass.dual_control_status === 'PENDING_CHECKER' ? $l('Pending Checker Authorization', 'Menunggu Otorisasi Verifikator') : trainingClass.dual_control_status"
        :status-class="trainingClass.dual_control_status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
        @back="emit('back')"
      >
        <template #actions>
          <div v-if="trainingClass.dual_control_status === 'PENDING_CHECKER'" class="flex items-center gap-2">
            <button
              @click="handleCheckerDecision('APPROVED')"
              class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
            >
              <Check class="w-3.5 h-3.5" />
              <span>{{ $l('Authorize (Approve)', 'Otorisasi (Setujui)') }}</span>
            </button>
            <button
              @click="handleCheckerDecision('REJECTED')"
              class="px-3.5 py-2 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition cursor-pointer"
            >
              {{ $l('Reject', 'Tolak') }}
            </button>
          </div>

          <button
            @click="fetchClassDetail"
            class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200 cursor-pointer shadow-2xs"
            :title="$l('Refresh Data', 'Segarkan Data')"
          >
            <RotateCcw class="w-4 h-4" />
          </button>
        </template>
      </SubpageHeader>

      <!-- KPI Ribbon & Navigation Tabs Card -->
      <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-2xs space-y-4">

        <!-- Metric KPI Ribbon Strip -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 pt-3 border-t border-gray-100 text-xs">
          <div class="bg-blue-50/50 p-3 rounded-2xl border border-blue-100/60">
            <span class="text-[10px] text-gray-500 block font-medium">{{ $l('Participant Capacity', 'Kapasitas Peserta') }}</span>
            <span class="text-base font-extrabold text-[#002855]">
              {{ trainingClass.enrollments?.length || 0 }} / {{ trainingClass.max_quota }} {{ $l('Seats', 'Kursi') }}
            </span>
          </div>

          <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100/60">
            <span class="text-[10px] text-gray-500 block font-medium">{{ $l('Training Sessions', 'Sesi Pelatihan') }}</span>
            <span class="text-base font-extrabold text-purple-800">
              {{ trainingClass.sessions?.length || 0 }} {{ $l('Daily Sessions', 'Sesi Harian') }}
            </span>
          </div>

          <div class="bg-emerald-50/50 p-3 rounded-2xl border border-emerald-100/60">
            <span class="text-[10px] text-gray-500 block font-medium">{{ $l('Average Attendance', 'Rata-rata Kehadiran') }}</span>
            <span class="text-base font-extrabold text-emerald-700 font-mono">
              {{ classStats.avg_attendance_pct || 0 }}%
            </span>
          </div>

          <div class="bg-amber-50/50 p-3 rounded-2xl border border-amber-100/60">
            <span class="text-[10px] text-gray-500 block font-medium">{{ $l('Skill Gain (Δ%)', 'Peningkatan Kompetensi (Δ%)') }}</span>
            <span class="text-base font-extrabold text-amber-700 font-mono">
              +{{ classStats.competency_gain_pct || 0 }}%
            </span>
          </div>

          <div class="bg-teal-50/50 p-3 rounded-2xl border border-teal-100/60">
            <span class="text-[10px] text-gray-500 block font-medium">{{ $l('Participant Pass Rate', 'Kelulusan Peserta') }}</span>
            <span class="text-base font-extrabold text-teal-800 font-mono">
              {{ classStats.passed_count || 0 }} {{ $l('Passed', 'Lulus') }} ({{ classStats.pass_rate_pct || 0 }}%)
            </span>
          </div>
        </div>

        <!-- Sub-Tab Navigation Header -->
        <div class="flex items-center gap-2 pt-2 border-t border-gray-100 overflow-x-auto">
          <button
            @click="activeSubTab = 'attendance'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer"
            :class="activeSubTab === 'attendance' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
          >
            <Clock class="w-3.5 h-3.5" />
            <span>{{ $l('Attendance Module', 'Modul Kehadiran') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeSubTab === 'attendance' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'">
              {{ trainingClass.sessions?.length || 0 }} {{ $l('Sessions', 'Sesi') }}
            </span>
          </button>

          <button
            @click="activeSubTab = 'evaluation'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer"
            :class="activeSubTab === 'evaluation' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
          >
            <Award class="w-3.5 h-3.5" />
            <span>{{ $l('Class Evaluation & Exams', 'Evaluasi Hasil Kelas & Ujian') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeSubTab === 'evaluation' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'">
              {{ $l('Pass:', 'Lulus:') }} {{ trainingClass.passing_grade || 75 }}
            </span>
          </button>

          <button
            @click="activeSubTab = 'overview'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer"
            :class="activeSubTab === 'overview' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
          >
            <BookOpen class="w-3.5 h-3.5" />
            <span>{{ $l('Overview & Session Schedule', 'Ikhtisar & Jadwal Sesi') }}</span>
          </button>

          <button
            @click="activeSubTab = 'roster'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer"
            :class="activeSubTab === 'roster' ? 'bg-[#0067AC] text-white shadow-xs' : 'text-gray-600 hover:bg-gray-100'"
          >
            <Users class="w-3.5 h-3.5" />
            <span>{{ $l('Participant Roster', 'Daftar Peserta') }}</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="activeSubTab === 'roster' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'">
              {{ trainingClass.enrollments?.length || 0 }}
            </span>
          </button>
        </div>

      </div>

      <!-- Action Feedback Alert -->
      <div v-if="actionFeedback.message" class="p-4 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-2xs"
        :class="actionFeedback.type === 'error' ? 'bg-rose-50 border border-rose-200 text-rose-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-800'">
        <div class="flex items-center gap-2.5">
          <AlertCircle v-if="actionFeedback.type === 'error'" class="w-4 h-4 shrink-0" />
          <CheckCircle2 v-else class="w-4 h-4 shrink-0" />
          <span>{{ actionFeedback.message }}</span>
        </div>
        <button @click="actionFeedback.message = ''" class="font-bold text-sm cursor-pointer">&times;</button>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 1: MODUL KEHADIRAN (ATTENDANCE TRACKING) -->
      <!-- ============================================================== -->
      <div v-if="activeSubTab === 'attendance'" class="flex flex-col gap-5">
        
        <!-- Session Pills Bar -->
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-2xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider shrink-0 mr-1">{{ $l('Select Session:', 'Pilih Sesi:') }}</span>
            <button
              v-for="s in trainingClass.sessions"
              :key="s.id"
              @click="onSessionChange(s.id)"
              class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 cursor-pointer"
              :class="selectedSessionId === s.id ? 'bg-[#002855] text-white shadow-2xs' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
            >
              <Clock class="w-3.5 h-3.5" />
              <span>{{ $l('Session', 'Sesi') }} {{ s.session_number }}</span>
              <span class="text-[10px] opacity-80 font-normal">({{ s.session_date?.substring(5) }})</span>
            </button>
          </div>

          <!-- Session Quick Actions -->
          <div class="flex items-center gap-2 shrink-0 w-full md:w-auto justify-end">
            <button
              type="button"
              :disabled="isSavingAttendance"
              @click="handleQuickMarkAllPresent"
              class="px-3.5 py-2 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <CheckCircle2 class="w-3.5 h-3.5" />
              <span>{{ $l('Mark All Present', 'Tandai Semua Hadir') }}</span>
            </button>

            <button
              type="button"
              :disabled="isSavingAttendance"
              @click="handleSaveAttendance"
              class="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
            >
              <Save class="w-3.5 h-3.5" />
              <span>{{ isSavingAttendance ? $l('Saving...', 'Menyimpan...') : $l('Save Session Attendance', 'Simpan Presensi Sesi Ini') }}</span>
            </button>
          </div>
        </div>

        <!-- Selected Session Header Info & Stats -->
        <div v-if="currentSession" class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 text-xs">
              <span class="font-bold text-[#0067AC]">{{ $l('Session', 'Sesi') }} {{ currentSession.session_number }}</span>
              <span class="text-gray-300">•</span>
              <span class="text-gray-500">{{ currentSession.session_date }}</span>
              <span class="text-gray-300">•</span>
              <span class="font-mono text-gray-600">{{ currentSession.start_time }} - {{ currentSession.end_time }} WIB</span>
            </div>
            <h3 class="text-base font-extrabold text-[#002855] mt-1">{{ currentSession.session_name }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ currentSession.topic || $l('BWS Training Material', 'Materi Pelatihan BWS') }}</p>
          </div>

          <!-- Session Breakdown Badges -->
          <div class="flex items-center gap-2 shrink-0">
            <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-extrabold">
              {{ currentSessionStats.hadir }} {{ $l('Present', 'Hadir') }}
            </span>
            <span class="px-3 py-1 rounded-xl bg-amber-50 text-amber-700 text-xs font-extrabold">
              {{ currentSessionStats.izin }} {{ $l('Excused', 'Izin') }}
            </span>
            <span class="px-3 py-1 rounded-xl bg-blue-50 text-blue-700 text-xs font-extrabold">
              {{ currentSessionStats.sakit }} {{ $l('Sick', 'Sakit') }}
            </span>
            <span class="px-3 py-1 rounded-xl bg-rose-50 text-rose-700 text-xs font-extrabold">
              {{ currentSessionStats.alpa }} {{ $l('Absent', 'Alpa') }}
            </span>
            <span class="px-3 py-1 rounded-xl bg-purple-50 text-purple-700 text-xs font-black font-mono">
              {{ currentSessionStats.pct }}%
            </span>
          </div>
        </div>

        <!-- Attendance Participant Input Table -->
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-gray-100">
            <div>
              <h4 class="text-sm font-extrabold text-[#002855]">{{ $l('Participant Attendance Sheet', 'Daftar Presensi Peserta Diklat') }}</h4>
              <p class="text-[11px] text-gray-500">{{ $l('Select attendance status for participants in the active session.', 'Pilih status kehadiran peserta untuk sesi yang sedang aktif.') }}</p>
            </div>
            <span class="text-xs text-gray-400 font-medium">
              {{ $l('Total:', 'Total:') }} {{ trainingClass.enrollments?.length || 0 }} {{ $l('Participants', 'Peserta') }}
            </span>
          </div>

          <div class="overflow-x-auto border border-gray-100 rounded-2xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-gray-50/80 text-gray-600 font-bold border-b border-gray-100">
                <tr>
                  <th class="py-2.5 px-3 w-10 text-center">No</th>
                  <th class="py-2.5 px-3">{{ $l('Employee Name & NIK', 'Nama Pegawai & NIK') }}</th>
                  <th class="py-2.5 px-3">{{ $l('Division / Work Unit', 'Divisi / Unit Kerja') }}</th>
                  <th class="py-2.5 px-3 w-64 text-center">{{ $l('Attendance Status', 'Status Presensi') }}</th>
                  <th class="py-2.5 px-3 w-32">{{ $l('Check-In Time', 'Jam Masuk') }}</th>
                  <th class="py-2.5 px-3">{{ $l('Notes / Dispensation', 'Catatan / Dispensasi') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-700">
                <tr
                  v-for="(e, idx) in trainingClass.enrollments"
                  :key="e.id"
                  class="hover:bg-gray-50/50"
                  :class="attendanceRecords[e.id]?.status === 'HADIR' ? 'bg-emerald-50/15' : attendanceRecords[e.id]?.status === 'ALPA' ? 'bg-rose-50/20' : ''"
                >
                  <td class="py-2.5 px-3 text-center text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-2.5 px-3">
                    <p class="font-bold text-gray-900">{{ e.participant_name || e.user?.name || $l('BWS Participant', 'Peserta BWS') }}</p>
                    <p class="font-mono text-[10px] text-gray-400">{{ e.nik || e.user?.nik || '-' }}</p>
                  </td>
                  <td class="py-2.5 px-3 text-gray-600">
                    <p>{{ e.department || e.user?.department || 'Operations' }}</p>
                    <p class="text-[10px] text-gray-400">{{ e.position || e.user?.role_name || '-' }}</p>
                  </td>

                  <!-- Status Selector Buttons -->
                  <td class="py-2.5 px-3">
                    <div class="flex items-center justify-center gap-1">
                      <button
                        type="button"
                        @click="attendanceRecords[e.id].status = 'HADIR'"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer"
                        :class="attendanceRecords[e.id]?.status === 'HADIR' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-emerald-50'"
                      >
                        {{ $l('Present', 'Hadir') }}
                      </button>
                      <button
                        type="button"
                        @click="attendanceRecords[e.id].status = 'IZIN'"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer"
                        :class="attendanceRecords[e.id]?.status === 'IZIN' ? 'bg-amber-500 text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-amber-50'"
                      >
                        {{ $l('Excused', 'Izin') }}
                      </button>
                      <button
                        type="button"
                        @click="attendanceRecords[e.id].status = 'SAKIT'"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer"
                        :class="attendanceRecords[e.id]?.status === 'SAKIT' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-blue-50'"
                      >
                        {{ $l('Sick', 'Sakit') }}
                      </button>
                      <button
                        type="button"
                        @click="attendanceRecords[e.id].status = 'ALPA'"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition cursor-pointer"
                        :class="attendanceRecords[e.id]?.status === 'ALPA' ? 'bg-rose-600 text-white shadow-2xs' : 'bg-gray-100 text-gray-600 hover:bg-rose-50'"
                      >
                        {{ $l('Absent', 'Alpa') }}
                      </button>
                    </div>
                  </td>

                  <td class="py-2.5 px-3">
                    <input
                      type="text"
                      v-model="attendanceRecords[e.id].check_in_time"
                      placeholder="08:15 WIB"
                      class="w-full bg-white border border-gray-200 rounded-xl px-2 py-1 text-xs text-center font-mono text-gray-800 focus:outline-none focus:border-[#0067AC]"
                    />
                  </td>

                  <td class="py-2.5 px-3">
                    <input
                      type="text"
                      v-model="attendanceRecords[e.id].notes"
                      :placeholder="$l('Notes / assignment letter...', 'Catatan / surat tugas...')"
                      class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC]"
                    />
                  </td>
                </tr>
                <tr v-if="!trainingClass.enrollments?.length">
                  <td colspan="6" class="py-8 text-center text-gray-400">
                    {{ $l('No participants enrolled in this batch yet. Open the "Participant Roster" tab to enroll participants.', 'Belum ada peserta terdaftar dalam angkatan ini. Buka tab "Daftar Peserta" untuk mendaftarkan peserta.') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Cumulative Attendance Matrix (All Sessions) -->
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs space-y-3">
          <div class="flex items-center justify-between pb-2 border-b border-gray-100">
            <div>
              <h4 class="text-sm font-extrabold text-[#002855]">{{ $l('Cumulative Attendance Matrix Across All Sessions', 'Rekapitulasi Kehadiran Kumulatif Seluruh Sesi') }}</h4>
              <p class="text-[11px] text-gray-500">{{ $l('Minimum attendance requirement for certification is ≥ 80%.', 'Syarat minimal kelulusan sertifikasi adalah kehadiran ≥ 80%.') }}</p>
            </div>
            <span class="text-xs font-bold text-[#0067AC]">
              {{ classStats.eligible_for_cert_count || 0 }} / {{ trainingClass.enrollments?.length || 0 }} {{ $l('Eligible ≥ 80%', 'Memenuhi Syarat ≥ 80%') }}
            </span>
          </div>

          <div class="overflow-x-auto border border-gray-100 rounded-2xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-gray-50/80 text-gray-600 font-bold border-b border-gray-100">
                <tr>
                  <th class="py-2 px-3 w-10 text-center">No</th>
                  <th class="py-2 px-3">{{ $l('Employee Name', 'Nama Pegawai') }}</th>
                  <th
                    v-for="s in trainingClass.sessions"
                    :key="s.id"
                    class="py-2 px-2 text-center w-16"
                  >
                    {{ $l('Session', 'Sesi') }} {{ s.session_number }}
                  </th>
                  <th class="py-2 px-3 text-center w-24">{{ $l('Attendance', 'Kehadiran') }}</th>
                  <th class="py-2 px-3 text-center w-28">{{ $l('Eligibility Status', 'Status Kelayakan') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-700">
                <tr v-for="(e, idx) in trainingClass.enrollments" :key="e.id">
                  <td class="py-2 px-3 text-center text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-2 px-3 font-bold text-gray-900">{{ e.participant_name || e.user?.name }}</td>
                  <td
                    v-for="s in trainingClass.sessions"
                    :key="s.id"
                    class="py-2 px-2 text-center"
                  >
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold"
                      :class="e.attendances?.find(a => a.class_session_id === s.id)?.status === 'HADIR' ? 'bg-emerald-100 text-emerald-800' : e.attendances?.find(a => a.class_session_id === s.id)?.status === 'IZIN' ? 'bg-amber-100 text-amber-800' : e.attendances?.find(a => a.class_session_id === s.id)?.status === 'SAKIT' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500'"
                    >
                      {{ formatAttendanceShort(e.attendances?.find(a => a.class_session_id === s.id)?.status) }}
                    </span>
                  </td>
                  <td class="py-2 px-3 text-center font-mono font-black text-[#0067AC]">
                    {{ e.attendance_percentage || 0 }}%
                  </td>
                  <td class="py-2 px-3 text-center">
                    <span
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                      :class="e.attendance_percentage >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                    >
                      {{ e.attendance_percentage >= 80 ? $l('Eligible', 'Memenuhi Syarat') : $l('Not Eligible', 'Belum Memenuhi') }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: EVALUASI HASIL KELAS & UJIAN (EVALUATION MODULE) -->
      <!-- ============================================================== -->
      <div v-if="activeSubTab === 'evaluation'" class="flex flex-col gap-5">
        
        <!-- Evaluation Top Banner & Controls -->
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC] uppercase tracking-wider">
                {{ $l('Kirkpatrick Model L1 & L2', 'Model Kirkpatrick L1 & L2') }}
              </span>
              <span class="text-xs text-gray-400">• {{ $l('BWS Evaluation Standard', 'Standar Evaluasi BWS') }}</span>
            </div>
            <h3 class="text-base font-extrabold text-[#002855] mt-1">
              {{ $l('Learning Outcome Evaluation & Pass Certification', 'Evaluasi Hasil Belajar & Pengesahan Kelulusan') }}
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
              <template v-if="$locale === 'en'">
                Minimum passing grade: <strong class="text-emerald-700">{{ trainingClass.passing_grade || 75 }} points</strong>. Score weighting: Pre-Test 30%, Post-Test 70%.
              </template>
              <template v-else>
                Batas nilai kelulusan: <strong class="text-emerald-700">{{ trainingClass.passing_grade || 75 }} poin</strong>. Bobot nilai: Tes Awal 30%, Tes Akhir 70%.
              </template>
            </p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              :disabled="isGeneratingCertificates"
              @click="handleGenerateCertificates"
              class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
            >
              <Award class="w-3.5 h-3.5" />
              <span>{{ isGeneratingCertificates ? $l('Issuing...', 'Menerbitkan...') : $l('Issue E-Certificates', 'Terbitkan E-Sertifikat') }}</span>
            </button>

            <button
              type="button"
              :disabled="isSavingScores"
              @click="handleSaveScores"
              class="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
            >
              <Save class="w-3.5 h-3.5" />
              <span>{{ isSavingScores ? $l('Saving...', 'Menyimpan...') : $l('Save Evaluation Scores', 'Simpan Nilai Evaluasi') }}</span>
            </button>
          </div>
        </div>

        <!-- Evaluation Statistics Ribbon -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
              <HelpCircle class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[11px] text-gray-400 font-medium">{{ $l('Average Pre-Test', 'Rata-rata Tes Awal') }}</p>
              <p class="text-xl font-extrabold text-gray-900 font-mono">{{ classStats.avg_pre_test || 0 }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
              <CheckCircle2 class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[11px] text-gray-400 font-medium">{{ $l('Average Post-Test', 'Rata-rata Tes Akhir') }}</p>
              <p class="text-xl font-extrabold text-emerald-700 font-mono">{{ classStats.avg_post_test || 0 }} <span class="text-xs text-gray-400 font-normal">/ 100</span></p>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
              <TrendingUp class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[11px] text-gray-400 font-medium">{{ $l('Competency Gain', 'Peningkatan Kompetensi') }}</p>
              <p class="text-xl font-extrabold text-[#0067AC] font-mono">+{{ classStats.competency_gain_pct || 0 }}%</p>
            </div>
          </div>

          <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
              <Star class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[11px] text-gray-400 font-medium">{{ $l('Instructor Rating', 'Penilaian Pengajar') }}</p>
              <p class="text-xl font-extrabold text-purple-700 font-mono">{{ classStats.avg_instructor_rating || '4.8' }} <span class="text-xs text-gray-400 font-normal">/ 5.0</span></p>
            </div>
          </div>
        </div>

        <!-- Participant Score Input Table -->
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-gray-100">
            <div>
              <h4 class="text-sm font-extrabold text-[#002855]">{{ $l('Exam Scores & Completion Status Entry Table', 'Tabel Pengisian Nilai Ujian & Status Kelulusan') }}</h4>
              <p class="text-[11px] text-gray-500">{{ $l('Enter Pre-Test and Post-Test scores for each participant.', 'Masukkan nilai Tes Awal dan Tes Akhir untuk setiap peserta.') }}</p>
            </div>
            <span class="text-xs font-bold text-emerald-700">
              {{ classStats.passed_count || 0 }} {{ $l('PASSED', 'LULUS') }} • {{ classStats.failed_count || 0 }} {{ $l('NOT PASSED', 'BELUM LULUS') }}
            </span>
          </div>

          <div class="overflow-x-auto border border-gray-100 rounded-2xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-gray-50/80 text-gray-600 font-bold border-b border-gray-100">
                <tr>
                  <th class="py-2.5 px-3 w-10 text-center">No</th>
                  <th class="py-2.5 px-3">{{ $l('Employee Name & NIK', 'Nama Pegawai & NIK') }}</th>
                  <th class="py-2.5 px-3 w-20 text-center">{{ $l('Present (%)', 'Hadir (%)') }}</th>
                  <th class="py-2.5 px-3 w-24 text-center">{{ $l('Pre-Test', 'Tes Awal') }}</th>
                  <th class="py-2.5 px-3 w-24 text-center">{{ $l('Post-Test', 'Tes Akhir') }}</th>
                  <th class="py-2.5 px-3 w-24 text-center">{{ $l('Final Score', 'Nilai Akhir') }}</th>
                  <th class="py-2.5 px-3 w-24 text-center">{{ $l('Delta (Δ%)', 'Selisih (Δ%)') }}</th>
                  <th class="py-2.5 px-3 w-28 text-center">{{ $l('Completion Status', 'Status Kelulusan') }}</th>
                  <th class="py-2.5 px-3 w-24 text-center">{{ $l('Trainer Rating', 'Nilai Pengajar') }}</th>
                  <th class="py-2.5 px-3">{{ $l('E-Certificate No.', 'No. E-Sertifikat') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-700">
                <tr v-for="(e, idx) in trainingClass.enrollments" :key="e.id" class="hover:bg-gray-50/50">
                  <td class="py-2 px-3 text-center text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-2 px-3">
                    <p class="font-bold text-gray-900">{{ e.participant_name || e.user?.name }}</p>
                    <p class="font-mono text-[10px] text-gray-400">{{ e.nik || e.user?.nik || '-' }}</p>
                  </td>
                  <td class="py-2 px-3 text-center font-mono font-bold"
                    :class="e.attendance_percentage >= 80 ? 'text-emerald-700' : 'text-rose-600'">
                    {{ e.attendance_percentage || 0 }}%
                  </td>

                  <!-- Input Pre-Test -->
                  <td class="py-2 px-3 text-center">
                    <input
                      type="number"
                      min="0"
                      max="100"
                      v-model="scoreRecords[e.id].pre_test_score"
                      placeholder="60"
                      class="w-16 bg-white border border-gray-200 rounded-xl px-2 py-1 text-center font-mono text-xs font-bold text-gray-800 focus:outline-none focus:border-[#0067AC]"
                    />
                  </td>

                  <!-- Input Post-Test -->
                  <td class="py-2 px-3 text-center">
                    <input
                      type="number"
                      min="0"
                      max="100"
                      v-model="scoreRecords[e.id].post_test_score"
                      placeholder="85"
                      class="w-16 bg-white border border-gray-200 rounded-xl px-2 py-1 text-center font-mono text-xs font-bold text-[#0067AC] focus:outline-none focus:border-[#0067AC]"
                    />
                  </td>

                  <!-- Calculated Final Score -->
                  <td class="py-2 px-3 text-center font-mono font-black text-gray-900">
                    {{ e.final_score !== null ? e.final_score : '-' }}
                  </td>

                  <!-- Delta Gain % -->
                  <td class="py-2 px-3 text-center font-mono font-bold">
                    <span v-if="e.pre_test_score && e.post_test_score"
                      :class="e.post_test_score >= e.pre_test_score ? 'text-emerald-700' : 'text-rose-600'">
                      {{ e.post_test_score >= e.pre_test_score ? '+' : '' }}{{ Math.round(((e.post_test_score - e.pre_test_score) / e.pre_test_score) * 100) }}%
                    </span>
                    <span v-else class="text-gray-300">-</span>
                  </td>

                  <!-- Status Kelulusan Badge -->
                  <td class="py-2 px-3 text-center">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-extrabold whitespace-nowrap"
                      :class="e.completion_status === 'PASSED' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : e.completion_status === 'FAILED' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-gray-100 text-gray-600'"
                    >
                      {{ e.completion_status === 'PASSED' ? $l('PASSED', 'LULUS') + ' (' + (e.grade || 'B') + ')' : e.completion_status === 'FAILED' ? $l('FAILED', 'TIDAK LULUS') : $l('Pending Exam', 'Belum Ujian') }}
                    </span>
                  </td>

                  <!-- Instructor Rating Input -->
                  <td class="py-2 px-3 text-center">
                    <select
                      v-model="scoreRecords[e.id].eval_instructor_rating"
                      class="bg-white border border-gray-200 rounded-xl px-2 py-1 text-xs text-amber-600 font-bold focus:outline-none"
                    >
                      <option :value="5">5 &starf;</option>
                      <option :value="4">4 &starf;</option>
                      <option :value="3">3 &starf;</option>
                      <option :value="2">2 &starf;</option>
                      <option :value="1">1 &starf;</option>
                    </select>
                  </td>

                  <!-- Certificate Number -->
                  <td class="py-2 px-3">
                    <div v-if="e.certificate_number" class="flex items-center gap-1.5 text-[11px] font-mono text-emerald-700 font-bold">
                      <FileCheck2 class="w-3.5 h-3.5 shrink-0" />
                      <span class="truncate">{{ e.certificate_number }}</span>
                    </div>
                    <span v-else class="text-[10px] text-gray-400 italic">{{ $l('Not issued yet', 'Belum terbit') }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- ============================================================== -->
      <!-- TAB 3: IKHTISAR & JADWAL SESI (OVERVIEW) -->
      <!-- ============================================================== -->
      <div v-if="activeSubTab === 'overview'" class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- Left: Class Details & Schedule -->
        <div class="md:col-span-2 space-y-5">
          <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
            <h3 class="text-sm font-extrabold text-[#002855] pb-3 border-b border-gray-100">
              {{ $l('Batch Execution Details', 'Informasi Pelaksanaan Angkatan') }}
            </h3>

            <div class="grid grid-cols-2 gap-4 text-xs">
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Delivery Method', 'Metode Pelaksanaan') }}</span>
                <span class="font-bold text-gray-800 text-sm">{{ trainingClass.method }}</span>
              </div>
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Location / Room', 'Lokasi / Ruangan') }}</span>
                <span class="font-bold text-gray-800 text-sm">{{ trainingClass.location_or_url }}</span>
              </div>
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Schedule Dates', 'Jadwal Tanggal') }}</span>
                <span class="font-bold text-gray-800 text-sm">
                  {{ trainingClass.start_date?.substring(0, 10) }} {{ $l('to', 's/d') }} {{ trainingClass.end_date?.substring(0, 10) }}
                </span>
              </div>
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Daily Schedule Hours', 'Jam Pelaksanaan Harian') }}</span>
                <span class="font-bold text-gray-800 text-sm">{{ trainingClass.daily_schedule || '08:30 - 16:30 WIB' }}</span>
              </div>
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Instructor / Trainer', 'Pengajar / Instruktur') }}</span>
                <span class="font-bold text-[#0067AC] text-sm">{{ trainingClass.trainer_name || $l('BWS Trainer Team', 'Tim Pengajar BWS') }}</span>
              </div>
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Minimum Passing Grade', 'Nilai Minimum Kelulusan') }}</span>
                <span class="font-bold text-emerald-700 text-sm">{{ trainingClass.passing_grade || 75 }} {{ $l('Points Min.', 'Poin Minimal') }}</span>
              </div>
            </div>

            <div v-if="trainingClass.description" class="pt-3 border-t border-gray-100 text-xs">
              <span class="text-gray-400 block font-medium mb-1">{{ $l('Batch Syllabus Description:', 'Deskripsi Silabus Angkatan:') }}</span>
              <p class="text-gray-700 leading-relaxed">{{ trainingClass.description }}</p>
            </div>
          </div>

          <!-- Sessions List Table -->
          <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
            <h3 class="text-sm font-extrabold text-[#002855] pb-3 border-b border-gray-100">
              {{ $l('Daily Training Sessions', 'Daftar Sesi Pelatihan Harian') }} ({{ trainingClass.sessions?.length || 0 }} {{ $l('Sessions', 'Sesi') }})
            </h3>

            <div class="space-y-3">
              <div
                v-for="s in trainingClass.sessions"
                :key="s.id"
                class="p-4 rounded-2xl bg-gray-50/70 border border-gray-100 flex items-start justify-between gap-3 text-xs"
              >
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#0067AC] flex items-center justify-center font-bold font-mono shrink-0">
                    #{{ s.session_number }}
                  </div>
                  <div>
                    <h4 class="font-bold text-gray-900 text-sm">{{ s.session_name }}</h4>
                    <p class="text-gray-500 mt-0.5">{{ s.topic || $l('Training Material', 'Materi Pelatihan') }}</p>
                    <div class="flex items-center gap-2 text-[11px] text-gray-400 mt-1">
                      <span>{{ s.session_date }}</span>
                      <span>•</span>
                      <span>{{ s.start_time }} - {{ s.end_time }} WIB</span>
                    </div>
                  </div>
                </div>

                <button
                  @click="activeSubTab = 'attendance'; onSessionChange(s.id)"
                  class="px-3 py-1.5 rounded-xl bg-white hover:bg-gray-100 border border-gray-200 text-[#0067AC] text-xs font-bold transition shrink-0 cursor-pointer"
                >
                  {{ $l('Session Attendance', 'Presensi Sesi') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Maker-Checker & Program Card -->
        <div class="space-y-5">
          <!-- Dual Control Card -->
          <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-3 text-xs">
            <h3 class="font-extrabold text-[#002855] pb-2 border-b border-gray-100 flex items-center gap-2">
              <Scale class="w-4 h-4 text-amber-600" />
              <span>{{ $l('Banking Dual-Control Authorization', 'Otorisasi Kontrol Ganda Perbankan') }}</span>
            </h3>

            <div class="space-y-2.5">
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Dual-Control Status', 'Status Kontrol Ganda') }}</span>
                <span class="font-bold text-sm" :class="trainingClass.dual_control_status === 'APPROVED' ? 'text-emerald-700' : 'text-amber-600'">
                  {{ trainingClass.dual_control_status === 'APPROVED' ? $l('APPROVED', 'DISETUJUI') : trainingClass.dual_control_status === 'PENDING_CHECKER' ? $l('PENDING CHECKER', 'MENUNGGU VERIFIKATOR') : trainingClass.dual_control_status }}
                </span>
              </div>
              <div>
                <span class="text-gray-400 block font-medium">{{ $l('Created by (Maker)', 'Dibuat oleh (Pembuat)') }}</span>
                <span class="font-semibold text-gray-800">{{ trainingClass.maker?.name || $l('HC Training Officer', 'Petugas Pelatihan HC') }}</span>
              </div>
              <div v-if="trainingClass.checker">
                <span class="text-gray-400 block font-medium">{{ $l('Approved by (Checker)', 'Disetujui oleh (Verifikator)') }}</span>
                <span class="font-semibold text-gray-800">{{ trainingClass.checker?.name }}</span>
              </div>
              <div v-if="trainingClass.checker_notes" class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-[11px] text-gray-600">
                <strong>{{ $l('Checker Notes:', 'Catatan Verifikator:') }}</strong> {{ trainingClass.checker_notes }}
              </div>
            </div>
          </div>

          <!-- Program Parent Card -->
          <div v-if="trainingClass.program" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-3 text-xs">
            <h3 class="font-extrabold text-[#002855] pb-2 border-b border-gray-100 flex items-center gap-2">
              <BookOpen class="w-4 h-4 text-[#0067AC]" />
              <span>{{ $l('Parent Work Program', 'Program Kerja Induk') }}</span>
            </h3>

            <div>
              <span class="font-mono text-[#0067AC] font-bold">{{ trainingClass.program.program_code }}</span>
              <h4 class="font-bold text-gray-900 text-sm mt-0.5">{{ trainingClass.program.title }}</h4>
              <p class="text-gray-500 mt-1">{{ trainingClass.program.target_audience }}</p>
            </div>
          </div>
        </div>

      </div>

      <!-- ============================================================== -->
      <!-- TAB 4: DAFTAR PESERTA (ROSTER) -->
      <!-- ============================================================== -->
      <div v-if="activeSubTab === 'roster'" class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
          <div>
            <h3 class="text-sm font-extrabold text-[#002855]">{{ $l('Enrolled Participants (Batch Roster)', 'Daftar Peserta Terdaftar (Anggota Angkatan)') }}</h3>
            <p class="text-[11px] text-gray-500">
              {{ $l('Currently enrolled participants:', 'Total peserta terdaftar saat ini:') }} {{ trainingClass.enrollments?.length || 0 }} {{ $l('of quota', 'dari kuota') }} {{ trainingClass.max_quota }} {{ $l('seats.', 'kursi.') }}
            </p>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              @click="showPegawaiModal = true"
              class="px-4 py-2 bg-[#0067AC] hover:bg-[#00528c] text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
              <Users class="w-3.5 h-3.5" />
              <span>+ {{ $l('Add from Employee Master', 'Tambah dari Master Pegawai') }}</span>
            </button>
            <AppButton
              v-if="availableNominations.length"
              type="button"
              variant="outline"
              pill
              icon="plus"
              @click="showAddParticipantModal = true"
            >
              {{ $l('Add from Program Nominations', 'Tambah dari Nominasi Program') }} ({{ availableNominations.length }})
            </AppButton>
          </div>
        </div>

        <div class="overflow-x-auto border border-gray-100 rounded-2xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-gray-50/80 text-gray-600 font-bold border-b border-gray-100">
              <tr>
                <th class="py-2.5 px-3 w-10 text-center">No</th>
                <th class="py-2.5 px-3 w-28">NIK</th>
                <th class="py-2.5 px-3">{{ $l('Full Name', 'Nama Lengkap') }}</th>
                <th class="py-2.5 px-3">{{ $l('Division / Work Unit', 'Divisi / Unit Kerja') }}</th>
                <th class="py-2.5 px-3">{{ $l('Position', 'Jabatan') }}</th>
                <th class="py-2.5 px-3 text-center">{{ $l('Attendance (%)', 'Kehadiran (%)') }}</th>
                <th class="py-2.5 px-3 text-center">{{ $l('Final Score', 'Nilai Akhir') }}</th>
                <th class="py-2.5 px-3 text-center">{{ $l('Completion Status', 'Status Kelulusan') }}</th>
                <th class="py-2.5 px-3 w-16 text-center">{{ $l('Action', 'Aksi') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-700">
              <tr v-for="(e, idx) in trainingClass.enrollments" :key="e.id" class="hover:bg-gray-50/50">
                <td class="py-2 px-3 text-center text-gray-400">{{ idx + 1 }}</td>
                <td class="py-2 px-3 font-mono font-bold text-[#0067AC]">{{ e.nik || e.user?.nik || '-' }}</td>
                <td class="py-2 px-3 font-bold text-gray-900">{{ e.participant_name || e.user?.name }}</td>
                <td class="py-2 px-3 text-gray-600">{{ e.department || e.user?.department || 'Operations' }}</td>
                <td class="py-2 px-3 text-gray-500">{{ e.position || e.user?.role_name || '-' }}</td>
                <td class="py-2 px-3 text-center font-mono font-bold"
                  :class="e.attendance_percentage >= 80 ? 'text-emerald-700' : 'text-rose-600'">
                  {{ e.attendance_percentage || 0 }}%
                </td>
                <td class="py-2 px-3 text-center font-mono font-black text-gray-900">
                  {{ e.final_score !== null ? e.final_score : '-' }}
                </td>
                <td class="py-2 px-3 text-center">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="e.completion_status === 'PASSED' ? 'bg-emerald-50 text-emerald-800' : e.completion_status === 'FAILED' ? 'bg-rose-50 text-rose-700' : 'bg-gray-100 text-gray-600'"
                  >
                    {{ e.completion_status === 'PASSED' ? $l('PASSED', 'LULUS') : e.completion_status === 'FAILED' ? $l('FAILED', 'TIDAK LULUS') : $l('In Progress', 'Sedang Berjalan') }}
                  </span>
                </td>
                <td class="py-2 px-3 text-center">
                  <button
                    type="button"
                    @click="handleRemoveParticipant(e.id)"
                    class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition cursor-pointer"
                    :title="$l('Remove from Batch', 'Hapus dari Angkatan')"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal: Add Participant from Program Nominations -->
      <div v-if="showAddParticipantModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="showAddParticipantModal = false"></div>
        <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10 animate-scaleIn">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
              <h3 class="font-extrabold text-sm text-[#002855]">{{ $l('Enroll Participants from Program Nominations', 'Daftarkan Peserta dari Nominasi Program') }}</h3>
              <p class="text-xs text-gray-400">{{ $l('Select employees to add to this class batch.', 'Pilih pegawai yang akan dimasukkan ke dalam angkatan kelas ini.') }}</p>
            </div>
            <button @click="showAddParticipantModal = false" class="text-gray-400 hover:text-gray-600 font-bold text-lg cursor-pointer">&times;</button>
          </div>

          <div class="max-h-64 overflow-y-auto space-y-2 text-xs">
            <div
              v-for="p in availableNominations"
              :key="p.id"
              class="p-3 rounded-2xl border border-gray-200 flex items-center justify-between gap-2 hover:bg-gray-50 cursor-pointer"
              @click="selectedNominationIds.includes(p.id) ? selectedNominationIds = selectedNominationIds.filter(id => id !== p.id) : selectedNominationIds.push(p.id)"
            >
              <div>
                <p class="font-bold text-gray-900">{{ p.name }}</p>
                <p class="text-[10px] text-gray-400">{{ p.nik }} • {{ p.department }}</p>
              </div>
              <input
                type="checkbox"
                :checked="selectedNominationIds.includes(p.id)"
                class="w-4 h-4 text-[#0067AC] rounded cursor-pointer"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
            <button
              @click="showAddParticipantModal = false"
              class="px-4 py-2 rounded-full border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition cursor-pointer"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              @click="handleEnrollSelectedNominations"
              :disabled="!selectedNominationIds.length"
              class="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
            >
              {{ $l('Enroll', 'Daftarkan') }} ({{ selectedNominationIds.length }})
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Tambah Peserta dari Master Pegawai -->
      <div
        v-if="showPegawaiModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity"
      >
        <div class="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
          
          <!-- Header -->
          <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center">
                <Users class="w-5 h-5" />
              </div>
              <div>
                <h3 class="font-extrabold text-base text-gray-900">{{ $l('Add Participants from Employee Master', 'Tambah Peserta dari Master Pegawai') }}</h3>
                <p class="text-xs text-gray-500">
                  {{ $l('Select Bank Woori Saudara employees to enroll directly into this class batch', 'Pilih pegawai Bank Woori Saudara untuk didaftarkan langsung ke angkatan kelas ini') }}
                </p>
              </div>
            </div>
            <button
              type="button"
              @click="showPegawaiModal = false; selectedPegawaiIds = []"
              class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- Filter & Search Toolbar -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 pb-2 text-xs">
            <div class="sm:col-span-2 relative">
              <input
                v-model="searchPegawai"
                type="text"
                :placeholder="$l('Search NIK, Employee Name, Position, or Branch...', 'Cari NIK, Nama Pegawai, Jabatan, atau Cabang...')"
                class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0067AC]"
              />
              <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
            </div>

            <div class="w-full">
              <AppSearchableSelect
                v-model="filterPegawaiDept"
                :options="deptFilterOptions"
                :placeholder="$l('-- All Work Units --', '-- Seluruh Unit Kerja --')"
                :search-placeholder="$l('Search work unit...', 'Cari unit kerja...')"
                input-class="bg-gray-50 border-gray-200 text-gray-700 py-2"
              />
            </div>
          </div>

          <!-- Table List of Pegawai -->
          <div class="overflow-y-auto flex-1 my-3 border border-gray-100 rounded-2xl">
            <table class="w-full text-left text-xs">
              <thead class="bg-gray-50 text-gray-600 font-bold sticky top-0 border-b border-gray-100">
                <tr>
                  <th class="py-3 px-3 w-10 text-center">
                    <input
                      type="checkbox"
                      @change="selectAllFilteredPegawai"
                      :checked="filteredMasterPegawai.length > 0 && filteredMasterPegawai.filter(k => !isPegawaiEnrolledInDetail(k)).every(k => selectedPegawaiIds.includes(k.karyawan_id))"
                      class="rounded text-[#0067AC] cursor-pointer"
                    />
                  </th>
                  <th class="py-3 px-3">NIK</th>
                  <th class="py-3 px-3">{{ $l('Employee Name', 'Nama Pegawai') }}</th>
                  <th class="py-3 px-3">{{ $l('Department & Position', 'Departemen & Jabatan') }}</th>
                  <th class="py-3 px-3">{{ $l('Branch Office', 'Kantor Cabang') }}</th>
                  <th class="py-3 px-3 text-center">{{ $l('Status', 'Status') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-if="filteredMasterPegawai.length === 0">
                  <td colspan="6" class="py-8 text-center text-gray-400">
                    {{ $l('No employees match your search criteria.', 'Tidak ada data pegawai yang sesuai dengan pencarian.') }}
                  </td>
                </tr>
                <tr
                  v-for="k in filteredMasterPegawai"
                  :key="k.karyawan_id"
                  @click="toggleSelectPegawai(k)"
                  class="hover:bg-blue-50/30 transition cursor-pointer"
                  :class="isPegawaiEnrolledInDetail(k) ? 'bg-gray-50/70 opacity-60 cursor-not-allowed' : selectedPegawaiIds.includes(k.karyawan_id) ? 'bg-blue-50/50' : ''"
                >
                  <td class="py-2.5 px-3 text-center" @click.stop>
                    <input
                      v-if="!isPegawaiEnrolledInDetail(k)"
                      type="checkbox"
                      :checked="selectedPegawaiIds.includes(k.karyawan_id)"
                      @change="toggleSelectPegawai(k)"
                      class="rounded text-[#0067AC] cursor-pointer"
                    />
                    <Check v-else class="w-4 h-4 text-emerald-600 mx-auto" />
                  </td>
                  <td class="py-2.5 px-3 font-mono font-bold text-[#0067AC]">{{ k.nik }}</td>
                  <td class="py-2.5 px-3 font-bold text-gray-800">{{ k.nama }}</td>
                  <td class="py-2.5 px-3 text-gray-600">
                    <p class="font-medium text-gray-800">{{ k.departemen }}</p>
                    <p class="text-[10px] text-gray-400">{{ k.jabatan }}</p>
                  </td>
                  <td class="py-2.5 px-3 text-gray-600">{{ k.kantor_cabang || $l('Sudirman Head Office', 'Kantor Pusat Sudirman') }}</td>
                  <td class="py-2.5 px-3 text-center">
                    <span
                      v-if="isPegawaiEnrolledInDetail(k)"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200"
                    >
                      {{ $l('Already Enrolled', 'Sudah Terdaftar') }}
                    </span>
                    <span
                      v-else
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100"
                    >
                      {{ k.st_kerja === 'Aktif' || !k.st_kerja ? $l('Active', 'Aktif') : k.st_kerja }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer Actions -->
          <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-500">
              {{ $l('Selected:', 'Terpilih:') }} <strong class="text-[#0067AC]">{{ selectedPegawaiIds.length }} {{ $l('Employees', 'Pegawai') }}</strong>
            </p>
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="showPegawaiModal = false; selectedPegawaiIds = []"
                :disabled="isEnrollingPegawai"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
              >
                {{ $t('common.cancel') }}
              </button>
              <button
                type="button"
                @click="handleEnrollSelectedPegawai"
                :disabled="selectedPegawaiIds.length === 0 || isEnrollingPegawai"
                class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-bold transition cursor-pointer disabled:opacity-40 flex items-center gap-1.5"
              >
                <span>{{ isEnrollingPegawai ? $l('Enrolling...', 'Mendaftarkan...') : `${$l('Enroll to Class', 'Daftarkan ke Kelas')} (${selectedPegawaiIds.length})` }}</span>
              </button>
            </div>
          </div>

        </div>
      </div>

    </template>

  </div>
</template>
