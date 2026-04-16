import api from './api'

export const authAPI = {
  login: (credentials) => api.post('/login', credentials),
  logout: () => api.post('/logout'),
  register: (data) => api.post('/register', data),
  registerCompany: (data) => api.post('/company/register', data),
  forgotPassword: (email) => api.post('/forgot-password', { email }),
  resetPassword: (data) => api.post('/reset-password', data),
  changePassword: (data) => api.post('/change-password', data),
  verifyEmail: (token) => api.post('/email/verify', { token }),
  resendVerification: () => api.post('/email/resend'),
}