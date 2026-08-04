import { defineStore } from 'pinia'
import { ref } from 'vue'
import { publicAxios } from '../services/axios'

interface User {
  first_name: string
  last_name: string
  email: string
  role: string
}

interface ValidationResult {
  success: boolean
  message: string
  error_type?: string
  user?: User
}

interface ActivationResult {
  success: boolean
  message: string
  error_type?: string
  user?: {
    id: string
    first_name: string
    last_name: string
    email: string
    role: string
  }
}

interface ResendResult {
  success: boolean
  message: string
}

export const useActivationStore = defineStore('activation', () => {
  // State
  const loading = ref(false)
  const validatingToken = ref(false)
  const activating = ref(false)
  const resending = ref(false)
  const user = ref<User | null>(null)
  const error = ref<string | null>(null)
  const errorType = ref<string | null>(null)

  
  async function validateToken(token: string): Promise<ValidationResult> {
    validatingToken.value = true
    error.value = null
    errorType.value = null

    try {
      const response = await publicAxios.get(`/activation/${token}`)

      if (response.data.success) {
        user.value = response.data.user
        return {
          success: true,
          message: response.data.message,
          user: response.data.user
        }
      }

      return {
        success: false,
        message: response.data.message || 'Invalid activation token'
      }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to validate token'
      const type = err.response?.data?.error_type || 'validation_error'

      error.value = message
      errorType.value = type

      return {
        success: false,
        message,
        error_type: type,
        user: err.response?.data?.user
      }
    } finally {
      validatingToken.value = false
    }
  }

  /**
   * Activate account with password
   */
  async function activateAccount(
    token: string,
    password: string,
    passwordConfirmation: string
  ): Promise<ActivationResult> {
    activating.value = true
    error.value = null
    errorType.value = null

    try {
      const response = await publicAxios.post('/activate-account', {
        token,
        password,
        password_confirmation: passwordConfirmation
      })

      if (response.data.success) {
        return {
          success: true,
          message: response.data.message,
          user: response.data.user
        }
      }

      return {
        success: false,
        message: response.data.message || 'Activation failed'
      }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to activate account'
      const type = err.response?.data?.error_type || 'activation_error'

      error.value = message
      errorType.value = type

      return {
        success: false,
        message,
        error_type: type
      }
    } finally {
      activating.value = false
    }
  }

  /**
   * Resend activation email
   */
  async function resendActivation(email: string): Promise<ResendResult> {
    resending.value = true
    error.value = null

    try {
      const response = await publicAxios.post('/resend-activation', { email })

      return {
        success: response.data.success,
        message: response.data.message
      }
    } catch (err: any) {
      const message = err.response?.data?.message || 'Failed to resend activation email'
      error.value = message

      return {
        success: false,
        message
      }
    } finally {
      resending.value = false
    }
  }

  /**
   * Check password strength
   */
  function checkPasswordStrength(password: string): {
    score: number
    label: string
    color: string
    feedback: string[]
  } {
    let score = 0
    const feedback: string[] = []

    // Length check
    if (password.length >= 8) {
      score++
    } else {
      feedback.push('At least 8 characters required')
    }

    // Uppercase check
    if (/[A-Z]/.test(password)) {
      score++
    } else {
      feedback.push('Add uppercase letters')
    }

    // Lowercase check
    if (/[a-z]/.test(password)) {
      score++
    } else {
      feedback.push('Add lowercase letters')
    }

    // Number check
    if (/[0-9]/.test(password)) {
      score++
    } else {
      feedback.push('Add numbers')
    }

    // Special character check
    if (/[^A-Za-z0-9]/.test(password)) {
      score++
    } else {
      feedback.push('Add special characters (!@#$%^&*)')
    }

    // Determine label and color
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

  /**
   * Reset store state
   */
  function resetState() {
    loading.value = false
    validatingToken.value = false
    activating.value = false
    resending.value = false
    user.value = null
    error.value = null
    errorType.value = null
  }

  return {
    // State
    loading,
    validatingToken,
    activating,
    resending,
    user,
    error,
    errorType,

    // Actions
    validateToken,
    activateAccount,
    resendActivation,
    checkPasswordStrength,
    resetState
  }
})
