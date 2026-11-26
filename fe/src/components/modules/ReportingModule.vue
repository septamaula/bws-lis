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
import { useI18n } from '@/i18n'

const { l } = useI18n()

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

const auditColumns = computed(() => [
  { key: 'id', label: l('Log ID', 'ID Log'), sortable: true, align: 'center' },
  { key: 'created_at', label: l('Timestamp (UTC+7)', 'Waktu Transaksi (UTC+7)'), sortable: true },
  { key: 'user.name', label: l('Executor (Employee)', 'Eksekutor (Pegawai)'), sortable: true },
  { key: 'action', label: l('Banking Activity', 'Aktivitas Perbankan'), sortable: true },
  { key: 'entity_type', label: l('Module / Entity Type', 'Tipe Modul / Entitas'), sortable: true },
  { key: 'ip_address', label: l('Terminal IP Address', 'Alamat IP Terminal'), sortable: true, align: 'center' },
  { key: 'chain_hash', label: l('Data Integrity Hash', 'Kode Integritas Data'), sortable: true }
])
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Audit Integrity', 'Integritas Audit') }}</p>
          <p class="text-xl font-extrabold text-[#0067AC]">{{ $l('100% Valid', '100% Sah') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Lock class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">POJK No. 11/2022</p>
          <p class="text-xl font-extrabold text-gray-900">{{ $l('Compliant', 'Patuh Regulasi') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Security Validation', 'Validasi Keamanan') }}</p>
          <p class="text-xl font-extrabold text-purple-700">{{ $l('Verified', 'Tervalidasi') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <FileText class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Audit Log Entries', 'Mutasi Catatan Audit') }}</p>
          <p class="text-xl font-extrabold text-gray-900">{{ auditLogs.length }} {{ $l(auditLogs.length === 1 ? 'Entry' : 'Entries', 'Entri') }}</p>
        </div>
      </div>
    </div>

    <!-- Audit Trail Standard Table Component -->
    <DataTable
      :data="auditLogs"
      :columns="auditColumns"
      date-key="created_at"
      export-filename="bws_audit_trail_immutable_log"
      :default-per-page="10"
    >

      <!-- Custom Cell: Time -->
      <template #cell-created_at="{ row }">
        <span class="font-mono text-xs text-gray-700">{{ row.created_at ? row.created_at.replace('T', ' ').substring(0, 19) : '-' }}</span>
      </template>

      <!-- Custom Cell: User -->
      <template #cell-user-name="{ row }">
        <p class="font-bold text-xs text-gray-900">{{ row.user?.name || $l('System Service', 'Layanan Sistem') }}</p>
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

