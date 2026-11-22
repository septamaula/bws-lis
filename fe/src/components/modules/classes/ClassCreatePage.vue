<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
  AlertCircle,
  Sparkles,
  ChevronRight,
  ShieldCheck,
  UserCheck
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  initialProgramId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['back', 'created'])
const auth = useAuthStore()

const loadingPrograms = ref(true)
const programs = ref([])
const selectedProgram = ref(null)
const loadingProgramDetail = ref(false)
const availableNominations = ref([])
const isSubmitting = ref(false)
const feedback = ref({ type: '', message: '' })

// Form State
const form = ref({
  program_id: props.initialProgramId || '',
  batch_name: 'Batch 1 - 2026',
  method: 'In-Classroom',
  start_date: new Date().toISOString().substring(0, 10),
  end_date: new Date(Date.now() + 2 * 86400000).toISOString().substring(0, 10),
  daily_schedule: '08:30 - 16:30 WIB',
  location_or_url: 'BWS Corporate Learning Center, Ruang Diklat Lt. 8',
  max_quota: 25,
  trainer_name: 'Dr. Bambang Soedjarwo, M.M. (Senior Banking Trainer)',
  passing_grade: 75,
  description: '',
  notes: '',
  auto_approve: true,
  sessions: [],
  enrolled_participants: []
})

// New manual participant input
const newParticipant = ref({
  nik: '',
  name: '',
  department: '',
  branch: 'Kantor Pusat',
  position: ''
})

async function fetchPrograms() {
  loadingPrograms.value = true
  try {
    const { data } = await apiClient.get('/programs')
    programs.value = data.programs || []
    if (form.value.program_id) {
      onProgramSelected()
    }
  } catch (err) {
    console.error('Failed to load programs:', err)
  } finally {
    loadingPrograms.value = false
  }
}

async function onProgramSelected() {
  if (!form.value.program_id) {
    selectedProgram.value = null
    availableNominations.value = []
    return
  }
  loadingProgramDetail.value = true
  try {
    const { data } = await apiClient.get(`/programs/${form.value.program_id}`)
    selectedProgram.value = data.program
    availableNominations.value = data.program?.participants || []
    
    // Auto fill suggested batch name if default
    if (form.value.batch_name.startsWith('Batch 1')) {
      const existingCount = data.program?.classes_count || 0
      form.value.batch_name = `Batch ${existingCount + 1} - ${data.program.title.substring(0, 28)}...`
    }
    if (data.program.passing_grade) {
      form.value.passing_grade = data.program.passing_grade
    }
    generateSessions()
  } catch (err) {
    console.error('Failed to load program detail:', err)
  } finally {
    loadingProgramDetail.value = false
  }
}

function generateSessions() {
  if (!form.value.start_date || !form.value.end_date) return
  const start = new Date(form.value.start_date)
  const end = new Date(form.value.end_date)
  if (isNaN(start) || isNaN(end) || end < start) return

  const sessions = []
  let current = new Date(start)
  let count = 1

  while (current <= end && count <= 10) {
    const dateStr = current.toISOString().substring(0, 10)
    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
    const dayName = dayNames[current.getDay()]
    
    sessions.push({
      session_number: count,
      session_name: `Hari ke-${count}: ${dayName}, ${dateStr}`,
      session_date: dateStr,
      start_time: '08:30',
      end_time: '16:30',
      topic: `Modul Pembelajaran Sesi ${count}`,
      trainer_name: form.value.trainer_name,
      location_or_url: form.value.location_or_url
    })
    current.setDate(current.getDate() + 1)
    count++
  }
  form.value.sessions = sessions
}

function addSessionRow() {
  const nextNum = form.value.sessions.length + 1
  form.value.sessions.push({
    session_number: nextNum,
    session_name: `Sesi Tambahan ${nextNum}`,
    session_date: form.value.end_date || form.value.start_date,
    start_time: '08:30',
    end_time: '16:30',
    topic: 'Workshop & Diskusi Studi Kasus',
    trainer_name: form.value.trainer_name,
    location_or_url: form.value.location_or_url
  })
}

function removeSession(idx) {
  form.value.sessions.splice(idx, 1)
  form.value.sessions.forEach((s, i) => {
    s.session_number = i + 1
  })
}

function isParticipantSelected(p) {
  return form.value.enrolled_participants.some(ep => ep.nik === p.nik || (ep.name === p.name && ep.department === p.department))
}

