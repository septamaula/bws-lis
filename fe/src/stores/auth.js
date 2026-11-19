import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import apiClient from '@/api/client';

export const useAuthStore = defineStore('auth', () => {
  const getStoredToken = () =>
    localStorage.getItem('bws_fe_token') || sessionStorage.getItem('bws_fe_token') || '';

  const getStoredUser = () => {
    const raw = localStorage.getItem('bws_fe_user') || sessionStorage.getItem('bws_fe_user');
    try {
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  };

  const user = ref(getStoredUser());
  const token = ref(getStoredToken());

  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  function setSession(userData, newToken, rememberMe = true) {
    user.value = userData;
    token.value = newToken;

    // Clear old keys to avoid mismatch
    localStorage.removeItem('bws_fe_user');
    localStorage.removeItem('bws_fe_token');
    sessionStorage.removeItem('bws_fe_user');
    sessionStorage.removeItem('bws_fe_token');

    const storage = rememberMe ? localStorage : sessionStorage;
    storage.setItem('bws_fe_user', JSON.stringify(userData));
    storage.setItem('bws_fe_token', newToken);
  }

  function clearSession() {
    user.value = null;
    token.value = '';
    localStorage.removeItem('bws_fe_user');
    localStorage.removeItem('bws_fe_token');
    sessionStorage.removeItem('bws_fe_user');
    sessionStorage.removeItem('bws_fe_token');
  }

  async function login(email, password, rememberMe = true) {
    const { data } = await apiClient.post('/auth/login', { email, password });
    setSession(data.user, data.token, rememberMe);
    return data;
  }

  async function checkSession() {
    if (!token.value) {
      clearSession();
      return null;
    }
    try {
      const { data } = await apiClient.get('/auth/me');
      if (data.user) {
        user.value = data.user;
        const isPersisted = Boolean(localStorage.getItem('bws_fe_token'));
        const storage = isPersisted ? localStorage : sessionStorage;
        storage.setItem('bws_fe_user', JSON.stringify(data.user));
        return data.user;
      }
    } catch {
      clearSession();
      return null;
    }
  }

  async function switchRole(roleName) {
    const { data } = await apiClient.post('/auth/switch-role', { role_name: roleName });
    const isPersisted = Boolean(localStorage.getItem('bws_fe_token'));
    setSession(data.user, data.token, isPersisted);
    return data;
  }

  async function logout() {
    try {
      await apiClient.post('/auth/logout');
    } catch (err) {
      console.warn('Logout API failed:', err);
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
    checkSession,
    switchRole,
    logout,
  };
});

