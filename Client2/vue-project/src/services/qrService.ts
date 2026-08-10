import { axiosInstance as axios } from './axios'

export interface QRResolutionResult {
  success: boolean
  context: 'room' | 'table' | null
  data: {
    // Room data
    room_id?: string
    room_number?: string
    floor?: string
    floor_id?: string
    room_type?: string
    // Table data
    table_id?: string
    table_number?: string
    table_name?: string | null
    capacity?: number
    location?: string | null
    // Common
    status?: string
  } | null
  message: string
}

export interface QRValidationResult {
  valid: boolean
  context: 'room' | 'table' | null
}

/**
 * QR Service - Handles QR token resolution and validation
 */
export const qrService = {
  /**
   * Resolve QR token to determine context (room or table)
   * @param token - 8-character QR token
   * @returns Resolution result with context and data
   */
  async resolveQRToken(token: string): Promise<QRResolutionResult> {
    try {
      const response = await axios.get(`/qr/resolve/${token}`)
      return response.data
    } catch (error: any) {
      // Return error in expected format
      return {
        success: false,
        context: null,
        data: null,
        message: error.response?.data?.message || 'Failed to resolve QR code'
      }
    }
  },

  /**
   * Validate QR token (lightweight check)
   * @param token - 8-character QR token
   * @returns Validation result
   */
  async validateQRToken(token: string): Promise<QRValidationResult> {
    try {
      const response = await axios.post('/qr/validate', { qr_token: token })
      return response.data
    } catch (error: any) {
      return {
        valid: false,
        context: null
      }
    }
  }
}

export default qrService
