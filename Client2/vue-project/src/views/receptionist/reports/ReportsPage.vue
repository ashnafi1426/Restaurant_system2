<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  getReservationReport,
  getOccupancyReport,
  getGuestReport,
  getRevenueReport,
  getCheckInOutReport,
  type ReservationReportData,
  type OccupancyReportData,
  type GuestReportData,
  type RevenueReportData,
  type CheckInOutReportData
} from '@/services/receptionReportService'
import { jsPDF } from 'jspdf'
import html2canvas from 'html2canvas'

type ReportType = 'reservation' | 'occupancy' | 'guest' | 'revenue' | 'checkinout'

const activeReport = ref<ReportType>('reservation')
const loading = ref(false)
const exporting = ref(false)
const dateRange = ref({
  start_date: new Date(new Date().getFullYear(), new Date().getMonth(), 1)
    .toISOString()
    .split('T')[0],
  end_date: new Date().toISOString().split('T')[0]
})

// Report data
const reservationData = ref<ReservationReportData | null>(null)
const occupancyData = ref<OccupancyReportData | null>(null)
const guestData = ref<GuestReportData | null>(null)
const revenueData = ref<RevenueReportData | null>(null)
const checkInOutData = ref<CheckInOutReportData | null>(null)

const reportTabs = [
  { id: 'reservation', label: 'Reservations', icon: '📅' },
  { id: 'occupancy', label: 'Occupancy', icon: '🏨' },
  { id: 'guest', label: 'Guests', icon: '👥' },
  { id: 'revenue', label: 'Revenue', icon: '💰' },
  { id: 'checkinout', label: 'Check-In/Out', icon: '🚪' }
]

const loadReportData = async () => {
  loading.value = true
  try {
    const params = {
      start_date: dateRange.value.start_date,
      end_date: dateRange.value.end_date
    }

    switch (activeReport.value) {
      case 'reservation':
        const resData = await getReservationReport(params)
        reservationData.value = resData.data
        break
      case 'occupancy':
        const occData = await getOccupancyReport(params)
        occupancyData.value = occData.data
        break
      case 'guest':
        const gstData = await getGuestReport(params)
        guestData.value = gstData.data
        break
      case 'revenue':
        const revData = await getRevenueReport(params)
        revenueData.value = revData.data
        break
      case 'checkinout':
        const chkData = await getCheckInOutReport(params)
        checkInOutData.value = chkData.data
        break
    }
  } catch (error) {
    console.error('Failed to load report:', error)
  } finally {
    loading.value = false
  }
}

const switchReport = (reportType: ReportType) => {
  activeReport.value = reportType
  loadReportData()
}

const applyDateFilter = () => {
  loadReportData()
}

const exportToPDF = async () => {
  exporting.value = true
  try {
    // Get the report content element
    const reportElement = document.getElementById('report-content')
    if (!reportElement) {
      console.error('Report content element not found')
      return
    }

    // Show message
    console.log('Generating PDF... Please wait.')

    // Generate canvas from HTML
    const canvas = await html2canvas(reportElement, {
      scale: 2,
      useCORS: true,
      logging: false,
      backgroundColor: '#ffffff',
      windowWidth: reportElement.scrollWidth,
      windowHeight: reportElement.scrollHeight
    })

    // Calculate PDF dimensions
    const imgWidth = 210 // A4 width in mm
    const pageHeight = 297 // A4 height in mm
    const imgHeight = (canvas.height * imgWidth) / canvas.width
    let heightLeft = imgHeight

    // Create PDF
    const pdf = new jsPDF('p', 'mm', 'a4')
    
    // Add header
    pdf.setFontSize(16)
    pdf.setTextColor(0, 128, 128) // Teal color
    const reportTitles = {
      reservation: 'Reservation Report',
      occupancy: 'Occupancy Report',
      guest: 'Guest Report',
      revenue: 'Revenue Report',
      checkinout: 'Check-In/Check-Out Report'
    }
    pdf.text(reportTitles[activeReport.value], 105, 15, { align: 'center' })
    
    pdf.setFontSize(10)
    pdf.setTextColor(100, 100, 100)
    pdf.text(`Period: ${dateRange.value.start_date} to ${dateRange.value.end_date}`, 105, 22, { align: 'center' })
    pdf.text(`Generated: ${new Date().toLocaleString()}`, 105, 28, { align: 'center' })

    // Add image to PDF starting below header
    const imgData = canvas.toDataURL('image/png')
    let position = 35 // Start below header
    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
    heightLeft -= (pageHeight - position)

    // Add more pages if needed
    while (heightLeft >= 0) {
      position = heightLeft - imgHeight
      pdf.addPage()
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
      heightLeft -= pageHeight
    }

    // Generate filename
    const reportNames = {
      reservation: 'Reservation_Report',
      occupancy: 'Occupancy_Report',
      guest: 'Guest_Report',
      revenue: 'Revenue_Report',
      checkinout: 'CheckInOut_Report'
    }
    const filename = `${reportNames[activeReport.value]}_${dateRange.value.start_date}_to_${dateRange.value.end_date}.pdf`

    // Save PDF
    pdf.save(filename)
    console.log('PDF generated successfully!')
  } catch (error) {
    console.error('Failed to export PDF:', error)
    alert('Failed to export PDF. Please try again.')
  } finally {
    exporting.value = false
  }
}

