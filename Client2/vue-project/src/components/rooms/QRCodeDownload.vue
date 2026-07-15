<template>
  <div class="qr-code-section">
    <!-- Header -->
    <div class="header">
      <h3>📱 QR Code Management</h3>
      <p class="subtitle">Download or print the QR code for this room</p>
    </div>

    <!-- QR Code Display -->
    <div v-if="room.qr_code_url" class="qr-display">
      <div class="qr-container">
        <img :src="room.qr_code_url" :alt="`QR Code for Room ${room.room_number}`" />
        <p class="qr-token">Token: {{ room.qr_token }}</p>
      </div>

      <!-- Action Buttons -->
      <div class="actions">
        <button @click="downloadQRCode" class="btn btn-primary">
          <span>📥 Download PNG</span>
        </button>
        <button @click="printQRCode" class="btn btn-secondary">
          <span>🖨️ Print</span>
        </button>
        <button @click="regenerateQRCode" class="btn btn-warning" :disabled="regenerating">
          <span v-if="!regenerating">🔄 Regenerate</span>
          <span v-else>Regenerating...</span>
        </button>
      </div>

      <!-- Info -->
      <div v-if="room.qr_generated_at" class="info">
        <p>✅ QR Code generated: {{ formatDate(room.qr_generated_at) }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <p>Loading QR code...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error">
      <p>❌ {{ error }}</p>
      <button @click="loadQRCode" class="btn btn-small">Retry</button>
    </div>

    <!-- No QR Code -->
    <div v-else class="no-qr">
      <p>No QR code found for this room</p>
      <button @click="regenerateQRCode" class="btn btn-primary" :disabled="regenerating">
        <span v-if="!regenerating">Generate QR Code</span>
        <span v-else>Generating...</span>
      </button>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="successMessage" class="message success">
      ✅ {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="message error">
      ❌ {{ errorMessage }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import api from '../../api/auth'

interface Room {
  id?: string | number
  room_number?: string | number
  qr_token?: string
  qr_code_url?: string
  qr_image_path?: string
  qr_generated_at?: string
}

const props = defineProps<{
  room: Room
}>()

const loading = ref(false)
const error = ref('')
const regenerating = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

/**
 * Load QR code info
 */
const loadQRCode = async () => {
  if (!props.room.id) return

  loading.value = true
  error.value = ''

  try {
    const response = await api.get(`/admin/qr-codes/${props.room.id}/image`)

    if (response.data.success && response.data.data) {
      const qrData = response.data.data
      // Update room object with QR data
      Object.assign(props.room, {
        qr_code_url: qrData.qr_url,
        qr_token: qrData.qr_token,
        qr_image_path: qrData.qr_image_path,
        qr_generated_at: qrData.qr_generated_at,
      })
      console.log('✅ QR code loaded:', {
        room_id: props.room.id,
        url: qrData.qr_url,
        token: qrData.qr_token,
      })
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load QR code'
    console.error('❌ Load QR Code Error:', err)
  } finally {
    loading.value = false
  }
}

/**
 * Download QR code as PNG
 * Uses public API endpoint for more reliable downloads
 */
const downloadQRCode = async () => {
  if (!props.room.id || !props.room.qr_code_url) return

  try {
    successMessage.value = ''
    errorMessage.value = ''

    console.log(' Starting download for room:', props.room.id)
    console.log('🔗 QR Code URL:', props.room.qr_code_url)

    // Use the public download endpoint
    const downloadUrl = `${api.defaults.baseURL?.replace('/api', '')}/api/qr-codes/download/${props.room.id}`
    console.log('📥 Download URL:', downloadUrl)

    const response = await fetch(downloadUrl, {
      method: 'GET',
      credentials: 'omit', // Don't send credentials for public endpoint
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`)
    }

    const blob = await response.blob()
    
    if (blob.size === 0) {
      throw new Error('Downloaded file is empty')
    }

    console.log(`✅ Blob received: ${blob.size} bytes, type: ${blob.type}`)

    // Create blob URL and trigger download
    const blobUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = `Room_${props.room.room_number}_QR.png`
    link.style.display = 'none'
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Clean up blob URL after a short delay
    setTimeout(() => {
      window.URL.revokeObjectURL(blobUrl)
    }, 100)

    successMessage.value = `✅ QR code downloaded: Room_${props.room.room_number}_QR.png`
    console.log('✅ Download successful for room:', props.room.id)
  } catch (err: any) {
    const message = err.message || 'Failed to download QR code'
    errorMessage.value = message
    console.error('❌ Download Error:', err)

    // Fallback: Try direct URL access via storage symlink
    console.log('⚠️ Fallback: Trying direct storage URL access...')
    try {
      const directUrl = props.room.qr_code_url
      console.log('📥 Direct URL:', directUrl)

      const response = await fetch(directUrl, {
        method: 'GET',
        credentials: 'omit',
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const blob = await response.blob()
      const blobUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = blobUrl
      link.download = `Room_${props.room.room_number}_QR.png`
      link.style.display = 'none'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(blobUrl)

      successMessage.value = `✅ QR code downloaded: Room_${props.room.room_number}_QR.png (direct)`
      console.log('✅ Fallback download successful')
    } catch (fallbackErr: any) {
      console.error('❌ Both methods failed:', fallbackErr)
      errorMessage.value = `Download failed: ${fallbackErr.message}`
    }
  }
}

/**
 * Print QR code
 */
const printQRCode = async () => {
  if (!props.room.id) return

  try {
    successMessage.value = ''
    errorMessage.value = ''

    const response = await api.get(`/admin/qr-codes/${props.room.id}/print-template`, {
      responseType: 'text',
    })

    if (!response.data) {
      throw new Error('No HTML template received')
    }

    // Open print window
    const printWindow = window.open('', '_blank')
    if (printWindow) {
      printWindow.document.write(response.data)
      printWindow.document.close()
      printWindow.focus()
      
      // Delay print dialog to ensure content is loaded
      setTimeout(() => {
        printWindow.print()
      }, 500)
    } else {
      throw new Error('Could not open print window. Check browser popup settings.')
    }

    successMessage.value = '✅ Print dialog opened - Ready to print'
    console.log('Print template opened for room:', props.room.id)
  } catch (err: any) {
    const message = err.response?.data?.message || err.message || 'Failed to open print template'
    errorMessage.value = message
    console.error('Print Error:', err)
  }
}

/**
 * Regenerate QR code
 */
const regenerateQRCode = async () => {
  if (!props.room.id) return

  regenerating.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const response = await api.post(`/admin/qr-codes/${props.room.id}/regenerate`)

    if (response.data.success && response.data.data) {
      const qrData = response.data.data
      Object.assign(props.room, {
        qr_token: qrData.qr_token,
        qr_code_url: qrData.qr_url,
        qr_image_path: qrData.qr_image_path,
        qr_generated_at: qrData.qr_generated_at,
      })

      successMessage.value = `✅ QR code regenerated successfully for Room ${props.room.room_number}`
      console.log('QR regenerated for room:', props.room.id)
    } else {
      throw new Error(response.data.message || 'Regeneration failed')
    }
  } catch (err: any) {
    const message = err.response?.data?.message || err.message || 'Failed to regenerate QR code'
    errorMessage.value = message
    console.error('Regenerate Error:', err)
  } finally {
    regenerating.value = false
  }
}

/**
 * Format date
 */
const formatDate = (date: string | undefined) => {
  if (!date) return 'Unknown'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Load QR code on mount
onMounted(() => {
  if (props.room.id && !props.room.qr_code_url) {
    loadQRCode()
  }
})

// Reload when room ID changes
watch(
  () => props.room.id,
  () => {
    if (props.room.id) {
      loadQRCode()
    }
  }
)
</script>

<style scoped>
.qr-code-section {
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  margin: 20px 0;
}

.header {
  margin-bottom: 20px;
}

.header h3 {
  margin: 0 0 5px 0;
  color: #333;
  font-size: 18px;
}

.subtitle {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.qr-display {
  text-align: center;
}

.qr-container {
  margin: 20px 0;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
  display: inline-block;
}

.qr-container img {
  width: 200px;
  height: 200px;
  border: 2px solid #ddd;
  padding: 10px;
  background: white;
  border-radius: 4px;
  display: block;
}
.qr-token {
  margin: 10px 0 0 0;
  color: #666;
  font-size: 12px;
  font-family: monospace;
}
.actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin: 20px 0;
  flex-wrap: wrap;
}

.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s;
  font-weight: 500;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #6366f1;
  color: white;
}

.btn-secondary:hover {
  background: #4f46e5;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover {
  background: #d97706;
}

.btn-warning:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-small {
  padding: 8px 12px;
  font-size: 12px;
}

.info {
  margin-top: 15px;
  padding: 10px;
  background: #e8f5e9;
  border-left: 4px solid #4caf50;
  border-radius: 4px;
  color: #2e7d32;
  font-size: 13px;
}

.info p {
  margin: 0;
}

.loading,
.no-qr,
.error {
  padding: 20px;
  text-align: center;
  color: #666;
}

.no-qr {
  background: #f5f5f5;
  border-radius: 4px;
}

.error {
  background: #fee;
  color: #c33;
}

.message {
  margin-top: 15px;
  padding: 12px;
  border-radius: 4px;
  font-size: 14px;
}

.message.success {
  background: #e8f5e9;
  color: #2e7d32;
  border-left: 4px solid #4caf50;
}

.message.error {
  background: #ffebee;
  color: #c62828;
  border-left: 4px solid #f44336;
}
</style>
