export interface DashboardGuest {
  id: string
  name: string
  initials: string
  email: string
}

export interface DashboardReservation {
  id: string
  booking_reference: string
  guest: DashboardGuest
  room_type: string
  check_in_date: string
  status: 'Pending' | 'Confirmed' | 'Checked_in' | 'Checked_out' | 'Cancelled'
  total_price: number
}

export interface MonthlyRevenueData {
  month: string
  revenue: number
}

export interface StaffActivityItem {
  id: number
  staff_name: string
  staff_initials: string
  action: string
  timestamp: string
}

export interface MaintenanceAlert {
  id: number
  title: string
  description: string
  severity: 'high' | 'medium' | 'low'
}

export interface DashboardData {
  overview: {
    totalUsers: number
    activeStaff: number
    totalRooms: number
    totalRoomTypes: number
    occupancyRate: number
    todayRevenue: number
  }
  roomStatistics: {
    available: number
    reserved: number
    occupied: number
    maintenance: number
  }
  recentUsers: any[]
  recentReservations: DashboardReservation[]
  monthlyRevenue: MonthlyRevenueData[]
  staffActivity: StaffActivityItem[]
  maintenanceAlerts: MaintenanceAlert[]
}