onMounted(() => {
  loadReportData()
})
</script>

<template>
  <DashboardLayout>
    <div class="w-full min-h-screen bg-gray-50 dark:bg-slate-900">
      <!-- Header -->
      <div class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📊 Reception Reports</h1>
            <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">
              Comprehensive analytics and insights
            </p>
          </div>

          <!-- Date Range Filter -->
          <div class="flex gap-3 items-center">
            <div class="flex gap-2 items-center">
              <label class="text-sm text-gray-600 dark:text-slate-400">From:</label>
              <input
                v-model="dateRange.start_date"
                type="date"
                class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white"
              />
            </div>
            <div class="flex gap-2 items-center">
              <label class="text-sm text-gray-600 dark:text-slate-400">To:</label>
              <input
                v-model="dateRange.end_date"
                type="date"
                class="px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-700 dark:text-white"
              />
            </div>
            <button
              @click="applyDateFilter"
              class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition"
            >
              Apply
            </button>
            <button
              @click="exportToPDF"
              :disabled="exporting || loading"
              class="px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition flex items-center gap-2"
            >
              <svg v-if="!exporting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ exporting ? 'Exporting...' : 'Export PDF' }}</span>
            </button>
          </div>
        </div>

        <!-- Report Tabs -->
        <div class="flex gap-2 mt-4 border-t border-gray-200 dark:border-slate-700 pt-4">
          <button
            v-for="tab in reportTabs"
            :key="tab.id"
            @click="switchReport(tab.id as ReportType)"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2',
              activeReport === tab.id
                ? 'bg-teal-600 text-white shadow-md'
                : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600'
            ]"
          >
            <span>{{ tab.icon }}</span>
            <span>{{ tab.label }}</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
          <div
            class="w-16 h-16 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin mx-auto mb-4"
          ></div>
          <p class="text-gray-600 dark:text-slate-400">Loading report data...</p>
        </div>
      </div>

      <!-- Report Content -->
      <div v-else id="report-content" class="p-6 bg-white dark:bg-slate-900">
        <!-- Reservation Report -->
        <div v-if="activeReport === 'reservation' && reservationData" class="space-y-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-6 gap-4">
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
              <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ reservationData.summary.total }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-orange-200">
              <p class="text-xs text-orange-600 uppercase">Pending</p>
              <p class="text-2xl font-bold text-orange-600 mt-1">
                {{ reservationData.summary.pending }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-purple-200">
              <p class="text-xs text-purple-600 uppercase">Confirmed</p>
              <p class="text-2xl font-bold text-purple-600 mt-1">
                {{ reservationData.summary.confirmed }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-teal-200">
              <p class="text-xs text-teal-600 uppercase">Checked In</p>
              <p class="text-2xl font-bold text-teal-600 mt-1">
                {{ reservationData.summary.checked_in }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-green-200">
              <p class="text-xs text-green-600 uppercase">Checked Out</p>
              <p class="text-2xl font-bold text-green-600 mt-1">
                {{ reservationData.summary.checked_out }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-red-200">
              <p class="text-xs text-red-600 uppercase">Cancelled</p>
              <p class="text-2xl font-bold text-red-600 mt-1">
                {{ reservationData.summary.cancelled }}
              </p>
            </div>
          </div>

          <!-- Daily Stats Table -->
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-4 border-b border-gray-200 dark:border-slate-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Statistics</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Pending</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Confirmed</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                  <tr v-for="stat in reservationData.daily_stats" :key="stat.date" class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ stat.date }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300">{{ stat.count }}</td>
                    <td class="px-4 py-3 text-sm text-orange-600">{{ stat.pending }}</td>
                    <td class="px-4 py-3 text-sm text-purple-600">{{ stat.confirmed }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Occupancy Report -->
        <div v-if="activeReport === 'occupancy' && occupancyData" class="space-y-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
              <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total Rooms</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ occupancyData.summary.total_rooms }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-green-200">
              <p class="text-xs text-green-600 uppercase">Available</p>
              <p class="text-3xl font-bold text-green-600 mt-2">
                {{ occupancyData.summary.available }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-blue-200">
              <p class="text-xs text-blue-600 uppercase">Occupied</p>
              <p class="text-3xl font-bold text-blue-600 mt-2">
                {{ occupancyData.summary.occupied }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-teal-200">
              <p class="text-xs text-teal-600 uppercase">Avg Occupancy</p>
              <p class="text-3xl font-bold text-teal-600 mt-2">
                {{ occupancyData.summary.avg_occupancy_rate }}%
              </p>
            </div>
          </div>

          <!-- Daily Occupancy Table -->
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-4 border-b border-gray-200 dark:border-slate-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Occupancy</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Occupied</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Available</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Rate</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                  <tr v-for="stat in occupancyData.daily_occupancy" :key="stat.date" class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ stat.date }}</td>
                    <td class="px-4 py-3 text-sm text-blue-600">{{ stat.occupied }}</td>
                    <td class="px-4 py-3 text-sm text-green-600">{{ stat.available }}</td>
                    <td class="px-4 py-3 text-sm text-teal-600 font-semibold">{{ stat.occupancy_rate }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Guest Report -->
        <div v-if="activeReport === 'guest' && guestData" class="space-y-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
              <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total Guests</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ guestData.summary.total_guests }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-teal-200">
              <p class="text-xs text-teal-600 uppercase">New Guests (Period)</p>
              <p class="text-3xl font-bold text-teal-600 mt-2">
                {{ guestData.summary.new_guests }}
              </p>
            </div>
          </div>

          <!-- Top Guests Table -->
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-4 border-b border-gray-200 dark:border-slate-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Guests (By Reservations)</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Phone</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Reservations</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                  <tr v-for="guest in guestData.top_guests" :key="guest.id" class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">
                      {{ guest.first_name }} {{ guest.last_name }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-400">{{ guest.email }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-400">{{ guest.phone }}</td>
                    <td class="px-4 py-3 text-sm text-teal-600 font-bold">{{ guest.reservations_count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Revenue Report -->
        <div v-if="activeReport === 'revenue' && revenueData" class="space-y-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-teal-200">
              <p class="text-xs text-teal-600 uppercase">Total Revenue</p>
              <p class="text-3xl font-bold text-teal-600 mt-2">
                ETB {{ revenueData.summary.total_revenue.toLocaleString() }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-blue-200">
              <p class="text-xs text-blue-600 uppercase">Reservations</p>
              <p class="text-2xl font-bold text-blue-600 mt-2">
                ETB {{ revenueData.summary.reservation_revenue.toLocaleString() }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-purple-200">
              <p class="text-xs text-purple-600 uppercase">Orders</p>
              <p class="text-2xl font-bold text-purple-600 mt-2">
                ETB {{ revenueData.summary.order_revenue.toLocaleString() }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
              <p class="text-xs text-gray-500 dark:text-slate-400 uppercase">Payment Count</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ revenueData.summary.payment_count }}
              </p>
            </div>
          </div>

          <!-- Daily Revenue Table -->
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-4 border-b border-gray-200 dark:border-slate-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Revenue</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Revenue</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Transactions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                  <tr v-for="stat in revenueData.daily_revenue" :key="stat.date" class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ stat.date }}</td>
                    <td class="px-4 py-3 text-sm text-teal-600 font-bold">ETB {{ stat.total.toLocaleString() }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-400">{{ stat.count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Check-In/Out Report -->
        <div v-if="activeReport === 'checkinout' && checkInOutData" class="space-y-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-teal-200">
              <p class="text-xs text-teal-600 uppercase">Total Check-Ins</p>
              <p class="text-3xl font-bold text-teal-600 mt-2">
                {{ checkInOutData.summary.total_check_ins }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-red-200">
              <p class="text-xs text-red-600 uppercase">Total Check-Outs</p>
              <p class="text-3xl font-bold text-red-600 mt-2">
                {{ checkInOutData.summary.total_check_outs }}
              </p>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm border border-blue-200">
              <p class="text-xs text-blue-600 uppercase">Active Guests</p>
              <p class="text-3xl font-bold text-blue-600 mt-2">
                {{ checkInOutData.summary.active_guests }}
              </p>
            </div>
          </div>

          <!-- Daily Stats Table -->
          <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
            <div class="p-4 border-b border-gray-200 dark:border-slate-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Activity</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Check-Ins</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase">Check-Outs</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                  <tr v-for="stat in checkInOutData.daily_stats" :key="stat.date" class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ stat.date }}</td>
                    <td class="px-4 py-3 text-sm text-teal-600 font-semibold">{{ stat.check_ins }}</td>
                    <td class="px-4 py-3 text-sm text-red-600 font-semibold">{{ stat.check_outs }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
