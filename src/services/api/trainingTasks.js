import axios from 'axios'

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const webApi = axios.create({
  baseURL: '',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf,
    Accept: 'application/json'
  },
  withCredentials: true
})

export const trainingTasksAPI = {
  getWorkspace: () => webApi.get('/workspace/tasks'),
  createTask: (data) => webApi.post('/workspace/tasks', data),
  submitTask: (taskId, data) => webApi.post(`/workspace/tasks/${taskId}/submit`, data, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
  gradeTask: (taskId, data) => webApi.post(`/workspace/tasks/${taskId}/grade`, data)
}
