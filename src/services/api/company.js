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
  getTrelloAuthorizeUrl: () => webApi.get('/company/trello/authorize'),
  completeTrelloAuthorization: (data) => webApi.post('/company/trello/oauth/callback', data),
  completeTrelloPinAuthorization: (data) => webApi.post('/company/trello/oauth/pin', data),
  getTrelloSettings: () => webApi.get('/company/trello/settings'),
  saveTrelloSettings: (data) => webApi.post('/company/trello/settings', data),
  testTrelloConnection: () => webApi.post('/company/trello/test'),
  getTrelloBoards: () => webApi.get('/company/trello/boards'),
  getTrelloLists: (boardId) => webApi.get(`/company/trello/boards/${boardId}/lists`),
  getTrelloIntegrations: () => webApi.get('/company/trello/integrations'),
  getTrelloSyncLogs: () => webApi.get('/company/trello/sync-logs'),
  connectTrelloBoard: (internshipId, data) => webApi.post(`/company/trello/internships/${internshipId}/connect`, data),
  syncTrello: (internshipId) => webApi.post(`/company/trello/internships/${internshipId}/sync`),
  unlinkTrelloInternship: (internshipId) => webApi.delete(`/company/trello/internships/${internshipId}/unlink`),
  disconnectTrello: () => webApi.delete('/company/trello/disconnect')
}
