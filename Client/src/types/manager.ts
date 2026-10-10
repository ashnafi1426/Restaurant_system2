export type ReservationStatus = 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'

export type OrderStatus =
  | 'pending'
  | 'accepted'
  | 'preparing'
  | 'ready'
  | 'served'
  | 'delivered'
  | 'completed'
  | 'cancelled'

export type RoomStatus = 'available' | 'occupied' | 'reserved' | 'cleaning' | 'maintenance'

export type StaffStatus = 'active' | 'inactive' | 'off_duty' | 'leave' | 'on_leave' | 'on_break'

export type WaiterStatus =
  | 'active'
  | 'break'
  | 'on_break'
  | 'inactive'
  | 'available'
  | 'offline'
  | 'busy'
  | 'on_duty'

export type DeliveryStatus = 'waiting' | 'assigned' | 'delivering' | 'completed'

export type HousekeepingStatus = 'pending' | 'in_progress' | 'completed'

export type LaundryStatus = 'pending' | 'in_progress' | 'completed'

export interface DashboardStatistics {
  totalReservations: number
  todayCheckIns: number
  todayCheckOuts: number
  availableRooms: number
  occupiedRooms: number

  totalRooms: number
  reservedRooms: number
  maintenanceRooms: number

  totalGuests: number
  checkedInGuests: number
  guestCheckouts: number

  pendingOrders: number
  preparingOrders: number
  completedOrders: number

  pendingLaundry: number
  pendingHousekeeping: number

  activeStaff: number
  pendingTasks: number

  todayRevenue: number
  monthlyRevenue: number
}

export interface RevenueSummary {
  today: number
  yesterday: number
  thisWeek: number
  thisMonth: number
  thisYear: number
}

export interface OccupancySummary {
  totalRooms: number
  occupiedRooms: number
  availableRooms: number
  reservedRooms: number
  maintenanceRooms: number
  occupancyRate: number
}

export interface ReservationSummary {
  pending: number
  confirmed: number
  checkedIn: number
  checkedOut: number
  cancelled: number
}

export interface StaffSummary {
  id: string
  name: string
  phone: string
  role?: string
  department: string
  position: string
  status: StaffStatus
  shift?: string
}

export interface OrderSummary {
  id: string
  roomNumber: string
  guestName: string
  orderItems: string
  total?: number
  status: OrderStatus
  estimatedTime?: number
  orderedAt?: string
  orderNumber?: string
  itemCount?: number
}

export interface RoomServiceDelivery {
  id: string
  roomNumber: string
  guestName: string
  items: string
  waiterName?: string | null
  status: 'pending' | 'in_transit' | 'delivered'
  estimatedTime?: number
}

export interface HousekeepingTask {
  id: string
  roomNumber: string
  taskType: string
  assignedTo?: string | null
  priority: 'high' | 'medium' | 'low'
  status: HousekeepingStatus
  estimatedTime?: number
}

export interface LaundryRequest {
  id: string
  roomNumber: string
  guestName?: string
  itemCount: number
  itemDetails?: string
  priority?: 'high' | 'normal' | 'low'
  status: LaundryStatus
  estimatedCompletion?: number
}

export interface RecentActivity {
  id: string
  type: string
  description: string
  details?: string
  timestamp: string
}

export interface NotificationItem {
  id: string
  title: string
  message: string
  type: 'success' | 'warning' | 'error' | 'info'
  read_at?: string
  createdAt?: string
}

export interface RevenueChartItem {
  label: string
  revenue: number
}

export interface OccupancyChartItem {
  label: string
  occupied: number
  available: number
}

export interface Waiter {
  id: string
  userId: string
  name: string
  section: string
  phone?: string
  shift: 'morning' | 'afternoon' | 'evening' | 'night'
  status: WaiterStatus
  experienceLevel: 'junior' | 'senior' | 'head'
  activeOrders?: number
  rating?: number
  createdAt?: string
  user?: {
    id?: string
    name?: string
    first_name?: string
    last_name?: string
    email?: string
    phone?: string
  }
}

export interface ManagerDashboardResponse {
  statistics: DashboardStatistics

  revenue: RevenueSummary

  occupancy: OccupancySummary

  reservations: ReservationSummary

  staff: StaffSummary[]

  recentOrders: OrderSummary[]

  deliveries: RoomServiceDelivery[]

  housekeeping: HousekeepingTask[]

  laundry: LaundryRequest[]

  recentActivities: RecentActivity[]

  notifications: NotificationItem[]

  revenueChart: RevenueChartItem[]

  occupancyChart: OccupancyChartItem[]

  waiters?: Waiter[]
}
