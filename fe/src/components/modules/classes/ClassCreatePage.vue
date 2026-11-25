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
  ChevronDown,
  ShieldCheck,
  UserCheck,
  FileCheck2,
  FileText,
  Upload,
  Image,
  Star,
  Check,
  Search,
  HelpCircle,
  Layers
} from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import SubpageHeader from '@/components/SubpageHeader.vue'

const props = defineProps({
  initialProgramId: {
    type: [Number, String],
    default: null
  },
  editClassId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['back', 'created'])
const auth = useAuthStore()

const isEditMode = computed(() => !!props.editClassId)
const loadingInit = ref(true)
const isSubmitting = ref(false)
const feedback = ref({ type: '', message: '' })

// Master data lists
const tnaProposals = ref([])
const allPrograms = ref([])
const masterFacilitators = ref([])
const masterPegawai = ref([])
const masterLocations = ref([])

// Master Pegawai Modal & Selection State
const showPegawaiModal = ref(false)
const searchPegawai = ref('')
const filterPegawaiDept = ref('')
const selectedPegawaiIds = ref([])
const selectedQuickKaryawanId = ref('')

// Form State matching SS2 Excel Wireframe
const form = ref({
  tna_id: null,
  program_id: props.initialProgramId || '',
  class_code: '',
  pic_name: auth.user?.name || 'Budi Pratama (Super Admin)',
  batch_name: 'Batch 1',
  method: 'In-Classroom',
  start_date: new Date().toISOString().substring(0, 10),
  end_date: new Date(Date.now() + 2 * 86400000).toISOString().substring(0, 10),
  daily_schedule: '08:30 - 16:30 WIB',
  location_or_url: 'Ruang 1 - BWS Corporate Learning Center',
  max_quota: 25,
  trainer_name: 'PT Mitra Konsultan Perbankan',
  passing_grade: 75,
  description: '',
  notes: '',
  auto_approve: true,
  
  // SS2 Section: Agenda
  sessions: [],
  
  // SS2 Section: Document
  documents: [],
  
  // SS2 Section: Upload Documentation (Foto 1 - 10)
  documentation_photos: Array.from({ length: 10 }, (_, i) => ({
    photo_number: i + 1,
    name: `Foto ${i + 1}`,
    file_name: '',
    file_path: '',
    preview_url: ''
  })),

  // SS2 Section: Evaluation (9 Pertanyaan Skala 1 - 4)
  evaluation_config: [
    { id: 1, category: 'Materi', question: 'Memberikan manfaat dalam meningkatkan wawasan pengetahuan', rating: 4 },
    { id: 2, category: 'Materi', question: 'Memberikan manfaat dalam menunjang kinerja pekerjaan', rating: 4 },
    { id: 3, category: 'Materi', question: 'Tampilan presentasi modul dapat dimengerti', rating: 4 },
    { id: 4, category: 'Pengajar', question: 'Kemampuan menjelaskan materi dengan bahasa yang mudah dipahami', rating: 4 },
    { id: 5, category: 'Pengajar', question: 'Menguasai isi materi yang dibawakan', rating: 4 },
    { id: 6, category: 'Pengajar', question: 'Kemampuan menciptakan suasana belajar yang efektif baik dari sisi Metode dan Waktu', rating: 4 },
    { id: 7, category: 'Kepanitiaan', question: 'Support panitia terhadap kelancaran proses training', rating: 4 },
    { id: 8, category: 'Kepanitiaan', question: 'Support panitia terhadap pemenuhan kebutuhan peserta training', rating: 4 },
    { id: 9, category: 'Kepanitiaan', question: 'Etika panitia dalam berinteraksi dengan peserta training', rating: 4 }
  ],

  enrolled_participants: []
})

const evalCategoryOptions = ['Materi', 'Pengajar', 'Kepanitiaan']

function addEvaluationItem() {
  const nextId = form.value.evaluation_config.reduce((max, item) => Math.max(max, Number(item.id) || 0), 0) + 1
  form.value.evaluation_config.push({
    id: nextId,
    category: 'Materi',
    question: '',
    rating: 4,
    is_custom: true
  })
}

function removeEvaluationItem(idx) {
  if (form.value.evaluation_config.length > 1) {
    form.value.evaluation_config.splice(idx, 1)
  }
}

// Active TNA proposals
const activeTnaList = computed(() => {
  return tnaProposals.value.filter(t => t.tna_status === 'Aktif')
})

const displayedTnaList = computed(() => {
  const list = [...activeTnaList.value]
  if (form.value.tna_id) {
    const cur = tnaProposals.value.find(t => Number(t.id) === Number(form.value.tna_id))
    if (cur && !list.some(t => Number(t.id) === Number(cur.id))) {
      list.unshift(cur)
    }
  }
  return list.length > 0 ? list : tnaProposals.value
})

// Selected TNA
const selectedTna = computed(() => {
  if (!form.value.tna_id) return null
  return tnaProposals.value.find(t => Number(t.id) === Number(form.value.tna_id)) || null
})

// Programs filtered by chosen TNA (or all programs if TNA has no filter)
const filteredPrograms = computed(() => {
  if (!form.value.tna_id) return allPrograms.value
  return allPrograms.value.filter(p => Number(p.tna_proposal_id) === Number(form.value.tna_id))
})

// Selected Program
const selectedProgram = computed(() => {
  if (!form.value.program_id) return null
  return allPrograms.value.find(p => Number(p.id) === Number(form.value.program_id)) || null
})

// Available Nominations from Selected Program
const availableNominations = computed(() => {
  return selectedProgram.value?.participants || []
})

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

function onTnaChange() {
  // If current program doesn't belong to this TNA, clear or pick first
  if (form.value.program_id) {
    const p = allPrograms.value.find(prog => Number(prog.id) === Number(form.value.program_id))
    if (p && Number(p.tna_proposal_id) !== Number(form.value.tna_id)) {
      const firstProg = filteredPrograms.value[0]
      form.value.program_id = firstProg ? firstProg.id : ''
      onProgramSelected()
    }
  } else {
    const firstProg = filteredPrograms.value[0]
    if (firstProg) {
      form.value.program_id = firstProg.id
      onProgramSelected()
    }
  }
}

function onProgramSelected() {
  if (!selectedProgram.value) return
  const prog = selectedProgram.value

  // Sync TNA if not set
  if (prog.tna_proposal_id && !form.value.tna_id) {
    form.value.tna_id = prog.tna_proposal_id
  }

  // Pre-fill location & trainer from program
  if (prog.location && (!form.value.location_or_url || form.value.location_or_url.startsWith('Ruang 1'))) {
    form.value.location_or_url = prog.location
  }
  if (prog.facilitator) {
    form.value.trainer_name = prog.facilitator
  }
  if (prog.passing_grade) {
    form.value.passing_grade = prog.passing_grade
  }

  // Suggest batch name based on existing count (Supports: 1 program multiple classes!)
  if (!isEditMode.value) {
    const existingCount = prog.classes_count || prog.classes?.length || 0
    form.value.batch_name = `Batch ${existingCount + 1}`
    
    // Auto populate initial participants from program nominations
    if (prog.participants && prog.participants.length > 0 && form.value.enrolled_participants.length === 0) {
      form.value.enrolled_participants = prog.participants.map(p => ({
        nik: p.nik || '',
        name: p.name,
        department: p.department || prog.department,
        branch: p.branch || 'Kantor Pusat',
        position: p.position || 'Staff'
      }))
    }
  }

  // If agenda empty, generate default sessions
  if (form.value.sessions.length === 0) {
    generateDefaultSessions()
  }
}

// Generate default sessions / agenda from dates
function generateDefaultSessions() {
  const topics = [
    'Materi 1: Pengenalan Regulasi & Tata Kelola Risiko Perbankan',
    'Materi 2: Kerangka Kerja Operasional & Pengendalian Internal',
    'Materi 3: Studi Kasus, Simulasi Praktik & Problem Solving',
    'Materi 4: Ujian Evaluasi Kompetensi & Action Plan'
  ]

  const list = []
  topics.forEach((t, i) => {
    list.push({
      session_number: i + 1,
      topic: t,
      duration_hours: 2,
      trainer_name: form.value.trainer_name || 'Tim Fasilitator BWS',
      material_file_name: '',
      material_file_path: ''
    })
  })
  form.value.sessions = list
}

// Add Agenda (ss2: Add Agenda [+])
function addAgendaRow() {
  const nextNum = form.value.sessions.length + 1
  form.value.sessions.push({
    session_number: nextNum,
    topic: `Materi ${nextNum}: Modul Pelatihan Lanjutan`,
    duration_hours: 2,
    trainer_name: form.value.trainer_name || 'Instruktur',
    material_file_name: '',
    material_file_path: ''
  })
}

function removeAgendaRow(idx) {
  form.value.sessions.splice(idx, 1)
  form.value.sessions.forEach((s, i) => {
    s.session_number = i + 1
  })
}

// Add Document (ss2: Add Document [+])
function addDocumentRow() {
  const nextNum = form.value.documents.length + 1
  form.value.documents.push({
    id: Date.now() + Math.random(),
    label: `File ${nextNum}`,
    name: nextNum === 1 ? 'Surat Undangan Pelatihan Resmi' : nextNum === 2 ? 'Kerangka Acuan Kerja (TOR)' : `Dokumen Pendukung ${nextNum}`,
    file_name: '',
    file_path: '',
    file_size: null
  })
}

function removeDocumentRow(idx) {
  form.value.documents.splice(idx, 1)
}

// Mock/Upload file for Agenda Material
async function handleMaterialFileUpload(e, session) {
  const file = e.target.files[0]
  if (!file) return
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', 'material')
    const { data } = await apiClient.post('/classes/upload-attachment', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    session.material_file_name = data.file_name
    session.material_file_path = data.file_path
  } catch (err) {
    // Fallback: save client-side file name
    session.material_file_name = file.name
  }
}

// Mock/Upload file for Document
async function handleDocumentFileUpload(e, doc) {
  const file = e.target.files[0]
  if (!file) return
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', 'document')
    const { data } = await apiClient.post('/classes/upload-attachment', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    doc.file_name = data.file_name
    doc.file_path = data.file_path
    doc.file_size = data.file_size
  } catch (err) {
    doc.file_name = file.name
    doc.file_size = file.size
  }
}

// Upload Documentation Photo (Foto 1 s/d Foto 10)
async function handlePhotoUpload(e, photoItem) {
  const file = e.target.files[0]
  if (!file) return
  photoItem.preview_url = URL.createObjectURL(file)
  photoItem.file_name = file.name
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', 'photo')
    const { data } = await apiClient.post('/classes/upload-attachment', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    photoItem.file_path = data.file_path
  } catch (err) {
    // Fallback keep local preview
  }
}

function clearPhoto(photoItem) {
  photoItem.file_name = ''
  photoItem.file_path = ''
  photoItem.preview_url = ''
}

// Participant management
const newParticipant = ref({ nik: '', name: '', department: '', position: '' })

function addManualParticipant() {
  if (!newParticipant.value.name.trim()) return
  form.value.enrolled_participants.push({ ...newParticipant.value, branch: 'Kantor Pusat' })
  newParticipant.value = { nik: '', name: '', department: '', position: '' }
}

function removeParticipant(idx) {
  form.value.enrolled_participants.splice(idx, 1)
}

function selectAllNominations() {
  availableNominations.value.forEach(p => {
    if (!form.value.enrolled_participants.some(ep => ep.nik === p.nik || ep.name === p.name)) {
      form.value.enrolled_participants.push({
        nik: p.nik,
        name: p.name,
        department: p.department || selectedProgram.value?.department,
        branch: p.branch || 'Kantor Pusat',
        position: p.position || 'Staff'
      })
    }
  })
}

function clearAllParticipants() {
  form.value.enrolled_participants = []
}

// Master Pegawai Picker Logics
const picOptions = computed(() => {
  return masterPegawai.value.map(k => ({
    value: k.nama,
    label: k.nama,
    subLabel: `[${k.nik}] ${k.departemen || '-'} • ${k.jabatan || '-'}`
  }))
})

// Location Options from Master Ruang / Lokasi Diklat
const locationOptions = computed(() => {
  return masterLocations.value.map(loc => ({
    value: loc.name,
    label: loc.name,
    subLabel: `[${loc.code}] ${loc.building || '-'} (${loc.floor || '-'}) • Kapasitas: ${loc.capacity || '-'} orang`
  }))
})

// Facilitator Options from Master Facilitator
const facilitatorOptions = computed(() => {
  return masterFacilitators.value.map(fac => ({
    value: fac.name,
    label: fac.name,
    subLabel: `[${fac.code}] ${fac.type}${fac.specialization ? ' • ' + fac.specialization : ''}`
  }))
})

const tnaSelectOptions = computed(() => [
  { value: null, label: '-- Seluruh Dokumen TNA --' },
  ...displayedTnaList.value.map(tna => ({
    value: tna.id,
    label: `${tna.proposal_code || 'TNA-' + tna.year} (Tahun ${tna.year}) • ${tna.tna_status || 'Aktif'}`
  }))
])

const programSelectOptions = computed(() => [
  { value: '', label: '-- Pilih Program Pelatihan --' },
  ...filteredPrograms.value.map(p => ({
    value: p.id,
    label: `[${p.program_code}] ${p.title}`,
    subLabel: `Pagu: ${formatRupiah(p.total_budget)} • Target: ${p.target_participants_count || 0} Peserta`
  }))
])

const methodSelectOptions = [
  { value: 'In-Classroom', label: 'Tatap Muka (In-Classroom)' },
  { value: 'Virtual', label: 'Virtual Classroom (Zoom/Teams)' },
  { value: 'Hybrid', label: 'Hybrid Learning' },
  { value: 'E-Learning', label: 'ELAPSE E-Learning LMS' }
]

const quickKaryawanOptions = computed(() => [
  { value: '', label: '-- Pilih Pegawai dari Master Data Karyawan BWS --' },
  ...masterPegawai.value.map(k => ({
    value: k.karyawan_id,
    label: `[${k.nik}] ${k.nama}`,
    subLabel: `${k.departemen || '-'} (${k.jabatan || '-'}) ${isPegawaiEnrolled(k) ? '• (Sudah Terdaftar)' : ''}`
  }))
])

const deptFilterOptions = computed(() => [
  { value: '', label: '-- Seluruh Unit Kerja --' },
  ...uniquePegawaiDepts.value.map(d => ({ value: d, label: d }))
])

const uniquePegawaiDepts = computed(() => {
  const set = new Set()
  masterPegawai.value.forEach(k => {
    if (k.departemen) set.add(k.departemen)
  })
  return Array.from(set).sort()
})

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

function isPegawaiEnrolled(k) {
  return form.value.enrolled_participants.some(p =>
    (p.nik && k.nik && p.nik.toLowerCase() === k.nik.toLowerCase()) ||
    (p.name && k.nama && p.name.toLowerCase() === k.nama.toLowerCase())
  )
}

function toggleSelectPegawai(k) {
  if (isPegawaiEnrolled(k)) return
  const id = k.karyawan_id
  const idx = selectedPegawaiIds.value.indexOf(id)
  if (idx >= 0) {
    selectedPegawaiIds.value.splice(idx, 1)
  } else {
    selectedPegawaiIds.value.push(id)
  }
}

function selectAllFilteredPegawai() {
  const available = filteredMasterPegawai.value.filter(k => !isPegawaiEnrolled(k))
  const allSelected = available.length > 0 && available.every(k => selectedPegawaiIds.value.includes(k.karyawan_id))
  if (allSelected) {
    selectedPegawaiIds.value = []
  } else {
    selectedPegawaiIds.value = available.map(k => k.karyawan_id)
  }
}

function addSelectedPegawaiToClass() {
  const selected = masterPegawai.value.filter(k => selectedPegawaiIds.value.includes(k.karyawan_id))
  selected.forEach(k => {
    if (!isPegawaiEnrolled(k)) {
      form.value.enrolled_participants.push({
        user_id: k.user?.id || k.karyawan_id,
        nik: k.nik,
        name: k.nama,
        department: k.departemen,
        branch: k.kantor_cabang || 'Kantor Pusat Sudirman',
        position: k.jabatan || 'Staff'
      })
    }
  })
  selectedPegawaiIds.value = []
  showPegawaiModal.value = false
}

function onQuickKaryawanSelected() {
  if (!selectedQuickKaryawanId.value) return
  const k = masterPegawai.value.find(item => String(item.karyawan_id) === String(selectedQuickKaryawanId.value))
  if (k) {
    newParticipant.value.nik = k.nik
    newParticipant.value.name = k.nama
    newParticipant.value.department = k.departemen
    newParticipant.value.position = k.jabatan
  }
}

function addQuickKaryawan() {
  if (!selectedQuickKaryawanId.value) return
  const k = masterPegawai.value.find(item => String(item.karyawan_id) === String(selectedQuickKaryawanId.value))
  if (k && !isPegawaiEnrolled(k)) {
    form.value.enrolled_participants.push({
      user_id: k.user?.id || k.karyawan_id,
      nik: k.nik,
      name: k.nama,
      department: k.departemen,
      branch: k.kantor_cabang || 'Kantor Pusat Sudirman',
      position: k.jabatan || 'Staff'
    })
  }
  selectedQuickKaryawanId.value = ''
  newParticipant.value = { nik: '', name: '', department: '', position: '' }
}

// Load data on Mount
async function loadData() {
  loadingInit.value = true
  try {
    const [resTna, resProg, resFac, resKaryawan, resLoc] = await Promise.all([
      apiClient.get('/tna'),
      apiClient.get('/programs'),
      apiClient.get('/facilitators'),
      apiClient.get('/karyawan'),
      apiClient.get('/training-locations')
    ])
    tnaProposals.value = resTna.data.proposals || []
    allPrograms.value = resProg.data.programs || []
    masterFacilitators.value = resFac.data.facilitators || []
    masterPegawai.value = resKaryawan.data?.karyawans || []
    masterLocations.value = resLoc.data?.locations || []

    if (isEditMode.value) {
      const { data } = await apiClient.get(`/classes/${props.editClassId}`)
      const c = data.class
      form.value.tna_id = c.tna_proposal_id || c.program?.tna_proposal_id || null
      form.value.program_id = c.program_id
      form.value.class_code = c.class_code || ''
      form.value.pic_name = c.pic_name || (c.maker?.name || 'Staff HC')
      form.value.batch_name = c.batch_name || 'Batch 1'
      form.value.method = c.method || 'In-Classroom'
      form.value.start_date = c.start_date ? c.start_date.substring(0, 10) : ''
      form.value.end_date = c.end_date ? c.end_date.substring(0, 10) : ''
      form.value.daily_schedule = c.daily_schedule || '08:30 - 16:30 WIB'
      form.value.location_or_url = c.location_or_url || 'Ruang 1'
      form.value.max_quota = c.max_quota || 25
      form.value.trainer_name = c.trainer_name || ''
      form.value.passing_grade = c.passing_grade || 75
      form.value.description = c.description || ''
      form.value.notes = c.notes || ''

      // Sessions
      if (c.sessions && c.sessions.length > 0) {
        form.value.sessions = c.sessions.map((s, idx) => ({
          session_number: s.session_number || idx + 1,
          topic: s.topic || s.session_name || `Materi ${idx + 1}`,
          duration_hours: 2,
          trainer_name: s.trainer_name || c.trainer_name,
          material_file_name: s.location_or_url?.includes('attachments') ? s.location_or_url.split('/').pop() : '',
          material_file_path: s.location_or_url || ''
        }))
      } else {
        generateDefaultSessions()
      }

      // Documents
      if (c.documents && Array.isArray(c.documents) && c.documents.length > 0) {
        form.value.documents = c.documents
      } else {
        form.value.documents = [
          { id: 1, label: 'File 1', name: 'Surat Undangan Pelatihan Resmi', file_name: '', file_path: '' },
          { id: 2, label: 'File 2', name: 'Kerangka Acuan Kerja (TOR)', file_name: '', file_path: '' }
        ]
      }

      // Photos
      if (c.documentation_photos && Array.isArray(c.documentation_photos) && c.documentation_photos.length > 0) {
        c.documentation_photos.forEach((cp, idx) => {
          if (form.value.documentation_photos[idx]) {
            form.value.documentation_photos[idx].file_name = cp.file_name || ''
            form.value.documentation_photos[idx].file_path = cp.file_path || ''
          }
        })
      }

      // Evaluation
      if (c.evaluation_config && Array.isArray(c.evaluation_config) && c.evaluation_config.length > 0) {
        form.value.evaluation_config = c.evaluation_config
      }

      // Enrollments
      if (c.enrollments && c.enrollments.length > 0) {
        form.value.enrolled_participants = c.enrollments.map(e => ({
          user_id: e.user_id,
          nik: e.nik,
          name: e.participant_name || e.user?.name || 'Peserta',
          department: e.department,
          branch: e.branch,
          position: e.position
        }))
      }
    } else {
      // Create mode defaults
      const randNum = String(Math.floor(100 + Math.random() * 900))
      form.value.class_code = `C26_${randNum}`

      // Default active TNA
      const activeTna = activeTnaList.value[0] || tnaProposals.value[0]
      if (activeTna) {
        form.value.tna_id = activeTna.id
      }

      // If initialProgramId given
      if (props.initialProgramId) {
        form.value.program_id = props.initialProgramId
      } else if (filteredPrograms.value.length > 0) {
        form.value.program_id = filteredPrograms.value[0].id
      }

      onProgramSelected()

      // Default 2 documents
      form.value.documents = [
        { id: 1, label: 'File 1', name: 'Surat Undangan Pelatihan Resmi', file_name: '', file_path: '' },
        { id: 2, label: 'File 2', name: 'Kerangka Acuan Kerja (TOR)', file_name: '', file_path: '' }
      ]
    }
  } catch (err) {
    feedback.value = { type: 'error', message: 'Gagal memuat data formulir kelas.' }
  } finally {
    loadingInit.value = false
  }
}

onMounted(() => {
  loadData()
})

// Submit Form
async function submitForm() {
  if (!form.value.program_id) {
    feedback.value = { type: 'error', message: 'Silakan pilih Program Pelatihan induk terlebih dahulu.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  if (!form.value.batch_name) {
    feedback.value = { type: 'error', message: 'Nama batch kelas wajib diisi (e.g. Batch 1).' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  if (!form.value.pic_name) {
    feedback.value = { type: 'error', message: 'PIC Koordinator Kelas wajib dipilih dari Master Pegawai.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  if (!form.value.start_date || !form.value.end_date) {
    feedback.value = { type: 'error', message: 'Tanggal mulai dan selesai kelas wajib diisi.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  if (!form.value.location_or_url) {
    feedback.value = { type: 'error', message: 'Location / Ruang kelas wajib dipilih dari Master Ruangan.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  if (!form.value.trainer_name) {
    feedback.value = { type: 'error', message: 'Facilitator / Lembaga pelatihan wajib dipilih dari Master Facilitator.' }
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }

  isSubmitting.value = true
  feedback.value = { type: '', message: '' }

  try {
    const payload = {
      tna_proposal_id: form.value.tna_id,
      program_id: form.value.program_id,
      class_code: form.value.class_code,
      pic_name: form.value.pic_name,
      batch_name: form.value.batch_name,
      method: form.value.method,
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      daily_schedule: form.value.daily_schedule,
      location_or_url: form.value.location_or_url,
      max_quota: form.value.max_quota,
      trainer_name: form.value.trainer_name,
      passing_grade: form.value.passing_grade,
      description: form.value.description,
      notes: form.value.notes,
      auto_approve: true,
      sessions: form.value.sessions,
      documents: form.value.documents,
      documentation_photos: form.value.documentation_photos.map(p => ({
        photo_number: p.photo_number,
        name: p.name,
        file_name: p.file_name,
        file_path: p.file_path
      })),
      evaluation_config: form.value.evaluation_config,
      enrolled_participants: form.value.enrolled_participants
    }

    if (isEditMode.value) {
      const { data } = await apiClient.put(`/classes/${props.editClassId}`, payload)
      feedback.value = { type: 'success', message: data.message || 'Data kelas berhasil disimpan.' }
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      const { data } = await apiClient.post('/classes', payload)
      emit('created', data.message || 'Batch Kelas berhasil dibuka!')
    }
  } catch (err) {
    feedback.value = {
      type: 'error',
      message: err.response?.data?.message || 'Gagal menyimpan data kelas. Periksa kembali inputan Anda.'
    }
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="flex flex-col gap-6 w-full pb-20">

    <!-- Subpage Navigation Header -->
    <SubpageHeader
      back-label="Kembali ke Daftar Class"
      :title="isEditMode ? `Edit Batch Kelas: ${form.class_code}` : 'Form Buat Kelas Pelatihan Baru'"
      subtitle="Pengaturan kelas diklat perbankan, pemilihan TNA & program, agenda materi, dokumen, foto dokumentasi, dan evaluasi"
      :badge="isEditMode ? 'Mode Edit Kelas' : 'Create Class Batch'"
      @back="emit('back')"
    />

    <!-- Feedback Message Banner -->
    <div
      v-if="feedback.message"
      :class="[
        'p-4 rounded-2xl flex items-center gap-3 text-xs font-semibold shadow-2xs border transition',
        feedback.type === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'
      ]"
    >
      <CheckCircle2 v-if="feedback.type !== 'error'" class="w-5 h-5 text-emerald-600 shrink-0" />
      <AlertCircle v-else class="w-5 h-5 text-rose-600 shrink-0" />
      <span>{{ feedback.message }}</span>
    </div>

    <!-- MAIN FORM BODY -->
    <form @submit.prevent="submitForm" class="flex flex-col gap-6">

      <!-- ========================================== -->
      <!-- SECTION 1: PILIH TNA & PROGRAM KERJA INDUK -->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-5">
        
        <!-- TNA Selection Banner -->
        <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50/80 via-indigo-50/40 to-white border border-blue-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#0067AC] text-white flex items-center justify-center font-bold shadow-xs shrink-0">
              <FileCheck2 class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold text-gray-900 uppercase tracking-wider">Pilih TNA Aktif</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  <span>TNA Aktif</span>
                </span>
              </div>
              <p class="text-[11px] text-gray-600 mt-0.5">
                Pilih usulan TNA aktif untuk memfilter program kerja diklat yang tersedia.
              </p>
            </div>
          </div>

          <div class="w-full md:w-80 shrink-0">
            <AppSearchableSelect
              v-model="form.tna_id"
              :options="tnaSelectOptions"
              placeholder="-- Seluruh Dokumen TNA --"
              search-placeholder="Cari nomor atau tahun TNA..."
              input-class="bg-white text-xs font-bold text-gray-900 py-2.5 border-2 border-blue-300 shadow-2xs"
              @change="onTnaChange"
            />
          </div>
        </div>

        <!-- Program Induction (1 Program bisa untuk beberapa kelas) -->
        <div class="flex flex-col gap-3">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider">
              Program Pelatihan Induk (Work Plan) <span class="text-rose-500">*</span>
            </label>
            <span class="text-[11px] text-[#0067AC] font-bold bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">
              1 Program Bisa Untuk Beberapa Batch Kelas
            </span>
          </div>

          <div>
            <AppSearchableSelect
              v-model="form.program_id"
              :options="programSelectOptions"
              placeholder="-- Pilih Program Pelatihan --"
              search-placeholder="Cari kode atau judul program pelatihan..."
              input-class="bg-white text-xs font-bold text-gray-900 py-3 border-gray-300 shadow-2xs"
              @change="onProgramSelected"
            />
          </div>

          <!-- Highlight Preview Selected Program -->
          <div
            v-if="selectedProgram"
            class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs"
          >
            <div class="flex flex-col gap-1">
              <div class="flex items-center gap-2">
                <span class="font-mono font-extrabold text-[#0067AC] bg-blue-100/70 px-2.5 py-0.5 rounded-md">
                  {{ selectedProgram.program_code }}
                </span>
                <span class="text-gray-400">•</span>
                <span class="font-bold text-gray-800">{{ selectedProgram.category }}</span>
                <span class="text-gray-400">•</span>
                <span class="text-gray-600 font-semibold">Tahun {{ selectedProgram.year }}</span>
              </div>
              <h4 class="font-extrabold text-sm text-gray-900">{{ selectedProgram.title }}</h4>
              <p class="text-[11px] text-gray-500 line-clamp-1">
                Unit Pengusul: {{ selectedProgram.unit_initiator || selectedProgram.department }} &bull; Lokasi Rujukan: {{ selectedProgram.location || '-' }}
              </p>
            </div>

            <div class="flex items-center gap-4 bg-white p-3 rounded-xl border border-gray-200 shrink-0">
              <div class="text-center px-2">
                <span class="text-[10px] text-gray-400 block font-bold uppercase">Batch Terdaftar</span>
                <span class="text-sm font-black text-[#0067AC]">
                  {{ selectedProgram.classes_count || selectedProgram.classes?.length || 1 }} Batch
                </span>
              </div>
              <div class="h-6 w-px bg-gray-200"></div>
              <div class="text-center px-2">
                <span class="text-[10px] text-gray-400 block font-bold uppercase">Passing Grade</span>
                <span class="text-sm font-black text-emerald-600">
                  {{ selectedProgram.passing_grade || 75 }} / 100
                </span>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ========================================== -->
      <!-- SECTION 2: INFORMASI KELAS (SS2 WIREFRAME) -->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-5">
        <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
          <div class="w-9 h-9 rounded-2xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
            <Presentation class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-sm font-extrabold text-gray-900">Informasi Pokok Kelas</h3>
            <p class="text-[11px] text-gray-500">Parameter batch, penanggung jawab PIC, jadwal, lokasi, dan instruktur</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 text-xs">
          
          <!-- Class ID (e.g. C26_001 / C24_001) -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Class ID</span>
              <span class="text-[10px] text-[#0067AC] font-mono font-bold">Kode Unik</span>
            </label>
            <input
              v-model="form.class_code"
              type="text"
              required
              placeholder="e.g. C26_001"
              class="w-full bg-white text-xs font-mono font-bold text-[#0067AC] rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
            />
          </div>

          <!-- PIC Name (Koordinator Kelas - Master Pegawai) -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>PIC Name (Koordinator Kelas) <span class="text-rose-500">*</span></span>
              <span class="text-[10px] text-[#0067AC] font-medium font-mono">Master Pegawai</span>
            </label>
            <AppSearchableSelect
              v-model="form.pic_name"
              :options="picOptions"
              placeholder="-- Pilih PIC Koordinator dari Master Pegawai --"
              search-placeholder="Cari nama, NIK, unit, atau jabatan..."
              input-class="bg-white font-bold text-gray-900 py-2.5 border-gray-300 shadow-2xs"
            />
          </div>

          <!-- Batch (e.g. Batch 1 / Batch 2) -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Batch Pelaksanaan <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.batch_name"
              type="text"
              required
              placeholder="e.g. Batch 1"
              class="w-full bg-white text-xs font-bold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
            />
          </div>

          <!-- Start Date -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              Start Date <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.start_date"
              type="date"
              required
              class="w-full bg-white text-xs font-mono text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
            />
          </div>

          <!-- End Date -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5">
              End Date <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.end_date"
              type="date"
              required
              class="w-full bg-white text-xs font-mono text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
            />
          </div>

          <!-- Location / Ruang (Master Ruangan) -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Location / Ruang <span class="text-rose-500">*</span></span>
              <span class="text-[10px] text-[#0067AC] font-medium font-mono">Master Ruangan</span>
            </label>
            <AppSearchableSelect
              v-model="form.location_or_url"
              :options="locationOptions"
              placeholder="-- Pilih Ruangan / Lokasi dari Master Data --"
              search-placeholder="Cari nama ruang, kode, gedung, atau tipe..."
              input-class="bg-white font-bold text-gray-900 py-2.5 border-gray-300 shadow-2xs"
            />
          </div>

          <!-- Facilitator / Lembaga (Master Facilitator) -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
              <span>Facilitator / Lembaga <span class="text-rose-500">*</span></span>
              <span class="text-[10px] text-[#0067AC] font-medium font-mono">Master Facilitator</span>
            </label>
            <AppSearchableSelect
              v-model="form.trainer_name"
              :options="facilitatorOptions"
              placeholder="-- Pilih Facilitator dari Master Data --"
              search-placeholder="Cari nama, kode, tipe, atau spesialisasi..."
              input-class="bg-white font-bold text-gray-900 py-2.5 border-gray-300 shadow-2xs"
            />
          </div>

          <!-- Metode Diklat -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5">Metode Diklat</label>
            <AppSearchableSelect
              v-model="form.method"
              :options="methodSelectOptions"
              placeholder="-- Pilih Metode Diklat --"
              search-placeholder="Cari metode diklat..."
              input-class="bg-white font-semibold text-gray-900 py-2.5 border-gray-300 shadow-2xs"
            />
          </div>

          <!-- Kuota Peserta -->
          <div>
            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1.5">Kapasitas Kuota Kelas</label>
            <input
              v-model.number="form.max_quota"
              type="number"
              min="1"
              max="200"
              class="w-full bg-white text-xs font-semibold text-gray-900 rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-[#0067AC] focus:outline-none transition shadow-2xs"
            />
          </div>

        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 3: AGENDA (SS2 WIREFRAME)          -->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
              <Clock class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">Agenda</h3>
              <p class="text-[11px] text-gray-500">Materi, durasi jam, pengajar/facilitator, dan file modul diklat</p>
            </div>
          </div>

          <button
            type="button"
            @click="addAgendaRow"
            class="px-3.5 py-2 bg-[#0067AC] hover:bg-[#00558F] text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Agenda [+]</span>
          </button>
        </div>

        <!-- Table Agenda -->
        <div class="overflow-x-auto md:overflow-visible rounded-2xl border border-gray-100">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gray-50 text-[11px] font-extrabold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                <th class="py-3 px-3 w-14 text-center">No</th>
                <th class="py-3 px-4">Material (Topik Materi)</th>
                <th class="py-3 px-4 w-32 text-center">Duration (Jam)</th>
                <th class="py-3 px-4 w-60 min-w-[240px]">Facilitator</th>
                <th class="py-3 px-4 w-64">Upload Materi</th>
                <th class="py-3 px-3 w-16 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="form.sessions.length === 0">
                <td colspan="6" class="py-8 text-center text-gray-400 font-semibold">
                  Belum ada sesi agenda. Klik "Add Agenda [+]" di atas untuk menambahkan sesi materi.
                </td>
              </tr>
              <tr
                v-for="(session, idx) in form.sessions"
                :key="idx"
                class="hover:bg-blue-50/20 transition"
              >
                <!-- No -->
                <td class="py-3 px-3 text-center font-bold text-gray-500">
                  {{ idx + 1 }}
                </td>

                <!-- Material Topic -->
                <td class="py-3 px-4">
                  <input
                    v-model="session.topic"
                    type="text"
                    required
                    placeholder="e.g. Materi 1: Modul Dasar"
                    class="w-full bg-white rounded-xl px-3 py-1.5 border border-gray-300 font-semibold text-gray-900 focus:border-[#0067AC] focus:outline-none"
                  />
                </td>

                <!-- Duration -->
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <input
                      v-model.number="session.duration_hours"
                      type="number"
                      min="1"
                      class="w-16 bg-white rounded-xl px-2 py-1.5 border border-gray-300 font-mono font-bold text-center text-gray-900 focus:border-[#0067AC] focus:outline-none"
                    />
                    <span class="text-gray-500 font-medium text-[11px]">Jam</span>
                  </div>
                </td>

                <!-- Facilitator (Master Facilitator) -->
                <td class="py-3 px-4 min-w-[240px]">
                  <AppSearchableSelect
                    v-model="session.trainer_name"
                    :options="facilitatorOptions"
                    placeholder="-- Pilih Facilitator --"
                    search-placeholder="Cari facilitator..."
                    input-class="bg-white rounded-xl px-3 py-1.5 border-gray-300 font-semibold text-gray-800"
                  />
                </td>

                <!-- Upload Materi -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <label class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-[11px] font-bold cursor-pointer transition shrink-0 flex items-center gap-1">
                      <Upload class="w-3 h-3" />
                      <span>Browse</span>
                      <input
                        type="file"
                        class="hidden"
                        @change="handleMaterialFileUpload($event, session)"
                      />
                    </label>
                    <span
                      class="text-[11px] truncate text-gray-600 font-medium"
                      :title="session.material_file_name || 'Belum ada file modul'"
                    >
                      {{ session.material_file_name || 'Pilih modul PPT/PDF...' }}
                    </span>
                  </div>
                </td>

                <!-- Action -->
                <td class="py-3 px-3 text-center">
                  <button
                    type="button"
                    @click="removeAgendaRow(idx)"
                    class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg transition cursor-pointer"
                    title="Hapus Agenda"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 4: DOCUMENT (SS2 WIREFRAME)        -->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold">
              <FileText class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">Document</h3>
              <p class="text-[11px] text-gray-500">Unggah dokumen resmi pelaksanaan (Surat Undangan, TOR, Rundown, Absensi)</p>
            </div>
          </div>

          <button
            type="button"
            @click="addDocumentRow"
            class="px-3.5 py-2 bg-[#0067AC] hover:bg-[#00558F] text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Document [+]</span>
          </button>
        </div>

        <!-- Table Document -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gray-50 text-[11px] font-extrabold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                <th class="py-3 px-4 w-28">File</th>
                <th class="py-3 px-4">File Name / Deskripsi Dokumen</th>
                <th class="py-3 px-4 w-72">Action / File Attachment</th>
                <th class="py-3 px-3 w-16 text-center">Hapus</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="form.documents.length === 0">
                <td colspan="4" class="py-8 text-center text-gray-400 font-semibold">
                  Belum ada dokumen. Klik "Add Document [+]" untuk menambahkan.
                </td>
              </tr>
              <tr
                v-for="(doc, idx) in form.documents"
                :key="doc.id || idx"
                class="hover:bg-blue-50/20 transition"
              >
                <!-- File Label (File 1, File 2) -->
                <td class="py-3 px-4 font-mono font-bold text-gray-700">
                  <span class="px-2.5 py-1 bg-gray-100 rounded-md border border-gray-200 text-[11px]">
                    {{ doc.label || `File ${idx + 1}` }}
                  </span>
                </td>

                <!-- File Description Name -->
                <td class="py-3 px-4">
                  <input
                    v-model="doc.name"
                    type="text"
                    required
                    placeholder="e.g. Surat Undangan Resmi Diklat"
                    class="w-full bg-white rounded-xl px-3 py-1.5 border border-gray-300 font-semibold text-gray-900 focus:border-[#0067AC] focus:outline-none"
                  />
                </td>

                <!-- Action Browse/Upload -->
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <label class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-[#0067AC] rounded-lg text-[11px] font-bold cursor-pointer transition shrink-0 flex items-center gap-1 border border-blue-200">
                      <Upload class="w-3 h-3" />
                      <span>Browse / Replace</span>
                      <input
                        type="file"
                        class="hidden"
                        @change="handleDocumentFileUpload($event, doc)"
                      />
                    </label>
                    <span
                      class="text-[11px] truncate text-gray-600 font-semibold"
                      :title="doc.file_name || 'Belum ada file terpilih'"
                    >
                      {{ doc.file_name || 'Belum ada file diunggah' }}
                    </span>
                  </div>
                </td>

                <!-- Delete Action -->
                <td class="py-3 px-3 text-center">
                  <button
                    type="button"
                    @click="removeDocumentRow(idx)"
                    class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg transition cursor-pointer"
                    title="Hapus Dokumen"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 5: UPLOAD DOCUMENTATION (FOTO 1-10)-->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
              <Image class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">Upload Documentation</h3>
              <p class="text-[11px] text-gray-500">Unggah foto dokumentasi kegiatan pelatihan perbankan (Foto 1 s/d Foto 10)</p>
            </div>
          </div>
          <span class="text-xs font-mono font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
            Class ID: {{ form.class_code || 'C26_001' }}
          </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5">
          <div
            v-for="photo in form.documentation_photos"
            :key="photo.photo_number"
            class="bg-gray-50/80 rounded-2xl border border-gray-200 p-3 flex flex-col items-center justify-between text-center gap-2 transition hover:border-blue-300 hover:bg-blue-50/20 group"
          >
            <span class="text-[11px] font-bold text-gray-700 uppercase">{{ photo.name }}</span>
            
            <!-- Thumbnail or Placeholder -->
            <div class="w-full h-20 rounded-xl bg-white border border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative">
              <img
                v-if="photo.preview_url || photo.file_path"
                :src="photo.preview_url || `/storage/${photo.file_path}`"
                alt="Foto Dokumentasi"
                class="w-full h-full object-cover"
              />
              <div v-else class="flex flex-col items-center justify-center text-gray-400">
                <Image class="w-6 h-6 stroke-1 mb-1" />
                <span class="text-[9px] font-semibold">Kosong</span>
              </div>
            </div>

            <!-- Upload / Clear Buttons -->
            <div class="flex items-center gap-1 w-full justify-center">
              <label class="px-2 py-1 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg text-[10px] font-bold text-gray-700 cursor-pointer shadow-2xs transition flex items-center gap-1">
                <Upload class="w-2.5 h-2.5" />
                <span>Browse</span>
                <input
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handlePhotoUpload($event, photo)"
                />
              </label>
              <button
                v-if="photo.file_name || photo.preview_url"
                type="button"
                @click="clearPhoto(photo)"
                class="p-1 text-gray-400 hover:text-rose-600 rounded-lg cursor-pointer"
                title="Hapus Foto"
              >
                <Trash2 class="w-3 h-3" />
              </button>
            </div>
            
            <span v-if="photo.file_name" class="text-[9px] text-gray-500 truncate max-w-[100px]" :title="photo.file_name">
              {{ photo.file_name }}
            </span>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 6: EVALUATION (SS2 9 QUESTIONNAIRE)-->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-3 pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-rose-50 text-rose-700 flex items-center justify-center font-bold">
              <Award class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">Evaluation</h3>
              <p class="text-[11px] text-gray-500">Standar kuesioner evaluasi Level 1 Kirkpatrick (skala kepuasan 1 s/d 4)</p>
            </div>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <span class="text-[11px] font-bold text-gray-500">Skala Penilaian: 1 (Kurang) &bull; 2 (Cukup) &bull; 3 (Baik) &bull; 4 (Sangat Baik)</span>
            <button
              type="button"
              @click="addEvaluationItem"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[#0067AC] hover:bg-[#00558F] text-white text-xs font-bold shadow-xs transition cursor-pointer"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Tambah Evaluasi</span>
            </button>
          </div>
        </div>

        <div class="space-y-3 divide-y divide-gray-100 text-xs">
          <div
            v-for="(item, idx) in form.evaluation_config"
            :key="item.id"
            class="pt-3 first:pt-0 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
          >
            <div v-if="item.is_custom" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1 w-full">
              <div class="w-full sm:w-40 shrink-0">
                <AppSearchableSelect
                  v-model="item.category"
                  :options="evalCategoryOptions"
                  placeholder="Kategori..."
                  search-placeholder="Cari kategori..."
                  input-class="bg-[#f8fafc] border-gray-200 rounded-xl px-2.5 py-1.5 font-bold text-gray-800"
                />
              </div>
              <input
                v-model="item.question"
                type="text"
                placeholder="Tulis pertanyaan evaluasi..."
                class="flex-1 bg-[#f8fafc] focus:bg-white border border-gray-200 rounded-xl px-3 py-1.5 font-semibold text-gray-800 focus:border-[#0067AC] focus:outline-none"
              />
            </div>

            <div v-else class="flex items-center gap-2.5 flex-1">
              <span
                class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase border shrink-0"
                :class="item.category === 'Materi' ? 'bg-blue-50 text-[#0067AC] border-blue-200' : item.category === 'Pengajar' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-purple-50 text-purple-700 border-purple-200'"
              >
                {{ item.category }}
              </span>
              <span class="font-bold text-gray-800">{{ item.question }}</span>
            </div>

            <!-- Radio 1 - 2 - 3 - 4 matching SS2 + Delete -->
            <div class="flex items-center gap-2 shrink-0">
              <div class="flex items-center gap-4 bg-gray-50 px-3.5 py-1.5 rounded-xl border border-gray-200">
                <label
                  v-for="val in [1, 2, 3, 4]"
                  :key="val"
                  class="flex items-center gap-1.5 font-bold text-xs cursor-pointer"
                  :class="item.rating === val ? 'text-[#0067AC]' : 'text-gray-500'"
                >
                  <input
                    type="radio"
                    :name="`eval-${item.id}`"
                    :value="val"
                    v-model="item.rating"
                    class="w-3.5 h-3.5 text-[#0067AC] focus:ring-[#0067AC] cursor-pointer"
                  />
                  <span>{{ val }}</span>
                </label>
              </div>
              <button
                v-if="form.evaluation_config.length > 1"
                type="button"
                @click="removeEvaluationItem(idx)"
                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                title="Hapus pertanyaan evaluasi"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 7: PESERTA DIKLAT (ENROLLMENT)     -->
      <!-- ========================================== -->
      <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-2xs flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold">
              <Users class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-gray-900">Peserta Terdaftar di Batch Kelas Ini</h3>
              <p class="text-[11px] text-gray-500">
                Total terdaftar: <strong>{{ form.enrolled_participants.length }}</strong> dari kuota <strong>{{ form.max_quota }}</strong> peserta
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              @click="showPegawaiModal = true"
              class="px-4 py-2 bg-[#0067AC] hover:bg-[#00528c] text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
              <Users class="w-3.5 h-3.5" />
              <span>+ Pilih dari Master Pegawai</span>
            </button>
            <button
              v-if="availableNominations.length > 0"
              type="button"
              @click="selectAllNominations"
              class="px-3 py-2 bg-blue-50 text-[#0067AC] rounded-xl text-xs font-bold hover:bg-blue-100 transition cursor-pointer"
            >
              Pilih Semua Nominasi ({{ availableNominations.length }})
            </button>
            <button
              v-if="form.enrolled_participants.length > 0"
              type="button"
              @click="clearAllParticipants"
              class="px-3 py-2 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition cursor-pointer"
            >
              Kosongkan
            </button>
          </div>
        </div>

        <!-- Quick Select from Master Pegawai Strip -->
        <div class="bg-blue-50/50 p-3 rounded-2xl border border-blue-100 flex flex-col sm:flex-row items-center gap-2.5 text-xs">
          <span class="text-gray-700 font-bold shrink-0 flex items-center gap-1.5">
            <UserCheck class="w-4 h-4 text-[#0067AC]" />
            <span>Pilih Cepat Pegawai:</span>
          </span>
          <div class="flex-1 w-full">
            <AppSearchableSelect
              v-model="selectedQuickKaryawanId"
              :options="quickKaryawanOptions"
              placeholder="-- Pilih Pegawai dari Master Data Karyawan BWS --"
              search-placeholder="Cari NIK, nama, departemen, atau jabatan..."
              input-class="bg-white border-gray-300 font-medium text-gray-800"
              @change="onQuickKaryawanSelected"
            />
          </div>
          <button
            type="button"
            @click="addQuickKaryawan"
            :disabled="!selectedQuickKaryawanId"
            class="bg-[#0067AC] hover:bg-[#00528c] text-white font-bold rounded-xl py-2 px-4 shadow-xs transition flex items-center justify-center gap-1 cursor-pointer shrink-0 disabled:opacity-40"
          >
            <Plus class="w-4 h-4" />
            <span>Tambahkan</span>
          </button>
        </div>

        <!-- Participant List Table -->
        <div class="overflow-x-auto rounded-2xl border border-gray-100">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gray-50 text-[11px] font-extrabold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                <th class="py-3 px-3 w-12 text-center">No</th>
                <th class="py-3 px-4 w-32">NIK</th>
                <th class="py-3 px-4">Nama Peserta</th>
                <th class="py-3 px-4">Divisi / Unit Kerja</th>
                <th class="py-3 px-4 w-40">Jabatan</th>
                <th class="py-3 px-3 w-16 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="form.enrolled_participants.length === 0">
                <td colspan="6" class="py-8 text-center text-gray-400 font-semibold">
                  Belum ada peserta terdaftar. Tambahkan manual atau klik "Pilih Semua Nominasi".
                </td>
              </tr>
              <tr
                v-for="(p, idx) in form.enrolled_participants"
                :key="idx"
                class="hover:bg-blue-50/20 transition"
              >
                <td class="py-2.5 px-3 text-center font-bold text-gray-500">{{ idx + 1 }}</td>
                <td class="py-2.5 px-4 font-mono font-bold text-gray-700">{{ p.nik || '-' }}</td>
                <td class="py-2.5 px-4 font-extrabold text-gray-900">{{ p.name }}</td>
                <td class="py-2.5 px-4 font-medium text-gray-700">{{ p.department || '-' }}</td>
                <td class="py-2.5 px-4 text-gray-600">{{ p.position || 'Staff' }}</td>
                <td class="py-2.5 px-3 text-center">
                  <button
                    type="button"
                    @click="removeParticipant(idx)"
                    class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg transition cursor-pointer"
                    title="Hapus Peserta"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- BOTTOM ACTIONS: SUBMIT & CANCEL            -->
      <!-- ========================================== -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
        <button
          type="button"
          @click="emit('back')"
          class="px-6 py-3 rounded-2xl text-xs font-bold text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 transition cursor-pointer"
        >
          Cancel (Batal)
        </button>
        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-8 py-3 rounded-2xl text-xs font-bold bg-[#0067AC] hover:bg-[#00558F] text-white shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <Save class="w-4 h-4" />
          <span>{{ isSubmitting ? 'Menyimpan...' : (isEditMode ? 'SUBMIT (Simpan Perubahan Kelas)' : 'SUBMIT (Buka Batch Kelas Baru)') }}</span>
        </button>
      </div>

    </form>

    <!-- Modal Pilih dari Master Pegawai -->
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
              <h3 class="font-extrabold text-base text-gray-900">Pilih Peserta dari Master Pegawai</h3>
              <p class="text-xs text-gray-500">
                Pilih pegawai aktif Bank Woori Saudara untuk didaftarkan ke batch kelas ini
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
              placeholder="Cari NIK, Nama Pegawai, Jabatan, atau Cabang..."
              class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0067AC]"
            />
            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
          </div>

          <div class="w-full">
            <AppSearchableSelect
              v-model="filterPegawaiDept"
              :options="deptFilterOptions"
              placeholder="-- Seluruh Unit Kerja --"
              search-placeholder="Cari unit kerja..."
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
                    :checked="filteredMasterPegawai.length > 0 && filteredMasterPegawai.filter(k => !isPegawaiEnrolled(k)).every(k => selectedPegawaiIds.includes(k.karyawan_id))"
                    class="rounded text-[#0067AC] cursor-pointer"
                  />
                </th>
                <th class="py-3 px-3">NIK</th>
                <th class="py-3 px-3">Nama Pegawai</th>
                <th class="py-3 px-3">Departemen & Jabatan</th>
                <th class="py-3 px-3">Kantor Cabang</th>
                <th class="py-3 px-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="filteredMasterPegawai.length === 0">
                <td colspan="6" class="py-8 text-center text-gray-400">
                  Tidak ada data pegawai yang sesuai dengan pencarian.
                </td>
              </tr>
              <tr
                v-for="k in filteredMasterPegawai"
                :key="k.karyawan_id"
                @click="toggleSelectPegawai(k)"
                class="hover:bg-blue-50/30 transition cursor-pointer"
                :class="isPegawaiEnrolled(k) ? 'bg-gray-50/70 opacity-60 cursor-not-allowed' : selectedPegawaiIds.includes(k.karyawan_id) ? 'bg-blue-50/50' : ''"
              >
                <td class="py-2.5 px-3 text-center" @click.stop>
                  <input
                    v-if="!isPegawaiEnrolled(k)"
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
                <td class="py-2.5 px-3 text-gray-600">{{ k.kantor_cabang || 'Kantor Pusat Sudirman' }}</td>
                <td class="py-2.5 px-3 text-center">
                  <span
                    v-if="isPegawaiEnrolled(k)"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200"
                  >
                    Terdaftar
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100"
                  >
                    {{ k.st_kerja || 'Aktif' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
          <p class="text-xs text-gray-500">
            Terpilih: <strong class="text-[#0067AC]">{{ selectedPegawaiIds.length }} Pegawai</strong>
          </p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="showPegawaiModal = false; selectedPegawaiIds = []"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              @click="addSelectedPegawaiToClass"
              :disabled="selectedPegawaiIds.length === 0"
              class="px-5 py-2 rounded-xl bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-bold transition cursor-pointer disabled:opacity-40"
            >
              Tambahkan ke Kelas ({{ selectedPegawaiIds.length }})
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>
