<template>
  <div class="qr-code-section">
    <div class="header">
      <h3>QR Code Management</h3>
      <p class="subtitle">Download or print the QR code for this room</p>
    </div>

    <div v-if="room.qr_code_url" class="qr-display">
      <div class="qr-container">
        <div v-if="imageLoading || imageError" class="qr-placeholder">
          <div v-if="imageLoading" class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading QR code...</p>
          </div>
          <div v-else-if="imageError" class="error-placeholder">
            <p>❌ Image failed to load</p>
            <button @click="loadQRCode" class="btn btn-small">Retry Load</button>
          </div>
        </div>
        <img
          v-show="!imageLoading && !imageError"
          :src="room.qr_code_url"
          :alt="`QR Code for Room ${room.room_number}`"
          @error="handleImageError"
          @load="handleImageLoad"
        />
        <p class="qr-token">Token: {{ room.qr_token }}</p>
      </div>

      <div class="actions flex-wrap items-center gap-3">
        <div
          class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700"
        >
          <label for="qrCopies" class="text-xs font-bold text-slate-700 dark:text-slate-300"
            >Print Copies:</label
          >
          <select
            id="qrCopies"
            v-model="printCopies"
            class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-xs font-black text-slate-900 dark:text-white cursor-pointer"
          >
            <option v-for="n in 10" :key="n" :value="n">
              {{ n }} {{ n === 1 ? 'copy' : 'copies' }}
            </option>
          </select>
        </div>

        <button @click="downloadQRCode" class="btn btn-primary">
          <span> Download PNG</span>
        </button>
        <button @click="printQRCode" class="btn btn-secondary">
          <span>🖨️ Print ({{ printCopies }})</span>
        </button>
        <button @click="regenerateQRCode" class="btn btn-warning" :disabled="regenerating">
          <span v-if="!regenerating"> Regenerate</span>
          <span v-else>Regenerating...</span>
        </button>
      </div>

      <div v-if="room.qr_generated_at" class="info">
        <p>QR Code generated: {{ formatDate(room.qr_generated_at) }}</p>
      </div>
    </div>

    <div v-else-if="loading" class="loading">
      <p>Loading QR code...</p>
    </div>
    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="loadQRCode" class="btn btn-small">Retry</button>
    </div>

    <div v-else class="no-qr">
      <p>No QR code found for this room</p>
      <button @click="regenerateQRCode" class="btn btn-primary" :disabled="regenerating">
        <span v-if="!regenerating">Generate QR Code</span>
        <span v-else>Generating...</span>
      </button>
    </div>

    <div v-if="successMessage" class="message success">{{ successMessage }}</div>
    <div v-if="errorMessage" class="message error">{{ errorMessage }}</div>
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
const imageLoading = ref(true)
const imageError = ref(false)
const printCopies = ref(1)

const handleImageLoad = () => {
  imageLoading.value = false
  imageError.value = false
}

const handleImageError = (event: Event) => {
  imageLoading.value = false
  imageError.value = true
  errorMessage.value = 'Failed to load QR code image. Attempting to reload...'

  // Auto-retry once after 1 second
  setTimeout(() => {
    if (props.room.id) {
      loadQRCode()
    }
  }, 1000)
}

