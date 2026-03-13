import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/RegisterView.vue'),
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
  },
  {
    path: '/reset-password/:token',
    name: 'ResetPassword',
    component: () => import('@/views/auth/ResetPasswordView.vue'),
  },
  {
    path: '/dashboard',
    component: () => import('@/views/dashboard/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
      },
      {
        path: '/students',
        name: 'Students',
        component: () => import('@/views/students/StudentListView.vue'),
        meta: { roles: ['admin', 'staff', 'teacher'] },
      },
      {
        path: '/students/:id',
        name: 'StudentDetail',
        component: () => import('@/views/students/StudentDetailView.vue'),
        meta: { roles: ['admin', 'staff', 'teacher'] },
      },
      {
        path: '/classes',
        name: 'Classes',
        component: () => import('@/views/academic/ClassListView.vue'),
        meta: { roles: ['admin', 'staff'] },
      },
      {
        path: '/attendance',
        name: 'Attendance',
        component: () => import('@/views/academic/AttendanceView.vue'),
        meta: { roles: ['admin', 'teacher'] },
      },
      {
        path: '/grades',
        name: 'Grades',
        component: () => import('@/views/academic/GradesView.vue'),
        meta: { roles: ['admin', 'teacher'] },
      },
      {
        path: '/fees',
        name: 'Fees',
        component: () => import('@/views/financial/FeesView.vue'),
        meta: { roles: ['admin', 'staff'] },
      },
      {
        path: '/payments',
        name: 'Payments',
        component: () => import('@/views/financial/PaymentsView.vue'),
        meta: { roles: ['admin', 'staff', 'parent', 'student'] },
      },
      {
        path: '/announcements',
        name: 'Announcements',
        component: () => import('@/views/communication/AnnouncementsView.vue'),
      },
      {
        path: '/messages',
        name: 'Messages',
        component: () => import('@/views/communication/MessagesView.vue'),
      },
      {
        path: '/profile',
        name: 'Profile',
        component: () => import('@/views/profile/ProfileView.vue'),
      },
      {
        path: '/settings',
        name: 'Settings',
        component: () => import('@/views/admin/SettingsView.vue'),
        meta: { roles: ['admin', 'super_admin'] },
      },
      // Super Admin Routes
      {
        path: '/super-admin/schools',
        name: 'SuperAdminSchools',
        component: () => import('@/views/super-admin/SchoolsView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/modules',
        name: 'SuperAdminModules',
        component: () => import('@/views/super-admin/ModulesView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/subscriptions',
        name: 'SuperAdminSubscriptions',
        component: () => import('@/views/super-admin/SubscriptionsView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/subscriptions/:id',
        name: 'SuperAdminSubscriptionDetail',
        component: () => import('@/views/super-admin/SubscriptionDetailView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/plans',
        name: 'SuperAdminPlans',
        component: () => import('@/views/super-admin/PlansView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/plans/:id/modules',
        name: 'SuperAdminPlanModules',
        component: () => import('@/views/super-admin/PlanModulesView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/invoices',
        name: 'SuperAdminInvoices',
        component: () => import('@/views/super-admin/InvoicesView.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/super-admin/invoices/:id',
        name: 'SuperAdminInvoiceDetail',
        component: () => import('@/views/super-admin/InvoiceDetailView.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const isAuthenticated = !!token

  // Get user role from stored user data
  const userData = localStorage.getItem('user')
  const userRole = userData ? JSON.parse(userData).role?.name : null
  const isSuperAdmin = userData ? JSON.parse(userData).is_super_admin : false

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.meta.roles && !isSuperAdmin && (!userRole || !to.meta.roles.includes(userRole))) {
    // Super admins can access all routes
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
