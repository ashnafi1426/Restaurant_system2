/**
 * ============================================================================
 * Payment Service
 * ============================================================================
 * Handles all payment-related API calls to the backend
 */

const API_BASE_URL = 'http://127.0.0.1:8000/api'

interface InitializePaymentPayload {
  amount: number
  currency?: string
  first_name: string
  last_name: string
  email: string
  phone: string
  title?: string
  description?: string
  metadata?: any
}

interface ReservationPaymentPayload {
  room_id: string
  guest_id: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  special_requests?: string
  first_name: string
  last_name: string
  email: string
  phone: string
}

interface PaymentResponse {
  success: boolean
  message: string
  payment_id?: string
  tx_ref?: string
  checkout_url?: string
  amount?: number
  error?: string
}

interface PaymentStatusResponse {
  success: boolean
  payment?: any
  status?: string
}

/**
 * Initialize a general payment
 */
async function initializePayment(
  payload: InitializePaymentPayload
): Promise<PaymentResponse> {
  try {
    console.log('💳 [PAYMENT SERVICE] Initializing payment...', payload)

    const response = await fetch(`${API_BASE_URL}/payments/initialize`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const data: PaymentResponse = await response.json()

    if (!response.ok) {
      console.error('❌ [PAYMENT SERVICE] Initialize failed:', data)
      throw new Error(data.message || data.error || 'Failed to initialize payment')
    }

    console.log('✅ [PAYMENT SERVICE] Payment initialized:', data)
    return data
  } catch (error: any) {
    console.error('❌ [PAYMENT SERVICE] Initialize error:', error)
    throw error
  }
}

/**
 * Initialize a reservation payment
 */
async function initializeReservationPayment(
  payload: ReservationPaymentPayload
): Promise<PaymentResponse> {
  try {
    console.log('💳 [PAYMENT SERVICE] Initializing reservation payment...', payload)

    const response = await fetch(
      `${API_BASE_URL}/reservation-payments/initialize`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      }
    )

    const data: PaymentResponse = await response.json()

    if (!response.ok) {
      console.error('❌ [PAYMENT SERVICE] Reservation initialize failed:', data)
      throw new Error(data.message || data.error || 'Failed to initialize reservation payment')
    }

    console.log('✅ [PAYMENT SERVICE] Reservation payment initialized:', data)
    return data
  } catch (error: any) {
    console.error('❌ [PAYMENT SERVICE] Reservation initialize error:', error)
    throw error
  }
}

/**
 * Verify a payment by transaction reference
 */
async function verifyPayment(txRef: string): Promise<PaymentStatusResponse> {
  try {
    console.log('🔍 [PAYMENT SERVICE] Verifying payment:', txRef)

    const response = await fetch(`${API_BASE_URL}/payments/verify/${txRef}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data: PaymentStatusResponse = await response.json()

    if (!response.ok) {
      console.error('❌ [PAYMENT SERVICE] Verification failed:', data)
      throw new Error('Payment verification failed')
    }

    console.log('✅ [PAYMENT SERVICE] Payment verified:', data)
    return data
  } catch (error: any) {
    console.error('❌ [PAYMENT SERVICE] Verify error:', error)
    throw error
  }
}

/**
 * Get payment by transaction reference (from general payments)
 */
async function getPaymentByTxRef(txRef: string): Promise<any> {
  try {
    console.log('📡 [PAYMENT SERVICE] Fetching payment by tx_ref:', txRef)

    // Try general payment endpoint first
    const response = await fetch(`${API_BASE_URL}/payments/status/${txRef}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data = await response.json()

    if (!response.ok) {
      console.error('❌ [PAYMENT SERVICE] Fetch failed:', data)
      throw new Error(data.message || 'Failed to fetch payment')
    }

    console.log('✅ [PAYMENT SERVICE] Payment fetched:', data)

    // Return the payment object from the response
    if (data.payment) {
      return data.payment
    }
    if (data.data) {
      return data.data
    }
    return data
  } catch (error: any) {
    console.error('❌ [PAYMENT SERVICE] Fetch error:', error)
    throw error
  }
}

/**
 * Get reservation payment by transaction reference
 */
async function getReservationPaymentByTxRef(txRef: string): Promise<any> {
  try {
    console.log('📡 [PAYMENT SERVICE] Fetching reservation payment by tx_ref:', txRef)

    // Try reservation-payments endpoint
    const response = await fetch(`${API_BASE_URL}/reservation-payments/${txRef}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data = await response.json()

    if (!response.ok) {
      console.error('❌ [PAYMENT SERVICE] Fetch failed:', data)
      throw new Error(data.message || 'Failed to fetch reservation payment')
    }

    console.log('✅ [PAYMENT SERVICE] Reservation payment fetched:', data)

    // Return the payment/reservation object from the response
    if (data.reservation) {
      return data.reservation
    }
    if (data.payment) {
      return data.payment
    }
    if (data.data) {
      return data.data
    }
    return data
  } catch (error: any) {
    console.error('❌ [PAYMENT SERVICE] Fetch error:', error)
    throw error
  }
}

/**
 * Get payment status
 */
async function getPaymentStatus(paymentId: string): Promise<PaymentStatusResponse> {
  try {
    console.log('📡 [PAYMENT SERVICE] Fetching payment status:', paymentId)

    const response = await fetch(`${API_BASE_URL}/payments/${paymentId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data: PaymentStatusResponse = await response.json()

    if (!response.ok) {
      console.error('❌ [PAYMENT SERVICE] Status fetch failed:', data)
      throw new Error('Failed to fetch payment status')
    }

    console.log('✅ [PAYMENT SERVICE] Payment status fetched:', data)
    return data
  } catch (error: any) {
    console.error('❌ [PAYMENT SERVICE] Status fetch error:', error)
    throw error
  }
}

export default {
  initializePayment,
  initializeReservationPayment,
  verifyPayment,
  getPaymentByTxRef,
  getReservationPaymentByTxRef,
  getPaymentStatus,
}
