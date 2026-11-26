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
import { useI18n } from '@/i18n'

const { l } = useI18n()

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

const transactionColumns = computed(() => [
  { key: 'trx_number', label: l('Voucher / Document No.', 'No. Bukti / Dokumen'), sortable: true },
  { key: 'description', label: l('Transaction Details & Purpose', 'Rincian Transaksi & Keperluan'), sortable: true },
  { key: 'category', label: l('Cost Category', 'Kategori Biaya'), sortable: true },
  {
    key: 'amount',
    label: l('Amount (IDR)', 'Nominal Biaya (IDR)'),
    sortable: true,
    align: 'right',
    format: (val) => formatRupiah(val)
  },
  { key: 'maker.name', label: l('Requester (Maker)', 'Pengaju Transaksi'), sortable: true },
  { key: 'status', label: l('Checker Authorization', 'Status Verifikasi'), sortable: true, align: 'center' },
  {
    key: 'created_at',
    label: l('Transaction Date', 'Tgl Transaksi'),
    sortable: true,
    format: (val) => val ? val.substring(0, 10) : '-'
  }
])
</script>

<template>
  <div class="flex flex-col gap-5">
    
    <!-- Top Summary Statistics Cards (Standard BWS Strip) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
          <CheckCircle class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Disbursed Realization', 'Realisasi Terbayar') }}</p>
          <p class="text-xl font-extrabold text-[#0067AC] font-mono">{{ formatRupiah(totalDisbursed) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
          <Scale class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Checker Queue', 'Antrean Verifikasi') }}</p>
          <p class="text-xl font-extrabold text-amber-600 font-mono">{{ formatRupiah(pendingDisbursed) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-[#0067AC] flex items-center justify-center font-bold">
          <Receipt class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Total Vouchers', 'Total Bukti Kas') }}</p>
          <p class="text-xl font-extrabold text-gray-900">{{ financials.length }} {{ $l(financials.length === 1 ? 'Voucher' : 'Vouchers', 'Dokumen') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold">
          <FileCheck class="w-5 h-5" />
        </div>
        <div>
          <p class="text-[11px] text-gray-400 font-medium">{{ $l('Four-Eyes Control', 'Kontrol Ganda') }}</p>
          <p class="text-xl font-extrabold text-purple-700">{{ $l('100% Verified', '100% Sah') }}</p>
        </div>
      </div>
    </div>

    <!-- Financial Transactions Standard Table Component -->
    <DataTable
      :data="financials"
      :columns="transactionColumns"
      date-key="created_at"
      export-filename="bws_training_budget_transactions"
      :default-per-page="10"
    >

      <!-- Custom Cell: Voucher Code -->
      <template #cell-trx_number="{ row }">
        <span class="font-mono text-xs font-bold text-[#0067AC] block">{{ row.trx_number }}</span>
        <span class="text-[10px] text-gray-400">{{ $l('Attachment:', 'Lampiran:') }} {{ row.receipt_attachment_url ? $l('Valid Receipt', 'Kwitansi Sah') : $l('None', 'Belum Ada') }}</span>
      </template>

      <!-- Custom Cell: Description -->
      <template #cell-description="{ row }">
        <p class="font-bold text-xs text-gray-900 leading-tight">{{ row.description }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5">{{ row.training_class?.program?.title || $l('L&D Operational Expense', 'Biaya Operasional L&D') }}</p>
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
          :class="row.status === 'APPROVED' ? 'bg-emerald-50 text-[#0067AC]' : 'bg-amber-50 text-amber-700'"
        >
          <span
            class="w-1.5 h-1.5 rounded-full shrink-0"
            :class="row.status === 'APPROVED' ? 'bg-[#0067AC]' : 'bg-amber-500'"
          ></span>
          {{ row.status === 'APPROVED' ? $l('Approved by Checker', 'Disetujui Verifikator') : $l('Pending Approval', 'Menunggu Persetujuan') }}
        </span>
      </template>

      <!-- Action Column Slot -->
      <template #action="{ row }">
        <button
          v-if="row.status === 'PENDING_APPROVAL' && isCheckerRole"
          @click="emit('approve-voucher', row)"
          class="px-3 py-1.5 rounded-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition shadow-2xs"
          :title="$l('Authorize this fund disbursement', 'Otorisasi pencairan dana ini')"
        >
          {{ $l('Authorize', 'Otorisasi') }}
        </button>
        <span v-else-if="row.status === 'APPROVED'" class="text-[11px] text-[#0067AC] font-semibold">
          {{ $l('Disbursed', 'Terbayar') }}
        </span>
        <button
          v-else
          class="px-3 py-1.5 rounded-full bg-[#f4f6f5] hover:bg-gray-200 text-gray-800 text-xs font-semibold transition"
        >
          {{ $l('Receipt', 'Bukti') }}
        </button>
      </template>
    </DataTable>

  </div>
</template>

