import { publicAxios as axios } from './axios'

export interface QRResolutionResult {
  success: boolean
  context: 'room' | 'table' | null
  data: {
    hotel_id?: string
    hotel_name?: string
    room_id?: string
    room_number?: string
    floor?: string
    floor_id?: string
    room_type?: string
    guest?: {
      guest_id: string
      guest_name: string
      guest_email?: string
      guest_phone?: string
      reservation_id?: string
      check_in_date?: string
      expected_checkout?: string
    } | null
    table_id?: string
    table_number?: string
    table_name?: string | null
    capacity?: number
    location?: string | null
    status?: string
  } | null
  message: string
}

export interface QRValidationResult {
  valid: boolean
  context: 'room' | 'table' | null
}

export const qrService = {
  async resolveQRToken(token: string): Promise<QRResolutionResult> {
    try {
      const response = await axios.get(`/qr/resolve/${token}`)
      return response.data
    } catch (error: any) {
      console.error('[QRService] Error resolving QR token:', error)
      return {
        success: false,
        context: null,
        data: null,
        message: error.response?.data?.message || 'Failed to resolve QR code'
      }
    }
  },

  async validateQRToken(token: string): Promise<QRValidationResult> {
    try {
      const response = await axios.post('/qr/validate', { qr_token: token })
      return response.data
    } catch (error: any) {
      console.error('[QRService] Error validating QR token:', error)
      return {
        valid: false,
        context: null
      }
    }
  }
}

export default qrService
