<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import { useLanguageStore } from '@/stores/language'

interface WaiterSettings {
  notifications_enabled: boolean
  email_notifications: boolean
  sms_notifications: boolean
  theme: string
  language: string
}

interface PasswordForm {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

const languageStore = useLanguageStore()

const loading = ref(true)
const saving = ref(false)
const passwordSaving = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const changePasswordModal = ref(false)
let messageTimer: ReturnType<typeof setTimeout> | null = null

const showNotification = (msg: string, isError = false) => {
  if (messageTimer) clearTimeout(messageTimer)
  if (isError) {
    error.value = msg
    successMessage.value = null
  } else {
    successMessage.value = msg
    error.value = null
  }
  messageTimer = setTimeout(() => {
    successMessage.value = null
    error.value = null
  }, 5000)
}

const notifySuccess = (msg: string) => showNotification(msg, false)
const notifyError = (msg: string) => showNotification(msg, true)

const defaultSettings: WaiterSettings = {
  notifications_enabled: true,
  email_notifications: true,
  sms_notifications: false,
  theme: 'light',
  language: 'en',
}

const settings = ref<WaiterSettings>({ ...defaultSettings })
const originalSettings = ref<WaiterSettings>({ ...defaultSettings })

const createEmptyPasswordForm = (): PasswordForm => ({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const passwordForm = ref<PasswordForm>(createEmptyPasswordForm())

const loadSettings = async () => {
  try {
    loading.value = true
    error.value = null

    const data = await waiterService.getSettings()

    if (data) {
      settings.value = {
        notifications_enabled: data.notifications_enabled ?? true,
        email_notifications: data.email_notifications ?? true,
        sms_notifications: data.sms_notifications ?? false,
        theme: data.theme ?? 'light',
        language: data.language ?? 'en',
      }
      originalSettings.value = { ...settings.value }
    }
  } catch (err: any) {
    console.error('[WaiterSettings] Error loading settings:', err)
    error.value = err.message || 'Failed to load settings'
  } finally {
    loading.value = false
  }
}

onMounted(loadSettings)

onUnmounted(() => {
  if (messageTimer) clearTimeout(messageTimer)
})

const saveSettings = async () => {
  try {
    saving.value = true
    await waiterService.updateSettings(settings.value)
    originalSettings.value = { ...settings.value }
    notifySuccess('Settings saved successfully!')
  } catch (err: any) {
    console.error('[WaiterSettings] Error saving settings:', err)
    notifyError(err.message || 'Failed to save settings')
  } finally {
    saving.value = false
  }
}

const resetSettings = () => {
  settings.value = { ...originalSettings.value }
}

const updatePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    notifyError('Passwords do not match')
    return
  }

  if (passwordForm.value.new_password.length < 8) {
    notifyError('Password must be at least 8 characters')
    return
  }

  try {
    passwordSaving.value = true
    await waiterService.changePassword({
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation,
    })

    notifySuccess('Password updated successfully!')
    changePasswordModal.value = false
    passwordForm.value = createEmptyPasswordForm()
  } catch (err: any) {
    console.error('[WaiterSettings] Error updating password:', err)
    notifyError(err.message || 'Failed to update password')
  } finally {
    passwordSaving.value = false
  }
}

const onLanguageChange = () => {
  if (settings.value.language) {
    languageStore.setLanguage(settings.value.language)
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-4xl mx-auto">
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">{{ languageStore.t('settings', 'Settings') }}</h1>
          <p class="text-slate-600 mt-2">{{ languageStore.t('manage_preferences_account_settings', 'Manage your preferences and account settings') }}</p>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="relative w-12 h-12 mx-auto mb-4">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
              </svg>
              <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">{{ languageStore.t('loading_settings', 'Loading settings...') }}</p>
          </div>
        </div>

        <div v-else-if="error" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">{{ languageStore.t('error_loading_settings', 'Error loading settings') }}</p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
        </div>

        <div v-else class="space-y-6">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-6">{{ languageStore.t('notification_settings', 'Notification Settings') }}</h2>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                <div>
                  <p class="font-medium text-slate-900">{{ languageStore.t('push_notifications', 'Push Notifications') }}</p>
                  <p class="text-sm text-slate-600 mt-1">{{ languageStore.t('push_notifications_desc', 'Receive notifications for new orders and alerts') }}</p>
                </div>
                <div>
                  <input 
                    v-model="settings.notifications_enabled"
                    type="checkbox" 
                    class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                  >
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                <div>
                  <p class="font-medium text-slate-900">{{ languageStore.t('email_notifications', 'Email Notifications') }}</p>
                  <p class="text-sm text-slate-600 mt-1">{{ languageStore.t('email_notifications_desc', 'Receive email updates about your deliveries') }}</p>
                </div>
                <div>
                  <input 
                    v-model="settings.email_notifications"
                    type="checkbox" 
                    class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                  >
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                <div>
                  <p class="font-medium text-slate-900">{{ languageStore.t('sms_notifications', 'SMS Notifications') }}</p>
                  <p class="text-sm text-slate-600 mt-1">{{ languageStore.t('sms_notifications_desc', 'Receive SMS alerts for urgent messages') }}</p>
                </div>
                <div>
                  <input 
                    v-model="settings.sms_notifications"
                    type="checkbox" 
                    class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                  >
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-6">{{ languageStore.t('preferences', 'Preferences') }}</h2>
            
            <div class="space-y-4">
              <div class="p-4 bg-slate-50 rounded-lg">
                <p class="font-medium text-slate-900 mb-3">{{ languageStore.t('theme', 'Theme') }}</p>
                <div class="flex gap-4">
                  <label class="flex items-center gap-2">
                    <input 
                      v-model="settings.theme"
                      type="radio" 
                      value="light"
                      class="w-4 h-4 text-blue-600"
                    >
                    <span class="text-slate-700">{{ languageStore.t('light', 'Light') }}</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input 
                      v-model="settings.theme"
                      type="radio" 
                      value="dark"
                      class="w-4 h-4 text-blue-600"
                    >
                    <span class="text-slate-700">{{ languageStore.t('dark', 'Dark') }}</span>
                  </label>
                  <label class="flex items-center gap-2">
                    <input 
                      v-model="settings.theme"
                      type="radio" 
                      value="auto"
                      class="w-4 h-4 text-blue-600"
                    >
                    <span class="text-slate-700">{{ languageStore.t('auto', 'Auto') }}</span>
                  </label>
                </div>
              </div>

              <div class="p-4 bg-slate-50 rounded-lg">
                <label for="language" class="block font-medium text-slate-900 mb-3">{{ languageStore.t('language', 'Language') }}</label>
                <select 
                  v-model="settings.language"
                  id="language"
                  @change="onLanguageChange"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="en">English</option>
                  <option value="am">አማርኛ (Amharic)</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                </select>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-6">{{ languageStore.t('account', 'Account') }}</h2>
            
            <div class="space-y-4">
              <button
                @click="changePasswordModal = true"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
              >
                {{ languageStore.t('change_password', 'Change Password') }}
              </button>
              <button
                class="w-full px-4 py-3 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition font-medium border border-red-200"
              >
                {{ languageStore.t('logout_all_devices', 'Logout All Devices') }}
              </button>
            </div>
          </div>

          <div class="flex gap-4">
            <button
              @click="saveSettings"
              :disabled="saving"
              class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium disabled:opacity-50"
            >
              {{ saving ? languageStore.t('saving', 'Saving...') : languageStore.t('save_settings', 'Save Settings') }}
            </button>
            <button
              @click="resetSettings"
              class="px-6 py-3 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 transition font-medium"
            >
              {{ languageStore.t('reset', 'Reset') }}
            </button>
          </div>

          <div v-if="successMessage" class="bg-green-50 border-l-4 border-green-600 rounded-lg p-6">
            <p class="text-green-700 font-semibold">{{ successMessage }}</p>
          </div>
        </div>
      </div>

      <div v-if="changePasswordModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
          <h3 class="text-xl font-bold text-slate-900 mb-4">{{ languageStore.t('change_password', 'Change Password') }}</h3>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ languageStore.t('current_password', 'Current Password') }}</label>
              <input 
                v-model="passwordForm.current_password"
                type="password" 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ languageStore.t('new_password', 'New Password') }}</label>
              <input 
                v-model="passwordForm.new_password"
                type="password" 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ languageStore.t('confirm_password', 'Confirm Password') }}</label>
              <input 
                v-model="passwordForm.new_password_confirmation"
                type="password" 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button
              @click="changePasswordModal = false"
              class="flex-1 px-4 py-2 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300 transition font-medium"
            >
              {{ languageStore.t('cancel', 'Cancel') }}
            </button>
            <button
              @click="updatePassword"
              :disabled="passwordSaving"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50"
            >
              {{ passwordSaving ? languageStore.t('updating', 'Updating...') : languageStore.t('update_password', 'Update Password') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
