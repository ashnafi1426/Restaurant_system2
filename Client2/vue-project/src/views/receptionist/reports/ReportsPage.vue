<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
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
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { jsPDF } from 'jspdf'
import html2canvas from 'html2canvas'
import { ChevronLeft, ChevronRight, Loader2, Calendar, FileText, Download, Building2 } from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

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

// Pagination State
const currentPage = ref(1)
const perPage = ref(10)

// Report data
const reservationData = ref<ReservationReportData | null>(null)
const occupancyData = ref<OccupancyReportData | null>(null)
const guestData = ref<GuestReportData | null>(null)
const revenueData = ref<RevenueReportData | null>(null)
const checkInOutData = ref<CheckInOutReportData | null>(null)

const reportTabs = computed(() => [
  { id: 'reservation', label: languageStore.t('reservations', 'Reservations'), icon: '📅' },
  { id: 'occupancy', label: languageStore.t('occupancy', 'Occupancy'), icon: '🏨' },
  { id: 'guest', label: languageStore.t('guests', 'Guests'), icon: '👥' },
  { id: 'revenue', label: languageStore.t('revenue', 'Revenue'), icon: '💰' },
  { id: 'checkinout', label: languageStore.t('check_in_out', 'Check-In/Out'), icon: '🚪' }
])

// Computed Active Dataset Count
const activeDataset = computed<any[]>(() => {
  switch (activeReport.value) {
    case 'reservation':
      return reservationData.value?.daily_stats || []
    case 'occupancy':
      return occupancyData.value?.daily_occupancy || []
    case 'guest':
      return guestData.value?.top_guests || []
    case 'revenue':
      return revenueData.value?.daily_revenue || []
    case 'checkinout':
      return checkInOutData.value?.daily_stats || []
    default:
      return []
  }
})

const totalItems = computed(() => activeDataset.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / perPage.value) || 1)

const paginatedDataset = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return activeDataset.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (totalItems.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, totalItems.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= totalPages.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

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
    currentPage.value = 1
  } catch (error) {
    console.error('Failed to load report:', error)
  } finally {
    loading.value = false
  }
}

const switchReport = (reportType: ReportType) => {
  activeReport.value = reportType
  currentPage.value = 1
  loadReportData()
}

const applyDateFilter = () => {
  currentPage.value = 1
  loadReportData()
}

