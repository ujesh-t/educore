import api from './api'

export default {
  async login(credentials) {
    const response = await api.post('/auth/login', credentials)
    return response.data
  },

  async register(userData) {
    const response = await api.post('/auth/register', userData)
    return response.data
  },

  async logout() {
    const response = await api.post('/auth/logout')
    return response.data
  },

  async me() {
    const response = await api.get('/auth/me')
    return response.data
  },

  async resetPassword(email) {
    const response = await api.post('/auth/password/reset', { email })
    return response.data
  },

  async confirmPasswordReset(token, email, password) {
    const response = await api.post('/auth/password/confirm', {
      token,
      email,
      password,
      password_confirmation: password,
    })
    return response.data
  },
}
