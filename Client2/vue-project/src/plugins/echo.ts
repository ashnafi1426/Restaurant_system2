/**
 * Laravel Echo WebSocket Plugin
 * 
 * Configures Laravel Echo to connect to Laravel Reverb WebSocket server.
 * Uses Pusher protocol (pusher-js) but connects to self-hosted Reverb (not Pusher Cloud).
 * 
 * This plugin is imported in main.ts to initialize WebSocket connection on app startup.
 */

import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Make Pusher available globally for Echo
declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo
  }
}

window.Pusher = Pusher

/**
 * Configure Laravel Echo instance
 */
const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
  
  /**
   * Custom authorizer for private channel authentication.
   * Calls Laravel's /api/broadcasting/auth endpoint with hotel context.
   */
  authorizer: (channel: any) => {
    return {
      authorize: (socketId: string, callback: Function) => {
        const token = localStorage.getItem('token')
        const hotelId = localStorage.getItem('hotel_id') || localStorage.getItem('active_hotel_id')
        const qrToken = localStorage.getItem('guest_qr_token')
        
        console.log('[Echo] Authorizing channel:', channel.name)
        console.log('[Echo] Socket ID:', socketId)
        console.log('[Echo] Hotel ID:', hotelId || 'MISSING')
        console.log('[Echo] QR Token:', qrToken ? qrToken.substring(0, 4) + '****' : 'MISSING')
        
        // Call Laravel broadcasting auth endpoint
        fetch(`${import.meta.env.VITE_API_BASE_URL}/api/broadcasting/auth`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': token ? `Bearer ${token}` : '',
            'X-Hotel-ID': hotelId || '',
            'X-QR-Token': qrToken || '',
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name
          })
        })
        .then(response => {
          if (!response.ok) {
            console.error('[Echo] ❌ Authorization failed - HTTP', response.status, response.statusText)
            return response.text().then(text => {
              console.error('[Echo] Response body:', text)
              throw new Error(`Authorization failed: ${response.status} ${response.statusText} - ${text}`)
            })
          }
          return response.json()
        })
        .then(data => {
          console.log('[Echo] ✅ Channel authorization successful:', channel.name)
          callback(null, data)
        })
        .catch(error => {
          console.error('[Echo] ⚠️ Channel authorization error:', error)
          callback(error, null)
        })
      }
    }
  }
})

// Make Echo available globally
window.Echo = echo

// Log connection status in development
if (import.meta.env.DEV) {
  echo.connector.pusher.connection.bind('connected', () => {
    console.log('[Echo] ✅ Connected to Reverb WebSocket server')
  })
  
  echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('[Echo] ❌ Disconnected from Reverb WebSocket server')
  })
  
  echo.connector.pusher.connection.bind('error', (err: any) => {
    console.error('[Echo] ⚠️ Connection error:', err)
  })
}

export default echo
