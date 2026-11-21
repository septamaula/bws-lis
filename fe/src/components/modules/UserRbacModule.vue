<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/client'
import {
  Users,
  ShieldCheck,
  KeyRound,
  UserPlus,
  Edit2,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Search,
  Filter,
  Check,
  X,
  Crown,
  Lock,
  Building,
  Briefcase,
  Phone,
  Mail,
  UserCheck,
  RefreshCw,
  ChevronRight,
  Sliders,
  Save,
  CheckSquare,
  Square,
  BadgeCheck,
  Calendar,
  Award,
  Clock,
  Sparkles
} from 'lucide-vue-next'

const auth = useAuthStore()

const props = defineProps({
  subTab: {
    type: String,
    default: 'users'
  }
})

const emit = defineEmits(['open-modal', 'update:subTab'])

// Tab State synced with parent
const internalSubTab = ref('users')
const activeSubTab = computed({
  get: () => props.subTab || internalSubTab.value,
  set: (val) => {
    internalSubTab.value = val
    emit('update:subTab', val)
  }
})

// Data State
const loading = ref(true)
const savingMatrix = ref(false)
const savingUser = ref(false)
const feedbackMsg = ref('')
const feedbackType = ref('success') // 'success' | 'error'

const users = ref([])
const levels = ref([])
const karyawans = ref([])
const permissionModules = ref([])

const stats = ref({
  total_users: 0,
  active_users: 0,
  total_levels: 0,
  total_permissions: 0
})

// Search & Filter State (Users)
const searchQuery = ref('')
const selectedRoleFilter = ref('ALL')

// Form Modal State (User)
const showUserModal = ref(false)
const editingUser = ref(null)
const userForm = ref({
  name: '',
  username: '',
  email: '',
  password: '',
  level_id: 1,
  karyawan_id: '',
  department: '',
  branch: 'Kantor Pusat Sudirman',
  phone: '',
  is_active: true
})

// Delete User Modal State
const showDeleteUserModal = ref(false)
const userToDelete = ref(null)
const deletingUser = ref(false)

// Master Karyawan State
const karyawanList = ref([])
const loadingKaryawan = ref(false)
const searchKaryawan = ref('')
const filterStKerja = ref('ALL')
const filterCabang = ref('ALL')

const karyawanStats = ref({
  total_karyawan: 0,
  aktif_karyawan: 0,
  total_cabang: 0,
  target_jam_avg: 20
})

const showKaryawanModal = ref(false)
const editingKaryawan = ref(null)
const savingKaryawan = ref(false)

const showDeleteKaryawanModal = ref(false)
const karyawanToDelete = ref(null)
const deletingKaryawan = ref(false)

const karyawanForm = ref({
  nik: '',
  nama: '',
  nama_panggilan: '',
  jk: 'L',
  email: '',
  hp: '+62 811-',
  departemen: 'Human Capital',
  jabatan: 'Officer',
  kantor_cabang: 'Kantor Pusat Sudirman',
  golongan: 'Officer',
  st_kerja: 'AKTIF',
  tgl_masuk: new Date().toISOString().split('T')[0],
  target_jam_pelatihan: 20
})

// RBAC Matrix State
const selectedLevelId = ref(1)
const activePermissionKeys = ref(new Set())

const selectedLevel = computed(() => {
  return levels.value.find(l => l.level_id === selectedLevelId.value)
})

// Computed Filtered Users
const filteredUsers = computed(() => {
  let list = users.value
  if (selectedRoleFilter.value !== 'ALL') {
    list = list.filter(u => u.level_id === Number(selectedRoleFilter.value))
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(u =>
      u.name?.toLowerCase().includes(q) ||
      u.email?.toLowerCase().includes(q) ||
      u.nik?.toLowerCase().includes(q) ||
      u.department?.toLowerCase().includes(q) ||
      u.karyawan?.nama?.toLowerCase().includes(q)
    )
  }
  return list
})

// Computed Filtered Karyawans
const filteredKaryawans = computed(() => {
  let list = karyawanList.value
  if (filterStKerja.value !== 'ALL') {
    list = list.filter(k => k.st_kerja === filterStKerja.value)
  }
  if (filterCabang.value !== 'ALL') {
    list = list.filter(k => k.kantor_cabang === filterCabang.value)
  }
  if (searchKaryawan.value.trim()) {
    const q = searchKaryawan.value.toLowerCase()
    list = list.filter(k =>
      k.nama?.toLowerCase().includes(q) ||
      k.nik?.toLowerCase().includes(q) ||
      k.email?.toLowerCase().includes(q) ||
      k.departemen?.toLowerCase().includes(q) ||
      k.jabatan?.toLowerCase().includes(q) ||
      k.kantor_cabang?.toLowerCase().includes(q)
    )
  }
  return list
})

const uniqueCabangList = computed(() => {
  const set = new Set(karyawanList.value.map(k => k.kantor_cabang).filter(Boolean))
  return Array.from(set)
})

// API Loaders
async function fetchRbacData() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/users')
    users.value = data.users || []
    levels.value = data.levels || []
    karyawans.value = data.karyawans || []
    permissionModules.value = data.permissionModules || []
    stats.value = data.stats || stats.value
  } catch (err) {
    console.error('Error fetching RBAC data:', err)
  } finally {
    loading.value = false
  }
}