const exportToPDF = async () => {
  exporting.value = true
  try {
    const reportElement = document.getElementById('report-content')
    if (!reportElement) return

    const canvas = await html2canvas(reportElement, {
      scale: 2,
      useCORS: true,
      logging: false,
      backgroundColor: '#ffffff',
      windowWidth: reportElement.scrollWidth,
      windowHeight: reportElement.scrollHeight
    })

    const imgWidth = 210
    const pageHeight = 297
    const imgHeight = (canvas.height * imgWidth) / canvas.width
    let heightLeft = imgHeight

    const pdf = new jsPDF('p', 'mm', 'a4')
    
    pdf.setFontSize(16)
    pdf.setTextColor(0, 128, 128)
    const reportTitles = {
      reservation: 'Reservation Report',
      occupancy: 'Occupancy Report',
      guest: 'Guest Report',
      revenue: 'Revenue Report',
      checkinout: 'Check-In/Check-Out Report'
    }
    const hotelTitle = hotelStore.hotelName ? `${hotelStore.hotelName} - ${reportTitles[activeReport.value]}` : reportTitles[activeReport.value]
    pdf.text(hotelTitle, 105, 15, { align: 'center' })
    
    pdf.setFontSize(10)
    pdf.setTextColor(100, 100, 100)
    pdf.text(`Period: ${dateRange.value.start_date} to ${dateRange.value.end_date}`, 105, 22, { align: 'center' })
    pdf.text(`Generated: ${new Date().toLocaleString()}`, 105, 28, { align: 'center' })

    const imgData = canvas.toDataURL('image/png')
    let position = 35
    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
    heightLeft -= (pageHeight - position)

    while (heightLeft >= 0) {
      position = heightLeft - imgHeight
      pdf.addPage()
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
      heightLeft -= pageHeight
    }

    const reportNames = {
      reservation: 'Reservation_Report',
      occupancy: 'Occupancy_Report',
      guest: 'Guest_Report',
      revenue: 'Revenue_Report',
      checkinout: 'CheckInOut_Report'
    }
    const filename = `${reportNames[activeReport.value]}_${dateRange.value.start_date}_to_${dateRange.value.end_date}.pdf`

    pdf.save(filename)
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

watch(() => hotelStore.hotelId, () => {
  loadReportData()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('reception_reports', 'Reception Reports') }}</h1>
            <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
              <Building2 class="w-3 h-3" />
              {{ hotelStore.hotelName }}
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('reception_reports_desc', 'Comprehensive hotel occupancy, reservation, and revenue analytics.') }}</p>
        </div>

        <!-- Date Range Filter & Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ languageStore.t('from_date', 'From:') }}</span>
            <input
              v-model="dateRange.start_date"
              type="date"
              class="bg-transparent text-xs font-black text-slate-900 dark:text-white focus:outline-none"
            />
          </div>

          <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ languageStore.t('to_date', 'To:') }}</span>
            <input
              v-model="dateRange.end_date"
              type="date"
              class="bg-transparent text-xs font-black text-slate-900 dark:text-white focus:outline-none"
            />
          </div>

          <button
            @click="applyDateFilter"
            class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs shadow-md shadow-teal-600/20 transition cursor-pointer"
          >
            {{ languageStore.t('apply', 'Apply') }}
          </button>

          <button
            @click="exportToPDF"
            :disabled="exporting || loading"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-extrabold text-xs shadow-md shadow-purple-600/20 transition flex items-center gap-1.5 cursor-pointer"
          >
            <Loader2 v-if="exporting" class="w-4 h-4 animate-spin" />
            <Download v-else class="w-4 h-4" />
            <span>{{ exporting ? languageStore.t('exporting', 'Exporting...') : languageStore.t('export_pdf', 'Export PDF') }}</span>
          </button>
        </div>
      </div>

      <!-- Report Tab Selector Bar -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-1.5 shadow-xs">
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-1">
          <button
            v-for="tab in reportTabs"
            :key="tab.id"
            @click="switchReport(tab.id as ReportType)"
            :class="[
              'py-2.5 px-3 font-black text-xs rounded-2xl transition cursor-pointer flex items-center justify-center gap-2',
              activeReport === tab.id
                ? 'bg-teal-600 text-white shadow-md shadow-teal-600/20'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            <span>{{ tab.icon }}</span>
            <span>{{ tab.label }}</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-16 text-center space-y-3">
        <Loader2 class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ languageStore.t('loading_data', 'Loading report data...') }}</p>
      </div>

      <!-- Main Report Content -->
      <div v-else id="report-content" class="space-y-6">
        <!-- Reservation Report -->
        <div v-if="activeReport === 'reservation' && reservationData" class="space-y-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ languageStore.t('total', 'Total') }}</p>
              <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ reservationData.summary.total }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-amber-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ languageStore.t('pending', 'Pending') }}</p>
              <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ reservationData.summary.pending }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-purple-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">{{ languageStore.t('confirmed', 'Confirmed') }}</p>
              <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ reservationData.summary.confirmed }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-teal-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">{{ languageStore.t('checked_in', 'Checked In') }}</p>
              <p class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">{{ reservationData.summary.checked_in }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-emerald-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ languageStore.t('checked_out', 'Checked Out') }}</p>
              <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ reservationData.summary.checked_out }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-rose-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">{{ languageStore.t('cancelled', 'Cancelled') }}</p>
              <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ reservationData.summary.cancelled }}</p>
            </div>
          </div>

          <!-- Table Container -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('daily_reservation_statistics', 'Daily Reservation Statistics') }}</h3>
              <span class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700">
                {{ totalItems }} {{ languageStore.t('days_logged', 'Days Logged') }}
              </span>
            </div>

            <div class="overflow-x-auto w-full">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('date', 'Date') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('total', 'Total') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('pending', 'Pending') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('confirmed', 'Confirmed') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                  <tr v-for="stat in paginatedDataset" :key="stat.date" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                    <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-white whitespace-nowrap">{{ stat.date }}</td>
                    <td class="px-4 py-3 font-black text-slate-900 dark:text-white whitespace-nowrap">{{ stat.count }}</td>
                    <td class="px-4 py-3 font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ stat.pending }}</td>
                    <td class="px-4 py-3 font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap">{{ stat.confirmed }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Occupancy Report -->
        <div v-if="activeReport === 'occupancy' && occupancyData" class="space-y-6">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ languageStore.t('total_rooms', 'Total Rooms') }}</p>
              <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ occupancyData.summary.total_rooms }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-emerald-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ languageStore.t('available', 'Available') }}</p>
              <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ occupancyData.summary.available }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-blue-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">{{ languageStore.t('occupied', 'Occupied') }}</p>
              <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ occupancyData.summary.occupied }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-teal-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">{{ languageStore.t('avg_occupancy_rate', 'Avg Occupancy Rate') }}</p>
              <p class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">{{ occupancyData.summary.avg_occupancy_rate }}%</p>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('daily_occupancy_statistics', 'Daily Occupancy Statistics') }}</h3>
              <span class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700">
                {{ totalItems }} {{ languageStore.t('days_logged', 'Days Logged') }}
              </span>
            </div>

            <div class="overflow-x-auto w-full">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('date', 'Date') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('occupied', 'Occupied') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('available', 'Available') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('rate', 'Rate') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                  <tr v-for="stat in paginatedDataset" :key="stat.date" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                    <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-white whitespace-nowrap">{{ stat.date }}</td>
                    <td class="px-4 py-3 font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">{{ stat.occupied }}</td>
                    <td class="px-4 py-3 font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ stat.available }}</td>
                    <td class="px-4 py-3 font-black text-teal-600 dark:text-teal-400 whitespace-nowrap">{{ stat.occupancy_rate }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Guest Report -->
        <div v-if="activeReport === 'guest' && guestData" class="space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ languageStore.t('total_guests', 'Total Guests') }}</p>
              <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ guestData.summary.total_guests }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-teal-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">{{ languageStore.t('new_guests_period', 'New Guests (Period)') }}</p>
              <p class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">{{ guestData.summary.new_guests }}</p>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('top_guests_reservations', 'Top Guests (By Reservations)') }}</h3>
              <span class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700">
                {{ totalItems }} {{ languageStore.t('guests', 'Guests') }}
              </span>
            </div>

            <div class="overflow-x-auto w-full">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('name', 'Name') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('email', 'Email') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('phone', 'Phone') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('reservations', 'Reservations') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                  <tr v-for="guest in paginatedDataset" :key="guest.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                    <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-white whitespace-nowrap">{{ guest.first_name }} {{ guest.last_name }}</td>
                    <td class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ guest.email }}</td>
                    <td class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ guest.phone }}</td>
                    <td class="px-4 py-3 font-black text-teal-600 dark:text-teal-400 whitespace-nowrap">{{ guest.reservations_count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Revenue Report -->
        <div v-if="activeReport === 'revenue' && revenueData" class="space-y-6">
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-teal-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">{{ languageStore.t('total_revenue', 'Total Revenue') }}</p>
              <p class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">ETB {{ (revenueData.summary.total_revenue || 0).toLocaleString() }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-blue-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">{{ languageStore.t('reservations_revenue', 'Reservations Revenue') }}</p>
              <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">ETB {{ (revenueData.summary.reservation_revenue || 0).toLocaleString() }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-purple-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">{{ languageStore.t('orders_revenue', 'Orders Revenue') }}</p>
              <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">ETB {{ (revenueData.summary.order_revenue || 0).toLocaleString() }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ languageStore.t('payment_count', 'Payment Count') }}</p>
              <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ revenueData.summary.payment_count }}</p>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('daily_revenue_statistics', 'Daily Revenue Statistics') }}</h3>
              <span class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700">
                {{ totalItems }} {{ languageStore.t('days_logged', 'Days Logged') }}
              </span>
            </div>

            <div class="overflow-x-auto w-full">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('date', 'Date') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('revenue', 'Revenue') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('transactions', 'Transactions') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                  <tr v-for="stat in paginatedDataset" :key="stat.date" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                    <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-white whitespace-nowrap">{{ stat.date }}</td>
                    <td class="px-4 py-3 font-black text-teal-600 dark:text-teal-400 whitespace-nowrap">ETB {{ (stat.total || 0).toLocaleString() }}</td>
                    <td class="px-4 py-3 font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ stat.count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Check-In/Out Report -->
        <div v-if="activeReport === 'checkinout' && checkInOutData" class="space-y-6">
          <div class="grid grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-teal-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider">{{ languageStore.t('total_checkins', 'Total Check-Ins') }}</p>
              <p class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-1">{{ checkInOutData.summary.total_check_ins }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-rose-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">{{ languageStore.t('total_checkouts', 'Total Check-Outs') }}</p>
              <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ checkInOutData.summary.total_check_outs }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-blue-500/30 shadow-xs">
              <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">{{ languageStore.t('active_guests', 'Active Guests') }}</p>
              <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ checkInOutData.summary.active_guests }}</p>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ languageStore.t('daily_checkin_checkout_activity', 'Daily Check-In / Check-Out Activity') }}</h3>
              <span class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700">
                {{ totalItems }} {{ languageStore.t('days_logged', 'Days Logged') }}
              </span>
            </div>

            <div class="overflow-x-auto w-full">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('date', 'Date') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('check_ins', 'Check-Ins') }}</th>
                    <th class="px-4 py-3 whitespace-nowrap">{{ languageStore.t('check_outs', 'Check-Outs') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                  <tr v-for="stat in paginatedDataset" :key="stat.date" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                    <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-white whitespace-nowrap">{{ stat.date }}</td>
                    <td class="px-4 py-3 font-bold text-teal-600 dark:text-teal-400 whitespace-nowrap">{{ stat.check_ins }}</td>
                    <td class="px-4 py-3 font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">{{ stat.check_outs }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Global Interactive Pagination Bar for Active Report Table -->
        <div
          v-if="totalItems > 0"
          class="flex flex-col sm:flex-row items-center justify-between gap-4 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 rounded-3xl text-xs font-sans shadow-xs"
        >
          <!-- Left Side: Per Page Selector & Showing Count -->
          <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-700 dark:text-slate-300">{{ languageStore.t('items_per_page', 'Items per page:') }}</span>
              <select
                :value="perPage"
                @change="changePerPage"
                class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-black focus:outline-none focus:border-amber-500 cursor-pointer shadow-2xs"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="text-xs font-medium">
              {{ languageStore.t('showing', 'Showing') }} <span class="font-extrabold text-slate-900 dark:text-white">{{ showingFrom }}</span> {{ languageStore.t('to', 'to') }}
              <span class="font-extrabold text-slate-900 dark:text-white">{{ showingTo }}</span> {{ languageStore.t('of', 'of') }}
              <span class="font-extrabold text-slate-900 dark:text-white">{{ totalItems }}</span> {{ languageStore.t('records', 'records') }}
            </div>
          </div>

          <!-- Right Side: Page Controls -->
          <div class="flex items-center gap-1.5">
            <button
              @click="prevPage"
              :disabled="currentPage <= 1"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Previous Page"
            >
              <ChevronLeft class="w-4 h-4" />
              <span class="hidden sm:inline">{{ languageStore.t('prev', 'Prev') }}</span>
            </button>

            <div class="flex items-center gap-1">
              <button
                v-for="p in paginationPages"
                :key="p"
                @click="goToPage(p)"
                :class="[
                  'w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                  currentPage === p
                    ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                    : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button
              @click="nextPage"
              :disabled="currentPage >= totalPages"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Next Page"
            >
              <span class="hidden sm:inline">{{ languageStore.t('next', 'Next') }}</span>
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
