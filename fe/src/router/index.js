import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import LoginView from '@/views/LoginView.vue';
import DashboardView from '@/views/DashboardView.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true },
    },
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'dashboard' },
    },
    {
      path: '/tna',
      name: 'tna',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'tna', view: 'list' },
    },
    {
      path: '/tna/create',
      name: 'tna-create',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'tna', view: 'create' },
    },
    {
      path: '/tna/categories',
      name: 'tna-categories',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'tna', view: 'categories' },
    },
    {
      path: '/tna/years',
      name: 'tna-years',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'tna', view: 'years' },
    },
    {
      path: '/tna/:id',
      name: 'tna-detail',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'tna', view: 'detail' },
    },
    {
      path: '/tna/:id/edit',
      name: 'tna-edit',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'tna', view: 'edit' },
    },
    {
      path: '/program/:view?',
      name: 'program',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'program' },
    },
    {
      path: '/class/:view?',
      name: 'class',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'class' },
    },
    {
      path: '/participants',
      name: 'participants',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'participants' },
    },
    {
      path: '/eval',
      name: 'eval',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'eval' },
    },
    {
      path: '/task',
      name: 'task',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'task' },
    },
    {
      path: '/memo',
      name: 'memo',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'memo' },
    },
    {
      path: '/transaction',
      name: 'transaction',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'transaction' },
    },
    {
      path: '/certification',
      name: 'certification',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'certification' },
    },
    {
      path: '/reporting',
      name: 'reporting',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'reporting' },
    },
    {
      path: '/rbac/:subTab?',
      name: 'rbac',
      component: DashboardView,
      meta: { requiresAuth: true, tab: 'rbac' },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/dashboard',
    },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  if (auth.token && !auth.user) {
    await auth.checkSession();
  }
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' };
  }
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }
  return true;
});

export default router;

