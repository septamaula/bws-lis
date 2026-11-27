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
  RefreshCw,
  Star,
  CalendarOff,
  FileCheck2,
  Download,
  TrendingUp,
  X
} from 'lucide-react';
import {
  useLearnerPortal,
  useSubmitTask,
  useSubmitLeaveConfirmation,
  useSubmitCourseEvaluation,
  useSignCommitment
} from '@/hooks/useLearnerData';
import { EnrolledClass, LearnerTask, LearnerUser } from '@/types/learner';
import apiClient from '@/api/client';
import { useI18n } from '@/i18n';
import LanguageSwitcher from '@/components/LanguageSwitcher';

interface LearnerDashboardProps {
  user: LearnerUser;
  onLogout: () => void;
}

export default function LearnerDashboard({ user, onLogout }: LearnerDashboardProps) {
  const { l, locale } = useI18n();
  const [activeTab, setActiveTab] = useState<'classes' | 'tasks' | 'certificates'>('classes');
  const [isOnline, setIsOnline] = useState<boolean>(navigator.onLine);
  const [selectedCert, setSelectedCert] = useState<EnrolledClass | null>(null);
  const [toastMsg, setToastMsg] = useState<string>('');
  const [toastError, setToastError] = useState<string>('');

  // Task upload modal state
  const [taskModal, setTaskModal] = useState<LearnerTask | null>(null);
  const [taskNotes, setTaskNotes] = useState<string>('');
  const [taskFile, setTaskFile] = useState<File | null>(null);

  // Leave confirmation modal state
  const [leaveClass, setLeaveClass] = useState<EnrolledClass | null>(null);
  const [leaveStatus, setLeaveStatus] = useState<string>('Absent - Sick');
  const [leaveNotes, setLeaveNotes] = useState<string>('');
  const [leaveAttachment, setLeaveAttachment] = useState<File | null>(null);

  // List Evl Evaluation modal state (1-4 scale)
  const [evalClass, setEvalClass] = useState<EnrolledClass | null>(null);
  const [materialScores, setMaterialScores] = useState<number[]>([4, 4, 4]);
  const [instructorScores, setInstructorScores] = useState<Array<{ slot: string; name: string; scores: number[] }>>([]);
  const [committeeScores, setCommitteeScores] = useState<number[]>([4, 4, 4]);
  const [evalFeedback, setEvalFeedback] = useState<string>('');

  // E-Commitment modal state
  const [commitmentClass, setCommitmentClass] = useState<EnrolledClass | null>(null);
  const [commitmentAgreed, setCommitmentAgreed] = useState<boolean>(false);

  // Materials drawer modal state
  const [materialsClass, setMaterialsClass] = useState<EnrolledClass | null>(null);

  const { data, isLoading, refetch, isFetching } = useLearnerPortal(true);
  const submitTaskMutation = useSubmitTask();
  const submitLeaveMutation = useSubmitLeaveConfirmation();
  const submitEvaluationMutation = useSubmitCourseEvaluation();
  const signCommitmentMutation = useSignCommitment();

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
  const evalCatalog = data?.evaluation_catalog;
  const isPreviewMode = Boolean(data?.is_preview_mode);

  const completedHours = kpis?.completed_hours ?? 0;
  const targetHours = kpis?.target_hours ?? 20;
  const progressPercentage = Math.min(
    100,
    targetHours > 0 ? Math.round((completedHours / targetHours) * 100) : 0
  );

  const notifySuccess = (msg: string) => {
    setToastError('');
    setToastMsg(msg);
    setTimeout(() => setToastMsg(''), 4500);
  };

  const notifyError = (msg: string) => {
    setToastMsg('');
    setToastError(msg);
    setTimeout(() => setToastError(''), 5000);
  };

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

  const openTaskUploadModal = (task: LearnerTask) => {
    setTaskModal(task);
    setTaskNotes('');
    setTaskFile(null);
  };

  const handleSubmitTaskForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!taskModal) return;
    if (!taskFile && !taskNotes.trim()) {
      notifyError(
        l(
          'Please attach an Action Plan document or enter implementation notes.',
          'Harap unggah berkas Rencana Aksi atau isi catatan implementasi.'
        )
      );
      return;
    }
    try {
      await submitTaskMutation.mutateAsync({
        enrollmentId: taskModal.id,
        notes: taskNotes.trim() || undefined,
        file: taskFile,
      });
      setTaskModal(null);
      notifySuccess(
        l('Action Plan task submitted successfully!', 'Tugas Rencana Aksi berhasil dikumpulkan!')
      );
    } catch {
      notifyError(
        l('Failed to upload task. Please check your network connection.', 'Gagal mengunggah tugas. Periksa koneksi jaringan.')
      );
    }
  };

  const openLeaveModal = (cls: EnrolledClass) => {
    setLeaveClass(cls);
    setLeaveStatus(cls.leave_status || 'Absent - Sick');
    setLeaveNotes(cls.leave_notes || '');
    setLeaveAttachment(null);
  };

  const handleSubmitLeaveForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!leaveClass) return;
    try {
      await submitLeaveMutation.mutateAsync({
        enrollmentId: leaveClass.id,
        leave_status: leaveStatus,
        leave_notes: leaveNotes.trim() || undefined,
        attachment: leaveAttachment,
      });
      setLeaveClass(null);
      notifySuccess(
        l('Absence confirmation has been recorded.', 'Konfirmasi ketidakhadiran berhasil dikirim.')
      );
    } catch {
      notifyError(
        l('Failed to submit absence confirmation.', 'Gagal mengirim konfirmasi ketidakhadiran.')
      );
    }
  };

  const openEvalModal = (cls: EnrolledClass) => {
    setEvalClass(cls);
    const existing = cls.evaluation;
    setMaterialScores(
      existing?.material_scores && existing.material_scores.length === 3
        ? existing.material_scores
        : [4, 4, 4]
    );
    setCommitteeScores(
      existing?.committee_scores && existing.committee_scores.length === 3
        ? existing.committee_scores
        : [4, 4, 4]
    );

    const instructors = cls.instructors?.length
      ? cls.instructors
      : [{ slot: 'P1', name: cls.facilitator_name || 'Facilitator' }];

    setInstructorScores(
      instructors.map((inst, idx) => {
        const prev = existing?.instructor_scores?.[idx];
        return {
          slot: inst.slot,
          name: inst.name,
          scores: prev?.scores && prev.scores.length === 3 ? [...prev.scores] : [4, 4, 4],
        };
      })
    );
    setEvalFeedback(existing?.feedback_notes || '');
  };

  const handleSubmitEvaluationForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!evalClass) return;
    try {
      await submitEvaluationMutation.mutateAsync({
        enrollmentId: evalClass.id,
        material_scores: materialScores,
        instructor_scores: instructorScores,
        committee_scores: committeeScores,
        feedback_notes: evalFeedback.trim() || undefined,
      });
      setEvalClass(null);
      notifySuccess(
        l('Thank you! Your training evaluation has been saved.', 'Terima kasih! Evaluasi pelatihan Anda berhasil disimpan.')
      );
    } catch {
      notifyError(
        l('Failed to submit course evaluation.', 'Gagal menyimpan evaluasi pelatihan.')
      );
    }
  };

  const openCommitmentModal = (cls: EnrolledClass) => {
    setCommitmentClass(cls);
    setCommitmentAgreed(Boolean(cls.commitment_signed_at));
  };

  const handleSignCommitmentSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!commitmentClass || !commitmentAgreed) return;
    try {
      await signCommitmentMutation.mutateAsync({ enrollmentId: commitmentClass.id });
      setCommitmentClass(null);
      notifySuccess(
        l('E-Commitment Letter signed digitally.', 'Surat Komitmen Elektronik berhasil ditandatangani.')
      );
    } catch {
      notifyError(
        l('Failed to sign E-Commitment Letter.', 'Gagal menandatangani Surat Komitmen.')
      );
    }
  };

  const formatLeaveLabel = (status: string) => {
    if (status === 'Absent - Sick') return l('Sick Leave (Medical Note)', 'Izin Sakit (Surat Dokter)');
    if (status === 'Absent - Business') return l('Official Business Duty', 'Tugas Dinas');
    if (status === 'Absent - Maternity') return l('Maternity Leave', 'Cuti Melahirkan');
    if (status === 'Absent - Busy') return l('Operational Conflict', 'Bentrok Pekerjaan');
    return status;
  };

  const renderScaleButtons = (currentValue: number, onSelect: (v: number) => void) => (
    <div className="flex items-center gap-1.5">
      {[1, 2, 3, 4].map((val) => (
        <button
          key={val}
          type="button"
          onClick={() => onSelect(val)}
          className={`w-8 h-8 rounded-xl text-xs font-bold border transition ${
            currentValue === val
              ? 'bg-[#0067AC] text-white border-[#0067AC] shadow-xs'
              : 'bg-white text-gray-600 border-gray-200 hover:border-[#0067AC]'
          }`}
        >
          {val}
        </button>
      ))}
    </div>
  );

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

        {toastError && (
          <div className="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-2.5 rounded-2xl text-xs flex items-center justify-between shadow-2xs">
            <span>{toastError}</span>
            <button onClick={() => setToastError('')} className="font-bold">&times;</button>
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

            <div className="flex items-center gap-2 flex-wrap self-start sm:self-auto">
              {isPreviewMode && (
                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                  {l('Admin Preview Mode', 'Mode Pratinjau Admin')}
                </span>
              )}
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
                {completedHours} / {targetHours} {l('Hrs', 'Jam')}
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
          <div className="space-y-4">
            {enrolledClasses.length === 0 ? (
              <div className="bg-white rounded-3xl p-8 text-center text-gray-400 border border-gray-100">
                <BookOpen className="w-10 h-10 mx-auto text-gray-300 mb-2" />
                <p className="font-semibold text-gray-700 text-sm">{l('No Enrolled Classes Yet', 'Belum Ada Kelas Terdaftar')}</p>
                <p className="text-xs text-gray-400 mt-1">{l('You will receive a notification once HC Training registers a new training schedule.', 'Anda akan menerima notifikasi saat Pelatihan HC mendaftarkan jadwal diklat baru.')}</p>
              </div>
            ) : (
              enrolledClasses.map((cls) => {
                const deltaPct =
                  cls.pre_test_score !== null && cls.post_test_score !== null && cls.pre_test_score > 0
                    ? Math.round(((cls.post_test_score - cls.pre_test_score) / cls.pre_test_score) * 100)
                    : null;

                return (
                  <div key={cls.id} className="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4 hover:border-blue-200 transition">
                    <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                      <div className="space-y-1.5 flex-1">
                        <div className="flex items-center flex-wrap gap-2">
                          <span className="font-mono text-[11px] font-bold text-[#0067AC] bg-[#EBF4FC] px-2 py-0.5 rounded-full">
                            {cls.class_code} • {cls.batch_name}
                          </span>
                          <span className="text-[10px] text-gray-400 uppercase font-semibold">{cls.category}</span>
                          {cls.participant_code && (
                            <span className="font-mono text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                              {cls.participant_code}
                            </span>
                          )}
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

                      <div className="flex flex-row sm:flex-col items-center sm:items-end justify-between gap-1.5">
                        <span
                          className={`text-xs font-semibold px-3 py-1 rounded-full inline-flex items-center gap-1.5 ${
                            cls.completion_status === 'PASSED'
                              ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                              : cls.completion_status === 'FAILED'
                              ? 'bg-rose-50 text-rose-700 border border-rose-200'
                              : 'bg-blue-50 text-blue-700'
                          }`}
                        >
                          <span
                            className={`w-1.5 h-1.5 rounded-full ${
                              cls.completion_status === 'PASSED'
                                ? 'bg-emerald-600'
                                : cls.completion_status === 'FAILED'
                                ? 'bg-rose-500'
                                : 'bg-blue-600'
                            }`}
                          ></span>
                          {cls.completion_status === 'PASSED'
                            ? l('Passed', 'Lulus')
                            : cls.completion_status === 'FAILED'
                            ? l('Not Passed', 'Tidak Lulus')
                            : l('Progress {pct}%', 'Progres {pct}%', { pct: cls.elapse_progress_pct })}
                        </span>

                        {cls.leave_status && (
                          <span className="text-[10px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                            {formatLeaveLabel(cls.leave_status)}
                          </span>
                        )}
                      </div>
                    </div>

                    {/* Pre-Test / Post-Test / Final Score / Attendance Strip */}
                    <div className="grid grid-cols-2 sm:grid-cols-5 gap-2 bg-[#f8faf9] p-3 rounded-2xl border border-gray-100 text-xs">
                      <div>
                        <p className="text-[10px] text-gray-400 font-medium">{l('Pre-Test', 'Tes Awal')}</p>
                        <p className="font-mono font-bold text-gray-800">{cls.pre_test_score ?? '-'}</p>
                      </div>
                      <div>
                        <p className="text-[10px] text-gray-400 font-medium">{l('Post-Test', 'Tes Akhir')}</p>
                        <p className="font-mono font-bold text-[#0067AC]">{cls.post_test_score ?? '-'}</p>
                      </div>
                      <div>
                        <p className="text-[10px] text-gray-400 font-medium">{l('Gain (Δ%)', 'Peningkatan')}</p>
                        <p className={`font-mono font-bold flex items-center gap-0.5 ${deltaPct !== null && deltaPct >= 0 ? 'text-emerald-700' : 'text-gray-600'}`}>
                          {deltaPct !== null ? (
                            <>
                              <TrendingUp className="w-3 h-3" />
                              <span>{deltaPct >= 0 ? `+${deltaPct}%` : `${deltaPct}%`}</span>
                            </>
                          ) : '-'}
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-gray-400 font-medium">{l('Final Score', 'Nilai Akhir')}</p>
                        <p className="font-mono font-bold text-gray-900">
                          {cls.final_score ?? '-'}{' '}
                          <span className="text-[10px] font-normal text-gray-400">
                            (Min {cls.passing_grade || 70})
                          </span>
                        </p>
                      </div>
                      <div>
                        <p className="text-[10px] text-gray-400 font-medium">{l('Attendance', 'Kehadiran')}</p>
                        <p className="font-mono font-bold text-gray-800">
                          {cls.attendance_percentage ?? 0}%
                          <span className="text-[10px] font-normal text-gray-400 ml-1">
                            ({cls.point_add}/{cls.invitation_point})
                          </span>
                        </p>
                      </div>
                    </div>

                    {/* Interactive Learner Action Buttons */}
                    <div className="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100">
                      <button
                        type="button"
                        onClick={() => openEvalModal(cls)}
                        className={`px-3 py-1.5 rounded-full text-[11px] font-semibold inline-flex items-center gap-1.5 border transition ${
                          cls.has_evaluated
                            ? 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100'
                            : 'bg-white text-gray-700 border-gray-200 hover:border-[#0067AC] hover:text-[#0067AC]'
                        }`}
                      >
                        <Star className="w-3.5 h-3.5" />
                        <span>
                          {cls.has_evaluated
                            ? l('Evaluated ({avg}/4)', 'Dievaluasi ({avg}/4)', { avg: cls.evaluation?.overall_avg ?? 4 })
                            : l('Course Evaluation (1–4)', 'Evaluasi Pelatihan (1–4)')}
                        </span>
                      </button>

                      <button
                        type="button"
                        onClick={() => openCommitmentModal(cls)}
                        className={`px-3 py-1.5 rounded-full text-[11px] font-semibold inline-flex items-center gap-1.5 border transition ${
                          cls.commitment_signed_at
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            : 'bg-white text-gray-700 border-gray-200 hover:border-[#0067AC] hover:text-[#0067AC]'
                        }`}
                      >
                        <FileCheck2 className="w-3.5 h-3.5" />
                        <span>
                          {cls.commitment_signed_at
                            ? l('Commitment Signed', 'Komitmen Ditandatangani')
                            : l('Sign E-Commitment', 'Tanda Tangan Komitmen')}
                        </span>
                      </button>

                      <button
                        type="button"
                        onClick={() => openLeaveModal(cls)}
                        className="px-3 py-1.5 rounded-full text-[11px] font-semibold inline-flex items-center gap-1.5 bg-white text-gray-700 border border-gray-200 hover:border-amber-500 hover:text-amber-700 transition"
                      >
                        <CalendarOff className="w-3.5 h-3.5" />
                        <span>{l('Confirm Absence', 'Konfirmasi Izin')}</span>
                      </button>

                      {cls.materials?.length > 0 && (
                        <button
                          type="button"
                          onClick={() => setMaterialsClass(cls)}
                          className="px-3 py-1.5 rounded-full text-[11px] font-semibold inline-flex items-center gap-1.5 bg-white text-[#0067AC] border border-blue-200 hover:bg-blue-50 transition"
                        >
                          <Download className="w-3.5 h-3.5" />
                          <span>{l('Materials', 'Materi')} ({cls.materials.length})</span>
                        </button>
                      )}

                      {cls.completion_status === 'PASSED' && cls.certificate_number && (
                        <button
                          type="button"
                          onClick={() => setSelectedCert(cls)}
                          className="px-3 py-1.5 rounded-full text-[11px] font-semibold inline-flex items-center gap-1.5 bg-[#0067AC] text-white hover:bg-[#00528c] transition ml-auto"
                        >
                          <Award className="w-3.5 h-3.5" />
                          <span>{l('View Certificate', 'Lihat Sertifikat')}</span>
                        </button>
                      )}
                    </div>
                  </div>
                );
              })
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
                  <div className="space-y-1">
                    <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">{task.class_title}</span>
                    <h3 className="font-bold text-sm text-gray-900">{task.title}</h3>
                    <p className="text-xs text-gray-400">{l('Submission Deadline:', 'Batas Pengumpulan:')} <strong>{task.deadline}</strong></p>
                    {task.submitted_file && (
                      <a
                        href={task.submitted_file}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-[11px] font-semibold text-[#0067AC] hover:underline"
                      >
                        <Download className="w-3 h-3" />
                        <span>{l('View Submitted Attachment', 'Lihat Berkas Terkirim')}</span>
                      </a>
                    )}
                    {task.feedback && (
                      <p className="text-[11px] text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-xl border border-emerald-100">
                        {l('Facilitator Note:', 'Catatan Fasilitator:')} {task.feedback}
                      </p>
                    )}
                  </div>

                  <div className="flex items-center gap-2 self-end sm:self-auto">
                    {task.grade !== null ? (
                      <span className="font-mono text-xs font-bold text-[#0067AC] bg-[#EBF4FC] px-3 py-1 rounded-full border border-blue-200">
                        {l('Score:', 'Nilai:')} {task.grade} / 100
                      </span>
                    ) : (
                      <>
                        {task.status === 'SUBMITTED' && (
                          <span className="text-xs font-semibold text-blue-700 bg-blue-50 px-3 py-1 rounded-full">
                            {l('Submitted', 'Terkirim')}
                          </span>
                        )}
                        <button
                          onClick={() => openTaskUploadModal(task)}
                          className="px-4 py-1.5 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-xs"
                        >
                          <UploadCloud className="w-3.5 h-3.5" />
                          <span>
                            {task.status === 'SUBMITTED'
                              ? l('Update Submission', 'Perbarui Berkas')
                              : l('Submit Action Plan', 'Kumpulkan Rencana Aksi')}
                          </span>
                        </button>
                      </>
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
            {enrolledClasses.filter(c => c.completion_status === 'PASSED' && c.certificate_number).length === 0 ? (
              <div className="sm:col-span-2 bg-white rounded-3xl p-8 text-center text-gray-400 border border-gray-100">
                <Award className="w-10 h-10 mx-auto text-gray-300 mb-2" />
                <p className="font-semibold text-gray-700 text-sm">{l('No Digital Certificates Issued Yet', 'Belum Ada Sertifikat Digital Terbit')}</p>
                <p className="text-xs text-gray-400 mt-1">{l('Certificates become available once you pass the Post-Test and meet the 80% attendance requirement.', 'Sertifikat tersedia setelah lulus Tes Akhir dan memenuhi kehadiran minimal 80%.')}</p>
              </div>
            ) : (
              enrolledClasses
                .filter(c => c.completion_status === 'PASSED' && c.certificate_number)
                .map((c) => (
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
                ))
            )}
          </div>
        )}

      </main>

      {/* Modal: Action Plan Upload */}
      {taskModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" onClick={() => setTaskModal(null)}></div>
          <form onSubmit={handleSubmitTaskForm} className="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <div>
                <h3 className="font-bold text-sm text-gray-900">{l('Submit Action Learning Plan', 'Kumpulkan Rencana Aksi Pembelajaran')}</h3>
                <p className="text-[11px] text-gray-500">{taskModal.title}</p>
              </div>
              <button type="button" onClick={() => setTaskModal(null)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-gray-700 mb-1">{l('Upload Action Plan Document (PDF / Office / Image, max 10MB)', 'Unggah Dokumen Rencana Aksi (PDF / Office / Gambar, maks 10MB)')}</label>
                <input
                  type="file"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                  onChange={(e) => setTaskFile(e.target.files?.[0] || null)}
                  className="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-[#EBF4FC] file:text-[#0067AC] file:font-semibold"
                />
              </div>

              <div>
                <label className="block font-semibold text-gray-700 mb-1">{l('Implementation Summary / Notes', 'Ringkasan Implementasi / Catatan')}</label>
                <textarea
                  rows={3}
                  value={taskNotes}
                  onChange={(e) => setTaskNotes(e.target.value)}
                  placeholder={l('Describe planned workplace application or key takeaways...', 'Jelaskan rencana penerapan di unit kerja Anda...')}
                  className="w-full rounded-2xl border border-gray-200 p-3 text-xs focus:border-[#0067AC] focus:outline-none"
                ></textarea>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
              <button type="button" onClick={() => setTaskModal(null)} className="px-4 py-2 rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                {l('Cancel', 'Batal')}
              </button>
              <button
                type="submit"
                disabled={submitTaskMutation.isPending}
                className="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold disabled:opacity-50"
              >
                {submitTaskMutation.isPending ? l('Uploading...', 'Mengunggah...') : l('Submit Assignment', 'Kirim Tugas')}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Modal: Leave / Absence Confirmation */}
      {leaveClass && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" onClick={() => setLeaveClass(null)}></div>
          <form onSubmit={handleSubmitLeaveForm} className="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <div>
                <h3 className="font-bold text-sm text-gray-900">{l('Absence Confirmation & Supporting Document', 'Konfirmasi Ketidakhadiran & Dokumen Pendukung')}</h3>
                <p className="text-[11px] text-gray-500">{leaveClass.program_title} • {leaveClass.class_code}</p>
              </div>
              <button type="button" onClick={() => setLeaveClass(null)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-gray-700 mb-1">{l('Absence Category', 'Kategori Ketidakhadiran')}</label>
                <select
                  value={leaveStatus}
                  onChange={(e) => setLeaveStatus(e.target.value)}
                  className="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs bg-white focus:border-[#0067AC] focus:outline-none"
                >
                  <option value="Absent - Sick">{l('Sick Leave (With Medical Note)', 'Sakit (Dengan Surat Dokter)')}</option>
                  <option value="Absent - Business">{l('Official Business Duty (Confirmed by Supervisor)', 'Tugas Dinas (Terkonfirmasi Pejabat)')}</option>
                  <option value="Absent - Maternity">{l('Maternity Leave (Confirmed)', 'Cuti Melahirkan (Terkonfirmasi)')}</option>
                  <option value="Absent - Busy">{l('Operational Conflict / Busy', 'Bentrok Pekerjaan / Sibuk')}</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-gray-700 mb-1">{l('Supporting Letter / Medical Certificate (Optional, max 5MB)', 'Surat Keterangan Dokter / Surat Tugas (Opsional, maks 5MB)')}</label>
                <input
                  type="file"
                  accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                  onChange={(e) => setLeaveAttachment(e.target.files?.[0] || null)}
                  className="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:bg-[#EBF4FC] file:text-[#0067AC] file:font-semibold"
                />
              </div>

              <div>
                <label className="block font-semibold text-gray-700 mb-1">{l('Explanation Notes', 'Keterangan Tambahan')}</label>
                <textarea
                  rows={3}
                  value={leaveNotes}
                  onChange={(e) => setLeaveNotes(e.target.value)}
                  placeholder={l('Provide reason for absence...', 'Tuliskan alasan ketidakhadiran Anda...')}
                  className="w-full rounded-2xl border border-gray-200 p-3 text-xs focus:border-[#0067AC] focus:outline-none"
                ></textarea>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
              <button type="button" onClick={() => setLeaveClass(null)} className="px-4 py-2 rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                {l('Cancel', 'Batal')}
              </button>
              <button
                type="submit"
                disabled={submitLeaveMutation.isPending}
                className="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold disabled:opacity-50"
              >
                {submitLeaveMutation.isPending ? l('Submitting...', 'Mengirim...') : l('Submit Confirmation', 'Kirim Konfirmasi')}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Modal: List Evl Kirkpatrick L1 Evaluation (Scale 1-4) */}
      {evalClass && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" onClick={() => setEvalClass(null)}></div>
          <form onSubmit={handleSubmitEvaluationForm} className="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <div>
                <h3 className="font-bold text-sm text-gray-900">{l('Training Delivery Evaluation (Scale 1–4)', 'Evaluasi Penyelenggaraan Pelatihan (Skala 1–4)')}</h3>
                <p className="text-[11px] text-gray-500">{evalClass.program_title} • 1 = {l('Poor', 'Kurang')}, 4 = {l('Excellent', 'Sangat Baik')}</p>
              </div>
              <button type="button" onClick={() => setEvalClass(null)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="space-y-4 text-xs">
              {/* Material Section */}
              <div className="p-4 rounded-2xl bg-[#f8fafc] border border-gray-200 space-y-3">
                <h4 className="font-bold text-[#002855]">{l('1. Training Material Quality', '1. Kualitas Materi Pelatihan')}</h4>
                {(evalCatalog?.material || []).map((q, idx) => (
                  <div key={q.code} className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-gray-100">
                    <span className="text-gray-700 font-medium">{idx + 1}. {locale === 'en' ? q.label_en : q.label_id}</span>
                    {renderScaleButtons(materialScores[idx] ?? 4, (val) => {
                      const next = [...materialScores];
                      next[idx] = val;
                      setMaterialScores(next);
                    })}
                  </div>
                ))}
              </div>

              {/* Instructors Section (P1..P15) */}
              <div className="p-4 rounded-2xl bg-[#f8fafc] border border-gray-200 space-y-3">
                <h4 className="font-bold text-[#002855]">{l('2. Instructors / Facilitators', '2. Pengajar / Fasilitator')}</h4>
                {instructorScores.map((inst, instIdx) => (
                  <div key={inst.slot} className="bg-white p-3 rounded-xl border border-gray-200 space-y-2.5">
                    <p className="font-bold text-[#0067AC]">
                      {inst.slot} — {inst.name}
                    </p>
                    {(evalCatalog?.instructor || []).map((q, qIdx) => (
                      <div key={q.code} className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 border-t border-gray-100">
                        <span className="text-gray-700">{qIdx + 1}. {locale === 'en' ? q.label_en : q.label_id}</span>
                        {renderScaleButtons(inst.scores[qIdx] ?? 4, (val) => {
                          const next = [...instructorScores];
                          const updatedScores = [...next[instIdx].scores];
                          updatedScores[qIdx] = val;
                          next[instIdx] = { ...next[instIdx], scores: updatedScores };
                          setInstructorScores(next);
                        })}
                      </div>
                    ))}
                  </div>
                ))}
              </div>

              {/* Committee Section */}
              <div className="p-4 rounded-2xl bg-[#f8fafc] border border-gray-200 space-y-3">
                <h4 className="font-bold text-[#002855]">{l('3. Organizing Committee Support', '3. Dukungan Kepanitiaan')}</h4>
                {(evalCatalog?.committee || []).map((q, idx) => (
                  <div key={q.code} className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-gray-100">
                    <span className="text-gray-700 font-medium">{idx + 1}. {locale === 'en' ? q.label_en : q.label_id}</span>
                    {renderScaleButtons(committeeScores[idx] ?? 4, (val) => {
                      const next = [...committeeScores];
                      next[idx] = val;
                      setCommitteeScores(next);
                    })}
                  </div>
                ))}
              </div>

              <div>
                <label className="block font-semibold text-gray-700 mb-1">{l('Additional Feedback & Suggestions', 'Saran & Masukan Tambahan')}</label>
                <textarea
                  rows={2}
                  value={evalFeedback}
                  onChange={(e) => setEvalFeedback(e.target.value)}
                  placeholder={l('Share your feedback to help improve future training sessions...', 'Bagikan masukan Anda untuk peningkatan pelatihan berikutnya...')}
                  className="w-full rounded-2xl border border-gray-200 p-3 text-xs focus:border-[#0067AC] focus:outline-none"
                ></textarea>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
              <button type="button" onClick={() => setEvalClass(null)} className="px-4 py-2 rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                {l('Cancel', 'Batal')}
              </button>
              <button
                type="submit"
                disabled={submitEvaluationMutation.isPending}
                className="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold disabled:opacity-50"
              >
                {submitEvaluationMutation.isPending ? l('Submitting...', 'Menyimpan...') : l('Submit Evaluation', 'Kirim Evaluasi')}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Modal: E-Commitment Letter */}
      {commitmentClass && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" onClick={() => setCommitmentClass(null)}></div>
          <form onSubmit={handleSignCommitmentSubmit} className="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <div className="flex items-center gap-2">
                <FileCheck2 className="w-5 h-5 text-[#0067AC]" />
                <h3 className="font-bold text-sm text-gray-900">{l('Training E-Commitment Letter', 'Surat Komitmen Elektronik Pelatihan')}</h3>
              </div>
              <button type="button" onClick={() => setCommitmentClass(null)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="p-4 rounded-2xl bg-[#f8fafc] border border-gray-200 text-xs text-gray-700 leading-relaxed space-y-2">
              <p className="font-bold text-[#002855]">{commitmentClass.program_title} ({commitmentClass.class_code})</p>
              <p>
                {l(
                  'I hereby commit to attend the scheduled training sessions on time, complete all pre-test, post-test, and action learning requirements, and apply the knowledge gained to support operational excellence at Bank Woori Saudara.',
                  'Saya menyatakan berkomitmen untuk mengikuti seluruh sesi pelatihan tepat waktu, menyelesaikan Tes Awal, Tes Akhir, serta Rencana Aksi Pembelajaran, dan menerapkan kompetensi yang diperoleh untuk mendukung kinerja di Bank Woori Saudara.'
                )}
              </p>
              <p className="font-mono text-[11px] text-gray-500 pt-1">
                {currentUser?.name} ({currentUser?.nik}) • {currentUser?.department}
              </p>
            </div>

            {commitmentClass.commitment_signed_at ? (
              <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                {l('Digitally signed at:', 'Ditandatangani secara elektronik pada:')} {commitmentClass.commitment_signed_at.substring(0, 19).replace('T', ' ')}
              </div>
            ) : (
              <label className="flex items-start gap-2 text-xs text-gray-700 cursor-pointer">
                <input
                  type="checkbox"
                  checked={commitmentAgreed}
                  onChange={(e) => setCommitmentAgreed(e.target.checked)}
                  className="mt-0.5 rounded text-[#0067AC]"
                  required
                />
                <span>{l('I read and digitally approve this training commitment statement.', 'Saya membaca dan menyetujui pernyataan komitmen pelatihan ini secara elektronik.')}</span>
              </label>
            )}

            <div className="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
              <button type="button" onClick={() => setCommitmentClass(null)} className="px-4 py-2 rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                {l('Close', 'Tutup')}
              </button>
              {!commitmentClass.commitment_signed_at && (
                <button
                  type="submit"
                  disabled={!commitmentAgreed || signCommitmentMutation.isPending}
                  className="px-5 py-2 rounded-full bg-[#0067AC] hover:bg-[#00528c] text-white text-xs font-semibold disabled:opacity-50"
                >
                  {signCommitmentMutation.isPending ? l('Signing...', 'Menandatangani...') : l('Approve & Sign', 'Setujui & Tandatangani')}
                </button>
              )}
            </div>
          </form>
        </div>
      )}

      {/* Modal: Course Materials */}
      {materialsClass && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs" onClick={() => setMaterialsClass(null)}></div>
          <div className="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 flex flex-col gap-4 z-10">
            <div className="flex items-center justify-between pb-3 border-b border-gray-100">
              <h3 className="font-bold text-sm text-gray-900">{l('Training Modules & Resources', 'Modul & Materi Pelatihan')}</h3>
              <button type="button" onClick={() => setMaterialsClass(null)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <X className="w-4 h-4" />
              </button>
            </div>
            <div className="space-y-2 text-xs">
              {materialsClass.materials.map((mat, idx) => (
                <a
                  key={idx}
                  href={mat.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="p-3 rounded-2xl border border-gray-200 hover:border-[#0067AC] flex items-center justify-between gap-2 transition"
                >
                  <div>
                    <p className="font-bold text-gray-900">{mat.title}</p>
                    <p className="text-[10px] text-gray-400 uppercase">{mat.type}</p>
                  </div>
                  <Download className="w-4 h-4 text-[#0067AC] shrink-0" />
                </a>
              ))}
            </div>
          </div>
        </div>
      )}

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
              {selectedCert.final_score !== null && (
                <p className="text-xs font-mono text-emerald-700 font-bold">
                  {l('Final Score:', 'Nilai Akhir:')} {selectedCert.final_score} ({selectedCert.grade || 'C'})
                </p>
              )}

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
