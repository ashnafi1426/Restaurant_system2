export interface WaiterAssignment {
  id: string
  waiter_id: string
  order_id: string
  assigned_by: string
  assigned_at: string
  accepted_at: string | null
  rejected_at: string | null
  picked_up_at: string | null
  delivered_at: string | null
  failed_at: string | null
  status: AssignmentStatus
  rejection_reason: string | null
  failure_reason: string | null
  remarks: string | null
  delivery_time_minutes: number | null
  order?: {
    id: string
    order_number: string
    room_number: string
    guest_name: string
    priority: 'normal' | 'urgent' | 'vip'
    items_count: number
  }
  waiter?: {
    id: string
    name: string
    employee_code: string
  }
  assigned_by_user?: {
    id: string
    name: string
  }
}

export type AssignmentStatus =
  | 'pending'
  | 'accepted'
  | 'rejected'
  | 'picked_up'
  | 'on_delivery'
  | 'delivered'
  | 'failed'
  | 'cancelled'

export interface DeliveryLog {
  id: string
  waiter_id: string
  order_id: string
  room_id: string | null
  action: LogAction
  description: string
  created_at: string
  order?: {
    id: string
    order_number: string
    room_number: string
  }
}

export type LogAction =
  | 'assigned'
  | 'accepted'
  | 'rejected'
  | 'picked_up'
  | 'on_delivery'
  | 'delivered'
  | 'failed'
  | 'cancelled'
  | 'reassigned'

export interface WaiterPerformance {
  id: string
  waiter_id: string
  date: string
  metrics: {
    total_assignments: number
    accepted_assignments: number
    rejected_assignments: number
    completed_deliveries: number
    failed_deliveries: number
  }
  performance: {
    average_delivery_time: number
    completion_rate: number
    failure_rate: number
    guest_rating: number
    rating: number
  }
  created_at: string
  updated_at: string
}

export interface WaiterProfile {
  id: string
  name: string
  email: string
  phone: string
  avatar: string | null
  role: string
  waiter?: {
    id: string
    employee_code: string
    manager_id: string
    phone: string
    shift: 'morning' | 'afternoon' | 'night' | 'flexible'
    status: 'active' | 'inactive' | 'on_leave'
    created_at: string
    updated_at: string
  }
  created_at: string
  updated_at: string
}

export interface WaiterDashboard {
  today_stats: {
    total_assignments: number
    completed_deliveries: number
    failed_deliveries: number
    rejected_assignments: number
    pending_assignments: number
    active_assignments: number
    on_delivery_count: number
    average_delivery_time: number
    completion_rate: number
  }
  performance: {
    today: PerformanceMetrics
    week: PerformanceMetrics
    month: PerformanceMetrics
  }
  recent_assignments: WaiterAssignment[]
  pending_count: number
  active_count: number
}

export interface PerformanceMetrics {
  deliveries: number
  failed: number
  average_delivery_time: number
  rating: number
  guest_rating?: number
}

export interface WaiterNotification {
  id: string
  type: NotificationType
  title: string
  message: string
  priority: 'normal' | 'high' | 'urgent'
  related_id: string
  related_type: string
  read_at: string | null
  created_at: string
}

export type NotificationType =
  | 'new_assignment'
  | 'vip_delivery'
  | 'priority_delivery'
  | 'order_updated'
  | 'delivery_cancelled'
  | 'delivery_completed'
  | 'delivery_failed'
  | 'performance_update'
  | 'assignment_reassigned'

export interface WaiterSettings {
  notifications_enabled: boolean
  email_notifications: boolean
  sms_notifications: boolean
  theme: 'light' | 'dark'
  language: 'en' | 'es' | 'fr' | 'de'
}

export interface DeliveryTimeRange {
  '0-10': number
  '11-20': number
  '21-30': number
  '31-45': number
  '46-60': number
  '60+': number
}

export interface PerformanceReport {
  waiter: {
    id: string
    name: string
    employee_code: string
    shift: string
  }
  period: {
    start: string
    end: string
    days: number
  }
  summary: {
    total_assignments: number
    total_deliveries: number
    total_failed: number
    total_rejected: number
    completion_rate: number
    failure_rate: number
  }
  delivery_metrics: {
    average_delivery_time: number
    on_time_percentage: number
    deliveries_per_day: number
  }
  ratings: {
    average_guest_rating: number
    average_overall_rating: number
  }
  daily_breakdown: Array<{
    date: string
    deliveries: number
    failed: number
    rejected: number
    average_delivery_time: number
    guest_rating: number
    rating: number
  }>
}

export interface QuickStats {
  pending: number
  active: number
  completed: number
  failed: number
}

export interface ChartDataPoint {
  date: string
  day?: string
  week?: string
  deliveries: number
  failed: number
  average_delivery_time: number
  rating: number
}
