<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import apiClient from '@/api/client'
import {
  Lock,
  Mail,
  Eye,
  EyeOff,
  ArrowRight,
  Users,
  AlertCircle
} from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

const demoUsers = ref([])
const selectedUser = ref(null)
const email = ref('admin@bws.co.id')
const password = ref('password123')
const remember = ref(false)
const showPassword = ref(false)
const processing = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  try {
    const { data } = await apiClient.get('/demo-users')
    demoUsers.value = data.demoUsers || []
    if (demoUsers.value.length > 0) {
      quickSelectUser(demoUsers.value[0])
    }
  } catch (err) {
    console.error('Failed loading demo users:', err)
  }
})

function quickSelectUser(user) {
  selectedUser.value = user
  email.value = user.email
  password.value = 'password123'
  errorMessage.value = ''
}

function getInitials(name) {
  if (!name) return 'BW'
  const parts = name.split(' ')
  return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase()
}

async function submit() {
  processing.value = true
  errorMessage.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (err) {
    errorMessage.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Terjadi kesalahan saat otentikasi perbankan.'
  } finally {
    processing.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    <main class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        
        <!-- Left Branding & Role Quick Switcher -->
        <div class="lg:col-span-7 space-y-6">
          <div class="space-y-3">
            <div class="inline-flex items-center space-x-2.5 px-3 py-1.5 rounded-full bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              <span>Bank Woori Saudara Learning Integrated System (LIS)</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
              Portal Operasional Pengembangan SDM & Diklat Perbankan
            </h1>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
              Sistem Terpadu Pelatihan & Pengembangan Karyawan Bank Woori Saudara dengan Otorisasi Berjenjang (Dual-Control) dan Rekam Jejak Audit Terpercaya.
            </p>
          </div>

          <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-3 backdrop-blur shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
              <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                <Users class="w-4 h-4 text-emerald-400" />
                Pilih Akses Cepat Multi-Role (8 Hak Akses):
              </span>
              <span class="text-[11px] text-emerald-400 font-mono">1-Klik Otentikasi</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
              <button
                v-for="u in demoUsers"
                :key="u.id"
                @click="quickSelectUser(u)"
                class="flex items-start space-x-3 p-3 rounded-xl border text-left transition-all group"
                :class="selectedUser?.email === u.email
                  ? 'bg-emerald-950/50 border-emerald-500/80 text-white shadow-lg shadow-emerald-950/50'
                  : 'bg-slate-900/60 border-slate-800 text-slate-300 hover:border-slate-700 hover:bg-slate-800/50'"
              >
                <div
                  class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 font-bold text-xs"
                  :class="selectedUser?.email === u.email ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 group-hover:text-white'"
                >
                  {{ getInitials(u.name) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-xs font-bold truncate text-slate-200 group-hover:text-emerald-400">
                      {{ u.role_name }}
                    </p>
                    <span class="text-[10px] font-mono text-slate-500">{{ u.nik }}</span>
                  </div>
                  <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ u.name }}</p>
                  <p class="text-[10px] text-slate-500 truncate">{{ u.department }}</p>
                </div>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Login Card -->
        <div class="lg:col-span-5">
          <div class="bg-slate-950/90 border border-slate-800 rounded-3xl p-7 shadow-2xl backdrop-blur relative overflow-hidden">
            <div class="space-y-2 mb-6">
              <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-900/40">
                  <Lock class="w-5 h-5 text-white" />
                </div>
                <span class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-800 text-[10px] text-slate-400">
                  Ops Portal
                </span>
              </div>
              <h2 class="text-xl font-bold text-white tracking-tight">Otentikasi Karyawan BWS</h2>
              <p class="text-xs text-slate-400">Gunakan email korporat @bws.co.id untuk masuk ke sistem.</p>
            </div>

            <div v-if="errorMessage" class="mb-4 p-3 rounded-xl bg-rose-950/50 border border-rose-500/40 text-rose-300 text-xs flex items-center space-x-2">
              <AlertCircle class="w-4 h-4 shrink-0 text-rose-400" />
              <span>{{ errorMessage }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Korporat / NIK</label>
                <div class="relative">
                  <input
                    v-model="email"
                    type="email"
                    required
                    placeholder="nama.pegawai@bws.co.id"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                  />
                  <Mail class="w-4 h-4 text-slate-500 absolute right-3.5 top-3" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="block text-xs font-semibold text-slate-300">Password</label>
                  <span class="text-[11px] text-emerald-400 font-mono">Default: password123</span>
                </div>
                <div class="relative">
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3.5 top-3 text-slate-500 hover:text-slate-300 transition"
                  >
                    <Eye v-if="!showPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
                <label class="flex items-center space-x-2 cursor-pointer">
                  <input
                    v-model="remember"
                    type="checkbox"
                    class="rounded border-slate-700 bg-slate-900 text-emerald-600 focus:ring-emerald-500"
                  />
                  <span>Ingat sesi di perangkat terpercaya</span>
                </label>
              </div>

              <button
                type="submit"
                :disabled="processing"
                class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm shadow-lg shadow-emerald-950/60 transition flex items-center justify-center space-x-2 cursor-pointer disabled:opacity-50"
              >
                <span v-if="!processing">Masuk ke Portal LIS</span>
                <span v-else>Memverifikasi Otoritas Perbankan...</span>
                <ArrowRight v-if="!processing" class="w-4 h-4" />
              </button>
            </form>
          </div>
        </div>

      </div>
    </main>

    <footer class="border-t border-slate-800 bg-slate-950 px-6 py-4 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
      <div class="flex items-center space-x-2">
        <span class="font-bold text-slate-300">PT Bank Woori Saudara Indonesia 1906 Tbk</span>
      </div>
      <div class="flex items-center space-x-4">
        <span>Learning Integrated System</span>
      </div>
    </footer>
  </div>
</template>

