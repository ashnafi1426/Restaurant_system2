<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xs">
        <div>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white">Restaurant Tables</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage restaurant tables, floor layouts, and guest QR codes.</p>
        </div>

        <button
          @click="openCreateModal"
          class="bg-amber-500 hover:bg-amber-600 text-slate-950 px-5 py-2.5 rounded-xl font-extrabold text-xs shadow-md shadow-amber-500/20 transition cursor-pointer flex items-center justify-center gap-2"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Create Table</span>
        </button>
      </div>

      <!-- Statistics Cards Grid -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <!-- Total -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Tables</p>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ statistics.total }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
            <UtensilsCrossed class="w-5 h-5" />
          </div>
        </div>

        <!-- Active -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active</p>
            <h2 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ statistics.active }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <!-- Available -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Available</p>
            <h2 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ statistics.available }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <Sparkles class="w-5 h-5" />
          </div>
        </div>

        <!-- Occupied -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Occupied</p>
            <h2 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ statistics.occupied }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <Users class="w-5 h-5" />
          </div>
        </div>

        <!-- Cleaning -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cleaning</p>
            <h2 class="text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">{{ statistics.cleaning || 0 }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
            <RefreshCw class="w-5 h-5" />
          </div>
        </div>

        <!-- Out of Service -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Service Off</p>
            <h2 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ statistics.out_of_service || 0 }}</h2>
          </div>
          <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <AlertCircle class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Filters & Controls Bar -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-xs">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <!-- Search Input -->
          <div class="relative w-full md:w-80">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by table number, name, or location..."
              class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-800 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none transition font-medium"
              @input="handleSearchChange"
            />
          </div>

          <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <!-- Status Filter -->
            <select
              v-model="statusFilter"
              class="px-3.5 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 cursor-pointer"
              @change="handleFilterChange"
            >
              <option value="">All Status</option>
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="reserved">Reserved</option>
              <option value="cleaning">Cleaning</option>
              <option value="out_of_service">Out of Service</option>
            </select>

            <!-- Active Filter -->
            <select
              v-model="activeFilter"
              class="px-3.5 py-2 text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 cursor-pointer"
              @change="handleFilterChange"
            >
              <option :value="null">All Tables</option>
              <option :value="true">Active Only</option>
              <option :value="false">Inactive Only</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center space-y-3">
        <Loader2 class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading tables catalog...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold">
        <strong>Error:</strong> {{ error }}
      </div>

      <!-- Tables Data Container -->
      <div v-else class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden w-full">
        <!-- Desktop Table View -->
        <div class="hidden sm:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
              <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
                <th class="px-3.5 py-3 whitespace-nowrap">Table</th>
                <th class="px-3.5 py-3 whitespace-nowrap">Capacity</th>
                <th class="px-3.5 py-3 whitespace-nowrap">Location</th>
                <th class="px-3.5 py-3 text-center whitespace-nowrap">Status</th>
                <th class="px-3.5 py-3 text-center whitespace-nowrap">Active</th>
                <th class="px-3.5 py-3 text-center whitespace-nowrap">QR Code</th>
                <th class="px-3.5 py-3 text-right whitespace-nowrap pr-6">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
              <tr v-if="tables.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  No tables found. Create your first table to get started.
                </td>
              </tr>

              <tr
                v-for="table in tables"
                :key="table.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-150 group"
              >
                <!-- Table Number / Name -->
                <td class="px-3.5 py-3 whitespace-nowrap">
                  <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">
                    Table {{ table.table_number }}
                  </div>
                  <div v-if="table.table_name" class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                    {{ table.table_name }}
                  </div>
                </td>

                <!-- Capacity -->
                <td class="px-3.5 py-3 whitespace-nowrap font-extrabold text-slate-700 dark:text-slate-300">
                  {{ table.capacity }} guests
                </td>

                <!-- Location -->
                <td class="px-3.5 py-3 whitespace-nowrap text-slate-600 dark:text-slate-300 font-medium">
                  {{ table.location || '-' }}
                </td>

                <!-- Status Badge -->
                <td class="px-3.5 py-3 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border select-none uppercase tracking-wider',
                      getStatusBadgeClass(table.status)
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ table.status ? table.status.replace(/_/g, ' ') : 'N/A' }}
                  </span>
                </td>

                <!-- Active Badge -->
                <td class="px-3.5 py-3 text-center whitespace-nowrap">
                  <span
                    v-if="table.is_active"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Active</span>
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>Inactive</span>
                  </span>
                </td>

                <!-- QR Code View -->
                <td class="px-3.5 py-3 text-center whitespace-nowrap">
                  <button
                    @click="viewQRCode(table)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-500/20 transition cursor-pointer font-extrabold text-[11px] border border-blue-500/20"
                    title="View QR Code"
                  >
                    <QrCode class="w-3.5 h-3.5 text-blue-500" />
                    <span>View QR</span>
                  </button>
                </td>

                <!-- Actions -->
                <td class="px-3.5 py-3 text-right whitespace-nowrap pr-6">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      @click="editTable(table)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      title="Edit Table"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <button
                      @click="confirmDelete(table)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                      title="Delete Table"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile View -->
        <div class="sm:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div v-if="tables.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
            No tables found
          </div>

          <div
            v-for="table in tables"
            :key="'mob-' + table.id"
            class="p-4 space-y-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-start justify-between">
              <div>
                <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">Table {{ table.table_number }}</h3>
                <p v-if="table.table_name" class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ table.table_name }}</p>
              </div>
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border uppercase tracking-wider',
                  getStatusBadgeClass(table.status)
                ]"
              >
                {{ table.status }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
              <div>
                <span class="text-[10px] text-slate-400 block font-bold uppercase">Capacity</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ table.capacity }} guests</span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block font-bold uppercase">Location</span>
                <span class="font-bold text-slate-900 dark:text-white truncate block">{{ table.location || '-' }}</span>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1">
              <button
                @click="viewQRCode(table)"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold text-xs"
              >
                <QrCode class="w-3.5 h-3.5" />
                <span>QR Code</span>
              </button>

              <div class="flex items-center gap-2">
                <button
                  @click="editTable(table)"
                  class="p-1.5 rounded-lg text-amber-600 dark:text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  <Edit class="w-4 h-4" />
                </button>
                <button
                  @click="confirmDelete(table)"
                  class="p-1.5 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination Bar with 5, 10, 20, 50 Options -->
        <div
          v-if="pagination && (pagination.last_page || 1) >= 1"
          class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
        >
          <!-- Left Side: Per Page Selector & Showing Count -->
          <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
              <select
                :value="localPerPage"
                @change="changePerPage"
                class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-amber-500 cursor-pointer shadow-2xs"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="text-xs font-medium">
              Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ pagination.from || 0 }}</span> to
              <span class="font-extrabold text-slate-900 dark:text-white">{{ pagination.to || 0 }}</span> of
              <span class="font-extrabold text-slate-900 dark:text-white">{{ pagination.total || 0 }}</span> tables
            </div>
          </div>

          <!-- Right Side: Page Controls -->
          <div class="flex items-center gap-1.5">
            <button
              @click="goToPage((pagination.current_page || 1) - 1)"
              :disabled="(pagination.current_page || 1) <= 1"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Previous Page"
            >
              <ChevronLeft class="w-4 h-4" />
              <span class="hidden sm:inline">Prev</span>
            </button>

            <div class="flex items-center gap-1">
              <button
                v-for="p in getPageNumbers()"
                :key="p"
                @click="goToPage(p)"
                :class="[
                  'w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                  (pagination.current_page || 1) === p
                    ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button
              @click="goToPage((pagination.current_page || 1) + 1)"
              :disabled="(pagination.current_page || 1) >= (pagination.last_page || 1)"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Next Page"
            >
              <span class="hidden sm:inline">Next</span>
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <RestaurantTableFormModal
        v-if="showFormModal"
        :table="selectedTable"
        @close="closeFormModal"
        @success="handleFormSuccess"
      />

      <!-- QR Code Modal -->
      <Teleport to="body">
        <Transition name="fade">
          <div
            v-if="showQRModal && selectedTable"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
            @click.self="closeQRModal"
          >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">QR Code - Table {{ selectedTable.table_number }}</h3>
                <button
                  @click="closeQRModal"
                  class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

              <div v-if="selectedTable.qr_code_url" class="space-y-4">
                <img
                  :src="selectedTable.qr_code_url"
                  :alt="`QR Code for ${selectedTable.table_number}`"
                  class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-white p-2"
                />
                <div class="text-center text-xs text-slate-500 dark:text-slate-400">
                  <p>Token: <code class="bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white px-2 py-0.5 rounded-lg font-mono font-bold">{{ selectedTable.qr_token }}</code></p>
                  <p v-if="selectedTable.table_name" class="mt-1 font-bold text-slate-900 dark:text-white">{{ selectedTable.table_name }}</p>
                </div>
                <div class="flex gap-3">
                  <button
                    @click="downloadQR(selectedTable)"
                    class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl font-bold text-xs hover:bg-blue-700 transition cursor-pointer flex items-center justify-center gap-2 shadow-md shadow-blue-600/20"
                  >
                    <Download class="w-4 h-4" />
                    <span>Download</span>
                  </button>
                  <button
                    @click="regenerateQRCode(selectedTable.id)"
                    class="flex-1 px-4 py-2.5 bg-amber-500 text-slate-950 rounded-xl font-black text-xs hover:bg-amber-600 transition cursor-pointer flex items-center justify-center gap-2 shadow-md shadow-amber-500/20"
                  >
                    <RefreshCw class="w-4 h-4" />
                    <span>Regenerate</span>
                  </button>
                </div>
              </div>
              <div v-else class="text-center text-slate-500 text-xs py-8 font-bold">
                No QR code available
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <Transition name="fade">
          <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
            @click.self="closeDeleteModal"
          >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl max-w-md w-full p-6 space-y-4">
              <div class="text-center space-y-3">
                <div class="w-12 h-12 bg-rose-500/10 border border-rose-500/20 text-rose-500 rounded-2xl flex items-center justify-center mx-auto">
                  <AlertCircle class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Delete Table?</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
                  Are you sure you want to delete <strong class="text-slate-900 dark:text-white">Table {{ selectedTable?.table_number }}</strong>?
                  This action cannot be undone.
                </p>
              </div>
              <div class="flex gap-3 pt-2">
                <button
                  @click="closeDeleteModal"
                  class="flex-1 px-4 py-2.5 border border-slate-200 dark:border-slate-800 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  @click="handleDelete"
                  class="flex-1 px-4 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-xs hover:bg-rose-700 transition cursor-pointer shadow-md shadow-rose-600/20"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRestaurantTableStore } from '@/stores/restaurantTableStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RestaurantTableFormModal from '@/components/manager/RestaurantTableFormModal.vue'
