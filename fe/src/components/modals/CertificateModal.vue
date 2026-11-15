<script setup>
import { X, Award, Printer, ShieldCheck, QrCode } from 'lucide-vue-next'

const props = defineProps({
  show: Boolean,
  certificateData: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close'])

function printCertificate() {
  window.print()
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

    <div class="relative bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-5 z-10 animate-scaleIn">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-full bg-emerald-50 text-[#157f4f] flex items-center justify-center font-bold">
            <Award class="w-4 h-4" />
          </div>
          <div>
            <h3 class="font-bold text-base text-gray-900">E-Sertifikat Kelulusan Resmi BWS</h3>
            <p class="text-xs text-gray-400">Verifikasi Kredensial Digital & Kepatuhan Sertifikasi Perbankan</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="printCertificate"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>Cetak PDF</span>
          </button>
          <button @click="emit('close')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition">
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Certificate Document Canvas -->
      <div class="border-4 border-double border-[#157f4f]/30 rounded-2xl p-8 bg-gradient-to-b from-[#fbfdfc] to-white relative overflow-hidden text-center shadow-xs">
        
        <!-- Watermark / BG Stamp -->
        <div class="absolute -right-10 -bottom-10 opacity-5 pointer-events-none">
          <Award class="w-72 h-72 text-[#157f4f]" />
        </div>

        <div class="space-y-4 relative z-10">
          <div class="flex items-center justify-center gap-2">
            <div class="w-8 h-8 rounded-full bg-[#157f4f] text-white flex items-center justify-center font-black text-sm">W</div>
            <span class="font-extrabold text-sm tracking-wider uppercase text-gray-900">PT BANK WOORI SAUDARA INDONESIA 1906, TBK</span>
          </div>

          <h2 class="text-2xl font-serif font-black tracking-wide text-gray-900 uppercase">SERTIFIKAT KELULUSAN</h2>
          <p class="text-[11px] font-mono text-gray-400 tracking-wider">
            NO: {{ certificateData.certNumber || certificateData.certificate_number || 'BWS-CERT-2026-0899' }}
          </p>

          <p class="text-xs text-gray-500 pt-2">Diberikan secara resmi kepada:</p>
          <h3 class="text-xl font-bold text-[#157f4f] tracking-tight border-b-2 border-[#157f4f]/20 pb-2 inline-block px-8">
            {{ certificateData.employeeName || certificateData.user?.name || 'Pegawai Bank Woori Saudara' }}
          </h3>
          <p class="font-mono text-xs text-gray-400">
            NIK: {{ certificateData.nik || certificateData.user?.nik || 'NIK-10294' }}
          </p>

          <p class="text-xs text-gray-600 max-w-lg mx-auto leading-relaxed pt-2">
            Telah menyelesaikan seluruh modul dan dinyatakan <strong>LULUS (PASSED)</strong> dengan kompetensi memuaskan pada program pelatihan:
          </p>
          <h4 class="text-base font-bold text-gray-900">
            {{ certificateData.certificationName || certificateData.training_class?.program?.title || 'Sertifikasi Manajemen Risiko (SMR) Tingkat 2' }}
          </h4>

          <!-- Signatures & QR Section -->
          <div class="grid grid-cols-3 items-end pt-8 text-xs">
            <div class="text-center">
              <p class="text-gray-400 text-[10px] mb-8">Kepala Divisi Human Capital</p>
              <p class="font-bold text-gray-900">Bambang Suryo, S.E.</p>
              <p class="text-[10px] text-gray-400 font-mono">BWS HC Head</p>
            </div>

            <!-- QR Verification -->
            <div class="flex flex-col items-center justify-center">
              <div class="w-16 h-16 bg-white p-1 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-center">
                <QrCode class="w-12 h-12 text-[#157f4f]" />
              </div>
              <span class="text-[9px] text-gray-400 font-mono mt-1">Verifikasi Digital Resmi</span>
            </div>

            <div class="text-center">
              <p class="text-gray-400 text-[10px] mb-8">Lead Facilitator & Penguji</p>
              <p class="font-bold text-gray-900">Dr. Ir. Hendra Gunawan</p>
              <p class="text-[10px] text-gray-400 font-mono">Senior Banking Trainer</p>
            </div>
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
  animation: scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>

