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
    mutationFn: async ({ enrollmentId, notes }: { enrollmentId: number; notes?: string }) => {
      const { data } = await apiClient.post(`/enrollments/${enrollmentId}/submit-task`, { notes });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['learner-portal'] });
    },
  });
}