import type { RestaurantTable } from '@/types/restaurantTable'
import {
  UtensilsCrossed,
  CheckCircle2,
  Sparkles,
  Users,
  RefreshCw,
  AlertCircle,
  Search,
  Plus,
  QrCode,
  Edit,
  Trash2,
  Loader2,
  ChevronLeft,
  ChevronRight,
  Download,
  X
} from 'lucide-vue-next'

// Store
const tableStore = useRestaurantTableStore()
const { tables, statistics, pagination, loading, error } = storeToRefs(tableStore)

// Local state
const searchQuery = ref('')
const statusFilter = ref('')
const activeFilter = ref<boolean | null>(null)
const localPerPage = ref(10)
const showFormModal = ref(false)
const showQRModal = ref(false)
const showDeleteModal = ref(false)
const selectedTable = ref<RestaurantTable | null>(null)
let searchTimeout: ReturnType<typeof setTimeout> | null = null

// Methods
const getStatusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    available: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
    occupied: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
    reserved: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
    cleaning: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
    out_of_service: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
  }
  return classes[status] || 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
}

const handleSearchChange = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    tableStore.setFilters({ search: searchQuery.value, page: 1 })
    tableStore.fetchTables()
  }, 500)
}

const handleFilterChange = () => {
  tableStore.setFilters({
    status: statusFilter.value as any,
    is_active: activeFilter.value,
    page: 1,
  })
  tableStore.fetchTables()
}

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  localPerPage.value = Number(target.value)
  tableStore.setFilters({ per_page: localPerPage.value, page: 1 })
  tableStore.fetchTables()
}

