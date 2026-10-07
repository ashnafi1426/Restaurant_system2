import { defineStore } from 'pinia'
import { ref } from 'vue'
import { publicAxios } from '@/services/axios'

export const usePasswordResetStore = defineStore('passwordReset', () => {
  const loading = ref(false)
  const sending = ref(false)
  const resetting = ref(false)
  const error = ref<string | null>(null)
  const successMessage = ref<string | null>(null)

  async function requestReset(email: string) {
    sending.value = true
    error.value = null
    successMessage.value = null

    try {
      const response = await publicAxios.post('/forgot-password', { email })

      if (response.data?.success) {
        successMessage.value = response.data.message
        return { success: true, message: response.data.message }
      }

      return { success: false, message: response.data?.message }
    } catch (err: any) {
      console.error('[passwordResetStore] Failed to send reset email:', err)
      const message = err.response?.data?.message || 'Failed to send reset email'
      error.value = message

      if (err.response?.data?.needs_activation) {
        return {
          success: false,
          message,
          needsActivation: true,
        }
      }

      return { success: false, message }
    } finally {
      sending.value = false
    }
  }

  async function resetPassword(email: string, token: string, password: string, passwordConfirmation: string) {
    resetting.value = true
    error.value = null
    successMessage.value = null

    try {
      const response = await publicAxios.post('/reset-password', {
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      })

      if (response.data?.success) {
        successMessage.value = response.data.message
        return { success: true, message: response.data.message }
      }

      return { success: false, message: response.data?.message }
    } catch (err: any) {
      console.error('[passwordResetStore] Failed to reset password:', err)
      const message = err.response?.data?.message || 'Failed to reset password'
      const errorType = err.response?.data?.error_type || 'reset_error'

      error.value = message

      return {
        success: false,
        message,
        errorType,
      }
    } finally {
      resetting.value = false
    }
  }

  async function verifyToken(email: string, token: string) {
    loading.value = true
    error.value = null

    try {
      const response = await publicAxios.post('/verify-reset-token', {
        email,
        token,
      })

      return { success: !!response.data?.success, message: response.data?.message }
    } catch (err: any) {
      console.error('[passwordResetStore] Failed to verify reset token:', err)
      const message = err.response?.data?.message || 'Invalid or expired token'
      error.value = message

      return { success: false, message }
    } finally {
      loading.value = false
    }
  }

  function checkPasswordStrength(password: string): {
    score: number
    label: string
    color: string
    feedback: string[]
  } {
    let score = 0
    const feedback: string[] = []

    if (password.length >= 8) {
      score++
    } else {
      feedback.push('At least 8 characters required')
    }

    if (/[A-Z]/.test(password)) {
      score++
    } else {
      feedback.push('Add uppercase letters')
    }

    if (/[a-z]/.test(password)) {
      score++
    } else {
      feedback.push('Add lowercase letters')
    }

    if (/[0-9]/.test(password)) {
      score++
    } else {
      feedback.push('Add numbers')
    }

    if (/[^A-Za-z0-9]/.test(password)) {
      score++
    } else {
      feedback.push('Add special characters (!@#$%^&*)')
    }

    let label = ''
    let color = ''

    if (score <= 2) {
      label = 'Weak'
      color = 'red'
    } else if (score <= 3) {
      label = 'Fair'
      color = 'orange'
    } else if (score <= 4) {
      label = 'Good'
      color = 'yellow'
    } else {
      label = 'Strong'
      color = 'green'
    }

    return { score, label, color, feedback }
  }

  function resetState() {
    loading.value = false
    sending.value = false
    resetting.value = false
    error.value = null
    successMessage.value = null
  }

  return {
    loading,
    sending,
    resetting,
    error,
    successMessage,

    requestReset,
    resetPassword,
    verifyToken,
    checkPasswordStrength,
    resetState,
  }
})
