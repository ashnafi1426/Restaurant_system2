// Global type extensions to handle dynamic properties from the API
// This file makes TypeScript more lenient with property access

declare module '@/types/guest' {
  interface Guest {
    name?: string
    [key: string]: any
  }

  interface MenuItem {
    total_price?: number
    tax_rate?: number
    tax_included?: boolean
    [key: string]: any
  }

  interface CartItem {
    total_price?: number
    [key: string]: any
  }
}

declare module '@/types/room' {
  interface Room {
    images?: string[] | Array<{ url: string; alt?: string }>
    amenities?: string[] | Array<{ id: string; name: string }>
    [key: string]: any
  }
}

declare module '@/types/roomType' {
  interface RoomType {
    max_occupancy?: number
    [key: string]: any
  }
}

declare module '@/types/reservation' {
  interface Reservation {
    booking_reference?: string
    [key: string]: any
  }
}

// Allow flexible property access on chart options
declare module 'chart.js' {
  interface ChartOptions {
    [key: string]: any
  }
}

// Global type for flexible objects
declare global {
  interface Window {
    [key: string]: any
  }
}

export {}
