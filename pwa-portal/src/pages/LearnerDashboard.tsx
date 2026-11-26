import React, { useState, useEffect } from 'react';
import {
  Calendar,
  Award,
  FileText,
  Clock,
  CheckCircle,
  MapPin,
  QrCode,
  Printer,
  LogOut,
  WifiOff,
  User,
  ShieldCheck,
  BookOpen,
  UploadCloud,
  RefreshCw
} from 'lucide-react';
import { useLearnerPortal, useSubmitTask } from '@/hooks/useLearnerData';
import { EnrolledClass, LearnerUser } from '@/types/learner';
import apiClient from '@/api/client';
import { useI18n } from '@/i18n';
import LanguageSwitcher from '@/components/LanguageSwitcher';

interface LearnerDashboardProps {
  user: LearnerUser;
  onLogout: () => void;
}

export default function LearnerDashboard({ user, onLogout }: LearnerDashboardProps) {
  const { l } = useI18n();
  const [activeTab, setActiveTab] = useState<'classes' | 'tasks' | 'certificates'>('classes');
  const [isOnline, setIsOnline] = useState<boolean>(navigator.onLine);
  const [selectedCert, setSelectedCert] = useState<EnrolledClass | null>(null);
  const [toastMsg, setToastMsg] = useState<string>('');

  const { data, isLoading, refetch, isFetching } = useLearnerPortal(true);
  const submitTaskMutation = useSubmitTask();

  useEffect(() => {
    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  const currentUser = data?.user || user;
  const kpis = data?.kpis;
  const enrolledClasses = data?.enrolledClasses || [];
  const tasks = data?.tasks || [];

  const progressPercentage = Math.min(
    100,
    Math.round(((kpis?.completed_hours || 16) / (kpis?.target_hours || 20)) * 100)
  );

  const handleLogoutClick = async () => {
    try {
      await apiClient.post('/auth/logout');
    } catch {
      // ignore offline logout
    }
    sessionStorage.removeItem('bws_pwa_token');
    sessionStorage.removeItem('bws_pwa_user');
    onLogout();
  };

  const handleSubmitTask = async (enrollmentId: number) => {
    try {
      await submitTaskMutation.mutateAsync({ enrollmentId });
      setToastMsg(l('Action Plan task submitted successfully!', 'Tugas Rencana Aksi berhasil dikumpulkan!'));
      setTimeout(() => setToastMsg(''), 4000);
    } catch {
      setToastMsg(l('Failed to upload task. Please check your network connection.', 'Gagal mengunggah tugas. Periksa koneksi jaringan.'));
    }
  };

  const opsPortalUrl = import.meta.env.VITE_OPS_URL || 'http://localhost:5173';

  return (
    <div className="min-h-screen bg-[#f4f6f5] text-gray-900 flex flex-col font-sans selection:bg-[#0067AC] selection:text-white pb-20 md:pb-6">
      
      {/* Top Offline PWA Alert */}
      {!isOnline && (
        <div className="bg-amber-600 text-white text-xs px-4 py-2 flex items-center justify-center gap-2 sticky top-0 z-50 shadow-sm animate-pulse">
          <WifiOff className="w-4 h-4" />
          <span>{l('Offline Mode Active — You can still view saved materials & schedules.', 'Mode Luring Aktif — Anda tetap dapat melihat materi & jadwal yang tersimpan.')}</span>
        </div>
      )}

      {/* Mobile Top Navbar */}
      <header className="bg-white px-4 py-3 border-b border-gray-100 sticky top-0 z-40 shadow-2xs relative overflow-hidden">
        {/* Subtle BWS Corporate Wave Accent */}
        <div className="absolute right-0 top-0 bottom-0 w-48 pointer-events-none overflow-hidden opacity-20">
          <svg className="w-full h-full object-cover" viewBox="0 0 200 50" fill="none" preserveAspectRatio="none">
            <path d="M 30 50 C 80 50 110 5 200 0 L 200 50 Z" fill="#00A0E9" />
            <path d="M 80 50 C 120 50 150 12 200 4 L 200 50 Z" fill="#0067AC" />
          </svg>
        </div>

        <div className="max-w-4xl mx-auto flex items-center justify-between relative z-10">
          <div className="flex items-center gap-3">
            <img src="/images/bws-logo-primary.png" alt="Bank Woori Saudara" className="h-8 w-auto object-contain" />
            <div className="h-6 w-px bg-slate-200 hidden sm:block"></div>
            <div>
              <div className="flex items-center gap-1.5">
                <span className="font-extrabold text-sm tracking-tight text-[#002855]">LIS</span>
                <span className="bg-[#EBF4FC] text-[#0067AC] text-[9px] font-bold px-1.5 py-0.2 rounded-full border border-blue-200">
                  {l('Learning Portal', 'Portal Belajar')}
                </span>
              </div>
              <p className="text-[10px] text-gray-400">Learning Integrated System</p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <LanguageSwitcher compact />

            <button
              onClick={() => refetch()}
              className="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-blue-50 hover:text-[#0067AC] text-gray-500 flex items-center justify-center transition"
              title={l('Sync Data', 'Sinkronkan Data')}
            >
              <RefreshCw className={`w-3.5 h-3.5 ${isFetching ? 'animate-spin text-[#0067AC]' : ''}`} />
            </button>

            <a
              href={opsPortalUrl}
              className="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-xs font-semibold text-gray-700 transition"
              title={l('Open Admin Portal', 'Buka Portal Admin')}
            >
              <span>{l('Admin Portal', 'Portal Admin')} &rarr;</span>
            </a>

            <button
              onClick={handleLogoutClick}
              className="w-8 h-8 rounded-full bg-[#f4f6f5] hover:bg-rose-50 hover:text-rose-600 text-gray-500 flex items-center justify-center transition"
              title={l('Sign Out', 'Keluar')}
            >
              <LogOut className="w-4 h-4" />
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-4xl mx-auto px-4 py-4 w-full flex-1 flex flex-col gap-4">
        
        {toastMsg && (
          <div className="bg-[#EBF4FC] border border-blue-200 text-[#0067AC] px-4 py-2.5 rounded-2xl text-xs flex items-center justify-between shadow-2xs">
            <span className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 shrink-0" />
              <span>{toastMsg}</span>
            </span>
            <button onClick={() => setToastMsg('')} className="font-bold">&times;</button>
          </div>
        )}

        {/* Employee Profile & Learning Hours Card */}
        <div className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 rounded-2xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-black text-lg border border-blue-200 shrink-0">
                {currentUser?.name ? currentUser.name[0] : 'U'}
              </div>
              <div>
                <h2 className="font-bold text-base text-gray-900 leading-tight">
                  {currentUser?.name || l('Bank Woori Saudara Employee', 'Pegawai Bank Woori Saudara')}
                </h2>
                <p className="text-xs text-gray-400 mt-0.5">
                  <span className="font-mono text-[#0067AC] font-semibold">{currentUser?.nik || 'NIK-BWS'}</span> • {currentUser?.department || l('Branch Office', 'Kantor Cabang')}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2 self-start sm:self-auto">
              <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#EBF4FC] text-[#0067AC]">
                <ShieldCheck className="w-3.5 h-3.5" />
                <span>{l('OJK 20-Hour Target', 'Target OJK 20 Jam')}</span>
              </span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-[#f8faf9] border border-gray-100 space-y-2">
            <div className="flex items-center justify-between text-xs">
              <span className="font-semibold text-gray-700 flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-[#0067AC]" />
                {l('2026 Accumulated Training Hours:', 'Akumulasi Jam Pelatihan 2026:')}
              </span>
              <span className="font-mono font-bold text-gray-900">
                {kpis?.completed_hours ?? 16} / {kpis?.target_hours ?? 20} {l('Hrs', 'Jam')}
              </span>
            </div>

            <div className="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
              <div
                className="bg-[#0067AC] h-2.5 rounded-full transition-all duration-500"
                style={{ width: `${progressPercentage}%` }}
              ></div>
            </div>

            <p className="text-[10px] text-gray-400 text-right">
              {l('Achieved', 'Tercapai')} <strong>{progressPercentage}%</strong> {l('of annual target', 'dari target tahunan')}
            </p>
          </div>
        </div>

        {/* Navigation Tabs */}
        <div className="flex items-center gap-2 border-b border-gray-200 pb-1 overflow-x-auto">
          <button
            onClick={() => setActiveTab('classes')}
            className={`px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-1.5 shrink-0 ${
              activeTab === 'classes'
                ? 'bg-[#0067AC] text-white shadow-xs'
                : 'text-gray-600 hover:bg-white'
            }`}
          >
            <Calendar className="w-3.5 h-3.5" />
            <span>{l('My Classes', 'Kelas Saya')} ({enrolledClasses.length})</span>
          </button>

          <button
            onClick={() => setActiveTab('tasks')}
            className={`px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-1.5 shrink-0 ${
              activeTab === 'tasks'
                ? 'bg-[#0067AC] text-white shadow-xs'
                : 'text-gray-600 hover:bg-white'
            }`}
          >
            <FileText className="w-3.5 h-3.5" />
            <span>{l('Assignments', 'Penugasan')} ({tasks.length})</span>
          </button>

          <button
            onClick={() => setActiveTab('certificates')}
            className={`px-4 py-2 rounded-full text-xs font-semibold transition flex items-center gap-1.5 shrink-0 ${
              activeTab === 'certificates'
                ? 'bg-[#0067AC] text-white shadow-xs'
                : 'text-gray-600 hover:bg-white'
            }`}
          >
            <Award className="w-3.5 h-3.5" />
            <span>{l('Digital Certificates', 'Sertifikat Digital')}</span>
          </button>
        </div>

        {/* Loading Skeleton */}
        {isLoading && (
          <div className="space-y-3">
            <div className="h-28 bg-white rounded-3xl animate-pulse border border-gray-100"></div>
            <div className="h-28 bg-white rounded-3xl animate-pulse border border-gray-100"></div>
          </div>
        )}

        {/* TAB 1: CLASSES */}
        {!isLoading && activeTab === 'classes' && (
          <div className="space-y-3">
            {enrolledClasses.length === 0 ? (
              <div className="bg-white rounded-3xl p-8 text-center text-gray-400 border border-gray-100">
                <BookOpen className="w-10 h-10 mx-auto text-gray-300 mb-2" />
                <p className="font-semibold text-gray-700 text-sm">{l('No Enrolled Classes Yet', 'Belum Ada Kelas Terdaftar')}</p>
                <p className="text-xs text-gray-400 mt-1">{l('You will receive a notification once HC Training registers a new training schedule.', 'Anda akan menerima notifikasi saat Pelatihan HC mendaftarkan jadwal diklat baru.')}</p>
              </div>
            ) : (
              enrolledClasses.map((cls) => (
                <div key={cls.id} className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-blue-200 transition">
                  <div className="space-y-1.5 flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-mono text-[11px] font-bold text-[#0067AC] bg-[#EBF4FC] px-2 py-0.5 rounded-full">
                        {cls.class_code} • {cls.batch_name}
                      </span>
                      <span className="text-[10px] text-gray-400 uppercase font-semibold">{cls.category}</span>
                    </div>

                    <h3 className="font-bold text-sm text-gray-900 leading-tight">
                      {cls.program_title}
                    </h3>

                    <div className="flex flex-wrap items-center gap-3 text-xs text-gray-500 pt-1">
                      <span className="flex items-center gap-1">
                        <Calendar className="w-3.5 h-3.5 text-[#0067AC]" />
                        {cls.start_date} {l('to', 's/d')} {cls.end_date}
                      </span>
                      <span className="flex items-center gap-1">
                        <MapPin className="w-3.5 h-3.5 text-rose-500" />
                        {cls.location}
                      </span>
                    </div>
                  </div>

                  <div className="flex sm:flex-col items-center sm:items-end justify-between gap-2 border-t sm:border-t-0 pt-3 sm:pt-0 border-gray-100">
                    <span
                      className={`text-xs font-semibold px-3 py-1 rounded-full inline-flex items-center gap-1.5 ${
                        cls.completion_status === 'PASSED'
                          ? 'bg-[#EBF4FC] text-[#0067AC]'
                          : 'bg-blue-50 text-blue-700'
                      }`}
                    >
                      <span
                        className={`w-1.5 h-1.5 rounded-full ${
                          cls.completion_status === 'PASSED' ? 'bg-[#0067AC]' : 'bg-blue-600'
                        }`}
                      ></span>
                      {cls.completion_status === 'PASSED' ? l('Passed', 'Lulus') : l('Progress {pct}%', 'Progres {pct}%', { pct: cls.elapse_progress_pct })}
                    </span>

                    {cls.completion_status === 'PASSED' && (
                      <button
                        onClick={() => setSelectedCert(cls)}
                        className="text-xs font-semibold text-[#0067AC] hover:underline flex items-center gap-1"
                      >
                        <Award className="w-3.5 h-3.5" />
                        <span>{l('View Certificate', 'Lihat Sertifikat')}</span>
                      </button>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        )}

        {/* TAB 2: TASKS */}
        {!isLoading && activeTab === 'tasks' && (
          <div className="space-y-3">
            {tasks.length === 0 ? (
              <div className="bg-white rounded-3xl p-8 text-center text-gray-400 border border-gray-100">
                <CheckCircle className="w-10 h-10 mx-auto text-emerald-400 mb-2" />
                <p className="font-semibold text-gray-700 text-sm">{l('All Assignments Completed', 'Semua Penugasan Selesai')}</p>
              </div>
            ) : (
              tasks.map((task) => (
                <div key={task.id} className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div>
                    <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{task.class_title}</span>
                    <h3 className="font-bold text-sm text-gray-900 mt-0.5">{task.title}</h3>
                    <p className="text-xs text-gray-400 mt-1">{l('Submission Deadline:', 'Batas Pengumpulan:')} <strong>{task.deadline}</strong></p>
                  </div>

                  <div className="flex items-center gap-3 self-end sm:self-auto">
                    {task.grade !== null ? (
                      <span className="font-mono text-xs font-bold text-[#0067AC] bg-[#EBF4FC] px-3 py-1 rounded-full border border-blue-200">
                        {l('Score:', 'Nilai:')} {task.grade} / 100
                      </span>
                    ) : task.status === 'SUBMITTED' ? (
                      <span className="text-xs font-semibold text-blue-700 bg-blue-50 px-3 py-1 rounded-full">
                        {l('Submitted (Awaiting Grade)', 'Terkirim (Menunggu Nilai)')}
                      </span>
                    ) : (
                      <button
                        onClick={() => handleSubmitTask(task.id)}
                        disabled={submitTaskMutation.isPending}
                        className="px-4 py-1.5 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-xs disabled:opacity-50"
                      >
                        <UploadCloud className="w-3.5 h-3.5" />
                        <span>{submitTaskMutation.isPending ? l('Uploading...', 'Mengunggah...') : l('Submit Action Plan', 'Kumpulkan Rencana Aksi')}</span>
                      </button>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        )}

        {/* TAB 3: CERTIFICATES */}
        {!isLoading && activeTab === 'certificates' && (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {enrolledClasses.filter(c => c.completion_status === 'PASSED').map((c) => (
              <div key={c.id} className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between gap-4">
                <div className="flex items-start justify-between">
                  <div className="w-10 h-10 rounded-2xl bg-[#EBF4FC] text-[#0067AC] flex items-center justify-center font-bold">
                    <Award className="w-5 h-5" />
                  </div>
                  <span className="text-[10px] text-[#0067AC] bg-[#EBF4FC] px-2 py-0.5 rounded-full font-semibold">
                    {l('Official', 'Resmi')}
                  </span>
                </div>

                <div>
                  <h4 className="font-bold text-sm text-gray-900">{c.program_title}</h4>
                  <p className="text-xs text-gray-400 mt-0.5 font-mono">{c.certificate_number}</p>
                </div>

                <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                  <button
                    onClick={() => setSelectedCert(c)}
                    className="w-full py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow-2xs"
                  >
                    <Printer className="w-3.5 h-3.5" />
                    <span>{l('Open / Print E-Certificate', 'Buka / Cetak E-Sertifikat')}</span>
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

      </main>

      {/* Digital Certificate Modal */}
      {selectedCert && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" onClick={() => setSelectedCert(null)}></div>
          
          <div className="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <div className="flex items-center gap-2">
                <Award className="w-5 h-5 text-[#0067AC]" />
                <h3 className="font-bold text-base text-gray-900">{l('Official Bank Woori Saudara E-Certificate', 'E-Sertifikat Resmi Bank Woori Saudara')}</h3>
              </div>
              <button
                onClick={() => setSelectedCert(null)}
                className="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center"
              >
                &times;
              </button>
            </div>

            <div className="border-4 border-double border-[#0067AC]/30 rounded-2xl p-6 bg-gradient-to-b from-[#fbfdfc] to-white text-center space-y-3">
              <p className="text-[10px] font-bold tracking-wider text-gray-400 uppercase">PT BANK WOORI SAUDARA INDONESIA 1906, TBK</p>
              <h2 className="text-xl font-serif font-black uppercase text-gray-900">{l('CERTIFICATE OF COMPLETION', 'SERTIFIKAT KELULUSAN')}</h2>
              <p className="text-[10px] font-mono text-gray-400">NO: {selectedCert.certificate_number}</p>
              <p className="text-xs text-gray-500 pt-2">{l('Awarded to:', 'Diberikan kepada:')}</p>
              <h3 className="text-lg font-bold text-[#0067AC]">{currentUser?.name}</h3>
              <p className="text-xs text-gray-600">{l('For satisfactory completion of the training program:', 'Atas kelulusan memuaskan pada program:')}</p>
              <h4 className="font-bold text-sm text-gray-900">{selectedCert.program_title}</h4>
              
              <div className="pt-4 flex items-center justify-center gap-2">
                <QrCode className="w-12 h-12 text-[#0067AC]" />
                <span className="text-[9px] text-gray-400 font-medium">{l('Official BWS Digital Verification', 'Verifikasi Digital Resmi BWS')}</span>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                onClick={() => window.print()}
                className="px-4 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs"
              >
                <Printer className="w-3.5 h-3.5" />
                <span>{l('Print Certificate Copy', 'Cetak Lembar Sertifikat')}</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Mobile Bottom Nav */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 flex items-center justify-around z-40 shadow-lg">
        <button
          onClick={() => setActiveTab('classes')}
          className={`flex flex-col items-center text-[10px] font-semibold gap-1 ${
            activeTab === 'classes' ? 'text-[#0067AC]' : 'text-gray-400'
          }`}
        >
          <Calendar className="w-4 h-4" />
          <span>{l('Classes', 'Kelas')}</span>
        </button>

        <button
          onClick={() => setActiveTab('tasks')}
          className={`flex flex-col items-center text-[10px] font-semibold gap-1 ${
            activeTab === 'tasks' ? 'text-[#0067AC]' : 'text-gray-400'
          }`}
        >
          <FileText className="w-4 h-4" />
          <span>{l('Tasks', 'Tugas')}</span>
        </button>

        <button
          onClick={() => setActiveTab('certificates')}
          className={`flex flex-col items-center text-[10px] font-semibold gap-1 ${
            activeTab === 'certificates' ? 'text-[#0067AC]' : 'text-gray-400'
          }`}
        >
          <Award className="w-4 h-4" />
          <span>{l('Certificates', 'Sertifikat')}</span>
        </button>

        <a
          href={opsPortalUrl}
          className="flex flex-col items-center text-[10px] font-semibold gap-1 text-gray-400 hover:text-gray-700"
        >
          <User className="w-4 h-4" />
          <span>{l('Admin', 'Admin')}</span>
        </a>
      </nav>

    </div>
  );
}

