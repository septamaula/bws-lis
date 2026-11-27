export interface LearnerUser {
  id: number;
  nik: string;
  name: string;
  email: string;
  role_name: string;
  department: string;
  branch: string;
}

export interface LearnerKpis {
  completed_hours: number;
  target_hours: number;
  enrolled_classes_count: number;
  active_certificates_count: number;
  pending_tasks_count: number;
}

export interface ClassInstructor {
  slot: string;
  name: string;
}

export interface ClassMaterialItem {
  title: string;
  url: string;
  type: string;
}

export interface ClassEvaluationDetail {
  evaluation_code: string | null;
  material_scores: number[] | null;
  instructor_scores: Array<{
    slot: string;
    name: string;
    scores: number[];
    avg: number;
  }> | null;
  committee_scores: number[] | null;
  material_avg: number | null;
  instructor_avg: number | null;
  committee_avg: number | null;
  overall_avg: number | null;
  feedback_notes: string | null;
}

export interface EvaluationQuestionItem {
  code: string;
  label_en: string;
  label_id: string;
}

export interface EvaluationCatalog {
  scale_min: number;
  scale_max: number;
  material: EvaluationQuestionItem[];
  instructor: EvaluationQuestionItem[];
  committee: EvaluationQuestionItem[];
}

export interface EnrolledClass {
  id: number;
  training_class_id: number;
  participant_code?: string | null;
  program_title: string;
  program_code: string;
  category: string;
  batch_name: string;
  class_code: string;
  start_date: string;
  end_date: string;
  location: string;
  facilitator_name: string;
  instructors: ClassInstructor[];
  materials: ClassMaterialItem[];
  passing_grade: number;
  elapse_progress_pct: number;
  completion_status: 'NOT_STARTED' | 'ONGOING' | 'IN_PROGRESS' | 'PASSED' | 'FAILED' | 'CANCELLED';
  grade: string | null;
  pre_test_score: number | null;
  post_test_score: number | null;
  final_score: number | null;
  attendance_percentage: number;
  attendance_status: string | null;
  attendance_remark: string | null;
  invitation_point: number;
  point_add: number;
  leave_status: string | null;
  leave_notes: string | null;
  leave_attachment_url: string | null;
  commitment_signed_at: string | null;
  has_evaluated: boolean;
  evaluation: ClassEvaluationDetail | null;
  certificate_number: string | null;
}

export interface LearnerTask {
  id: number;
  title: string;
  class_title: string;
  deadline: string;
  submitted_file: string | null;
  status: 'PENDING' | 'SUBMITTED' | 'GRADED';
  grade: number | null;
  feedback?: string | null;
}

export interface LearnerPortalResponse {
  user: LearnerUser;
  is_preview_mode?: boolean;
  kpis: LearnerKpis;
  enrolledClasses: EnrolledClass[];
  tasks: LearnerTask[];
  evaluation_catalog: EvaluationCatalog;
}
