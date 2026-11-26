<script setup>
import { reactive, ref, computed } from 'vue'
import { X, Receipt, Scale, Paperclip } from 'lucide-vue-next'
import apiClient from '@/api/client'
import { useI18n } from '@/i18n'

const { l, t } = useI18n()

const props = defineProps({
  show: Boolean,
  classes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'submitted'])
const loading = ref(false)
const errorMessage = ref('')

const classOptions = computed(() => [
  { value: null, label: l('-- General L&D Operational Expense --', '-- Biaya Operasional Umum L&D --') },
  ...props.classes.map(c => ({
    value: c.id,
    label: `${c.batch_name} - ${c.program?.title || ''}`,
    subLabel: c.class_code ? `${l('Code:', 'Kode:')} ${c.class_code}` : ''
  }))
])

const voucherCategoryOptions = computed(() => [
  { value: 'Certification Fee', label: l('Official Certification Exam', 'Ujian Sertifikasi Resmi') },
  { value: 'Vendor Fee', label: l('Institution / Vendor Fee', 'Biaya Lembaga / Vendor') },
  { value: 'Facilitator Honorarium', label: l('Instructor Honorarium', 'Honorarium Instruktur') },
  { value: 'Venue & Catering', label: l('Venue & Catering', 'Akomodasi & Konsumsi') },
  { value: 'Learning Materials', label: l('Learning Modules & Materials', 'Materi & Modul Ajar') }
])

const form = reactive({
  training_class_id: null,
  amount: 42000000,
  category: 'Certification Fee',
  description: 'Risk Management Certification Exam SMR Level 2 Reimbursement (12 Participants)',
  receipt_attachment_url: 'Kwitansi_Resmi_LSPP_BWS2026.pdf'
})

async function submit() {
  loading.value = true
  errorMessage.value = ''
  try {
    await apiClient.post('/transactions', form)
    emit('submitted', l('Disbursement voucher has been submitted to Checker.', 'Voucher pencairan berhasil dikirim ke Verifikator.'))
    emit('close')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || l('Failed to submit disbursement voucher.', 'Gagal mengajukan voucher pencairan.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-5 z-10 animate-scaleIn">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-full bg-emerald-50 text-[#0067AC] flex items-center justify-center font-bold">
            <Receipt class="w-4 h-4" />
          </div>
          <div>
            <h3 class="font-bold text-base text-gray-900">{{ $l('Fund Claim / Disbursement Voucher Form', 'Formulir Pengajuan Klaim / Voucher Dana') }}</h3>
            <p class="text-xs text-gray-400">{{ $l('Training Fund Disbursement & Dual-Control Checker Verification', 'Pencairan Dana Diklat & Verifikasi Kontrol Ganda') }}</p>
          </div>
        </div>
        <button @click="emit('close')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition">
          <X class="w-4 h-4" />
        </button>
      </div>

      <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="submit" class="space-y-4 text-xs">
        <div>
          <label class="block font-semibold text-gray-700 mb-1">{{ $l('Select Related Class', 'Pilih Kelas Terkait') }}</label>
          <AppSearchableSelect
            v-model="form.training_class_id"
            :options="classOptions"
            :placeholder="$l('Select Related Class...', 'Pilih Kelas Terkait...')"
            :search-placeholder="$l('Search batch / program...', 'Cari angkatan / program...')"
            input-class="bg-[#f4f6f5] rounded-xl px-3 py-2 border-transparent"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Cost Category', 'Kategori Biaya') }}</label>
            <AppSearchableSelect
              v-model="form.category"
              :options="voucherCategoryOptions"
              :placeholder="$l('Select Cost Category...', 'Pilih Kategori Biaya...')"
              :search-placeholder="$l('Search category...', 'Cari kategori...')"
              input-class="bg-[#f4f6f5] rounded-xl px-3 py-2 border-transparent"
            />
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">{{ $l('Amount (IDR)', 'Nominal Biaya (IDR)') }}</label>
            <AppNumberInput v-model="form.amount" prefix="Rp" :min="10000" placeholder="10.000" required />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">{{ $l('Transaction Details / Purpose', 'Rincian Transaksi / Keperluan') }}</label>
          <textarea v-model="form.description" rows="2" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required></textarea>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">{{ $l('Receipt Proof File (PDF/JPG)', 'Berkas Bukti Kwitansi (PDF/JPG)') }}</label>
          <div class="flex items-center gap-2">
            <Paperclip class="w-4 h-4 text-[#0067AC]" />
            <input type="text" v-model="form.receipt_attachment_url" class="flex-1 bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" />
          </div>
        </div>

        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-emerald-900 flex items-start gap-2.5">
          <Scale class="w-4 h-4 text-[#0067AC] shrink-0 mt-0.5" />
          <p class="text-[11px] leading-tight">
            <template v-if="$locale === 'en'">
              <strong>Banking Compliance Standard:</strong> Makers are strictly prohibited from approving their own transactions. This voucher will be routed to the Checker queue.
            </template>
            <template v-else>
              <strong>Kepatuhan Standar Perbankan:</strong> Pembuat dilarang menyetujui transaksi miliknya sendiri. Voucher akan diteruskan ke antrean Verifikator.
            </template>
          </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button type="button" @click="emit('close')" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
            {{ $t('common.cancel') }}
          </button>
          <button type="submit" :disabled="loading" class="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-semibold transition shadow-sm disabled:opacity-50">
            {{ loading ? $l('Submitting...', 'Mengirim...') : $l('Submit Voucher to Checker', 'Kirim Voucher ke Verifikator') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
.animate-scaleIn {
  animation: scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