const openCreateModal = () => {
  selectedTable.value = null
  showFormModal.value = true
}

const editTable = (table: RestaurantTable) => {
  selectedTable.value = table
  showFormModal.value = true
}

const closeFormModal = () => {
  showFormModal.value = false
  selectedTable.value = null
}

const handleFormSuccess = () => {
  closeFormModal()
  tableStore.fetchTables()
  tableStore.fetchStatistics()
}

const viewQRCode = (table: RestaurantTable) => {
  selectedTable.value = table
  showQRModal.value = true
}

const closeQRModal = () => {
  showQRModal.value = false
  selectedTable.value = null
}

const downloadQR = async (table: RestaurantTable) => {
  await tableStore.downloadQRCode(table)
}

const regenerateQRCode = async (tableId: string) => {
  if (confirm('Are you sure you want to regenerate the QR code? The old QR code will no longer work.')) {
    try {
      await tableStore.regenerateQR(tableId)
      const updatedTable = await tableStore.fetchTableById(tableId)
      selectedTable.value = updatedTable
      alert('QR code regenerated successfully!')
    } catch (err) {
      alert('Failed to regenerate QR code')
    }
  }
}

const confirmDelete = (table: RestaurantTable) => {
  selectedTable.value = table
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedTable.value = null
}

const handleDelete = async () => {
  if (!selectedTable.value) return

  try {
    await tableStore.deleteTable(selectedTable.value.id)
    closeDeleteModal()
    alert('Table deleted successfully!')
  } catch (err) {
    alert('Failed to delete table')
  }
}

const goToPage = (page: number) => {
  tableStore.setFilters({ page })
  tableStore.fetchTables()
}

const getPageNumbers = () => {
  if (!pagination.value || !pagination.value.current_page || !pagination.value.last_page) return []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages: number[] = []

  pages.push(1)
  for (let i = Math.max(2, current - 2); i <= Math.min(last - 1, current + 2); i++) {
    pages.push(i)
  }
  if (last > 1) pages.push(last)

  return [...new Set(pages)].sort((a, b) => a - b)
}

// Lifecycle
onMounted(() => {
  tableStore.fetchTables()
  tableStore.fetchStatistics()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
