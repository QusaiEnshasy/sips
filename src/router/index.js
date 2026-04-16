import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // ========== مسار تسجيل الدخول الموحد ==========
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { requiresAuth: false }
  },

  // ========== إعادة توجيه المسارات القديمة إلى المسار الموحد ==========
  {
    path: '/student/login',
    redirect: '/login'
  },
  {
    path: '/supervisor/login',
    redirect: '/login'
  },
  {
    path: '/company/login',
    redirect: '/login'
  },
  {
    path: '/admin/login',
    redirect: '/login'
  },

  // ========== مسارات المصادقة الأخرى ==========
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/CreateAccount.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/company/register',
    name: 'CompanyRegister',
    component: () => import('@/views/auth/CompanyRegistration.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/change-password',
    name: 'ChangePassword',
    component: () => import('@/views/auth/ChangePassword.vue'),
    meta: { requiresAuth: true }
  },

  // ========== مسارات المدير (Admin) ==========
  {
    path: '/admin',
    redirect: '/admin/dashboard',
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('@/views/admin/AdminDashboard.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: () => import('@/views/admin/Users.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/companies',
    name: 'AdminCompanies',
    component: () => import('@/views/admin/Companies.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/reports',
    name: 'AdminReports',
    component: () => import('@/views/admin/Reports.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/add-supervisor',
    name: 'AddSupervisor',
    component: () => import('@/views/admin/AddSupervisor.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/tasks/workspace',
    name: 'AdminGeneralTasks',
    component: () => import('@/views/admin/AdminGeneralTasks.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },

  // ========== مسارات المشرف (Supervisor) ==========
  {
    path: '/supervisor',
    redirect: '/supervisor/dashboard',
    meta: { requiresAuth: true, role: 'supervisor' }
  },
  {
    path: '/supervisor/dashboard',
    name: 'SupervisorDashboard',
    component: () => import('@/views/supervisor/SupervisorDashboard.vue'),
    meta: { requiresAuth: true, role: 'supervisor' }
  },
  {
    path: '/supervisor/students',
    name: 'SupervisorStudents',
    component: () => import('@/views/supervisor/Students.vue'),
    meta: { requiresAuth: true, role: 'supervisor' }
  },
  {
    path: '/supervisor/student/:id',
    name: 'StudentFullDetails',
    component: () => import('@/views/supervisor/StudentFullDetails.vue'),
    meta: { requiresAuth: true, role: 'supervisor' }
  },
  {
    path: '/supervisor/weekly-tasks',
    name: 'WeeklyTasksMonitoring',
    component: () => import('@/views/supervisor/WeeklyTasksMonitoring.vue'),
    meta: { requiresAuth: true, role: 'supervisor' }
  },
  {
    path: '/supervisor/reports',
    name: 'TrainerReports',
    component: () => import('@/views/supervisor/TrainerReports.vue'),
    meta: { requiresAuth: true, role: 'supervisor' }
  },
  {
    path: '/supervisor/evaluations',
    name: 'Evaluations',
    component: () => import('@/views/supervisor/Evaluations.vue'),
    meta: { requiresAuth: true, role: 'supervisor' }
  },

  // ========== مسارات الطالب (Student) ==========
  {
    path: '/student',
    redirect: '/student/dashboard',
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/dashboard',
    name: 'StudentDashboard',
    component: () => import('@/views/student/StudentDashboard.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/workspace',
    name: 'TrainingWorkspace',
    component: () => import('@/views/student/TrainingWorkspace.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/application-status',
    name: 'ApplicationStatus',
    component: () => import('@/views/student/ApplicationStatus.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/browse-programs',
    name: 'BrowseTrainingPrograms',
    component: () => import('@/views/student/BrowseTrainingPrograms.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/program/:id',
    name: 'TrainingProgramDetails',
    component: () => import('@/views/student/TrainingProgramDetails.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/submit-task/:taskId',
    name: 'SubmitWeeklyTask',
    component: () => import('@/views/student/SubmitWeeklyTask.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/accreditation',
    name: 'FinalAccreditationStatus',
    component: () => import('@/views/student/FinalAccreditationStatus.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/jisr',
    name: 'JisrProgram',
    component: () => import('@/views/student/JisrProgram.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/skill-test',
    name: 'SkillTest',
    component: () => import('@/views/student/SkillTest.vue'),
    meta: { requiresAuth: true, role: 'student' }
  },

  // ========== مسارات الشركة (Company) ==========
  {
    path: '/company',
    redirect: '/company/dashboard',
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/dashboard',
    name: 'CompanyDashboard',
    component: () => import('@/views/company/CompanyDashboard.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/programs',
    name: 'CompanyPrograms',
    component: () => import('@/views/company/Programs.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/programs/create',
    name: 'CreateProgram',
    component: () => import('@/views/company/CreateProgram.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/programs/:id',
    name: 'ProgramDetails',
    component: () => import('@/views/company/ProgramDetails.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/programs/:id/edit',
    name: 'EditProgram',
    component: () => import('@/views/company/CreateProgram.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/applicants',
    name: 'CompanyApplicants',
    component: () => import('@/views/company/Applicants.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/applicants/:id',
    name: 'ApplicantDetails',
    component: () => import('@/views/company/ApplicantDetails.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/reports',
    name: 'CompanyReports',
    component: () => import('@/views/company/Reports.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },
  {
    path: '/company/trello-settings',
    name: 'TrelloSettings',
    component: () => import('@/views/company/TrelloSettings.vue'),
    meta: { requiresAuth: true, role: 'company' }
  },

  // ========== المسارات المشتركة ==========
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/shared/Profile.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('@/views/shared/Notifications.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/submission-review/:id',
    name: 'SubmissionReview',
    component: () => import('@/views/shared/SubmissionReview.vue'),
    meta: { requiresAuth: true }
  },

  // ========== مسار الصفحة غير الموجودة ==========
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/errors/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

const getStudentGatePath = (user) => {
  if (!user || user.role !== 'student') return null

  if (user.is_in_jisr) {
    return '/student/jisr'
  }

  if (user.skill_test_required && !user.skill_test_passed) {
    return '/student/skill-test'
  }

  return null
}

const isStudentAllowedDuringGate = (path) => {
  return [
    '/student/skill-test',
    '/student/jisr',
    '/notifications',
    '/profile',
    '/change-password'
  ].some((allowedPath) => path === allowedPath || path.startsWith(allowedPath + '/'))
}

// ========== حماية المسارات (Navigation Guard) ==========
router.beforeEach(async (to, from, next) => {
  // استيراد الـ store ديناميكياً لتجنب مشاكل التحميل
  const { useAuthStore } = await import('@/stores/auth')
  const authStore = useAuthStore()

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiredRole = to.meta.role

  // إذا كان المسار يتطلب مصادقة
  if (requiresAuth) {
    // التحقق من المصادقة
    if (!authStore.isAuthenticated) {
      // غير مسجل دخول - التوجيه لتسجيل الدخول
      next('/login')
    }
    // التحقق من الدور المطلوب
    else if (requiredRole && authStore.userType !== requiredRole) {
      // دور غير مناسب - التوجيه للوحة المناسبة
      const userRole = authStore.userType
      if (!userRole || !['admin', 'student', 'supervisor', 'company'].includes(userRole)) {
        await authStore.logout()
        next('/login')
      } else {
        const redirectPath = getStudentGatePath(authStore.user) || `/${userRole}/dashboard`
        next(redirectPath)
      }
    }
    else {
      const studentGatePath = getStudentGatePath(authStore.user)
      if (studentGatePath && !isStudentAllowedDuringGate(to.path)) {
        next(studentGatePath)
      } else {
        next()
      }
    }
  }
  // صفحات عامة (لا تتطلب مصادقة)
  else {
    // إذا كان المستخدم مسجل دخول ويحاول الدخول لصفحة تسجيل الدخول أو التسجيل
    next()
  }
})

export default router
