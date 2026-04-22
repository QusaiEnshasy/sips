import axios from 'axios'

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const webApi = axios.create({
  baseURL: '',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf,
    'Accept': 'application/json'
  },
  withCredentials: true
})

export const supervisorAPI = {
  getDashboard: () => webApi.get('/supervisor/dashboard/data'),
  getStudents: () => webApi.get('/supervisor/students'),
  createStudent: (data) => webApi.post('/supervisor/students', data),
  getStudent: (id) => webApi.get(`/supervisor/student/${id}`),
  getJisrReviews: () => webApi.get('/supervisor/jisr-reviews/data'),
  reviewJisrSubmission: (id, data) => webApi.post(`/supervisor/jisr-reviews/${id}/review`, data),
  getWeeklyTasks: () => webApi.get('/supervisor/weekly-tasks'),
  broadcastTask: (data) => webApi.post('/supervisor/tasks/broadcast', data),
  getApplications: () => webApi.get('/supervisor/applications'),
  getApplication: (id) => webApi.get(`/supervisor/applications/${id}`),
  approveStudentActivation: (id) => webApi.post(`/supervisor/students/${id}/approve`),
  rejectStudentActivation: (id, data) => webApi.post(`/supervisor/students/${id}/reject`, data || {}),
  deleteStudent: (id) => webApi.post(`/supervisor/students/${id}/delete`),
  approveApplication: (id) => webApi.post(`/supervisor/applications/${id}/approve`),
  rejectApplication: (id, data) => webApi.post(`/supervisor/applications/${id}/reject`, data || {}),
  completeTraining: (id, data) => webApi.post(`/supervisor/applications/${id}/complete-training`, data),
  getReports: () => webApi.get('/supervisor/reports/data'),
  getEvaluations: () => Promise.resolve({ data: { status: 'success', data: { evaluations: [] } } }),
  getInternships: () => Promise.resolve({ data: { status: 'success', data: [] } }),
  createEvaluation: () => Promise.resolve({ data: { status: 'success' } }),
  updateEvaluation: () => Promise.resolve({ data: { status: 'success' } }),
  deleteEvaluation: () => Promise.resolve({ data: { status: 'success' } })
}
