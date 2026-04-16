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

export const adminAPI = {
  getDashboard: () => webApi.get('/admin/dashboard'),
  getUsers: () => webApi.get('/admin/users'),
  getCompanies: () => webApi.get('/admin/companies'),
  getReports: () => webApi.get('/admin/reports/data'),
  createSupervisor: (data) => webApi.post('/admin/supervisors', data),

  approveSupervisor: (id) => webApi.post(`/admin/supervisors/${id}/status`, { action: 'active' }),
  rejectSupervisor: (id) => webApi.post(`/admin/supervisors/${id}/status`, { action: 'reject' }),
  deleteSupervisor: (id) => webApi.post(`/admin/supervisors/${id}/status`, { action: 'delete' }),

  approveCompany: (id) => webApi.post(`/admin/companies/${id}/status`, { action: 'active' }),
  rejectCompany: (id) => webApi.post(`/admin/companies/${id}/status`, { action: 'reject' }),
  deleteCompany: (id) => webApi.post(`/admin/companies/${id}/status`, { action: 'delete' })
}
