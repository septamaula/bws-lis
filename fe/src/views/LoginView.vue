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
  ShieldCheck,
  UserCheck,
  ChevronDown,
  ChevronUp,
  AlertCircle,
  Building2,
  Award,
  Clock,
  Sparkles
} from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

const demoUsers = ref([])
const selectedUser = ref(null)
const email = ref('admin@bws.co.id')
const password = ref('password123')
const remember = ref(true)
const showPassword = ref(false)
const showAllRoles = ref(false)
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
    await auth.login(email.value, password.value, remember.value)
    router.push({ name: 'dashboard' })
  } catch (err) {
    errorMessage.value =
      err.response?.data?.errors?.email?.[0] ||
      err.response?.data?.message ||
      'Email atau password tidak sesuai. Silakan coba lagi.'
  } finally {
    processing.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-white text-slate-800 flex items-center justify-center p-6 sm:p-10 lg:p-12 font-sans selection:bg-[#00A0E9] selection:text-white">
    
    <!-- Clean Havenix Split Layout (Unboxed Canvas) -->
    <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center">
      
      <!-- ======================================================== -->
      <!-- LEFT COLUMN: LOGIN FORM & 1-CLICK ROLES (~50%)          -->
      <!-- ======================================================== -->
      <div class="lg:col-span-6 flex flex-col justify-center space-y-6">
        
        <!-- Top Logo: Official Bank Woori Saudara + Title BWS - LIS -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <!-- Official BWS Logo Vector (Luminous Horizon Arc & Text) -->
            <svg class="h-10 w-auto" viewBox="0 0 290 80" fill="none" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <radialGradient id="bwsGlobeLogin" cx="50%" cy="50%" r="50%">
                  <stop offset="0%" stop-color="#00A0E9"/>
                  <stop offset="65%" stop-color="#0067AC"/>
                  <stop offset="100%" stop-color="#003876"/>
                </radialGradient>
                <linearGradient id="horizonGlowLogin" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#00A0E9" stop-opacity="0.3"/>
                  <stop offset="20%" stop-color="#FFFFFF"/>
                  <stop offset="80%" stop-color="#FFFFFF"/>
                  <stop offset="100%" stop-color="#00A0E9" stop-opacity="0.3"/>
                </linearGradient>
              </defs>
              <!-- Outer Circle -->
              <circle cx="40" cy="40" r="36" fill="url(#bwsGlobeLogin)"/>
              <!-- Top Dome -->
              <path d="M 6 40 A 34 34 0 0 1 74 40 Z" fill="#0067AC"/>
              <!-- Glowing Crescent Horizon -->
              <path d="M 6 40 Q 40 18 74 40 Q 40 30 6 40 Z" fill="url(#horizonGlowLogin)"/>
              <!-- Bottom Dome -->
              <path d="M 6 40 Q 40 30 74 40 A 34 34 0 0 1 6 40 Z" fill="#0055A5"/>

              <!-- Bold Italic BWS -->
              <text x="86" y="47" font-family="'Plus Jakarta Sans', sans-serif" font-weight="900" font-style="italic" font-size="44" fill="#0067AC" letter-spacing="-1">BWS</text>
              <!-- Subtitle BANK WOORI SAUDARA -->
              <text x="88" y="65" font-family="'Plus Jakarta Sans', sans-serif" font-weight="800" font-style="italic" font-size="11.5" fill="#0067AC" letter-spacing="0.5">BANK WOORI SAUDARA</text>
            </svg>
          </div>

          <!-- Title Badge: BWS - LIS -->
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#EBF4FC] border border-[#00A0E9]/30 text-[#0067AC] text-xs font-extrabold tracking-tight">
            <span class="w-2 h-2 rounded-full bg-[#00A0E9] animate-pulse"></span>
            <span>BWS - LIS</span>
          </div>
        </div>

        <!-- Heading & Subtitle (Havenix Style) -->
        <div class="space-y-1 pb-2">
          <h1 class="text-3xl sm:text-4xl font-extrabold text-[#002855] tracking-tight">
            Welcome Back
          </h1>
          <p class="text-xs sm:text-sm text-slate-500">
            Silakan masuk untuk mengakses portal diklat & sertifikasi terpadu.
          </p>
        </div>

        <!-- Alert Error -->
        <div v-if="errorMessage" class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
          <AlertCircle class="w-4 h-4 shrink-0 text-rose-500" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Form Inputs (Havenix Style) -->
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email / NIK Input -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Korporat / NIK</label>
            <div class="relative">
              <input
                v-model="email"
                type="text"
                autocomplete="username"
                required
                placeholder="nama.pegawai@bws.co.id atau NIK (misal: BWS-ADM-001)"
                class="w-full px-4 py-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-200 text-slate-900 placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:border-[#0067AC] focus:ring-2 focus:ring-[#00A0E9]/20 focus:bg-white transition"
              />
              <Mail class="w-4 h-4 text-slate-400 absolute right-4 top-4 pointer-events-none" />
            </div>
          </div>

          <!-- Password Input -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold text-slate-700">Password</label>
              <span class="text-[11px] font-mono text-[#0067AC] bg-[#EBF4FC] px-2 py-0.5 rounded-md">Default: password123</span>
            </div>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="w-full px-4 py-3.5 rounded-2xl bg-[#F8FAFC] border border-slate-200 text-slate-900 placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:border-[#0067AC] focus:ring-2 focus:ring-[#00A0E9]/20 focus:bg-white transition"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-4 top-4 text-slate-400 hover:text-slate-600 transition cursor-pointer"
              >
                <Eye v-if="!showPassword" class="w-4 h-4" />
                <EyeOff v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Remember me & Forgot Password Row (Havenix Style) -->
          <div class="flex items-center justify-between text-xs pt-1">
            <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-medium select-none">
              <input
                v-model="remember"
                type="checkbox"
                class="w-4 h-4 rounded border-slate-300 text-[#0067AC] focus:ring-[#00A0E9]"
              />
              <span>Remember me</span>
            </label>
            <a href="#" class="text-[#0067AC] hover:text-[#002855] font-semibold transition" @click.prevent>
              Forgot Password?
            </a>
          </div>

          <!-- Primary Submit Button (Havenix Style in BWS Woori Blue) -->
          <button
            type="submit"
            :disabled="processing"
            class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#002855] via-[#003876] to-[#0067AC] hover:from-[#001D40] hover:to-[#0055A5] text-white font-bold text-sm shadow-lg shadow-[#003876]/25 transition transform active:scale-[0.98] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <span v-if="!processing">Login</span>
            <span v-else>Memverifikasi Kredensial...</span>
            <ArrowRight v-if="!processing" class="w-4 h-4" />
          </button>
        </form>

        <!-- Footer Sign Up / Helpdesk Link -->
        <div class="text-center text-xs text-slate-500 pt-1">
          <span>Belum terdaftar di sistem? </span>
          <a href="#" class="text-[#0067AC] font-bold hover:underline" @click.prevent>Hubungi HC BWS</a>
        </div>

      </div>

      <!-- ======================================================== -->
      <!-- RIGHT COLUMN: HAVENIX ARCHED GRAPHIC & STATEMENT (~50%)  -->
      <!-- ======================================================== -->
      <div class="lg:col-span-6 flex items-center justify-center h-full">
        <div class="w-full h-full min-h-[500px] lg:min-h-[620px] rounded-tl-[80px] rounded-br-[80px] rounded-tr-[36px] rounded-bl-[36px] overflow-hidden relative shadow-2xl flex flex-col justify-between p-8 sm:p-10 text-white bg-[#002855]">
          
          <!-- Background Corporate High-Rise Architecture Photo -->
          <img
            src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=1200&auto=format&fit=crop"
            alt="Bank Woori Saudara Headquarters"
            class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-60 scale-105"
          />

          <!-- Gradient Overlay following BWS Woori Brand -->
          <div class="absolute inset-0 bg-gradient-to-b from-[#002855]/85 via-[#003876]/60 to-[#002855]/95"></div>

          <!-- Top Open Door Arch Graphic / Accent -->
          <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center gap-2 bg-white/15 backdrop-blur-md px-3.5 py-1.5 rounded-full border border-white/20 text-xs font-semibold text-white">
              <Sparkles class="w-3.5 h-3.5 text-[#00A0E9]" />
              <span>LIS Platform v2.0</span>
            </div>
            <div class="w-8 h-8 rounded-full bg-white/10 backdrop-blur flex items-center justify-center border border-white/20">
              <Building2 class="w-4 h-4 text-white" />
            </div>
          </div>

          <!-- Center Havenix Statement Text -->
          <div class="relative z-10 space-y-4 max-w-md my-auto pt-10">
            <div class="inline-block px-3 py-1 rounded-xl bg-[#00A0E9]/25 border border-[#00A0E9]/40 text-[#00A0E9] text-[11px] font-extrabold uppercase tracking-widest">
              Human Capital Excellence
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
              Akselerasi Kompetensi & Integritas Perbankan Insan BWS.
            </h2>
            <p class="text-xs sm:text-sm text-slate-200/90 leading-relaxed">
              Platform diklat terintegrasi dengan validasi Dual-Control (Four-Eyes Principle), monitoring progres pembelajaran terpadu, dan rekam jejak audit sesuai standar OJK & Bank Indonesia.
            </p>
          </div>

          <!-- Bottom Floating Glass Badge -->
          <div class="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 sm:p-5 flex items-center justify-between gap-4 shadow-xl">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-white/15 text-[#00A0E9] flex items-center justify-center shrink-0">
                <ShieldCheck class="w-5 h-5 text-white" />
              </div>
              <div>
                <p class="text-xs font-extrabold text-white">Bank Woori Saudara</p>
                <p class="text-[11px] text-slate-300">Standar Kepatuhan OJK & Bank Indonesia</p>
              </div>
            </div>
            <div class="border-l border-white/20 pl-4 text-right">
              <span class="text-xs font-mono font-extrabold text-[#00A0E9] block">100%</span>
              <span class="text-[10px] text-slate-300">Audit Ready</span>
            </div>
          </div>

        </div>
      </div>

    </div>

  </div>
</template>
