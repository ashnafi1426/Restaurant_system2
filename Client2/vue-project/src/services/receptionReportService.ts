import api from '@/api/auth'

export interface DateRange {
  start_date?: string
  end_date?: string
}

export interface ReservationReportData {
  summary: {
    total: number
    pending: number
    confirmed: number
    checked_in: number
    checked_out: number
    cancelled: number
  }
  daily_stats: Array<{
    date: string
    count: number
    pending: number
    confirmed: number
  }>
  reservations: any[]
  period: {
    start: string
    end: string
  }
}

export interface OccupancyReportData {
  summary: {
    total_rooms: number
    available: number
    occupied: number
    avg_occupancy_rate: number
  }
  daily_occupancy: Array<{
    date: string
    occupied: number
    available: number
    occupancy_rate: number
  }>
  period: {
    start: string
    end: string
  }
}

export interface GuestReportData {
  summary: {
    total_guests: number
    new_guests: number
  }
  top_guests: Array<{
    id: number
    first_name: string
    last_name: string
    email: string
    phone: string
    reservations_count: number
  }>
  period: {
    start: string
    end: string
  }
}

export interface RevenueReportData {
  summary: {
    total_revenue: number
    reservation_revenue: number
    order_revenue: number
    payment_count: number
  }
  daily_revenue: Array<{
    date: string
    total: number
    count: number
  }>
  period: {
    start: string
    end: string
  }
}

export interface CheckInOutReportData {
  summary: {
    total_check_ins: number
    total_check_outs: number
    active_guests: number
  }
  daily_stats: Array<{
    date: string
    check_ins: number
    check_outs: number
  }>
  period: {
    start: string
    end: string
  }
}

/**
 * Get reservation report with date range filter
 */
export async function getReservationReport(dateRange: DateRange = {}) {
  try {
    const response = await api.get<{ success: boolean; data: ReservationReportData }>(
      '/reception/reports/reservations',
      { params: dateRange }
    )
    return response.data
  } catch (error: any) {
    console.error('❌ [REPORT SERVICE] Failed to fetch reservation report:', error)
    throw error
  }
}

/**
 * Get occupancy report with date range filter
 */
export async function getOccupancyReport(dateRange: DateRange = {}) {
  try {
    const response = await api.get<{ success: boolean; data: OccupancyReportData }>(
      '/reception/reports/occupancy',
      { params: dateRange }
    )
    return response.data
  } catch (error: any) {
    console.error('❌ [REPORT SERVICE] Failed to fetch occupancy report:', error)
    throw error
  }
}

/**
 * Get guest report with date range filter
 */
export async function getGuestReport(dateRange: DateRange = {}) {
  try {
    const response = await api.get<{ success: boolean; data: GuestReportData }>(
      '/reception/reports/guests',
      { params: dateRange }
    )
    return response.data
  } catch (error: any) {
    console.error('❌ [REPORT SERVICE] Failed to fetch guest report:', error)
    throw error
  }
}

/**
 * Get revenue report with date range filter
 */
export async function getRevenueReport(dateRange: DateRange = {}) {
  try {
    const response = await api.get<{ success: boolean; data: RevenueReportData }>(
      '/reception/reports/revenue',
      { params: dateRange }
    )
    return response.data
  } catch (error: any) {
    console.error('❌ [REPORT SERVICE] Failed to fetch revenue report:', error)
    throw error
  }
}

/**
 * Get check-in/check-out report with date range filter
 */
export async function getCheckInOutReport(dateRange: DateRange = {}) {
  try {
    const response = await api.get<{ success: boolean; data: CheckInOutReportData }>(
      '/reception/reports/check-in-out',
      { params: dateRange }
    )
    return response.data
  } catch (error: any) {
    console.error('❌ [REPORT SERVICE] Failed to fetch check-in/out report:', error)
    throw error
  }
}
