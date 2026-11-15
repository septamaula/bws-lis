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

export interface EnrolledClass {
  id: number;
  program_title: string;
  program_code: string;
  category: string;
  batch_name: string;
  class_code: string;
  start_date: string;
  end_date: string;
  location: string;
  facilitator_name: string;
  elapse_progress_pct: number;
  completion_status: 'NOT_STARTED' | 'IN_PROGRESS' | 'PASSED' | 'FAILED';
  pre_test_score: number | null;
  post_test_score: number | null;
  final_score: number | null;
  certificate_number: string;
}

export interface LearnerTask {
  id: number;
  title: string;
  class_title: string;
  deadline: string;
  submitted_file: string | null;
  status: 'PENDING' | 'SUBMITTED' | 'GRADED';
  grade: number | null;
}

export interface LearnerPortalResponse {
  user: LearnerUser;
  kpis: LearnerKpis;
  enrolledClasses: EnrolledClass[];
  tasks: LearnerTask[];
}

