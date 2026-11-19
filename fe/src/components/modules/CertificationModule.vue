<script setup>
import { computed } from 'vue'
import {
  Award,
  ShieldCheck,
  AlertTriangle,
  CheckCircle,
  FileText,
  Clock,
  Printer
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  enrollments: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['view-certificate'])

// Generate certification database from passed participants
const certificationData = computed(() => {
  return props.enrollments.filter(e => e.completion_status === 'PASSED').map((e, index) => {
    const certTypes = [
      'Sertifikasi Manajemen Risiko (SMR) Level 2',
      'Sertifikasi General Banking (GBS) Level 1',
      'Anti-Money Laundering & Counter-Terrorism (APU-PPT)',
      'Sertifikasi Kepatuhan Perbankan Syariah / Konvensional',
      'Sertifikasi Analis Kredit Komersial (LSPP)'
    ]
    const certName = certTypes[index % certTypes.length]
    const certNumber = e.certificate_id || `BWS-CERT-2026-${String(index + 1).padStart(4, '0')}`
    const issuedDate = '2026-04-15'
    const expiryDate = '2029-04-15'

    return {
      id: e.id,
      certNumber: certNumber,
      employeeName: e.user?.name || `Pegawai ${index + 1}`,
      nik: e.user?.nik || `NIK-${1000 + index}`,
      department: e.user?.department || 'Kantor Cabang Utama',
      certificationName: certName,
      issuedDate: issuedDate,
      expiryDate: expiryDate,
      status: 'ACTIVE',
      qrHash: `SHA256-${e.id}BWS99`
    }
  })
})

const activeCount = computed(() => certificationData.value.length)

const certColumns = [
  { key: 'certNumber', label: 'Nomor Sertifikat / Lisensi', sortable: true },
  { key: 'certificationName', label: 'Bidang Sertifikasi Profesi', sortable: true },
  { key: 'employeeName', label: 'Nama Pemegang Lisensi', sortable: true },
  { key: 'department', label: 'Unit Kerja / Cabang', sortable: true },
  { key: 'issuedDate', label: 'Tanggal Terbit', sortable: true, format: (val) => val ? val.substring(0, 10) : '-' },
  { key: 'expiryDate', label: 'Masa Berlaku s/d', sortable: true, format: (val) => val ? val.substring(0, 10) : '-' },
  { key: 'status', label: 'Status Lisensi', sortable: true, align: 'center' }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Award class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Lisensi Aktif</p>
          <p class="text-xl font-extrabold text-gray-900">{{ activeCount }} Lisensi</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Kepatuhan OJK SMR</p>
          <p class="text-xl font-extrabold text-[#0067AC]">98.2% Sesuai</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <AlertTriangle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">Kadaluarsa &lt; 90 Hari</p>
          <p class="text-xl font-extrabold text-amber-600">3 Lisensi</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <FileText class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">QR Verifikasi</p>
          <p class="text-xl font-extrabold text-purple-700">Anti-Fraud</p>
        </div>
      </div>
    </div>

    <!-- Certifications Standard Table Component -->
    <DataTable
      :data="certificationData"
      :columns="certColumns"
      date-key="issuedDate"
      export-filename="database_sertifikasi_pegawai_bws"
      :default-per-page="10"
    >
      <!-- Custom Cell: Certificate Number -->
      <template #cell-certNumber="{ row }">
        <span class="font-mono text-xs font-bold text-[#0067AC] block">{{ row.certNumber }}</span>
        <span class="text-[10px] text-gray-400 font-mono">{{ row.qrHash }}</span>
      </template>

      <!-- Custom Cell: Certification Name -->
      <template #cell-certificationName="{ row }">
        <p class="font-bold text-xs text-gray-900 leading-tight">{{ row.certificationName }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">Lembaga Sertifikasi Profesi Perbankan</p>
      </template>

      <!-- Custom Cell: Employee Name -->
      <template #cell-employeeName="{ row }">
        <p class="font-semibold text-xs text-gray-800">{{ row.employeeName }}</p>
        <p class="font-mono text-[10px] text-gray-400">{{ row.nik }}</p>
      </template>

      <!-- Custom Cell: Status -->
      <template #cell-status="{ row }">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-[#0067AC] whitespace-nowrap">
          <span class="w-1.5 h-1.5 rounded-full bg-[#0067AC] shrink-0"></span>
          Aktif Valid
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          @click="emit('view-certificate', row)"
          class="flex items-center gap-1 px-3 py-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-[#0067AC] text-xs font-semibold transition"
          title="Buka Lembar E-Sertifikat Digital"
        >
          <Printer class="w-3 h-3" />
          <span>Cetak</span>
        </button>
      </template>
    </DataTable>

  </div>
</template>

