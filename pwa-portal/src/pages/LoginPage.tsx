import React, { useState, useEffect } from 'react';
import { Lock, Mail, Eye, EyeOff, ArrowRight, ShieldCheck, AlertCircle, Smartphone } from 'lucide-react';
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
        // Offline or unreachable backend fallback
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
          'Gagal terhubung ke server autentikasi BWS.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#f4f6f5] flex flex-col justify-between p-4 sm:p-6">
      <header className="max-w-md mx-auto w-full flex items-center justify-between pt-2">
        <div className="flex items-center gap-2.5">
          <div className="w-9 h-9 rounded-full bg-[#157f4f] text-white flex items-center justify-center font-bold text-base shadow-sm">
            W
          </div>
          <div>
            <span className="font-extrabold text-sm text-gray-900 block">BWS ELAPSE</span>
            <span className="text-[10px] text-gray-400 block -mt-0.5">Portal Belajar Pegawai</span>
          </div>
        </div>
      </header>

      <main className="max-w-md mx-auto w-full my-auto py-8">
        <div className="bg-white rounded-3xl p-6 sm:p-7 shadow-xl border border-gray-100 space-y-5">
          <div className="space-y-1.5">
            <div className="w-10 h-10 rounded-2xl bg-emerald-50 text-[#157f4f] flex items-center justify-center mb-3">
              <Lock className="w-5 h-5" />
            </div>
            <h1 className="text-xl font-bold text-gray-900">Masuk ke Portal Pegawai</h1>
            <p className="text-xs text-gray-400">
              Akses jadwal diklat, materi ELAPSE, action learning plan, dan e-sertifikat resmi Bank Woori Saudara.
            </p>
          </div>

          {demoUsers.length > 0 && (
            <div className="space-y-1.5">
              <label className="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                Pilih Cepat Akun Demo Karyawan:
              </label>
              <div className="flex flex-wrap gap-1.5">
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
                      className={`px-2.5 py-1 rounded-full text-[11px] font-semibold transition border ${
                        email === u.email
                          ? 'bg-[#157f4f] text-white border-[#157f4f]'
                          : 'bg-[#f8faf9] text-gray-600 border-gray-200 hover:border-emerald-300'
                      }`}
                    >
                      {u.role_name === 'Employee / Participant' ? 'Peserta' : u.role_name}
                    </button>
                  ))}
              </div>
            </div>
          )}

          {error && (
            <div className="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center gap-2">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4 text-xs">
            <div>
              <label className="block font-semibold text-gray-700 mb-1">Email Korporat BWS</label>
              <div className="relative">
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-[#f4f6f5] rounded-xl pl-3.5 pr-9 py-2.5 text-gray-900 border border-transparent focus:border-[#157f4f] focus:bg-white focus:outline-none transition"
                  placeholder="nama@bws.co.id"
                />
                <Mail className="w-4 h-4 text-gray-400 absolute right-3 top-2.5" />
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between mb-1">
                <label className="font-semibold text-gray-700">Password</label>
                <span className="text-[10px] font-mono text-[#157f4f]">Default: password123</span>
              </div>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full bg-[#f4f6f5] rounded-xl pl-3.5 pr-9 py-2.5 text-gray-900 border border-transparent focus:border-[#157f4f] focus:bg-white focus:outline-none transition"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 rounded-full bg-[#157f4f] hover:bg-emerald-800 text-white font-semibold text-xs shadow-md transition flex items-center justify-center gap-2 disabled:opacity-50"
            >
              <span>{loading ? 'Mengautentikasi...' : 'Masuk ke Mobile Portal'}</span>
              {!loading && <ArrowRight className="w-4 h-4" />}
            </button>
          </form>

          <div className="pt-3 border-t border-gray-100 flex items-center justify-center gap-1.5 text-[11px] text-gray-400">
            <ShieldCheck className="w-3.5 h-3.5 text-[#157f4f]" />
            <span>Sistem Otentikasi Resmi Bank Woori Saudara</span>
          </div>
        </div>
      </main>

      <footer className="text-center text-[11px] text-gray-400 pb-2">
        PT Bank Woori Saudara Indonesia 1906 Tbk • Learning Integrated System
      </footer>
    </div>
  );
}