const loadQRCode = async () => {
  if (!props.room.id) return

  loading.value = true
  error.value = ''
  errorMessage.value = ''
  imageError.value = false

  try {
    const response = await api.get(`/admin/qr-codes/${props.room.id}/image`)

    if (response.data.success && response.data.data) {
      const qrData = response.data.data

      // Clear previous error state
      imageError.value = false
      errorMessage.value = ''

      Object.assign(props.room, {
        qr_code_url: qrData.qr_url,
        qr_token: qrData.qr_token,
        qr_image_path: qrData.qr_image_path,
        qr_generated_at: qrData.qr_generated_at,
      })

      console.log('QR Code loaded successfully:', qrData)
    } else {
      throw new Error(response.data.message || 'No QR code data received')
    }
  } catch (err: any) {
    console.error('[QRCodeDownload] Error fetching QR code:', err)
    const message = err.response?.data?.message || err.message || 'Failed to load QR code'
    error.value = message
    errorMessage.value = message

    // If the QR code doesn't exist, try to generate it
    if (err.response?.status === 404 || message.includes('not found')) {
      console.log('QR code not found, attempting to regenerate...')
      await regenerateQRCode()
    }
  } finally {
    loading.value = false
  }
}
const downloadQRCode = async () => {
  if (!props.room.id || !props.room.qr_code_url) return

  try {
    successMessage.value = ''
    errorMessage.value = ''

    const downloadUrl = `${api.defaults.baseURL?.replace('/api', '')}/api/qr-codes/download/${props.room.id}`

    const response = await fetch(downloadUrl, {
      method: 'GET',
      credentials: 'omit',
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`)
    }

    const blob = await response.blob()

    if (blob.size === 0) {
      throw new Error('Downloaded file is empty')
    }

    const blobUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = `Room_${props.room.room_number}_QR.png`
    link.style.display = 'none'

    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    setTimeout(() => {
      window.URL.revokeObjectURL(blobUrl)
    }, 100)

    successMessage.value = ` QR code downloaded: Room_${props.room.room_number}_QR.png`
  } catch (err: any) {
    console.error('[QRCodeDownload] Error downloading QR code:', err)
    const message = err.message || 'Failed to download QR code'
    errorMessage.value = message

    try {
      const directUrl = props.room.qr_code_url

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

      successMessage.value = ` QR code downloaded: Room_${props.room.room_number}_QR.png (direct)`
    } catch (fallbackErr: any) {
      console.error('[QRCodeDownload] Fallback direct download failed:', fallbackErr)
      errorMessage.value = `Download failed: ${fallbackErr.message}`
    }
  }
}

const printQRCode = async () => {
  if (!props.room.id) return

  try {
    successMessage.value = ''
    errorMessage.value = ''

    const response = await api.get(
      `/admin/qr-codes/${props.room.id}/print-template?copies=${printCopies.value}`,
      {
        responseType: 'text',
      },
    )

    if (!response.data) {
      throw new Error('No HTML template received')
    }

    const printWindow = window.open('', '_blank')
    if (printWindow) {
      printWindow.document.write(response.data)
      printWindow.document.close()
      printWindow.focus()

      setTimeout(() => {
        printWindow.print()
      }, 500)
    } else {
      throw new Error('Could not open print window. Check browser popup settings.')
    }

    successMessage.value = ' Print dialog opened - Ready to print'
  } catch (err: any) {
    console.error('[QRCodeDownload] Error printing QR code:', err)
    const message = err.response?.data?.message || err.message || 'Failed to open print template'
    errorMessage.value = message
  }
}

const regenerateQRCode = async () => {
  if (!props.room.id) return

  regenerating.value = true
  successMessage.value = ''
  errorMessage.value = ''
  imageError.value = false

  try {
    console.log(`Regenerating QR code for room ${props.room.id}...`)
    const response = await api.post(`/admin/qr-codes/${props.room.id}/regenerate`)

    if (response.data.success && response.data.data) {
      const qrData = response.data.data
      Object.assign(props.room, {
        qr_token: qrData.qr_token,
        qr_code_url: qrData.qr_url,
        qr_image_path: qrData.qr_image_path,
        qr_generated_at: qrData.qr_generated_at,
      })

      successMessage.value = ` QR code regenerated successfully for Room ${props.room.room_number}`
      console.log('QR code regenerated successfully:', qrData)

      // Clear any error state
      error.value = ''
      errorMessage.value = ''
      imageError.value = false
    } else {
      throw new Error(response.data.message || 'Regeneration failed')
    }
  } catch (err: any) {
    console.error('[QRCodeDownload] Error regenerating QR code:', err)
    const message = err.response?.data?.message || err.message || 'Failed to regenerate QR code'
    errorMessage.value = message
    error.value = message
  } finally {
    regenerating.value = false
  }
}

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

onMounted(() => {
  if (props.room.id && !props.room.qr_code_url) {
    loadQRCode()
  }
})

watch(
  () => props.room.id,
  () => {
    if (props.room.id) {
      loadQRCode()
    }
  },
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
  object-fit: contain;
}

.qr-container img[src=''],
.qr-container img:not([src]) {
  opacity: 0.3;
  background: #f0f0f0;
}

.qr-placeholder {
  width: 200px;
  height: 200px;
  border: 2px solid #ddd;
  padding: 10px;
  background: white;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}

.loading-spinner {
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 10px;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.error-placeholder {
  text-align: center;
  color: #666;
}

.error-placeholder p {
  margin-bottom: 10px;
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