async function fetchKaryawanList() {
  loadingKaryawan.value = true
  try {
    const { data } = await apiClient.get('/karyawan')
    karyawanList.value = data.karyawans || []
    if (data.stats) {
      karyawanStats.value = data.stats
    }
  } catch (err) {
    console.error('Error fetching Karyawan data:', err)
  } finally {
    loadingKaryawan.value = false
  }
}

async function loadLevelPermissions(levelId) {
  selectedLevelId.value = levelId
  try {
    const { data } = await apiClient.get(`/rbac/levels/${levelId}/access`)
    activePermissionKeys.value = new Set(data.activeKeys || [])
  } catch (err) {
    console.error('Error loading level access:', err)
  }
}

function selectLevelForMatrix(level) {
  if (!level) return
  activeSubTab.value = 'matrix'
  loadLevelPermissions(level.level_id)
}

function isPermissionActive(key) {
  if (selectedLevel.value?.is_admin) return true
  return activePermissionKeys.value.has(key)
}

function togglePermission(key) {
  if (selectedLevel.value?.is_admin) return
  if (activePermissionKeys.value.has(key)) {
    activePermissionKeys.value.delete(key)
  } else {
    activePermissionKeys.value.add(key)
  }
}

function toggleModuleAll(mod) {
  if (selectedLevel.value?.is_admin) return
  const allActive = mod.permissions.every(p => activePermissionKeys.value.has(p.key))
  if (allActive) {
    mod.permissions.forEach(p => activePermissionKeys.value.delete(p.key))
  } else {
    mod.permissions.forEach(p => activePermissionKeys.value.add(p.key))
  }
}

function selectAllPermissions() {
  if (selectedLevel.value?.is_admin) return
  permissionModules.value.forEach(mod => {
    mod.permissions.forEach(p => activePermissionKeys.value.add(p.key))
  })
}

function deselectAllPermissions() {
  if (selectedLevel.value?.is_admin) return
  activePermissionKeys.value.clear()
}

async function savePermissionMatrix() {
  if (!selectedLevel.value) return
  savingMatrix.value = true
  feedbackMsg.value = ''
  try {
    const keysArray = Array.from(activePermissionKeys.value)
    const { data } = await apiClient.post(`/rbac/levels/${selectedLevel.value.level_id}/access`, {
      controller_keys: keysArray
    })
    feedbackType.value = 'success'
    feedbackMsg.value = data.message || 'Matriks Hak Akses RBAC berhasil diperbarui.'
    await fetchRbacData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menyimpan hak akses.'
  } finally {
    savingMatrix.value = false
  }
}

// User Modal Handlers
function openCreateUserModal() {
  editingUser.value = null
  userForm.value = {
    name: '',
    username: '',
    email: '',
    password: 'password123',
    level_id: levels.value[0]?.level_id || 1,
    karyawan_id: '',
    department: 'HC Learning & Development',
    branch: 'Kantor Pusat Sudirman',
    phone: '+62 811-',
    is_active: true
  }
  showUserModal.value = true
}

function openEditUserModal(user) {
  editingUser.value = user
  userForm.value = {
    name: user.name,
    username: user.email.split('@')[0],
    email: user.email,
    password: '',
    level_id: user.level_id || (user.level ? user.level.level_id : 1),
    karyawan_id: user.karyawan_id || '',
    department: user.department || '',
    branch: user.branch || 'Kantor Pusat Sudirman',
    phone: user.phone || '',
    is_active: user.is_active
  }
  showUserModal.value = true
}

function onKaryawanSelectChange() {
  const selectedKaryawan = karyawans.value.find(k => k.karyawan_id === userForm.value.karyawan_id)
  if (selectedKaryawan) {
    if (!editingUser.value) {
      userForm.value.name = selectedKaryawan.nama
      userForm.value.email = selectedKaryawan.email
      userForm.value.username = selectedKaryawan.email.split('@')[0]
      userForm.value.department = selectedKaryawan.departemen
      userForm.value.branch = selectedKaryawan.kantor_cabang
    }
  }
}