function toggleNomination(p) {
  const idx = form.value.enrolled_participants.findIndex(ep => ep.nik === p.nik)
  if (idx > -1) {
    form.value.enrolled_participants.splice(idx, 1)
  } else {
    form.value.enrolled_participants.push({
      nik: p.nik,
      name: p.name,
      department: p.department,
      branch: p.branch || 'Kantor Pusat',
      position: p.position || 'Staff'
    })
  }
}

function selectAllNominations() {
  availableNominations.value.forEach(p => {
    if (!isParticipantSelected(p)) {
      form.value.enrolled_participants.push({
        nik: p.nik,
        name: p.name,
        department: p.department,
        branch: p.branch || 'Kantor Pusat',
        position: p.position || 'Staff'
      })
    }
  })
}

function clearAllSelectedParticipants() {
  form.value.enrolled_participants = []
}

function addManualParticipant() {
  if (!newParticipant.value.name) return
  form.value.enrolled_participants.push({ ...newParticipant.value })
  newParticipant.value = {
    nik: '',
    name: '',
    department: '',
    branch: 'Kantor Pusat',
    position: ''
  }
}

function removeEnrolled(idx) {
  form.value.enrolled_participants.splice(idx, 1)
}

async function submitForm() {
  if (!form.value.program_id) {
    feedback.value = { type: 'error', message: 'Silakan pilih Program Pelatihan induk terlebih dahulu.' }
    return
  }
  if (!form.value.batch_name) {
    feedback.value = { type: 'error', message: 'Nama batch kelas wajib diisi.' }
    return
  }
  if (!form.value.start_date || !form.value.end_date) {
    feedback.value = { type: 'error', message: 'Tanggal pelaksanaan mulai dan selesai wajib diisi.' }
    return
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const payload = { ...form.value }
    const { data } = await apiClient.post('/classes', payload)
    emit('created', data.message || 'Batch Kelas berhasil dibuka!')
  } catch (err) {
    console.error('Submit class error:', err)
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menyimpan batch kelas. Periksa kembali input Anda.'
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchPrograms()
})
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-16">
    
    <!-- Standard Subpage Header (Navigation) -->
    <SubpageHeader
      back-label="Kembali ke Daftar Kelas"
      title="Buka Batch Kelas Pelatihan Baru"
      subtitle="Pendaftaran batch pelaksanaan diklat, alokasi sesi jadwal harian, instruktur, dan nominasi peserta"
      badge="Class Batch Maker"
      @back="emit('back')"
    />

    <!-- Feedback Banner -->
    <div v-if="feedback.message" class="p-4 rounded-2xl text-xs font-semibold flex items-center gap-2.5 shadow-2xs"
      :class="feedback.type === 'error' ? 'bg-rose-50 border border-rose-200 text-rose-700' : 'bg-emerald-50 border border-emerald-200 text-emerald-800'">
      <AlertCircle v-if="feedback.type === 'error'" class="w-4 h-4 shrink-0" />
      <CheckCircle2 v-else class="w-4 h-4 shrink-0" />
      <span>{{ feedback.message }}</span>
    </div>

    <!-- Section 1: Pilihan Program Pelatihan Induk -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
      <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
        <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <BookOpen class="w-4 h-4" />
        </div>
        <div>
          <h2 class="text-sm font-extrabold text-[#002855]">1. Program Pelatihan Induk (Work Plan)</h2>
          <p class="text-[11px] text-gray-500">Pilih program kerja pelatihan yang menjadi payung kurikulum dari batch kelas ini.</p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 mb-1.5">
          Program Pelatihan Terdaftar <span class="text-rose-500">*</span>
        </label>
        <select
          v-model="form.program_id"
          @change="onProgramSelected"
          class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl px-4 py-3 text-xs font-semibold text-gray-800 focus:outline-none focus:border-[#0067AC] transition cursor-pointer"
          :disabled="loadingPrograms"
        >
          <option value="" disabled>-- Pilih Program Pelatihan --</option>
          <option v-for="p in programs" :key="p.id" :value="p.id">
            [{{ p.program_code }}] {{ p.title }} (Target: {{ p.target_participants_count || 0 }} Pegawai) - Kuartal {{ p.execution_quarter || 'Q1' }}
          </option>
        </select>
      </div>

      <!-- Preview Program Selected Details -->
      <div v-if="selectedProgram" class="p-4 rounded-2xl bg-[#EBF4FC]/60 border border-blue-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
        <div>
          <div class="flex items-center gap-2">
            <span class="font-mono font-bold text-[#0067AC]">{{ selectedProgram.program_code }}</span>
            <span class="text-gray-300">•</span>
            <span class="text-gray-700 font-semibold">{{ selectedProgram.category || 'Perbankan' }}</span>
          </div>
          <p class="font-extrabold text-sm text-[#002855] mt-0.5">{{ selectedProgram.title }}</p>
          <p class="text-gray-500 text-[11px] mt-0.5">{{ selectedProgram.target_audience || 'Seluruh Pegawai Terkait' }}</p>
        </div>
        <div class="flex items-center gap-4 shrink-0 bg-white px-4 py-2 rounded-xl border border-blue-100 text-right">
          <div>
            <span class="text-[10px] text-gray-400 block font-medium">Batch Dibuka</span>
            <span class="text-xs font-black text-[#0067AC]">{{ selectedProgram.classes_count || 0 }} Batch</span>
          </div>
          <div class="h-6 w-px bg-gray-200"></div>
          <div>
            <span class="text-[10px] text-gray-400 block font-medium">Passing Grade</span>
            <span class="text-xs font-black text-emerald-600">{{ selectedProgram.passing_grade || 75 }} / 100</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Informasi Batch & Jadwal -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
      <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <Calendar class="w-4 h-4" />
        </div>
        <div>
          <h2 class="text-sm font-extrabold text-[#002855]">2. Informasi Batch, Metode &amp; Lokasi</h2>
          <p class="text-[11px] text-gray-500">Atur nama batch, metode pengajaran, jadwal tanggal, dan kapasitas kursi.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Nama Batch Pelaksanaan <span class="text-rose-500">*</span>
          </label>
          <input
            type="text"
            v-model="form.batch_name"
            placeholder="Contoh: Batch 1 - Regular Kantor Pusat"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl px-4 py-2.5 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC] transition"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Kapasitas Kuota Kursi <span class="text-rose-500">*</span>
          </label>
          <AppNumberInput
            v-model="form.max_quota"
            suffix="Peserta"
            :min="5"
            :max="200"
            placeholder="30"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Metode Pelaksanaan <span class="text-rose-500">*</span>
          </label>
          <select
            v-model="form.method"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl px-4 py-2.5 text-xs font-semibold text-gray-800 focus:outline-none focus:border-[#0067AC] transition cursor-pointer"
          >
            <option value="In-Classroom">In-Classroom (Tatap Muka Diklat)</option>
            <option value="Virtual Classroom">Virtual Classroom (Zoom / Teams)</option>
            <option value="Hybrid (ELAPSE + In-Classroom)">Hybrid (ELAPSE + In-Classroom)</option>
            <option value="ELAPSE E-Learning">ELAPSE E-Learning (Self-Paced)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Tanggal Mulai <span class="text-rose-500">*</span>
          </label>
          <input
            type="date"
            v-model="form.start_date"
            @change="generateSessions"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl px-4 py-2 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC] transition"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Tanggal Selesai <span class="text-rose-500">*</span>
          </label>
          <input
            type="date"
            v-model="form.end_date"
            @change="generateSessions"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl px-4 py-2 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC] transition"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Lokasi Ruangan / Tautan Virtual Meeting <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <MapPin class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
            <input
              type="text"
              v-model="form.location_or_url"
              placeholder="Contoh: BWS Corporate Learning Center Lt. 8, Ruang Diklat 801"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl pl-10 pr-4 py-2.5 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC] transition"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Jam Pelaksanaan Harian
          </label>
          <div class="relative">
            <Clock class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" />
            <input
              type="text"
              v-model="form.daily_schedule"
              placeholder="Contoh: 08:30 - 16:30 WIB"
              class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl pl-10 pr-4 py-2.5 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC] transition"
            />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Instruktur / Fasilitator Utama
          </label>
          <input
            type="text"
            v-model="form.trainer_name"
            placeholder="Nama Instruktur Internal / Eksternal"
            class="w-full bg-[#f8fafc] border border-gray-200 rounded-2xl px-4 py-2.5 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC] transition"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1.5">
            Passing Grade Kelulusan (Standar BWS: 75)
          </label>
          <AppNumberInput
            v-model="form.passing_grade"
            suffix="/ 100 Poin"
            :min="50"
            :max="100"
            placeholder="75"
          />
        </div>
      </div>
    </div>

    <!-- Section 3: Jadwal Sesi Pelatihan Harian -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <Clock class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-extrabold text-[#002855]">3. Rincian Sesi &amp; Jadwal Harian Pelatihan</h2>
            <p class="text-[11px] text-gray-500">Sesi harian ini akan menjadi dasar daftar hadir (presensi) per sesi.</p>
          </div>
        </div>

        <AppButton
          type="button"
          variant="purple"
          pill
          icon="plus"
          @click="addSessionRow"
        >
          Tambah Sesi
        </AppButton>
      </div>

      <div class="overflow-x-auto border border-gray-100 rounded-2xl">
        <table class="w-full text-left text-xs">
          <thead class="bg-gray-50/80 text-gray-600 font-bold border-b border-gray-100">
            <tr>
              <th class="py-2.5 px-3 w-12 text-center">Sesi</th>
              <th class="py-2.5 px-3">Nama Sesi / Tanggal</th>
              <th class="py-2.5 px-3 w-32">Waktu Sesi</th>
              <th class="py-2.5 px-3">Materi / Topik Pembahasan</th>
              <th class="py-2.5 px-3 w-16 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <tr v-for="(s, idx) in form.sessions" :key="idx" class="hover:bg-gray-50/50">
              <td class="py-2.5 px-3 text-center font-mono font-bold text-[#0067AC]">
                #{{ s.session_number }}
              </td>
              <td class="py-2.5 px-3">
                <input
                  type="text"
                  v-model="s.session_name"
                  class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC]"
                />
              </td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-1">
                  <input
                    type="text"
                    v-model="s.start_time"
                    class="w-14 bg-white border border-gray-200 rounded-xl px-1.5 py-1.5 text-center text-xs text-gray-800"
                  />
                  <span>-</span>
                  <input
                    type="text"
                    v-model="s.end_time"
                    class="w-14 bg-white border border-gray-200 rounded-xl px-1.5 py-1.5 text-center text-xs text-gray-800"
                  />
                </div>
              </td>
              <td class="py-2.5 px-3">
                <input
                  type="text"
                  v-model="s.topic"
                  placeholder="Materi yang dibahas..."
                  class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:border-[#0067AC]"
                />
              </td>
              <td class="py-2.5 px-3 text-center">
                <button
                  type="button"
                  @click="removeSession(idx)"
                  class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition cursor-pointer"
                  title="Hapus Sesi"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </td>
            </tr>
            <tr v-if="!form.sessions.length">
              <td colspan="5" class="py-6 text-center text-gray-400">
                Belum ada sesi harian. Masukkan tanggal mulai &amp; selesai atau klik "Tambah Sesi".
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Section 4: Nominasi & Pendaftaran Peserta -->
    <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <Users class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-extrabold text-[#002855]">4. Pendaftaran Peserta Batch Diklat</h2>
            <p class="text-[11px] text-gray-500">
              Impor pegawai dari nominasi Program Kerja atau daftarkan peserta baru secara manual.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            v-if="availableNominations.length"
            @click="selectAllNominations"
            class="px-3 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-[11px] font-bold transition cursor-pointer"
          >
            Pilih Semua Nominasi Program ({{ availableNominations.length }})
          </button>
          <button
            type="button"
            v-if="form.enrolled_participants.length"
            @click="clearAllSelectedParticipants"
            class="px-3 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-[11px] font-bold transition cursor-pointer"
          >
            Hapus Semua ({{ form.enrolled_participants.length }})
          </button>
        </div>
      </div>

      <!-- Quick Nomination Selector from Program -->
      <div v-if="availableNominations.length" class="space-y-2">
        <p class="text-xs font-bold text-gray-700">Pegawai Ternominasi dari Program Terkait:</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
          <div
            v-for="p in availableNominations"
            :key="p.id"
            @click="toggleNomination(p)"
            class="p-3 rounded-2xl border transition cursor-pointer flex items-center justify-between gap-2"
            :class="isParticipantSelected(p) ? 'bg-[#EBF4FC] border-[#0067AC] text-[#002855]' : 'bg-gray-50 border-gray-200 hover:border-gray-300 text-gray-700'"
          >
            <div class="min-w-0">
              <p class="text-xs font-bold truncate">{{ p.name }}</p>
              <div class="flex items-center gap-1.5 text-[10px] text-gray-500 mt-0.5">
                <span class="font-mono">{{ p.nik }}</span>
                <span>•</span>
                <span class="truncate">{{ p.department || p.branch }}</span>
              </div>
            </div>
            <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
              :class="isParticipantSelected(p) ? 'bg-[#0067AC] text-white' : 'border border-gray-300 bg-white'">
              <CheckCircle2 v-if="isParticipantSelected(p)" class="w-3.5 h-3.5" />
            </div>
          </div>
        </div>
      </div>

      <!-- Manual Add Participant Row -->
      <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 space-y-3">
        <p class="text-xs font-bold text-gray-700">Daftarkan Peserta Tambahan Secara Mandiri:</p>
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2.5 text-xs">
          <div>
            <input
              type="text"
              v-model="newParticipant.nik"
              placeholder="NIK Pegawai..."
              class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#0067AC]"
            />
          </div>
          <div class="sm:col-span-2">
            <input
              type="text"
              v-model="newParticipant.name"
              placeholder="Nama Lengkap Pegawai..."
              class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#0067AC]"
            />
          </div>
          <div>
            <input
              type="text"
              v-model="newParticipant.department"
              placeholder="Divisi / Unit..."
              class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-[#0067AC]"
            />
          </div>
          <div>
            <AppButton
              type="button"
              variant="primary"
              size="md"
              icon="plus"
              class="w-full"
              :disabled="!newParticipant.name"
              @click="addManualParticipant"
            >
              Tambah
            </AppButton>
          </div>
        </div>
      </div>

      <!-- Selected Participant Table -->
      <div class="space-y-2">
        <div class="flex items-center justify-between text-xs">
          <span class="font-bold text-gray-800">
            Daftar Peserta Terdaftar ({{ form.enrolled_participants.length }} / {{ form.max_quota }} Kursi)
          </span>
          <span v-if="form.enrolled_participants.length > form.max_quota" class="text-rose-600 font-bold">
            Peringatan: Melebihi kuota maksimal!
          </span>
        </div>

        <div class="overflow-x-auto border border-gray-100 rounded-2xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-gray-50/80 text-gray-600 font-bold border-b border-gray-100">
              <tr>
                <th class="py-2.5 px-3 w-12 text-center">No</th>
                <th class="py-2.5 px-3 w-28">NIK</th>
                <th class="py-2.5 px-3">Nama Pegawai</th>
                <th class="py-2.5 px-3">Divisi / Unit Kerja</th>
                <th class="py-2.5 px-3">Jabatan</th>
                <th class="py-2.5 px-3 w-16 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-700">
              <tr v-for="(p, idx) in form.enrolled_participants" :key="idx" class="hover:bg-gray-50/50">
                <td class="py-2 px-3 text-center text-gray-400">{{ idx + 1 }}</td>
                <td class="py-2 px-3 font-mono font-bold text-[#0067AC]">{{ p.nik || '-' }}</td>
                <td class="py-2 px-3 font-bold text-gray-900">{{ p.name }}</td>
                <td class="py-2 px-3 text-gray-600">{{ p.department || p.branch || '-' }}</td>
                <td class="py-2 px-3 text-gray-500">{{ p.position || '-' }}</td>
                <td class="py-2 px-3 text-center">
                  <button
                    type="button"
                    @click="removeEnrolled(idx)"
                    class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 transition cursor-pointer"
                    title="Hapus dari Batch"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!form.enrolled_participants.length">
                <td colspan="6" class="py-6 text-center text-gray-400">
                  Belum ada peserta yang dimasukkan. Pilih dari nominasi di atas atau masukkan secara manual.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Bottom Sticky Bar -->
    <div class="sticky bottom-4 z-20 bg-white/95 backdrop-blur-md p-4 rounded-3xl border border-gray-200 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-4 text-xs">
        <div>
          <span class="text-[10px] text-gray-400 block font-medium uppercase tracking-wider">Kapasitas Batch</span>
          <span class="text-sm font-extrabold text-[#002855]">
            {{ form.enrolled_participants.length }} / {{ form.max_quota }} Peserta Terisi
          </span>
        </div>
        <div class="h-6 w-px bg-gray-200"></div>
        <div>
          <span class="text-[10px] text-gray-400 block font-medium uppercase tracking-wider">Total Sesi Harian</span>
          <span class="text-sm font-extrabold text-[#0067AC]">{{ form.sessions.length }} Sesi Presensi</span>
        </div>
        <div class="h-6 w-px bg-gray-200"></div>
        <div>
          <span class="text-[10px] text-gray-400 block font-medium uppercase tracking-wider">Passing Grade</span>
          <span class="text-sm font-extrabold text-emerald-600">{{ form.passing_grade }} / 100</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
        <button
          type="button"
          @click="emit('back')"
          class="px-4 py-2.5 rounded-full border border-gray-200 text-gray-700 hover:bg-gray-100 text-xs font-bold transition cursor-pointer"
        >
          Batal
        </button>
        <button
          type="button"
          :disabled="isSubmitting"
          @click="submitForm"
          class="px-6 py-2.5 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-bold transition shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-60"
        >
          <Send class="w-3.5 h-3.5" />
          <span>{{ isSubmitting ? 'Memproses...' : 'Buka Batch Kelas Sekarang' }}</span>
        </button>
      </div>
    </div>

  </div>
</template>
