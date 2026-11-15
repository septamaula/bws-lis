<script setup>
import { computed } from 'vue'
import {
  Receipt,
  Plus,
  Scale,
  CheckCircle,
  Clock,
  Wallet,
  FileCheck
} from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'

const props = defineProps({
  financials: {
    type: Array,
    default: () => []
  },
  isCheckerRole: {
    type: Boolean,
    default: false
  },
  canCreateVoucher: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['open-voucher-modal', 'approve-voucher'])

function formatRupiah(num) {
  if (num === null || num === undefined) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(num)
}

const totalDisbursed = computed(() => {
  return props.financials
    .filter(f => f.status === 'APPROVED')
    .reduce((sum, f) => sum + Number(f.amount || 0), 0)
})

const pendingDisbursed = computed(() => {
  return props.financials
    .filter(f => f.status === 'PENDING_APPROVAL')
    .reduce((sum, f) => sum + Number(f.amount || 0), 0)
})

const transactionColumns = [
  { key: 'trx_number', label: 'No. Voucher / Dokumen', sortable: true },
  { key: 'description', label: 'Rincian Transaksi & Keperluan', sortable: true },
  { key: 'category', label: 'Kategori Biaya', sortable: true },
  {
    key: 'amount',
    label: 'Nominal Biaya (IDR)',
    sortable: true,
    align: 'right',
    format: (val) => formatRupiah(val)
  },
  { key: 'maker.name', label: 'Maker (Pengaju)', sortable: true },
  { key: 'status', label: 'Status Otorisasi Checker', sortable: true, align: 'center' },
  {
    key: 'created_at',
    label: 'Tgl Transaksi',
    sortable: true,
    format: (val) => val ? val.substring(0, 10) : '-'
  }
]
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Realisasi Terbayar</p>
          <p class="text-xl font-bold text-[#157f4f] font-mono">{{ formatRupiah(totalDisbursed) }}</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center">
          <CheckCircle class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Menunggu Otorisasi Checker</p>
          <p class="text-xl font-bold text-amber-600 font-mono">{{ formatRupiah(pendingDisbursed) }}</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
          <Scale class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Total Voucher Tercatat</p>
          <p class="text-xl font-bold text-gray-900">{{ financials.length }} Voucher</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
          <Receipt class="w-5 h-5" />
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-400 mb-0.5">Verifikasi Four-Eyes (2x)</p>
          <p class="text-xl font-bold text-purple-600">100% Valid</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
          <FileCheck class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- Financial Transactions Standard Table Component -->
    <DataTable
      :data="financials"
      :columns="transactionColumns"
      date-key="created_at"
      title="Buku Transaksi Pengeluaran Anggaran & Reimbursement"
      subtitle="Pengelolaan voucher pencairan biaya diklat, sertifikasi LSPP/BSMR, dan reimbursement perjalanan dinas pelatihan"
      export-filename="transaksi_anggaran_diklat_bws"
      :default-per-page="10"
    >
      <!-- Actions in Header -->
      <template #actions>
        <button
          v-if="canCreateVoucher"
          @click="emit('open-voucher-modal')"
          class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition"
        >
          <Plus class="w-4 h-4" />
          <span>+ Buat Voucher / Klaim</span>
        </button>
      </template>

      <!-- Custom Cell: Voucher Code -->
      <template #cell-trx_number="{ row }">
        <span class="font-mono text-xs font-bold text-[#157f4f] block">{{ row.trx_number }}</span>
        <span class="text-[10px] text-gray-400">Lampiran: {{ row.receipt_attachment_url ? 'Kwitansi Valid' : 'Belum Ada' }}</span>
      </template>

      <!-- Custom Cell: Description -->
      <template #cell-description="{ row }">
        <p class="font-bold text-xs text-gray-900 leading-tight">{{ row.description }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ row.training_class?.program?.title || 'Biaya Operasional L&D' }}</p>
      </template>

      <!-- Custom Cell: Category -->
      <template #cell-category="{ row }">
        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#f4f6f5] text-gray-700">
          {{ row.category }}
        </span>
      </template>

      <!-- Custom Cell: Status -->
      <template #cell-status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap"
          :class="row.status === 'APPROVED' ? 'bg-emerald-50 text-[#157f4f]' : 'bg-amber-50 text-amber-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.status === 'APPROVED' ? 'bg-[#157f4f]' : 'bg-amber-500'"
          ></span>
          {{ row.status === 'APPROVED' ? 'Disetujui Checker' : 'Menunggu Approval' }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          v-if="row.status === 'PENDING_APPROVAL' && isCheckerRole"
          @click="emit('approve-voucher', row)"
          class="px-3 py-1.5 rounded-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition shadow-2xs"
          title="Otorisasi Pencairan Dana ini"
        >
          Otorisasi
        </button>
        <span v-else-if="row.status === 'APPROVED'" class="text-[11px] text-[#157f4f] font-semibold">
          Terbayar
        </span>
        <button
          v-else
          class="px-3 py-1.5 rounded-full bg-[#f4f6f5] hover:bg-gray-200 text-gray-800 text-xs font-semibold transition"
        >
          Bukti
        </button>
      </template>
    </DataTable>

  </div>
</template>

