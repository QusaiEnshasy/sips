const jsonServer = require('json-server')
const path = require('path')

const server = jsonServer.create()
const router = jsonServer.router(path.join(__dirname, 'db.json'))
const middlewares = jsonServer.defaults()

server.use(middlewares)
server.use(jsonServer.bodyParser)

// نقطة تسجيل الدخول المخصصة
server.post('/api/login', (req, res) => {
  console.log('📝 Login request:', req.body)

  const { email, password, student_id, employee_id } = req.body
  const db = router.db.getState()

  let user = null

  // البحث حسب المعرف
  if (student_id) {
    user = db.users.find(u => u.student_id === student_id && u.password === password)
    console.log('🔍 Searching by student_id:', student_id, 'Found:', !!user)
  } else if (employee_id) {
    user = db.users.find(u => u.employee_id === employee_id && u.password === password)
    console.log('🔍 Searching by employee_id:', employee_id, 'Found:', !!user)
  } else if (email) {
    user = db.users.find(u => u.email === email && u.password === password)
    console.log('🔍 Searching by email:', email, 'Found:', !!user)
  }

  if (user) {
    console.log('✅ Login successful for:', user.name)
    res.json({
      status: 'success',
      data: {
        token: 'fake-jwt-token-' + user.id,
        user: { ...user, password: undefined }
      }
    })
  } else {
    console.log('❌ Login failed for:', req.body)
    res.status(401).json({
      status: 'error',
      message: 'بيانات الدخول غير صحيحة'
    })
  }
})

// نقطة تسجيل الخروج
server.post('/api/logout', (req, res) => {
  res.json({ status: 'success', message: 'Logged out' })
})

// نقطة للوحة تحكم المدير
server.get('/api/admin/dashboard', (req, res) => {
  res.json({
    status: 'success',
    data: {
      main_stats: [],
      recent_activities: [],
      pending_companies: [],
      recent_users: [],
      system_alerts: [],
      chart_data: { months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], users: [120, 135, 142, 158, 175, 190], programs: [45, 52, 58, 63, 71, 78] }
    }
  })
})

// نقطة للمشرف
server.get('/api/supervisor/dashboard', (req, res) => {
  res.json({
    status: 'success',
    data: {
      stats: { total_students: 24, pending_tasks: 8, completed_tasks: 156 },
      recent_students: []
    }
  })
})

// نقطة للطالب
server.get('/api/student/dashboard', (req, res) => {
  res.json({
    status: 'success',
    data: {
      stats: { total_programs: 1, total_tasks: 24, hours_left: 44 },
      weekly_tasks: []
    }
  })
})

// نقطة للشركة
server.get('/api/company/dashboard', (req, res) => {
  res.json({
    status: 'success',
    data: {
      stats: { total_programs: 5, active_students: 12, pending_applications: 3 },
      recent_programs: []
    }
  })
})

// باقي الطلبات
server.use('/api', router)

const PORT = 8000
server.listen(PORT, () => {
  console.log(`🚀 Mock backend running at http://localhost:${PORT}`)
  console.log(`📋 API endpoints:`)
  console.log(`   POST /api/login`)
  console.log(`   GET  /api/users`)
  console.log(`   GET  /api/admin/dashboard`)
})
