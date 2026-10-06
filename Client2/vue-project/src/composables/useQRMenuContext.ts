import { ref, type Ref } from 'vue'
import type { OrderContext } from '@/types/qrMenu'
import { qrService } from '@/services/qrService'

export function useQRMenuContext() {
  const qrToken: Ref<string> = ref('')
  const orderContext: Ref<OrderContext | null> = ref(null)
  const canOrderRoomService: Ref<boolean> = ref(true)
  const eligibilityMessage: Ref<string> = ref('')
  const reservationStatusVal: Ref<string> = ref('')
  const roomNumber: Ref<string> = ref('101')
  const guestName: Ref<string> = ref('Guest User')
  const guestEmail: Ref<string> = ref('guest@royalhorizon.com')
  const guestAvatar: Ref<string> = ref('/images/avatar.png')
  const isLoadingContext: Ref<boolean> = ref(true)
  const contextError: Ref<string | null> = ref(null)

  const detectOrderContext = async (token: string) => {
    isLoadingContext.value = true
    contextError.value = null

    try {
      const result = await qrService.resolveQRToken(token)

      if (!result.success || !result.context || !result.data) {
        throw new Error(result.message || 'Invalid QR code')
      }

      if (result.context === 'room') {
        const isCheckedIn = result.data.is_checked_in === true || result.data.can_order === true
        const resStatus = result.data.reservation_status || (isCheckedIn ? 'checked_in' : 'none')
        const eligMsg = result.data.eligibility_message || (isCheckedIn ? '' : 'Only checked-in guests can place room-service orders.')

        canOrderRoomService.value = isCheckedIn
        eligibilityMessage.value = eligMsg
        reservationStatusVal.value = resStatus

        // CRITICAL: Store hotel_id from QR resolution to ensure correct tenant isolation
        if (result.data.hotel_id) {
          localStorage.setItem('hotel_id', result.data.hotel_id)
          localStorage.setItem('active_hotel_id', result.data.hotel_id)
          console.log('[useQRMenuContext] Stored hotel_id from QR resolution:', result.data.hotel_id)
        }

        orderContext.value = {
          type: 'room',
          id: result.data.room_id!,
          displayName: `Room ${result.data.room_number}`,
          paymentOptions: [{ value: 'room_charge', label: 'Charge to Room' }],
          isCheckedIn: isCheckedIn,
          canOrder: isCheckedIn,
          reservationStatus: resStatus,
          eligibilityMessage: eligMsg,
        }
        roomNumber.value = result.data.room_number || '101'
        
        if (result.data.guest) {
          guestName.value = result.data.guest.guest_name
          guestEmail.value = result.data.guest.guest_email || 'guest@hotel.com'
        } else {
          guestName.value = 'Hotel Guest'
          guestEmail.value = 'guest@hotel.com'
        }

        console.log('[useQRMenuContext] Room context detected:', orderContext.value)
      } else if (result.context === 'table') {
        canOrderRoomService.value = true
        eligibilityMessage.value = ''
        reservationStatusVal.value = 'not_applicable'

        // CRITICAL: Store hotel_id from QR resolution for table orders
        if (result.data.hotel_id) {
          localStorage.setItem('hotel_id', result.data.hotel_id)
          localStorage.setItem('active_hotel_id', result.data.hotel_id)
          console.log('[useQRMenuContext] Stored hotel_id from table QR resolution:', result.data.hotel_id)
        }

        orderContext.value = {
          type: 'table',
          id: result.data.table_id!,
          displayName: result.data.table_name || `Table ${result.data.table_number}`,
          paymentOptions: [
            { value: 'cash', label: 'Pay with Cash' },
            { value: 'card', label: 'Pay with Card' },
          ],
          isCheckedIn: true,
          canOrder: true,
          reservationStatus: 'not_applicable',
          eligibilityMessage: '',
        }
        roomNumber.value = result.data.table_name || `Table ${result.data.table_number}`
        guestName.value = 'Walk-in Guest'
        guestEmail.value = 'walkin@restaurant.com'

        console.log('[useQRMenuContext] Table context detected:', orderContext.value)
      }
    } catch (error: any) {
      console.error('[useQRMenuContext] Failed to detect order context:', error)
      contextError.value = error.message || 'Failed to load menu'
      
      // Fallback to table context if QR resolution fails
      if (!orderContext.value || !orderContext.value.type) {
        orderContext.value = {
          type: 'table',
          id: '',
          displayName: 'Restaurant Dining',
          paymentOptions: [
            { value: 'cash', label: 'Pay with Cash' },
            { value: 'card', label: 'Pay with Card' },
          ],
        }
        roomNumber.value = 'Restaurant Dining'
        guestName.value = 'Walk-in Guest'
        guestEmail.value = 'walkin@restaurant.com'
      }
    } finally {
      isLoadingContext.value = false
    }
  }

  return {
    qrToken,
    orderContext,
    canOrderRoomService,
    eligibilityMessage,
    reservationStatusVal,
    roomNumber,
    guestName,
    guestEmail,
    guestAvatar,
    isLoadingContext,
    contextError,
    detectOrderContext,
  }
}
