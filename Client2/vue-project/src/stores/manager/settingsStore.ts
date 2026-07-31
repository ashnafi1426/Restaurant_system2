import { defineStore } from 'pinia'
import { ref } from 'vue'
import managerService from '@/services/managerService'

interface DashboardSetting {
  id: string
  key: string
  value: any
  userId: string
}

interface Announcement {
  id: string
  title: string
  content: string
  type: string
  createdAt: string
  updatedAt: string
}

interface Report {
  id: string
  name: string
  description: string
  type: string
  generatedAt: string
}

export const useManagerSettingsStore = defineStore('managerSettings', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const dashboardSettings = ref<DashboardSetting[]>([])
  const announcements = ref<Announcement[]>([])
  const reports = ref<Report[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadDashboardSettings() {
    try {
      loading.value = true
      error.value = null
      dashboardSettings.value = await managerService.getDashboardSettings()
    } catch (err: any) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  async function updateDashboardSetting(settingId: string, data: any) {
    try {
      const updated = await managerService.updateDashboardSetting(settingId, data)
      const index = dashboardSettings.value.findIndex((s) => s.id === settingId)
      if (index > -1) {
        dashboardSettings.value[index] = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function loadAnnouncements() {
    try {
      announcements.value = await managerService.getAnnouncements()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function createAnnouncement(data: { title: string; content: string; type: string }) {
    try {
      const announcement = await managerService.createAnnouncement(data)
      announcements.value.unshift(announcement)
      return announcement
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function updateAnnouncement(announcementId: string, data: any) {
    try {
      const updated = await managerService.updateAnnouncement(announcementId, data)
      const index = announcements.value.findIndex((a) => a.id === announcementId)
      if (index > -1) {
        announcements.value[index] = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function deleteAnnouncement(announcementId: string) {
    try {
      await managerService.deleteAnnouncement(announcementId)
      announcements.value = announcements.value.filter((a) => a.id !== announcementId)
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function loadReports() {
    try {
      reports.value = await managerService.getReports()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function initialize() {
    await Promise.all([loadDashboardSettings(), loadAnnouncements(), loadReports()])
  }

  function reset() {
    dashboardSettings.value = []
    announcements.value = []
    reports.value = []
    error.value = null
  }

  return {
    dashboardSettings,
    announcements,
    reports,
    loading,
    error,
    loadDashboardSettings,
    updateDashboardSetting,
    loadAnnouncements,
    createAnnouncement,
    updateAnnouncement,
    deleteAnnouncement,
    loadReports,
    initialize,
    reset,
  }
})
