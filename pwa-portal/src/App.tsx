import React, { useState, useEffect } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import LoginPage from '@/pages/LoginPage';
import LearnerDashboard from '@/pages/LearnerDashboard';
import { LearnerUser } from '@/types/learner';
import { I18nProvider } from '@/i18n';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

export default function App() {
  const [user, setUser] = useState<LearnerUser | null>(() => {
    const savedUser = sessionStorage.getItem('bws_pwa_user');
    const savedToken = sessionStorage.getItem('bws_pwa_token');
    return savedUser && savedToken ? JSON.parse(savedUser) : null;
  });

  useEffect(() => {
    const onUnauthorized = () => {
      queryClient.clear();
      setUser(null);
    };
    window.addEventListener('bws-pwa-unauthorized', onUnauthorized);
    return () => window.removeEventListener('bws-pwa-unauthorized', onUnauthorized);
  }, []);

  return (
    <I18nProvider>
      <QueryClientProvider client={queryClient}>
        {user ? (
          <LearnerDashboard
            user={user}
            onLogout={() => {
              queryClient.clear();
              setUser(null);
            }}
          />
        ) : (
          <LoginPage
            onLoginSuccess={(loggedUser) => {
              setUser(loggedUser);
            }}
          />
        )}
      </QueryClientProvider>
    </I18nProvider>
  );
}

