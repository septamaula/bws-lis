<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  BookOpen,
  Plus,
  Layers,
  GraduationCap,
  Award,
  Video,
  MapPin,
  CheckCircle2,
  Wallet,
  Clock,
  Compass,
  FileCheck2,
  ShieldCheck,
  ChevronRight,
  Eye,
  RotateCcw
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import apiClient from '@/api/client'
import ProgramCreatePage from './programs/ProgramCreatePage.vue'
import ProgramDetailPage from './programs/ProgramDetailPage.vue'

const props = defineProps({
  trainingPrograms: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['open-class-create'])

// Sub-View Modes: 'list' | 'create' | 'detail' | 'edit'
const programViewMode = ref('list')
const selectedProgramId = ref(null)

const loading = ref(false)
const programs = ref([])
const stats = ref({
  total_programs: 0,
  total_budget: 0,
  total_participants: 0,
  mandatory_count: 0,
  approved_count: 0
})

const flashSuccess = ref('')

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

async function fetchPrograms() {
  loading.value = true
  try {
    const { data } = await apiClient.get('/programs')
    programs.value = data.programs || []
    stats.value = data.stats || stats.value
  } catch (err) {
    console.error('Failed to fetch programs:', err)
  } finally {
    loading.value = false
  }
}

function openCreate() {
  selectedProgramId.value = null
  programViewMode.value = 'create'
}

function openDetail(id) {
  selectedProgramId.value = id
  programViewMode.value = 'detail'
}

function openEdit(id) {
  selectedProgramId.value = id
  programViewMode.value = 'edit'
}

function handleProgramCreated(msg) {
  flashSuccess.value = msg
  programViewMode.value = 'list'
  fetchPrograms()
  setTimeout(() => {
    flashSuccess.value = ''
  }, 6000)
}

const programColumns = [
  { key: 'program_code', label: 'Kode & Kategori', sortable: true, width: '160px' },
  { key: 'title', label: 'Nama Program & Kaitan TNA', sortable: true },
  { key: 'method', label: 'Metode & Kuartal', sortable: true, align: 'center', width: '140px' },
  {
    key: 'target_participants_count',
    label: 'Target Peserta',
    sortable: true,
    align: 'center',
    width: '120px',
    format: (val) => `${val || 0} Pegawai`
  },
  {
    key: 'total_budget',
    label: 'Pagu RAB (Rp)',
    sortable: true,
    align: 'right',
    width: '160px',
    format: (val) => formatRupiah(val)
  },
  { key: 'approval_type', label: 'Pengesahan', sortable: true, align: 'center', width: '130px' },
  { key: 'status', label: 'Status Otorisasi', sortable: true, align: 'center', width: '160px' },
]

onMounted(() => {
  fetchPrograms()
})

defineExpose({
  openCreate,
  openDetail,
  fetchPrograms
})
</script>

<template>
  <div>

    <!-- Sub-View: Create Form / Edit Form -->
    <ProgramCreatePage
      v-if="programViewMode === 'create' || programViewMode === 'edit'"
      :edit-program-id="programViewMode === 'edit' ? selectedProgramId : null"
      @back="programViewMode = (programViewMode === 'edit' ? 'detail' : 'list')"
      @created="handleProgramCreated"
    />

    <!-- Sub-View: Detail Page -->
    <ProgramDetailPage
      v-else-if="programViewMode === 'detail' && selectedProgramId"
      :program-id="selectedProgramId"
      @back="programViewMode = 'list'"
      @refresh-list="fetchPrograms"
      @edit="openEdit"
      @open-class-create="(progId) => emit('open-class-create', progId)"
    />

    <!-- Sub-View: Main List View -->
    <div v-else class="flex flex-col gap-5">
      
      <!-- Flash Alert -->
      <div v-if="flashSuccess" class="p-4 rounded-2xl bg-[#EBF4FC] border border-blue-200 text-[#0067AC] text-xs font-semibold flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 shrink-0" />
          <span>{{ flashSuccess }}</span>
        </div>
        <button @click="flashSuccess = ''" class="font-bold cursor-pointer">&times;</button>
      </div>

      <!-- Action Strip & Header -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white p-5 rounded-3xl border border-gray-100 shadow-2xs">
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#EBF4FC] text-[#0067AC] uppercase tracking-wider">
              Work Plan &amp; Program Kerja 2026
            </span>
            <span class="text-xs text-gray-400">• BWS Learning Integrated System</span>
          </div>
          <h2 class="text-lg font-extrabold text-[#002855] mt-1">
            Program Pelatihan &amp; Rencana Anggaran Biaya (RAB)
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Daftar program kerja pelatihan yang diturunkan dari TNA tahunan dan disahkan via Dokumen SK/Memo atau Multi-Approval.
          </p>
        </div>

        <button
          @click="openCreate"
          class="bg-[#0067AC] hover:bg-[#00528c] text-white px-5 py-2.5 rounded-full text-xs font-bold flex items-center gap-2 transition shadow-sm cursor-pointer shrink-0"
        >
          <Plus class="w-4 h-4" />
          <span>+ Buat Program Baru</span>
        </button>
      </div>

      <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
            <BookOpen class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Program Terdaftar</p>
            <p class="text-xl font-extrabold text-gray-900">{{ programs.length }} Program</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
            <Wallet class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Total Anggaran RAB</p>
            <p class="text-base sm:text-lg font-extrabold text-gray-900 font-mono">{{ formatRupiah(stats.total_budget) }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
            <GraduationCap class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Target Peserta</p>
            <p class="text-xl font-extrabold text-[#0067AC]">{{ stats.total_participants }} Pegawai</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
            <Award class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[11px] text-gray-400 font-medium">Wajib OJK / Disahkan</p>
            <p class="text-xl font-extrabold text-gray-900">{{ stats.approved_count }} Disahkan</p>
          </div>
        </div>
      </div>

      <!-- Training Programs DataTable Component -->
      <DataTable
        :data="programs"
        :columns="programColumns"
        date-key="created_at"
        export-filename="program_kerja_ld_bws_2026"
        :default-per-page="10"
      >

        <!-- Custom Cell: Code & Category -->
        <template #cell-program_code="{ row }">
          <span class="font-mono text-xs font-bold text-[#0067AC] block">{{ row.program_code }}</span>
          <span class="text-[10px] text-gray-400 font-semibold block truncate max-w-[140px]">{{ row.category }}</span>
        </template>

        <!-- Custom Cell: Title & TNA Link -->
        <template #cell-title="{ row }">
          <div class="space-y-1">
            <p class="font-extrabold text-xs text-gray-900 leading-snug">{{ row.title }}</p>
            
            <div class="flex items-center gap-2 flex-wrap">
              <span
                v-if="row.tna_proposal"
                class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md bg-[#EBF4FC] text-[#0067AC] border border-blue-200"
              >
                <Compass class="w-2.5 h-2.5" />
                <span>{{ row.tna_proposal.proposal_code }}</span>
              </span>
              <span v-else class="text-[10px] text-gray-400 italic">Program Insidental</span>

              <span
                v-if="row.is_mandatory"
                class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 uppercase"
              >
                Wajib OJK/BI
              </span>
            </div>

            <p v-if="row.description" class="text-[11px] text-gray-400 line-clamp-1">
              {{ row.description }}
            </p>
          </div>
        </template>

        <!-- Custom Cell: Method & Quarter -->
        <template #cell-method="{ row }">
          <span class="font-bold text-[#002855] text-xs block">{{ row.method || 'Hybrid' }}</span>
          <span class="text-[10px] text-gray-400 font-medium block">Kuartal {{ row.execution_quarter || 'Q1' }}</span>
        </template>

        <!-- Custom Cell: Approval Type -->
        <template #cell-approval_type="{ row }">
          <span
            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
            :class="row.approval_type === 'DOCUMENT' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-blue-50 text-[#0067AC] border border-blue-200'"
          >
            <FileCheck2 v-if="row.approval_type === 'DOCUMENT'" class="w-3 h-3 text-emerald-600" />
            <ShieldCheck v-else class="w-3 h-3 text-[#0067AC]" />
            <span>{{ row.approval_type === 'DOCUMENT' ? 'Dokumen SK' : 'Multi-Approval' }}</span>
          </span>
        </template>

        <!-- Custom Cell: Status -->
        <template #cell-status="{ row }">
          <span
            class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full border"
            :class="[
              row.status === 'APPROVED' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
              row.status === 'REJECTED' ? 'bg-rose-50 text-rose-800 border-rose-200' :
              row.status === 'REVISED' ? 'bg-amber-50 text-amber-800 border-amber-200' :
              'bg-[#EBF4FC] text-[#0067AC] border-blue-200'
            ]"
          >
            <span
              class="w-1.5 h-1.5 rounded-full"
              :class="row.status === 'APPROVED' ? 'bg-emerald-500' : row.status === 'REJECTED' ? 'bg-rose-500' : 'bg-[#0067AC]'"
            ></span>
            <span>{{ row.approval_status_badge || row.status }}</span>
          </span>
        </template>

        <!-- Action Column Slot -->
        <template #action="{ row }">
          <button
            @click="openDetail(row.id)"
            class="px-3.5 py-1.5 rounded-full bg-[#EBF4FC] hover:bg-[#d5e9f8] text-[#0067AC] text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs border border-blue-200"
            title="Lihat rincian program kerja, RAB, peserta, dan approval"
          >
            <Eye class="w-3.5 h-3.5" />
            <span>Rincian</span>
          </button>
        </template>
      </DataTable>

    </div>

  </div>
</template>
