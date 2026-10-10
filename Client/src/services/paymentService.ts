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
  guest_id?: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  special_requests?: string
  first_name: string
  last_name: string
  email: string
  phone: string
  include_breakfast?: boolean
  include_dinner?: boolean
  include_spa?: boolean
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

async function initializePayment(payload: InitializePaymentPayload): Promise<PaymentResponse> {
  try {
    const response = await fetch(`${API_BASE_URL}/payments/initialize`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const data: PaymentResponse = await response.json()

    if (!response.ok) {
      throw new Error(data.message || data.error || 'Failed to initialize payment')
    }

    return data
  } catch (error: any) {
    console.error('[PaymentService] Error initializing payment:', error)
    throw error
  }
}

async function initializeReservationPayment(
  payload: ReservationPaymentPayload,
): Promise<PaymentResponse> {
  try {
    const response = await fetch(`${API_BASE_URL}/reservation-payments/initialize`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const data: PaymentResponse = await response.json()

    if (!response.ok) {
      throw new Error(data.message || data.error || 'Failed to initialize reservation payment')
    }

    return data
  } catch (error: any) {
    console.error('[PaymentService] Error initializing reservation payment:', error)
    throw error
  }
}

async function verifyPayment(txRef: string): Promise<PaymentStatusResponse> {
  try {
    const response = await fetch(`${API_BASE_URL}/payments/verify/${txRef}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data: PaymentStatusResponse = await response.json()

    if (!response.ok) {
      throw new Error('Payment verification failed')
    }

    return data
  } catch (error: any) {
    console.error('[PaymentService] Error verifying payment:', error)
    throw error
  }
}

async function getPaymentByTxRef(txRef: string): Promise<any> {
  try {
    const response = await fetch(`${API_BASE_URL}/payments/status/${txRef}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to fetch payment')
    }

    if (data.payment) {
      return data.payment
    }
    if (data.data) {
      return data.data
    }
    return data
  } catch (error: any) {
    console.error('[PaymentService] Error getting payment by txRef:', error)
    throw error
  }
}

async function getReservationPaymentByTxRef(txRef: string): Promise<any> {
  try {
    const response = await fetch(`${API_BASE_URL}/reservation-payments/${txRef}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to fetch reservation payment')
    }

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
    console.error('[PaymentService] Error getting reservation payment by txRef:', error)
    throw error
  }
}

async function getPaymentStatus(paymentId: string): Promise<PaymentStatusResponse> {
  try {
    const response = await fetch(`${API_BASE_URL}/payments/${paymentId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    const data: PaymentStatusResponse = await response.json()

    if (!response.ok) {
      throw new Error('Failed to fetch payment status')
    }

    return data
  } catch (error: any) {
    console.error('[PaymentService] Error getting payment status:', error)
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
