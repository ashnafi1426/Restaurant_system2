import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import floorManagementService, { type Floor } from '@/services/manager/floorManagementService'

export const useAddFloorStore = defineStore('addFloor', () => {
  // State
  const formData = ref({
    floor_number: '',
    name: '',
    description: '',
  })

  const loading = ref(false)
  const submitting = ref(false)
  const error = ref<string | null>(null)
  const success = ref<string | null>(null)
  const validationErrors = ref<Record<string, string>>({})

  // Validation state
  const floorNumberUnique = ref(true)
  const floorNameUnique = ref(true)
  const checkingUniqueness = ref(false)

  // Computed
  const isFormValid = computed(() => {
    return (
      formData.value.floor_number &&
      formData.value.name &&
      floorNumberUnique.value &&
      floorNameUnique.value &&
      Object.keys(validationErrors.value).length === 0
    )
  })

  const canSubmit = computed(() => {
    return isFormValid.value && !submitting.value && !checkingUniqueness.value
  })

  // Methods
  const validateFloorNumber = () => {
    const num = parseInt(formData.value.floor_number)
    if (!num || num < 0) {
      validationErrors.value.floor_number = 'Floor number must be a positive number'
      return false
    }
    if (num > 100) {
      validationErrors.value.floor_number = 'Floor number cannot exceed 100'
      return false
    }
    delete validationErrors.value.floor_number
    return true
  }

  const validateName = () => {
    const name = formData.value.name.trim()
    if (!name) {
      validationErrors.value.name = 'Floor name is required'
      return false
    }
    if (name.length < 3) {
      validationErrors.value.name = 'Floor name must be at least 3 characters'
      return false
    }
    if (name.length > 100) {
      validationErrors.value.name = 'Floor name cannot exceed 100 characters'
      return false
    }
    delete validationErrors.value.name
    return true
  }

  const validateDescription = () => {
    const desc = formData.value.description.trim()
    if (desc && desc.length > 500) {
      validationErrors.value.description = 'Description cannot exceed 500 characters'
      return false
    }
    delete validationErrors.value.description
    return true
  }

  const validateAll = () => {
    const floorNumValid = validateFloorNumber()
    const nameValid = validateName()
    const descValid = validateDescription()
    return floorNumValid && nameValid && descValid
  }

  const checkFloorNumberUniqueness = async () => {
    if (!validateFloorNumber()) {
      floorNumberUnique.value = false
      return
    }

    checkingUniqueness.value = true
    try {
      const isUnique = await floorManagementService.validateFloorNumber(
        parseInt(formData.value.floor_number)
      )
      floorNumberUnique.value = isUnique
      if (!isUnique) {
        validationErrors.value.floor_number = 'This floor number already exists'
      } else {
        delete validationErrors.value.floor_number
      }
    } catch (err) {
      console.error('Error checking floor number uniqueness:', err)
      floorNumberUnique.value = true // Assume unique on error
    } finally {
      checkingUniqueness.value = false
    }
  }

  const resetForm = () => {
    formData.value = {
      floor_number: '',
      name: '',
      description: '',
    }
    validationErrors.value = {}
    error.value = null
    success.value = null
    floorNumberUnique.value = true
    floorNameUnique.value = true
  }

  const createFloor = async (): Promise<Floor | null> => {
    // Clear previous messages
    error.value = null
    success.value = null

    // Validate all fields
    if (!validateAll()) {
      error.value = 'Please fix validation errors before submitting'
      return null
    }

    submitting.value = true
    try {
      const floorData = {
        floor_number: parseInt(formData.value.floor_number),
        name: formData.value.name.trim(),
        description: formData.value.description.trim() || undefined,
      }

      const createdFloor = await floorManagementService.createFloor(floorData)

      success.value = `Floor "${createdFloor.name}" created successfully!`

      // Reset form after successful creation
      resetForm()

      return createdFloor
    } catch (err: any) {
      const errorMsg = err.response?.data?.message || err.message || 'Failed to create floor'
      error.value = errorMsg

      // Parse validation errors from Laravel
      if (err.response?.data?.errors) {
        validationErrors.value = err.response.data.errors
      }

      console.error('Error creating floor:', err)
      return null
    } finally {
      submitting.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  const clearSuccess = () => {
    success.value = null
  }

  const setFieldValue = (field: string, value: any) => {
    ;(formData.value as any)[field] = value
    
    // Clear error for this field when user starts typing
    if (validationErrors.value[field]) {
      delete validationErrors.value[field]
    }
  }

  return {
    // State
    formData,
    loading,
    submitting,
    error,
    success,
    validationErrors,
    floorNumberUnique,
    floorNameUnique,
    checkingUniqueness,

    // Computed
    isFormValid,
    canSubmit,

    // Methods
    validateFloorNumber,
    validateName,
    validateDescription,
    validateAll,
    checkFloorNumberUniqueness,
    resetForm,
    createFloor,
    clearError,
    clearSuccess,
    setFieldValue,
  }
})
