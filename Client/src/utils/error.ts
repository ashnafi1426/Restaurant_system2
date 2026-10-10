export function getErrorMessage(
  error: unknown,
  fallbackMessage = 'An unexpected error occurred. Please try again.',
): string {
  if (!error) return fallbackMessage

  if (typeof error === 'string') return error

  if (typeof error === 'object' && error !== null) {
    const err = error as Record<string, any>
    if (
      typeof err.response?.data?.message === 'string' &&
      err.response.data.message.trim().length > 0
    ) {
      return err.response.data.message
    }
    if (
      typeof err.response?.data?.error === 'string' &&
      err.response.data.error.trim().length > 0
    ) {
      return err.response.data.error
    }

    if (err.response?.data?.errors && typeof err.response.data.errors === 'object') {
      const validationErrors = err.response.data.errors as Record<string, unknown>
      const firstField = Object.keys(validationErrors)[0]
      if (firstField) {
        const fieldErrors = validationErrors[firstField]
        if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
          return String(fieldErrors[0])
        }
        if (typeof fieldErrors === 'string') {
          return fieldErrors
        }
      }
    }

    const status = err.response?.status
    if (status === 401) {
      return 'You must be logged in to perform this action.'
    }
    if (status === 403) {
      return 'You do not have permission to perform this action.'
    }
    if (status === 404) {
      return 'The requested resource was not found.'
    }
    if (status === 429) {
      return 'Too many requests. Please slow down and try again shortly.'
    }
    if (status >= 500) {
      return 'The server encountered an error. Please try again later.'
    }

    if (typeof err.message === 'string' && err.message.length > 0) {
      if (err.message === 'Network Error' || err.code === 'ERR_NETWORK') {
        return 'Unable to connect to the server. Please check your internet connection.'
      }
      return err.message
    }
  }

  return fallbackMessage
}

/**
 * Extracts Laravel validation error map { [field: string]: string[] }
 */
export function getValidationErrors(error: unknown): Record<string, string[]> {
  if (typeof error === 'object' && error !== null) {
    const err = error as Record<string, any>
    if (err.response?.data?.errors && typeof err.response.data.errors === 'object') {
      return err.response.data.errors
    }
  }
  return {}
}

/**
 * Checks if the error was caused by authentication / authorization issues.
 */
export function isAuthError(error: unknown): boolean {
  if (typeof error === 'object' && error !== null) {
    const status = (error as Record<string, any>).response?.status
    return status === 401 || status === 403
  }
  return false
}

/**
 * Checks if the error was a network connectivity failure.
 */
export function isNetworkError(error: unknown): boolean {
  if (typeof error === 'object' && error !== null) {
    const err = error as Record<string, any>
    return err.code === 'ERR_NETWORK' || err.message === 'Network Error'
  }
  return false
}
