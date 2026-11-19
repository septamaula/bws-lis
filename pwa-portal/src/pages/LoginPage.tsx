import React, { useState, useEffect } from 'react';
import { Lock, Mail, Eye, EyeOff, ArrowRight, ShieldCheck, AlertCircle, Sparkles, Building2, Smartphone, CheckCircle2 } from 'lucide-react';
import apiClient from '@/api/client';
import { LearnerUser } from '@/types/learner';

interface LoginPageProps {
  onLoginSuccess: (user: LearnerUser, token: string) => void;
}

export default function LoginPage({ onLoginSuccess }: LoginPageProps) {
  const [demoUsers, setDemoUsers] = useState<LearnerUser[]>([]);
  const [email, setEmail] = useState<string>('peserta@bws.co.id');
  const [password, setPassword] = useState<string>('password123');
  const [showPassword, setShowPassword] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string>('');

  useEffect(() => {
    apiClient
      .get('/demo-users')
      .then((res) => {
        const users: LearnerUser[] = res.data.demoUsers || [];
        setDemoUsers(users);
        const learner = users.find((u) => u.role_name === 'Employee / Participant') || users[0];
        if (learner) {
          setEmail(learner.email);
        }
      })
      .catch(() => {
        // Offline or unreachable fallback
      });
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const { data } = await apiClient.post('/auth/login', { email, password });
      sessionStorage.setItem('bws_pwa_token', data.token);
      sessionStorage.setItem('bws_pwa_user', JSON.stringify(data.user));
      onLoginSuccess(data.user, data.token);
    } catch (err: any) {
      setError(
        err.response?.data?.errors?.email?.[0] ||
          err.response?.data?.message ||
          'Gagal terhubung ke server autentikasi Bank Woori Saudara.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-white flex flex-col justify-between selection:bg-[#0067AC] selection:text-white">
      {/* Top Header */}
      <header className="w-full max-w-7xl mx-auto px-6 py-5 flex items-center justify-between border-b border-gray-100/80">
        <div className="flex items-center gap-3.5">
          <svg className="h-8 w-auto" viewBox="0 0 290 80" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <radialGradient id="bwsGlobePwaLogin" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stopColor="#00A0E9"/>
                <stop offset="65%" stopColor="#0067AC"/>
                <stop offset="100%" stopColor="#002855"/>
              </radialGradient>
              <linearGradient id="horizonGlowPwaLogin" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stopColor="#00A0E9" stopOpacity="0.3"/>
                <stop offset="20%" stopColor="#FFFFFF"/>
                <stop offset="80%" stopColor="#FFFFFF"/>
                <stop offset="100%" stopColor="#00A0E9" stopOpacity="0.3"/>
              </linearGradient>
            </defs>
            <circle cx="40" cy="40" r="36" fill="url(#bwsGlobePwaLogin)"/>
            <path d="M 6 40 A 34 34 0 0 1 74 40 Z" fill="#0067AC"/>
            <path d="M 6 40 Q 40 18 74 40 Q 40 30 6 40 Z" fill="url(#horizonGlowPwaLogin)"/>
            <path d="M 6 40 Q 40 30 74 40 A 34 34 0 0 1 6 40 Z" fill="#0055A5"/>
            <text x="86" y="47" fontFamily="'Plus Jakarta Sans', sans-serif" fontWeight="900" fontStyle="italic" fontSize="44" fill="#0067AC" letterSpacing="-1">BWS</text>
            <text x="88" y="65" fontFamily="'Plus Jakarta Sans', sans-serif" fontWeight="800" fontStyle="italic" fontSize="11.5" fill="#0067AC" letterSpacing="0.5">BANK WOORI SAUDARA</text>
          </svg>
          <div className="h-6 w-px bg-slate-200"></div>
          <div>
            <div className="flex items-center gap-1.5">
              <span className="font-extrabold text-sm text-[#002855] tracking-tight">LIS MOBILE</span>
              <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-[#EBF4FC] text-[#0067AC] uppercase">Pegawai</span>
            </div>
            <span className="text-[10px] text-gray-400 block -mt-0.5">Learning Integrated System</span>
          </div>
        </div>

        <div className="hidden sm:flex items-center gap-2 text-xs font-semibold text-gray-500 bg-[#EBF4FC]/60 px-3 py-1.5 rounded-full border border-[#0067AC]/20">
          <Smartphone className="w-3.5 h-3.5 text-[#0067AC]" />
          <span className="text-[#002855]">Portal Mandiri Pegawai BWS</span>
        </div>
      </header>

      {/* Main Split Body */}
      <main className="max-w-7xl mx-auto w-full px-6 py-6 lg:py-10 grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center my-auto">
        {/* Left Form Column */}
        <div className="lg:col-span-6 max-w-md w-full mx-auto space-y-6">
          <div className="space-y-2">
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#EBF4FC] text-[#0067AC] text-xs font-bold">
              <Sparkles className="w-3.5 h-3.5" />
              <span>Portal Belajar Pegawai BWS</span>
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-[#002855] tracking-tight">
              Selamat Datang Kembali
            </h1>
            <p className="text-xs sm:text-sm text-gray-500 leading-relaxed">
              Silakan masukkan NIK atau email korporat Bank Woori Saudara Anda untuk mengakses kelas dan sertifikat.
            </p>
          </div>

          {demoUsers.length > 0 && (
            <div className="space-y-2">
              <label className="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                Pilih Cepat Akun Login Demo:
              </label>
              <div className="flex flex-wrap gap-2">
                {demoUsers
                  .filter((u) => ['Employee / Participant', 'Facilitator / Trainer', 'Super Admin'].includes(u.role_name))
                  .map((u) => (
                    <button
                      key={u.id}
                      type="button"
                      onClick={() => {
                        setEmail(u.email);
                        setPassword('password123');
                      }}
                      className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition border ${
                        email === u.email
                          ? 'bg-[#002855] text-white border-[#002855] shadow-sm'
                          : 'bg-[#F8FAFC] text-gray-700 border-gray-200 hover:border-[#0067AC] hover:text-[#0067AC]'
                      }`}
                    >
                      {u.role_name === 'Employee / Participant' ? 'Peserta (Ahmad Pratama)' : u.role_name === 'Facilitator / Trainer' ? 'Fasilitator Diklat' : 'Super Admin'}
                    </button>
                  ))}
              </div>
            </div>
          )}

          {error && (
            <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2.5">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span className="font-medium">{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-1.5">
              <label className="block text-xs font-bold text-gray-700">
                Email Korporat atau NIK
              </label>
              <div className="relative">
                <input
                  type="text"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-[#F8FAFC] rounded-2xl pl-4 pr-10 py-3 text-sm text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:ring-2 focus:ring-[#0067AC]/15 focus:outline-none transition font-medium"
                  placeholder="nama@bws.co.id atau NIK pegawai"
                />
                <Mail className="w-4 h-4 text-gray-400 absolute right-3.5 top-3.5" />
              </div>
            </div>

            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <label className="block text-xs font-bold text-gray-700">Password</label>
                <span className="text-[11px] font-mono text-[#0067AC] font-semibold">Default: password123</span>
              </div>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full bg-[#F8FAFC] rounded-2xl pl-4 pr-10 py-3 text-sm text-gray-900 border border-gray-200 focus:border-[#0067AC] focus:bg-white focus:ring-2 focus:ring-[#0067AC]/15 focus:outline-none transition font-medium"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3.5 top-3.5 text-gray-400 hover:text-gray-600"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-[#002855] via-[#003876] to-[#0067AC] hover:opacity-95 text-white font-bold text-sm shadow-lg shadow-[#003876]/25 transition transform active:scale-[0.98] flex items-center justify-center gap-2.5 disabled:opacity-50"
            >
              <span>{loading ? 'Mengautentikasi...' : 'Masuk ke Portal Pegawai'}</span>
              {!loading && <ArrowRight className="w-4 h-4" />}
            </button>
          </form>

          <div className="pt-2 flex items-center justify-center gap-2 text-xs text-gray-400">
            <ShieldCheck className="w-4 h-4 text-[#0067AC]" />
            <span>Sistem Otentikasi Terintegrasi Bank Woori Saudara</span>
          </div>
        </div>

        {/* Right Havenix Hero Card (Desktop) */}
        <div className="hidden lg:block lg:col-span-6">
          <div className="relative rounded-[32px] overflow-hidden bg-gradient-to-br from-[#002855] via-[#003876] to-[#001D3D] p-8 sm:p-10 text-white shadow-2xl min-h-[500px] flex flex-col justify-between">
            {/* Background Texture & Glow */}
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(0,160,233,0.25),transparent_60%)]"></div>
            <div className="absolute bottom-0 right-0 w-80 h-80 bg-[#0067AC]/20 rounded-full blur-3xl pointer-events-none"></div>

            {/* Top Badge */}
            <div className="relative z-10 flex items-center justify-between">
              <div className="flex items-center gap-2 bg-white/10 backdrop-blur-md px-3.5 py-1.5 rounded-full border border-white/20 text-xs font-semibold text-white">
                <Sparkles className="w-3.5 h-3.5 text-[#00A0E9]" />
                <span>LIS Platform v2.0</span>
              </div>
              <div className="w-9 h-9 rounded-full bg-white/10 backdrop-blur flex items-center justify-center border border-white/20">
                <Building2 className="w-4 h-4 text-white" />
              </div>
            </div>

            {/* Statement Content */}
            <div className="relative z-10 space-y-4 max-w-md my-auto pt-8">
              <div className="inline-block px-3 py-1 rounded-xl bg-[#00A0E9]/25 border border-[#00A0E9]/40 text-[#00A0E9] text-[11px] font-extrabold uppercase tracking-widest">
                Karyawan & Pegawai Mandiri
              </div>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                Tuntaskan 20 Jam Pelatihan Tahunan Anda.
              </h2>
              <p className="text-xs sm:text-sm text-slate-200/90 leading-relaxed">
                Akses materi modul pelatihan terintegrasi, kerjakan Action Learning Project, ikuti pre-test/post-test, dan unduh sertifikat resmi langsung dari smartphone atau browser Anda.
              </p>
            </div>

            {/* Bottom Floating Glass Card */}
            <div className="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 flex items-center justify-between gap-4 shadow-xl">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-xl bg-[#00A0E9]/30 flex items-center justify-center text-white shrink-0">
                  <CheckCircle2 className="w-5 h-5 text-sky-200" />
                </div>
                <div>
                  <h4 className="text-xs font-bold text-white">PWA & Offline Ready</h4>
                  <p className="text-[11px] text-slate-300">Dapat diinstal di layar utama ponsel Anda</p>
                </div>
              </div>
              <span className="text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white px-2.5 py-1 rounded-full shrink-0">
                OJK & BI Compliant
              </span>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="w-full border-t border-gray-100 py-4">
        <div className="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
          <span>&copy; 2026 PT Bank Woori Saudara Indonesia 1906 Tbk. All Rights Reserved.</span>
          <span>Learning Integrated System (LIS) Mobile Portal</span>
        </div>
      </footer>
    </div>
  );
}

