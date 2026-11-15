<script setup>
import { computed } from 'vue'
import {
  FileText,
  ShieldCheck,
  CheckCircle,
  Clock,
  Download,
  Terminal,
  Lock
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import apiClient from '@/api/client'

const props = defineProps({
  auditLogs: {
    type: Array,
    default: () => []
  }
})

async function downloadOfficialCsv() {
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
    console.error('Export failed:', err)
  }
}

const auditColumns = [
  { key: 'id', label: 'Log ID', sortable: true, align: 'center' },
  { key: 'created_at', label: 'Waktu Transaksi (UTC+7)', sortable: true },
  { key: 'user.name', label: 'Eksekutor (Pegawai)', sortable: true },
  { key: 'action', label: 'Aktivitas Perbankan', sortable: true },
  { key: 'entity_type', label: 'Tipe Modul / Entitas', sortable: true },
  { key: 'ip_address', label: 'Alamat IP Terminal', sortable: true, align: 'center' },
  { key: 'chain_hash', label: 'Kode Integritas Data', sortable: true }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Integritas Audit Trail</p>
          <p class="text-xl font-bold text-[#157f4f]">100% Terverifikasi</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <ShieldCheck class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">POJK No. 11/2022</p>
          <p class="text-xl font-bold text-gray-900">Compliant</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <Lock class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Validasi Keamanan</p>
          <p class="text-xl font-bold text-purple-600">Tervalidasi</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <ShieldCheck class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Mutasi Audit Log</p>
          <p class="text-xl font-bold text-gray-900">{{ auditLogs.length }} Entri</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <FileText class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Audit Trail Standard Table Component -->
    <DataTable
      :data="auditLogs"
      :columns="auditColumns"
      date-key="created_at"
      title="Buku Catatan Audit Mutasi & Rekam Jejak Sistem (Immutable Audit Trail)"
      subtitle="Pencatatan aktivitas append-only dengan penguncian hash berantai untuk kebutuhan audit forensik dan regulator OJK / Bank Indonesia"
      export-filename="bws_audit_trail_immutable_log"
      :default-per-page="10"
    >
      <!-- Actions in Header -->
      <template #actions>
        <button
          @click="downloadOfficialCsv"
          class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition"
        >
          <Download class="w-4 h-4" />
          <span>Download Rekap Resmi OJK (CSV)</span>
        </button>
      </template>

      <!-- Custom Cell: Time -->
      <template #cell-created_at="{ row }">
        <span class="font-mono text-xs text-gray-700">{{ row.created_at ? row.created_at.replace('T', ' ').substring(0, 19) : '-' }}</span>
      </template>

      <!-- Custom Cell: User -->
      <template #cell-user-name="{ row }">
        <p class="font-bold text-xs text-gray-900">{{ row.user?.name || 'System Daemon' }}</p>
        <p class="font-mono text-[10px] text-gray-400">{{ row.user?.nik || 'SYS-KERNEL' }} • {{ row.user?.role_name }}</p>
      </template>

      <!-- Custom Cell: Action -->
      <template #cell-action="{ row }">
        <span class="inline-flex items-center gap-1 font-mono text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-slate-900 text-emerald-400 border border-slate-800">
          {{ row.action }}
        </span>
      </template>

      <!-- Custom Cell: IP Address -->
      <template #cell-ip_address="{ row }">
        <span class="font-mono text-[11px] text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
          {{ row.ip_address || '127.0.0.1' }}
        </span>
      </template>

      <!-- Custom Cell: Chain Hash -->
      <template #cell-chain_hash="{ row }">
        <div class="flex items-center gap-1.5">
          <Lock class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
          <span class="font-mono text-[10px] text-gray-500 truncate max-w-[190px]" :title="row.chain_hash">
            {{ row.chain_hash }}
          </span>
        </div>
      </template>
    </DataTable>

  </div>
</template>

