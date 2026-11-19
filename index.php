<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Woori Saudara — Learning Integrated System (LIS)</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            bws: {
              navy: '#002855',
              deep: '#003876',
              blue: '#0067AC',
              sky: '#00A0E9',
              light: '#EBF4FC',
              dark: '#001D40',
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace']
          }
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: radial-gradient(circle at 15% 15%, rgba(0, 160, 233, 0.22) 0%, transparent 45%),
                  radial-gradient(circle at 85% 85%, rgba(0, 103, 172, 0.28) 0%, transparent 50%),
                  radial-gradient(circle at 50% 50%, #002855 0%, #001D40 100%);
      background-attachment: fixed;
    }
  </style>
</head>
<body class="min-h-screen text-slate-100 flex flex-col justify-between selection:bg-bws-sky selection:text-white">

  <!-- ========================================================== -->
  <!-- TOP NAVBAR: BWS BRANDING & LIS INFORMATION                 -->
  <!-- ========================================================== -->
  <header class="w-full border-b border-white/10 bg-[#002855]/70 backdrop-blur-md sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
      
      <!-- Official Bank Woori Saudara Logo + LIS Information -->
      <div class="flex items-center gap-4">
        <!-- Official BWS Vector Logo -->
        <svg class="h-10 sm:h-11 w-auto" viewBox="0 0 290 80" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <radialGradient id="bwsGlobeNav" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#00A0E9"/>
              <stop offset="65%" stop-color="#0067AC"/>
              <stop offset="100%" stop-color="#003876"/>
            </radialGradient>
            <linearGradient id="horizonGlowNav" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#00A0E9" stop-opacity="0.4"/>
              <stop offset="20%" stop-color="#FFFFFF"/>
              <stop offset="80%" stop-color="#FFFFFF"/>
              <stop offset="100%" stop-color="#00A0E9" stop-opacity="0.4"/>
            </linearGradient>
          </defs>
          <!-- Globe -->
          <circle cx="40" cy="40" r="36" fill="url(#bwsGlobeNav)"/>
          <path d="M 6 40 A 34 34 0 0 1 74 40 Z" fill="#0067AC"/>
          <path d="M 6 40 Q 40 18 74 40 Q 40 30 6 40 Z" fill="url(#horizonGlowNav)"/>
          <path d="M 6 40 Q 40 30 74 40 A 34 34 0 0 1 6 40 Z" fill="#0055A5"/>
          <!-- Bold Italic BWS (White on dark canvas) -->
          <text x="86" y="47" font-family="'Plus Jakarta Sans', sans-serif" font-weight="900" font-style="italic" font-size="44" fill="#FFFFFF" letter-spacing="-1">BWS</text>
          <!-- Subtitle BANK WOORI SAUDARA -->
          <text x="88" y="65" font-family="'Plus Jakarta Sans', sans-serif" font-weight="800" font-style="italic" font-size="11.5" fill="#00A0E9" letter-spacing="0.5">BANK WOORI SAUDARA</text>
        </svg>

        <!-- Divider -->
        <div class="h-8 w-px bg-white/20 hidden sm:block"></div>

        <!-- LIS Information -->
        <div class="leading-tight hidden sm:block">
          <div class="flex items-center gap-1.5">
            <span class="font-extrabold text-sm sm:text-base tracking-tight text-white">
              LIS
            </span>
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-white/10 text-bws-sky border border-bws-sky/30 uppercase tracking-wider">
              PORTAL
            </span>
          </div>
          <span class="text-[11px] text-slate-300 font-medium block">Learning Integrated System</span>
        </div>
      </div>

      <!-- Right Side Tagline -->
      <div class="text-right hidden md:block">
        <span class="text-xs font-semibold text-slate-300">Human Capital & Corporate Academy</span>
        <span class="text-[10px] text-bws-sky block font-mono">PT Bank Woori Saudara Indonesia 1906 Tbk</span>
      </div>

    </div>
  </header>

  <!-- ========================================================== -->
  <!-- MAIN HERO & 2 REDESIGNED PORTAL CARDS                      -->
  <!-- ========================================================== -->
  <main class="max-w-6xl mx-auto px-6 py-12 md:py-16 flex-1 flex flex-col justify-center gap-12 w-full">
    
    <!-- Hero Title Header -->
    <div class="text-center max-w-3xl mx-auto space-y-4">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 text-bws-sky border border-bws-sky/30 text-xs font-bold tracking-wide backdrop-blur-md">
        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-bws-sky"></i>
        <span>Bank Woori Saudara Learning Integrated System</span>
      </div>
      <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight leading-tight">
        Selamat Datang di Portal Pembelajaran & SDM Terpadu
      </h1>
      <p class="text-slate-200 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto font-normal">
        Silakan pilih portal tujuan Anda di bawah ini untuk mengakses sistem administrasi pelatihan perbankan atau portal belajar mandiri karyawan.
      </p>
    </div>

    <!-- 2 Clean Modern Cards (Admin & Learner) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
      
      <!-- ======================================================== -->
      <!-- CARD 1: PORTAL OPERASIONAL & ADMIN                       -->
      <!-- ======================================================== -->
      <div class="bg-white rounded-[32px] p-8 sm:p-10 shadow-2xl border border-slate-100 flex flex-col justify-between group transition-all duration-300 hover:-translate-y-1 hover:shadow-cyan-500/10 text-slate-800">
        <div class="space-y-6">
          <!-- Icon & Port Badge -->
          <div class="flex items-center justify-between">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-bws-navy to-bws-blue text-white flex items-center justify-center shadow-lg shadow-bws-navy/20">
              <i data-lucide="layout-dashboard" class="w-7 h-7"></i>
            </div>
            <div class="flex items-center gap-2 bg-[#EBF4FC] px-3.5 py-1.5 rounded-full border border-bws-sky/30">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="font-mono text-xs font-bold text-bws-blue">PORT 5173</span>
            </div>
          </div>

          <!-- Card Content -->
          <div class="space-y-2">
            <span class="text-xs font-extrabold uppercase tracking-wider text-bws-blue">Divisi Human Capital & Diklat</span>
            <h2 class="text-2xl font-extrabold text-bws-navy group-hover:text-bws-blue transition">
              Portal Operasional & Admin
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed pt-1">
              Pusat kendali operasional diklat, perumusan TNA tahunan, jadwal kelas pelatihan berjenjang, dan otorisasi anggaran dengan prinsip <strong>Dual-Control (Maker-Checker)</strong>.
            </p>
          </div>
        </div>

        <!-- Launch Button & Caption -->
        <div class="pt-8">
          <a href="http://localhost:5173" target="_blank" class="w-full inline-flex items-center justify-center gap-2.5 py-4 px-6 rounded-2xl bg-gradient-to-r from-bws-navy via-bws-deep to-bws-blue hover:from-bws-dark hover:to-bws-deep text-white font-bold text-sm shadow-lg shadow-bws-navy/25 hover:shadow-bws-sky/30 transition transform active:scale-95 group/btn">
            <span>Masuk ke Portal Operasional</span>
            <i data-lucide="arrow-right" class="w-4 h-4 transition transform group-hover/btn:translate-x-1"></i>
          </a>
          <p class="text-xs text-center text-slate-400 mt-3 font-medium">
            Akses: Super Admin, HCBP, HC Training Maker, Learning Head Checker & Dept Head
          </p>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- CARD 2: PORTAL BELAJAR PEGAWAI (LIS MOBILE)               -->
      <!-- ======================================================== -->
      <div class="bg-white rounded-[32px] p-8 sm:p-10 shadow-2xl border border-slate-100 flex flex-col justify-between group transition-all duration-300 hover:-translate-y-1 hover:shadow-cyan-500/10 text-slate-800">
        <div class="space-y-6">
          <!-- Icon & Port Badge -->
          <div class="flex items-center justify-between">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-bws-blue to-bws-sky text-white flex items-center justify-center shadow-lg shadow-bws-sky/20">
              <i data-lucide="smartphone" class="w-7 h-7"></i>
            </div>
            <div class="flex items-center gap-2 bg-[#EBF4FC] px-3.5 py-1.5 rounded-full border border-bws-sky/30">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="font-mono text-xs font-bold text-bws-blue">PORT 5174</span>
            </div>
          </div>

          <!-- Card Content -->
          <div class="space-y-2">
            <span class="text-xs font-extrabold uppercase tracking-wider text-bws-blue">Portal Mandiri Pegawai BWS</span>
            <h2 class="text-2xl font-extrabold text-bws-navy group-hover:text-bws-blue transition">
              Portal Belajar Pegawai (LIS Mobile)
            </h2>
            <p class="text-sm text-slate-600 leading-relaxed pt-1">
              Aplikasi mobile PWA untuk seluruh insan Bank Woori Saudara dalam mengakses kelas, menyelesaikan kewajiban <strong>20 Jam Pelatihan Tahunan</strong>, dan mengunduh e-sertifikat.
            </p>
          </div>
        </div>

        <!-- Launch Button & Caption -->
        <div class="pt-8">
          <a href="http://localhost:5174" target="_blank" class="w-full inline-flex items-center justify-center gap-2.5 py-4 px-6 rounded-2xl bg-gradient-to-r from-bws-blue to-bws-sky hover:from-bws-deep hover:to-bws-blue text-white font-bold text-sm shadow-lg shadow-bws-sky/25 hover:shadow-cyan-500/30 transition transform active:scale-95 group/btn">
            <span>Buka Portal Belajar Pegawai</span>
            <i data-lucide="arrow-right" class="w-4 h-4 transition transform group-hover/btn:translate-x-1"></i>
          </a>
          <p class="text-xs text-center text-slate-400 mt-3 font-medium">
            Dapat diakses melalui Browser & Smartphone (Installable PWA App)
          </p>
        </div>
      </div>

    </div>

  </main>

  <!-- ========================================================== -->
  <!-- CLEAN CORPORATE FOOTER                                     -->
  <!-- ========================================================== -->
  <footer class="w-full border-t border-white/10 py-6 mt-8">
    <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
      <div class="flex items-center gap-2">
        <span class="font-bold text-slate-300">&copy; 2026 PT Bank Woori Saudara Indonesia 1906 Tbk.</span>
        <span>•</span>
        <span>All Rights Reserved</span>
      </div>
      <div class="flex items-center gap-4 text-slate-400">
        <span>BWS Corporate Learning Academy</span>
        <span>•</span>
        <span>LIS Platform v2.0</span>
      </div>
    </div>
  </footer>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>