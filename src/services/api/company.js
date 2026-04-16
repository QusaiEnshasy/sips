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

export const companyAPI = {
  getDashboard: () => webApi.get('/company/dashboard'),
  getPrograms: (params) => webApi.get('/company/programs', { params }),
  getProgram: (id) => webApi.get(`/company/programs/${id}`),
  createProgram: (data) => webApi.post('/company/programs', data),
  updateProgram: (id, data) => webApi.post(`/company/programs/${id}`, data),
  deleteProgram: (id) => webApi.post(`/company/programs/${id}/delete`),
  duplicateProgram: async (id) => {
    const base = await webApi.get(`/company/programs/${id}`)
    const payload = { ...(base.data?.data || {}) }
    delete payload.id
    payload.title = `${payload.title || 'Program'} (Copy)`
    return webApi.post('/company/programs', payload)
  },
  getApplicants: (params) => webApi.get('/company/applicants', { params }),
  getApplicant: (id) => webApi.get(`/company/applicants/${id}`),
  acceptApplicant: (id) => webApi.post(`/company/applications/${id}/approve`),
  rejectApplicant: (id, data) => webApi.post(`/company/applications/${id}/reject`, data || {}),
  getReports: () => webApi.get('/company/reports/data'),
  getTrelloSettings: () => Promise.resolve({ data: { status: 'success', data: {} } }),
  saveTrelloSettings: (data) => Promise.resolve({ data: { status: 'success', data } })
}
