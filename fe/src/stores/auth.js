import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '@/api/client';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(sessionStorage.getItem('bws_fe_user') || 'null'));
  const token = ref(sessionStorage.getItem('bws_fe_token') || '');

  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  function setSession(userData, newToken) {
    user.value = userData;
    token.value = newToken;
    sessionStorage.setItem('bws_fe_user', JSON.stringify(userData));
    sessionStorage.setItem('bws_fe_token', newToken);
  }

  function clearSession() {
    user.value = null;
    token.value = '';
    sessionStorage.removeItem('bws_fe_user');
    sessionStorage.removeItem('bws_fe_token');
  }

  async function login(email, password) {
    const { data } = await apiClient.post('/auth/login', { email, password });
    setSession(data.user, data.token);
    return data;
  }

  async function switchRole(roleName) {
    const { data } = await apiClient.post('/auth/switch-role', { role_name: roleName });
    setSession(data.user, data.token);
    return data;
  }

  async function logout() {
    try {
      await apiClient.post('/auth/logout');
    } finally {
      clearSession();
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    setSession,
    clearSession,
    login,
    switchRole,
    logout,
  };
});