async function submitUserForm() {
  savingUser.value = true
  feedbackMsg.value = ''
  try {
    if (editingUser.value) {
      const payload = { ...userForm.value }
      if (!payload.password) delete payload.password
      const { data } = await apiClient.put(`/users/${editingUser.value.id}`, payload)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Pengguna berhasil diperbarui.'
    } else {
      const { data } = await apiClient.post('/users', userForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Pengguna baru berhasil ditambahkan.'
    }
    showUserModal.value = false
    await fetchRbacData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menyimpan data pengguna.'
  } finally {
    savingUser.value = false
  }
}

// User Delete Handlers
function openDeleteUserModal(user) {
  userToDelete.value = user
  showDeleteUserModal.value = true
}

async function confirmDeleteUser() {
  if (!userToDelete.value) return
  deletingUser.value = true
  feedbackMsg.value = ''
  try {
    const { data } = await apiClient.delete(`/users/${userToDelete.value.id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = data.message || 'Pengguna berhasil dihapus dari sistem.'
    showDeleteUserModal.value = false
    userToDelete.value = null
    await fetchRbacData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menghapus pengguna.'
  } finally {
    deletingUser.value = false
  }
}

// Master Karyawan Handlers
function openCreateKaryawanModal() {
  editingKaryawan.value = null
  karyawanForm.value = {
    nik: 'BWS-EMP-' + Math.floor(1000 + Math.random() * 9000),
    nama: '',
    nama_panggilan: '',
    jk: 'L',
    email: '',
    hp: '+62 811-',
    departemen: 'Human Capital',
    jabatan: 'Staff Diklat',
    kantor_cabang: 'Kantor Pusat Sudirman',
    golongan: 'Officer',
    st_kerja: 'AKTIF',
    tgl_masuk: new Date().toISOString().split('T')[0],
    target_jam_pelatihan: 20
  }
  showKaryawanModal.value = true
}

function openEditKaryawanModal(k) {
  editingKaryawan.value = k
  karyawanForm.value = {
    nik: k.nik,
    nama: k.nama,
    nama_panggilan: k.nama_panggilan || '',
    jk: k.jk || 'L',
    email: k.email,
    hp: k.hp || '',
    departemen: k.departemen || '',
    jabatan: k.jabatan || '',
    kantor_cabang: k.kantor_cabang || 'Kantor Pusat Sudirman',
    golongan: k.golongan || 'Officer',
    st_kerja: k.st_kerja || 'AKTIF',
    tgl_masuk: k.tgl_masuk ? k.tgl_masuk.split('T')[0] : '',
    target_jam_pelatihan: k.target_jam_pelatihan || 20
  }
  showKaryawanModal.value = true
}

async function submitKaryawanForm() {
  savingKaryawan.value = true
  feedbackMsg.value = ''
  try {
    if (editingKaryawan.value) {
      const { data } = await apiClient.put(`/karyawan/${editingKaryawan.value.karyawan_id}`, karyawanForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Data karyawan berhasil diperbarui.'
    } else {
      const { data } = await apiClient.post('/karyawan', karyawanForm.value)
      feedbackType.value = 'success'
      feedbackMsg.value = data.message || 'Karyawan baru berhasil ditambahkan.'
    }
    showKaryawanModal.value = false
    await fetchKaryawanList()
    await fetchRbacData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menyimpan data karyawan.'
  } finally {
    savingKaryawan.value = false
  }
}

function openDeleteKaryawanModal(k) {
  karyawanToDelete.value = k
  showDeleteKaryawanModal.value = true
}

async function confirmDeleteKaryawan() {
  if (!karyawanToDelete.value) return
  deletingKaryawan.value = true
  feedbackMsg.value = ''
  try {
    const { data } = await apiClient.delete(`/karyawan/${karyawanToDelete.value.karyawan_id}`)
    feedbackType.value = 'success'
    feedbackMsg.value = data.message || 'Data karyawan berhasil dihapus.'
    showDeleteKaryawanModal.value = false
    karyawanToDelete.value = null
    await fetchKaryawanList()
    await fetchRbacData()
  } catch (err) {
    feedbackType.value = 'error'
    feedbackMsg.value = err.response?.data?.message || 'Gagal menghapus data karyawan.'
  } finally {
    deletingKaryawan.value = false
  }
}

function getInitials(name) {
  if (!name) return 'BW'
  const parts = name.split(' ')
  return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase()
}

function getRoleBadgeClass(level) {
  if (!level) return 'bg-gray-100 text-gray-700'
  if (level.is_admin || level.role === 'super_admin') return 'bg-[#002855] text-white'
  if (level.role === 'hc_checker') return 'bg-purple-100 text-purple-800 border-purple-200'
  if (level.role === 'hc_maker') return 'bg-blue-100 text-[#0067AC] border-blue-200'
  if (level.role === 'hcbp') return 'bg-sky-100 text-sky-800 border-sky-200'
  if (level.role === 'dept_head') return 'bg-indigo-100 text-indigo-800 border-indigo-200'
  if (level.role === 'facilitator') return 'bg-amber-100 text-amber-800 border-amber-200'
  if (level.role === 'executive') return 'bg-slate-800 text-white'
  return 'bg-[#EBF4FC] text-[#0067AC]'
}

onMounted(async () => {
  await Promise.all([
    fetchRbacData(),
    fetchKaryawanList()
  ])
  await loadLevelPermissions(1)
})
</script>

<template>
  <div class="flex flex-col gap-6">

    <!-- Feedback Toast Banner -->
    <div v-if="feedbackMsg" class="w-full">
      <div
        class="px-4 py-3 rounded-2xl text-xs flex items-center justify-between shadow-xs border transition"
        :class="feedbackType === 'success' ? 'bg-blue-50 text-[#0067AC] border-blue-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 v-if="feedbackType === 'success'" class="w-4 h-4 shrink-0 text-[#0067AC]" />
          <AlertCircle v-else class="w-4 h-4 shrink-0 text-rose-600" />
          <span>{{ feedbackMsg }}</span>
        </div>
        <button @click="feedbackMsg = ''" class="font-bold text-sm hover:opacity-75">&times;</button>
      </div>
    </div>

    <!-- Quick Metrics Counters (Dynamic per active tab) -->
    <div v-if="activeSubTab === 'karyawan'" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <BadgeCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Pegawai</p>
          <p class="text-xl font-extrabold text-gray-900">{{ karyawanStats.total_karyawan }} Orang</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Status Pegawai</p>
          <p class="text-xl font-extrabold text-gray-900">{{ karyawanStats.aktif_karyawan }} Aktif</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold">
          <Building class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Kantor Cabang</p>
          <p class="text-xl font-extrabold text-gray-900">{{ karyawanStats.total_cabang || 3 }} Wilayah</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <Clock class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Target Diklat OJK</p>
          <p class="text-xl font-extrabold text-gray-900">{{ karyawanStats.target_jam_avg }} Jam / Thn</p>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Users class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Total Pengguna</p>
          <p class="text-xl font-extrabold text-gray-900">{{ stats.total_users }} Akun</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle2 class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Status Akun</p>
          <p class="text-xl font-extrabold text-gray-900">{{ stats.active_users }} Aktif</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Tingkat Peran</p>
          <p class="text-xl font-extrabold text-gray-900">{{ stats.total_levels }} Level</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#0067AC] flex items-center justify-center font-bold">
          <KeyRound class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Matriks Hak Akses</p>
          <p class="text-xl font-extrabold text-gray-900">{{ stats.total_permissions }} Aturan</p>
        </div>
      </div>
    </div>

    <!-- ============================================================= -->
    <!-- TAB 1: KELOLA PENGGUNA (USER MANAGEMENT)                      -->
    <!-- ============================================================= -->
    <div v-if="activeSubTab === 'users'" class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4">
      
      <!-- Action & Search Bar -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-1 max-w-lg">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama, NIK, email, atau divisi..."
              class="w-full bg-[#f4f6f5] focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition"
            />
          </div>

          <!-- Role Filter Dropdown -->
          <select
            v-model="selectedRoleFilter"
            class="bg-[#f4f6f5] text-xs font-semibold text-gray-700 rounded-full px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition shrink-0"
          >
            <option value="ALL">Semua Peran ({{ levels.length }})</option>
            <option v-for="l in levels" :key="l.level_id" :value="l.level_id">
              {{ l.level }}
            </option>
          </select>
        </div>

        <AppButton
          variant="primary"
          pill
          icon="user-plus"
          @click="openCreateUserModal"
        >
          Tambah Pengguna Baru
        </AppButton>
      </div>

      <!-- Users Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-100">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-[#f8faf9] text-gray-500 font-bold uppercase tracking-wider text-[10px] border-b border-gray-100">
              <th class="py-3 px-4">Pengguna & Akun</th>
              <th class="py-3 px-4">Karyawan & NIK</th>
              <th class="py-3 px-4">Peran (User Level)</th>
              <th class="py-3 px-4">Departemen & Cabang</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="u in filteredUsers"
              :key="u.id"
              class="hover:bg-blue-50/40 transition"
            >
              <!-- Avatar & Name -->
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-[#EBF4FC] text-[#0067AC] font-bold text-xs flex items-center justify-center ring-2 ring-[#0067AC]/20 shrink-0">
                    {{ getInitials(u.name) }}
                  </div>
                  <div>
                    <p class="font-bold text-gray-900 leading-tight">{{ u.name }}</p>
                    <p class="text-[11px] text-gray-400 font-mono">{{ u.email }}</p>
                  </div>
                </div>
              </td>

              <!-- Karyawan & NIK -->
              <td class="py-3 px-4">
                <div v-if="u.karyawan">
                  <span class="font-mono font-bold text-[#0067AC] text-xs block">{{ u.karyawan.nik }}</span>
                  <span class="text-[11px] text-gray-400">{{ u.karyawan.jabatan || 'Pegawai BWS' }}</span>
                </div>
                <div v-else>
                  <span class="font-mono text-gray-400 text-xs">{{ u.nik || '-' }}</span>
                </div>
              </td>

              <!-- Role / Level -->
              <td class="py-3 px-4">
                <span
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border"
                  :class="getRoleBadgeClass(u.level)"
                >
                  <Crown v-if="u.level?.is_admin" class="w-3 h-3 text-amber-300" />
                  <ShieldCheck v-else class="w-3 h-3" />
                  <span>{{ u.level?.level || u.role_name }}</span>
                </span>
              </td>

              <!-- Department -->
              <td class="py-3 px-4">
                <p class="font-medium text-gray-800">{{ u.department || 'Kantor Pusat Sudirman' }}</p>
                <p class="text-[11px] text-gray-400">{{ u.branch || 'Kantor Pusat' }}</p>
              </td>

              <!-- Status -->
              <td class="py-3 px-4">
                <span
                  v-if="u.is_active"
                  class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  Aktif
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                  Nonaktif
                </span>
              </td>

              <!-- Action (Edit, Delete, RBAC) -->
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Edit Button -->
                  <button
                    @click="openEditUserModal(u)"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-[#0067AC] hover:bg-blue-50 transition"
                    title="Edit Pengguna"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>

                  <!-- Delete Button -->
                  <button
                    @click="openDeleteUserModal(u)"
                    class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition"
                    title="Hapus Pengguna"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>

                  <!-- RBAC Matrix Button -->
                  <button
                    @click="selectLevelForMatrix(u.level)"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EBF4FC] text-[#0067AC] hover:bg-blue-100 transition"
                    title="Buka Matriks RBAC untuk peran ini"
                  >
                    RBAC
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- ============================================================= -->
    <!-- TAB 2: MASTER KARYAWAN (BANK WOORI SAUDARA EMPLOYEE MASTER)   -->
    <!-- ============================================================= -->
    <div v-else-if="activeSubTab === 'karyawan'" class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4">
      
      <!-- Action & Search Bar -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-1 max-w-xl">
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchKaryawan"
              type="text"
              placeholder="Cari nama karyawan, NIK, jabatan, atau cabang..."
              class="w-full bg-[#f4f6f5] focus:bg-white text-xs text-gray-800 rounded-full pl-9 pr-4 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition"
            />
          </div>

          <!-- Status Kerja Filter Dropdown -->
          <select
            v-model="filterStKerja"
            class="bg-[#f4f6f5] text-xs font-semibold text-gray-700 rounded-full px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition shrink-0"
          >
            <option value="ALL">Semua Status</option>
            <option value="AKTIF">Aktif Tetap</option>
            <option value="KONTRAK">Kontrak</option>
            <option value="RESIGN">Resign / Nonaktif</option>
          </select>

          <!-- Cabang Filter Dropdown -->
          <select
            v-model="filterCabang"
            class="bg-[#f4f6f5] text-xs font-semibold text-gray-700 rounded-full px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none transition shrink-0"
          >
            <option value="ALL">Semua Cabang</option>
            <option v-for="c in uniqueCabangList" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>

        <AppButton
          variant="primary"
          pill
          icon="user-plus"
          @click="openCreateKaryawanModal"
        >
          Tambah Karyawan Baru
        </AppButton>
      </div>

      <!-- Karyawan Data Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-100">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-[#f8faf9] text-gray-500 font-bold uppercase tracking-wider text-[10px] border-b border-gray-100">
              <th class="py-3 px-4">Pegawai & NIK</th>
              <th class="py-3 px-4">Departemen & Jabatan</th>
              <th class="py-3 px-4">Kantor Cabang & Golongan</th>
              <th class="py-3 px-4">Status Kerja</th>
              <th class="py-3 px-4">Target Diklat</th>
              <th class="py-3 px-4">Akun Login</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="k in filteredKaryawans"
              :key="k.karyawan_id"
              class="hover:bg-blue-50/40 transition"
            >
              <!-- Avatar & Name -->
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-[#EBF4FC] text-[#0067AC] font-bold text-xs flex items-center justify-center ring-2 ring-[#0067AC]/20 shrink-0">
                    {{ getInitials(k.nama) }}
                  </div>
                  <div>
                    <p class="font-bold text-gray-900 leading-tight">{{ k.nama }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                      <span class="font-mono text-[#0067AC] font-extrabold text-[11px]">{{ k.nik }}</span>
                      <span class="text-[10px] text-gray-400">{{ k.jk === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Department & Position -->
              <td class="py-3 px-4">
                <p class="font-bold text-gray-800">{{ k.jabatan || 'Staff' }}</p>
                <p class="text-[11px] text-gray-400">{{ k.departemen }}</p>
              </td>

              <!-- Branch & Level -->
              <td class="py-3 px-4">
                <p class="font-medium text-gray-800">{{ k.kantor_cabang }}</p>
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 inline-block mt-0.5">
                  Gol: {{ k.golongan || 'Officer' }}
                </span>
              </td>

              <!-- Status Kerja -->
              <td class="py-3 px-4">
                <span
                  class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full border"
                  :class="k.st_kerja === 'AKTIF' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="k.st_kerja === 'AKTIF' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                  {{ k.st_kerja }}
                </span>
              </td>

              <!-- Target Diklat -->
              <td class="py-3 px-4">
                <div class="flex items-center gap-1.5">
                  <span class="font-mono font-extrabold text-[#0067AC]">{{ k.target_jam_pelatihan || 20 }}</span>
                  <span class="text-[10px] text-gray-400">Jam/Thn</span>
                </div>
              </td>

              <!-- Linked User Account -->
              <td class="py-3 px-4">
                <span
                  v-if="k.user"
                  class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC] border border-blue-200"
                >
                  <UserCheck class="w-3 h-3" />
                  <span>Akun Aktif</span>
                </span>
                <span
                  v-else
                  class="text-[10px] text-gray-400 italic"
                >
                  Belum ada akun
                </span>
              </td>

              <!-- Action (Edit & Delete) -->
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openEditKaryawanModal(k)"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-[#0067AC] hover:bg-blue-50 transition"
                    title="Edit Data Karyawan"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>

                  <button
                    @click="openDeleteKaryawanModal(k)"
                    class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition"
                    title="Hapus Karyawan"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- ============================================================= -->
    <!-- TAB 3: TINGKAT PERAN (USER LEVEL MANAGEMENT)                   -->
    <!-- ============================================================= -->
    <div v-else-if="activeSubTab === 'levels'" class="flex flex-col gap-4">
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="lvl in levels"
          :key="lvl.level_id"
          class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition group"
        >
          <div>
            <div class="flex items-start justify-between mb-3">
              <span
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider"
                :class="lvl.is_admin ? 'bg-[#002855] text-white' : 'bg-gray-100 text-gray-700'"
              >
                Level #{{ lvl.level_id }} • {{ lvl.role }}
              </span>
              <span v-if="lvl.is_admin" class="text-amber-500" title="Administrator (Full Access)">
                <Crown class="w-4 h-4 fill-amber-400 text-amber-500" />
              </span>
            </div>

            <h3 class="font-extrabold text-base text-gray-900 group-hover:text-[#0067AC] transition">
              {{ lvl.level }}
            </h3>
            <p class="text-xs text-gray-500 mt-1 line-clamp-3 leading-relaxed">
              {{ lvl.keterangan || 'Peran operasional perbankan di Bank Woori Saudara.' }}
            </p>
          </div>

          <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between">
            <div class="leading-tight">
              <span class="text-xs font-bold text-gray-900 block">{{ lvl.users_count || 1 }} User</span>
              <span class="text-[10px] text-gray-400 font-medium">
                {{ lvl.is_admin ? 'Akses Penuh (All)' : (lvl.accesses_count || (lvl.accesses ? lvl.accesses.length : 0)) + ' Permissions' }}
              </span>
            </div>

            <button
              @click="selectLevelForMatrix(lvl)"
              class="px-3 py-1.5 rounded-full text-xs font-bold bg-[#EBF4FC] text-[#0067AC] hover:bg-[#0067AC] hover:text-white transition flex items-center gap-1 shadow-2xs"
            >
              <span>Atur RBAC</span>
              <ChevronRight class="w-3 h-3" />
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================= -->
    <!-- TAB 4: MATRIKS HAK AKSES (RBAC PERMISSION MATRIX)             -->
    <!-- ============================================================= -->
    <div v-else-if="activeSubTab === 'matrix'" class="flex flex-col gap-5">
      
      <!-- Role Selector Top Bar -->
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <div>
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Pilih Peran untuk Dikonfigurasi</span>
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              v-for="lvl in levels"
              :key="lvl.level_id"
              @click="loadLevelPermissions(lvl.level_id)"
              class="px-3.5 py-1.5 rounded-full text-xs font-bold transition flex items-center gap-1.5"
              :class="selectedLevelId === lvl.level_id ? 'bg-[#0067AC] text-white shadow-xs' : 'bg-[#f4f6f5] text-gray-700 hover:bg-gray-200'"
            >
              <Crown v-if="lvl.is_admin" class="w-3 h-3 text-amber-300" />
              <span>{{ lvl.level }}</span>
            </button>
          </div>
        </div>

        <!-- Action Buttons (Select All / Deselect All / Save) -->
        <div v-if="!selectedLevel?.is_admin" class="flex items-center gap-2 self-end lg:self-auto shrink-0">
          <button
            @click="selectAllPermissions"
            class="px-3 py-1.5 rounded-full text-xs font-semibold bg-[#f4f6f5] hover:bg-gray-200 text-gray-700 transition"
          >
            Pilih Semua
          </button>
          <button
            @click="deselectAllPermissions"
            class="px-3 py-1.5 rounded-full text-xs font-semibold bg-[#f4f6f5] hover:bg-gray-200 text-gray-700 transition"
          >
            Batal Semua
          </button>
          <button
            @click="savePermissionMatrix"
            :disabled="savingMatrix"
            class="bg-[#0067AC] hover:bg-[#00528c] text-white px-5 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shadow-sm disabled:opacity-50"
          >
            <RefreshCw v-if="savingMatrix" class="w-3.5 h-3.5 animate-spin" />
            <Save v-else class="w-3.5 h-3.5" />
            <span>Simpan Matriks Hak Akses</span>
          </button>
        </div>

      </div>

      <!-- Administrator Exclusive Card Banner (Persis seperti LDMS) -->
      <div
        v-if="selectedLevel?.is_admin"
        class="bg-gradient-to-r from-[#002855] via-[#003876] to-[#0067AC] rounded-3xl p-6 text-white shadow-md flex items-center gap-4"
      >
        <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center shrink-0 border border-white/20">
          <Crown class="w-6 h-6 text-amber-300" />
        </div>
        <div>
          <h4 class="font-extrabold text-sm sm:text-base">Peran Super Administrator (Full Authority)</h4>
          <p class="text-xs text-blue-100 mt-1">
            Sesuai standar arsitektur perbankan LDMS, peran Administrator memiliki akses penuh (*bypass*) secara otomatis ke seluruh menu, controller, otorisasi Maker-Checker, dan mutasi audit tanpa perlu konfigurasi matriks manual.
          </p>
        </div>
      </div>

      <!-- Module & Controller Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="mod in permissionModules"
          :key="mod.code"
          class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between"
        >
          <div>
            <!-- Module Title & Select All Module Switch -->
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
              <div>
                <h4 class="font-extrabold text-sm text-gray-900">{{ mod.name }}</h4>
                <p class="text-[11px] text-gray-400">{{ mod.description }}</p>
              </div>

              <button
                v-if="!selectedLevel?.is_admin"
                @click="toggleModuleAll(mod)"
                class="text-[11px] font-bold text-[#0067AC] hover:underline"
              >
                Toggle Modul
              </button>
            </div>

            <!-- Permission Items -->
            <div class="space-y-2">
              <div
                v-for="perm in mod.permissions"
                :key="perm.key"
                @click="togglePermission(perm.key)"
                class="p-2.5 rounded-2xl transition flex items-start gap-3 cursor-pointer select-none"
                :class="isPermissionActive(perm.key) ? 'bg-[#EBF4FC]/70 border border-[#00A0E9]/30' : 'bg-[#f8faf9] border border-transparent hover:bg-gray-100'"
              >
                <!-- Checkbox -->
                <div class="mt-0.5 shrink-0">
                  <div
                    class="w-4 h-4 rounded-md flex items-center justify-center transition border"
                    :class="isPermissionActive(perm.key) ? 'bg-[#0067AC] border-[#0067AC] text-white' : 'bg-white border-gray-300'"
                  >
                    <Check v-if="isPermissionActive(perm.key)" class="w-3 h-3 stroke-[3]" />
                  </div>
                </div>

                <!-- Label & Key -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="font-bold text-xs" :class="isPermissionActive(perm.key) ? 'text-[#002855]' : 'text-gray-700'">
                      {{ perm.name }}
                    </p>
                    <span class="text-[10px] font-mono text-gray-400">{{ perm.key }}</span>
                  </div>
                  <p class="text-[10px] text-gray-500 mt-0.5 leading-tight">{{ perm.desc }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================= -->
    <!-- MODAL 1: TAMBAH / EDIT PENGGUNA (USER MODAL)                  -->
    <!-- ============================================================= -->
    <div
      v-if="showUserModal"
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl border border-gray-100 overflow-hidden animate-scaleIn">
        
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-[#f8faf9]">
          <div class="flex items-center gap-2">
            <UserPlus class="w-4 h-4 text-[#0067AC]" />
            <h3 class="font-extrabold text-sm text-gray-900">
              {{ editingUser ? 'Edit Data Pengguna' : 'Tambah Pengguna Baru' }}
            </h3>
          </div>
          <button @click="showUserModal = false" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitUserForm" class="p-5 space-y-4 text-xs">
          
          <!-- Hubungkan ke Master Karyawan -->
          <div>
            <label class="font-bold text-gray-700 block mb-1">Hubungkan ke Master Karyawan (Opsional)</label>
            <select
              v-model="userForm.karyawan_id"
              @change="onKaryawanSelectChange"
              class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
            >
              <option value="">-- Pilih dari Karyawan Terdaftar --</option>
              <option v-for="k in karyawans" :key="k.karyawan_id" :value="k.karyawan_id">
                {{ k.nik }} — {{ k.nama }} ({{ k.departemen }})
              </option>
            </select>
          </div>

          <!-- Nama Lengkap & Email -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Nama Lengkap *</label>
              <input
                v-model="userForm.name"
                type="text"
                required
                placeholder="cth: Budi Pratama"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Email Perusahaan *</label>
              <input
                v-model="userForm.email"
                type="email"
                required
                placeholder="nama@bws.co.id"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Tingkat Peran / Level RBAC -->
          <div>
            <label class="font-bold text-gray-700 block mb-1">Peran & Tingkat Otoritas (RBAC Level) *</label>
            <select
              v-model="userForm.level_id"
              required
              class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-bold text-gray-800"
            >
              <option v-for="l in levels" :key="l.level_id" :value="l.level_id">
                Level {{ l.level_id }}: {{ l.level }} ({{ l.keterangan || l.role }})
              </option>
            </select>
          </div>

          <!-- Departemen & Cabang -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Departemen / Divisi</label>
              <input
                v-model="userForm.department"
                type="text"
                placeholder="cth: HC Learning & Dev"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Kantor Cabang</label>
              <input
                v-model="userForm.branch"
                type="text"
                placeholder="cth: Kantor Pusat Sudirman"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Password & Status -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">
                Password {{ editingUser ? '(Kosongkan jika tidak diubah)' : '*' }}
              </label>
              <input
                v-model="userForm.password"
                type="password"
                :required="!editingUser"
                placeholder="Default: password123"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Status Akun</label>
              <select
                v-model="userForm.is_active"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              >
                <option :value="true">Aktif</option>
                <option :value="false">Nonaktif</option>
              </select>
            </div>
          </div>

          <!-- Footer Action Buttons -->
          <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-100">
            <button
              type="button"
              @click="showUserModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingUser"
              class="bg-[#0067AC] hover:bg-[#00528c] text-white px-5 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shadow-xs disabled:opacity-50"
            >
              <RefreshCw v-if="savingUser" class="w-3.5 h-3.5 animate-spin" />
              <span>{{ editingUser ? 'Simpan Perubahan' : 'Daftarkan Pengguna' }}</span>
            </button>
          </div>

        </form>

      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL 2: KONFIRMASI HAPUS PENGGUNA (DELETE USER MODAL)        -->
    <!-- ============================================================= -->
    <div
      v-if="showDeleteUserModal && userToDelete"
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl border border-gray-100 overflow-hidden animate-scaleIn">
        
        <div class="p-6 text-center space-y-4">
          <div class="w-14 h-14 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto ring-8 ring-rose-50/50">
            <Trash2 class="w-7 h-7" />
          </div>

          <div class="space-y-1">
            <h3 class="text-base font-extrabold text-gray-900">Hapus Akun Pengguna</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
              Apakah Anda yakin ingin menghapus akun pengguna berikut secara permanen?
            </p>
          </div>

          <!-- User Details Card -->
          <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 text-left text-xs space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Nama:</span>
              <span class="font-bold text-gray-800">{{ userToDelete.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Email:</span>
              <span class="font-mono text-gray-700">{{ userToDelete.email }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Peran:</span>
              <span class="font-bold text-[#0067AC]">{{ userToDelete.level?.level || userToDelete.role_name }}</span>
            </div>
          </div>

          <!-- Self Deletion Warning -->
          <div
            v-if="auth.user && auth.user.id === userToDelete.id"
            class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs text-left flex items-start gap-2"
          >
            <AlertCircle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
            <span>Akun ini sedang Anda gunakan untuk login saat ini. Sistem keamanan perbankan melarang penghapusan akun aktif sendiri.</span>
          </div>

          <!-- Actions -->
          <div class="pt-2 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="showDeleteUserModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="deletingUser || (auth.user && auth.user.id === userToDelete.id)"
              @click="confirmDeleteUser"
              class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shadow-xs disabled:opacity-40"
            >
              <RefreshCw v-if="deletingUser" class="w-3.5 h-3.5 animate-spin" />
              <span>Hapus Akun Pengguna</span>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL 3: TAMBAH / EDIT KARYAWAN (MASTER KARYAWAN MODAL)       -->
    <!-- ============================================================= -->
    <div
      v-if="showKaryawanModal"
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl border border-gray-100 overflow-hidden animate-scaleIn max-h-[90vh] flex flex-col">
        
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-[#f8faf9] shrink-0">
          <div class="flex items-center gap-2">
            <BadgeCheck class="w-4 h-4 text-[#0067AC]" />
            <h3 class="font-extrabold text-sm text-gray-900">
              {{ editingKaryawan ? 'Edit Master Data Karyawan' : 'Tambah Karyawan Baru BWS' }}
            </h3>
          </div>
          <button @click="showKaryawanModal = false" class="text-gray-400 hover:text-gray-600 font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitKaryawanForm" class="p-5 space-y-4 text-xs overflow-y-auto flex-1">
          
          <!-- NIK & Nama Lengkap -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">NIK Pegawai *</label>
              <input
                v-model="karyawanForm.nik"
                type="text"
                required
                placeholder="cth: BWS-EMP-1020"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none font-mono"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Nama Lengkap *</label>
              <input
                v-model="karyawanForm.nama"
                type="text"
                required
                placeholder="cth: Hendro Santoso"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Panggilan & Gender -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Nama Panggilan</label>
              <input
                v-model="karyawanForm.nama_panggilan"
                type="text"
                placeholder="cth: Hendro"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Jenis Kelamin</label>
              <select
                v-model="karyawanForm.jk"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              >
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
          </div>

          <!-- Email & No HP -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Email Korporat *</label>
              <input
                v-model="karyawanForm.email"
                type="email"
                required
                placeholder="hendro@bws.co.id"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">No. Handphone</label>
              <input
                v-model="karyawanForm.hp"
                type="text"
                placeholder="+62 811-xxx"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Departemen & Jabatan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Departemen / Divisi *</label>
              <input
                v-model="karyawanForm.departemen"
                type="text"
                required
                placeholder="cth: Information Technology"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Jabatan *</label>
              <input
                v-model="karyawanForm.jabatan"
                type="text"
                required
                placeholder="cth: IT Senior Officer"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Kantor Cabang & Golongan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Kantor Cabang / Unit Kerja *</label>
              <input
                v-model="karyawanForm.kantor_cabang"
                type="text"
                required
                placeholder="cth: Kantor Pusat Sudirman"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Golongan / Grade</label>
              <input
                v-model="karyawanForm.golongan"
                type="text"
                placeholder="cth: Senior Officer / AVP"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Status Kerja & Target Jam Pelatihan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-gray-700 block mb-1">Status Kepegawaian *</label>
              <select
                v-model="karyawanForm.st_kerja"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              >
                <option value="AKTIF">Aktif Tetap</option>
                <option value="KONTRAK">Pegawai Kontrak</option>
                <option value="PROBATION">Masa Percobaan (Probation)</option>
                <option value="RESIGN">Nonaktif / Resign</option>
              </select>
            </div>
            <div>
              <label class="font-bold text-gray-700 block mb-1">Target Jam Pelatihan (OJK)</label>
              <input
                v-model.number="karyawanForm.target_jam_pelatihan"
                type="number"
                min="1"
                placeholder="20"
                class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:outline-none"
              />
            </div>
          </div>

          <!-- Footer Action Buttons -->
          <div class="pt-4 flex items-center justify-end gap-2 border-t border-gray-100 shrink-0">
            <button
              type="button"
              @click="showKaryawanModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="savingKaryawan"
              class="bg-[#0067AC] hover:bg-[#00528c] text-white px-5 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shadow-xs disabled:opacity-50"
            >
              <RefreshCw v-if="savingKaryawan" class="w-3.5 h-3.5 animate-spin" />
              <span>{{ editingKaryawan ? 'Simpan Perubahan' : 'Simpan Karyawan' }}</span>
            </button>
          </div>

        </form>

      </div>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL 4: KONFIRMASI HAPUS KARYAWAN (DELETE KARYAWAN MODAL)    -->
    <!-- ============================================================= -->
    <div
      v-if="showDeleteKaryawanModal && karyawanToDelete"
      class="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl border border-gray-100 overflow-hidden animate-scaleIn">
        
        <div class="p-6 text-center space-y-4">
          <div class="w-14 h-14 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto ring-8 ring-rose-50/50">
            <Trash2 class="w-7 h-7" />
          </div>

          <div class="space-y-1">
            <h3 class="text-base font-extrabold text-gray-900">Hapus Data Karyawan</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
              Apakah Anda yakin ingin menghapus data karyawan ini dari database master?
            </p>
          </div>

          <!-- Karyawan Details Card -->
          <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 text-left text-xs space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-gray-400">NIK:</span>
              <span class="font-mono font-bold text-[#0067AC]">{{ karyawanToDelete.nik }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Nama:</span>
              <span class="font-bold text-gray-800">{{ karyawanToDelete.nama }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Jabatan:</span>
              <span class="text-gray-700">{{ karyawanToDelete.jabatan }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-400">Cabang:</span>
              <span class="text-gray-700">{{ karyawanToDelete.kantor_cabang }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="pt-2 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="showDeleteKaryawanModal = false"
              class="px-4 py-2 rounded-full text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="deletingKaryawan"
              @click="confirmDeleteKaryawan"
              class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-full text-xs font-bold transition flex items-center gap-1.5 shadow-xs disabled:opacity-40"
            >
              <RefreshCw v-if="deletingKaryawan" class="w-3.5 h-3.5 animate-spin" />
              <span>Hapus Karyawan</span>
            </button>
          </div>
        </div>

      </div>
    </div>

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