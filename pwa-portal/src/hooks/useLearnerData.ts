import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiClient from '@/api/client';
import { LearnerPortalResponse } from '@/types/learner';

export function useLearnerPortal(enabled: boolean) {
  return useQuery<LearnerPortalResponse>({
    queryKey: ['learner-portal'],
    queryFn: async () => {
      const { data } = await apiClient.get<LearnerPortalResponse>('/portal/learner');
      return data;
    },
    enabled,
    staleTime: 30 * 1000,
    refetchOnWindowFocus: true,
  });
}

export function useSubmitTask() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      enrollmentId,
      notes,
      file,
    }: {
      enrollmentId: number;
      notes?: string;
      file?: File | null;
    }) => {
      const formData = new FormData();
      if (notes) {
        formData.append('notes', notes);
      }
      if (file) {
        formData.append('file', file);
      }
      const { data } = await apiClient.post(`/enrollments/${enrollmentId}/submit-task`, formData);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['learner-portal'] });
    },
  });
}

export function useSubmitLeaveConfirmation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      enrollmentId,
      leave_status,
      leave_notes,
      attachment,
    }: {
      enrollmentId: number;
      leave_status: string;
      leave_notes?: string;
      attachment?: File | null;
    }) => {
      const formData = new FormData();
      formData.append('leave_status', leave_status);
      if (leave_notes) {
        formData.append('leave_notes', leave_notes);
      }
      if (attachment) {
        formData.append('attachment', attachment);
      }
      const { data } = await apiClient.post(`/portal/enrollments/${enrollmentId}/leave-confirmation`, formData);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['learner-portal'] });
    },
  });
}

export function useSubmitCourseEvaluation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      enrollmentId,
      material_scores,
      instructor_scores,
      committee_scores,
      feedback_notes,
    }: {
      enrollmentId: number;
      material_scores: number[];
      instructor_scores: Array<{ slot: string; name: string; scores: number[] }>;
      committee_scores: number[];
      feedback_notes?: string;
    }) => {
      const { data } = await apiClient.post(`/portal/enrollments/${enrollmentId}/evaluation`, {
        material_scores,
        instructor_scores,
        committee_scores,
        feedback_notes,
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['learner-portal'] });
    },
  });
}

export function useSignCommitment() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ enrollmentId }: { enrollmentId: number }) => {
      const { data } = await apiClient.post(`/portal/enrollments/${enrollmentId}/commitment`, {
        agreed: true,
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['learner-portal'] });
    },
  });
}
