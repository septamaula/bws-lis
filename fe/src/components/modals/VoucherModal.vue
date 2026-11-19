<script setup>
import { reactive, ref } from 'vue'
import { X, Receipt, Scale, Paperclip } from 'lucide-vue-next'
import apiClient from '@/api/client'

defineProps({
  show: Boolean,
  classes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'submitted'])
const loading = ref(false)
const errorMessage = ref('')

const form = reactive({
  training_class_id: null,
  amount: 42000000,
  category: 'Certification Fee',
  description: 'Reimburse Ujian Sertifikasi Manajemen Risiko SMR Level 2 (12 Peserta)',
  receipt_attachment_url: 'Kwitansi_Resmi_LSPP_BWS2026.pdf'
})

async function submit() {
  loading.value = true
  errorMessage.value = ''
  try {
    const { data } = await apiClient.post('/transactions', form)
    emit('submitted', data.message)
    emit('close')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal mengajukan voucher pencairan.'
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
            <h3 class="font-bold text-base text-gray-900">Form Pengajuan Klaim / Voucher Dana</h3>
            <p class="text-xs text-gray-400">Pencairan Dana Diklat & Verifikasi Dual-Control Checker</p>
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
          <label class="block font-semibold text-gray-700 mb-1">Pilih Kelas Terkait</label>
          <select v-model="form.training_class_id" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none">
            <option :value="null">-- Biaya Operasional Umum L&D --</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.batch_name }} - {{ c.program?.title }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Kategori Biaya</label>
            <select v-model="form.category" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required>
              <option value="Certification Fee">Ujian Sertifikasi Resmi</option>
              <option value="Vendor Fee">Biaya Lembaga / Vendor</option>
              <option value="Facilitator Honorarium">Honorarium Instruktur</option>
              <option value="Venue & Catering">Akomodasi & Konsumsi</option>
              <option value="Learning Materials">Materi & Modul Ajar</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-gray-700 mb-1">Nominal Biaya (IDR)</label>
            <input type="number" v-model.number="form.amount" min="10000" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">Rincian Transaksi / Keperluan</label>
          <textarea v-model="form.description" rows="2" class="w-full bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" required></textarea>
        </div>

        <div>
          <label class="block font-semibold text-gray-700 mb-1">Berkas Bukti Kwitansi (PDF/JPG)</label>
          <div class="flex items-center gap-2">
            <Paperclip class="w-4 h-4 text-[#0067AC]" />
            <input type="text" v-model="form.receipt_attachment_url" class="flex-1 bg-[#f4f6f5] rounded-xl px-3 py-2 border border-transparent focus:border-[#0067AC] focus:outline-none" />
          </div>
        </div>

        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-emerald-900 flex items-start gap-2.5">
          <Scale class="w-4 h-4 text-[#0067AC] shrink-0 mt-0.5" />
          <p class="text-[11px] leading-tight">
            <strong>Kepatuhan Standar Perbankan:</strong> Maker dilarang menyetujui transaksi miliknya sendiri. Voucher akan diteruskan ke antrean Checker.
          </p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button type="button" @click="emit('close')" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
            Batal
          </button>
          <button type="submit" :disabled="loading" class="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-semibold transition shadow-sm disabled:opacity-50">
            {{ loading ? 'Mengirim...' : 'Kirim Voucher ke Checker' }}
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
