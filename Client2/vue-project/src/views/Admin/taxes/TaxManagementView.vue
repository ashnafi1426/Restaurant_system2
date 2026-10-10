<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useTaxRateStore } from '@/stores/taxRateStore'
import type { TaxRate, TaxRateFormData } from '@/types/taxRate'
import {
  Percent,
  Plus,
  Search,
  RefreshCw,
  Edit2,
  Trash2,
  CheckCircle2,
  XCircle,
  ShieldCheck,
  AlertCircle,
  HelpCircle,
  DollarSign,
  Utensils,
  BedDouble,
  Layers,
} from 'lucide-vue-next'

const store = useTaxRateStore()

const searchQuery = ref('')
const filterType = ref<'all' | 'food' | 'beverage' | 'room' | 'service'>('all')
const isModalOpen = ref(false)
const editingId = ref<string | null>(null)
const isSubmitting = ref(false)
const formError = ref<string | null>(null)

const defaultForm: TaxRateFormData = {
  name: '',
  code: '',
  type: 'percentage',
  rate: 15,
  applies_to: 'all',
  is_active: true,
  is_default: false,
  description: '',
}

const formData = ref<TaxRateFormData>({ ...defaultForm })

onMounted(async () => {
  await store.fetchTaxRates()
})

const filteredRates = computed(() => {
  return store.taxRates.filter((item) => {
    const matchesSearch =
      !searchQuery.value.trim() ||
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (item.description && item.description.toLowerCase().includes(searchQuery.value.toLowerCase()))

    const matchesType =
      filterType.value === 'all' ||
      item.applies_to === filterType.value ||
      item.applies_to === 'all'

    return matchesSearch && matchesType
  })
})

const totalTaxRates = computed(() => store.taxRates.length)
const activeCount = computed(() => store.taxRates.filter((t) => t.is_active).length)
const defaultTax = computed(() => store.taxRates.find((t) => t.is_default))

function openCreateModal() {
  editingId.value = null
  formData.value = { ...defaultForm }
  formError.value = null
  isModalOpen.value = true
}

function openEditModal(rate: TaxRate) {
  editingId.value = rate.id
  formData.value = {
    name: rate.name,
    code: rate.code,
    type: rate.type || 'percentage',
    rate: Number(rate.rate),
    applies_to: rate.applies_to || 'all',
    is_active: rate.is_active,
    is_default: rate.is_default,
    description: rate.description || '',
  }
  formError.value = null
  isModalOpen.value = true
}

function closeModal() {
  isModalOpen.value = false
  editingId.value = null
  formError.value = null
}

async function handleSubmit() {
  if (!formData.value.name.trim()) {
    formError.value = 'Tax name is required.'
    return
  }
  if (!formData.value.code.trim()) {
    formError.value = 'Tax code is required.'
    return
  }
  if (formData.value.rate < 0) {
    formError.value = 'Tax rate cannot be negative.'
    return
  }

  isSubmitting.value = true
  formError.value = null

  try {
    if (editingId.value) {
      await store.updateTaxRate(editingId.value, formData.value)
    } else {
      await store.createTaxRate(formData.value)
    }
    closeModal()
  } catch (err: any) {
    formError.value = err.response?.data?.message || 'Failed to save tax rate.'
  } finally {
    isSubmitting.value = false
  }
}

async function handleToggleStatus(rate: TaxRate) {
  try {
    await store.toggleStatus(rate.id)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to toggle tax rate status')
  }
}

async function handleDelete(rate: TaxRate) {
  if (!confirm(`Are you sure you want to delete tax rate "${rate.name}"?`)) return
  try {
    await store.deleteTaxRate(rate.id)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to delete tax rate')
  }
}

function getAppliesBadgeClass(type: string) {
  switch (type) {
    case 'food':
    case 'beverage':
      return 'bg-amber-500/10 text-amber-500 border-amber-500/20'
    case 'room':
      return 'bg-blue-500/10 text-blue-500 border-blue-500/20'
    case 'service':
      return 'bg-purple-500/10 text-purple-500 border-purple-500/20'
    default:
      return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Header Section -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
              <Percent class="w-6 h-6" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                Tax & Charges Management
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Configure hotel-wide VAT, service charges, and food taxation rules
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="store.fetchTaxRates()"
            class="p-2.5 rounded-xl border border-gray-200 dark:border-zinc-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors"
            title="Refresh"
          >
            <RefreshCw class="w-5 h-5" :class="{ 'animate-spin': store.loading }" />
          </button>
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-medium shadow-lg shadow-amber-500/25 transition-all"
          >
            <Plus class="w-5 h-5" />
            <span>Add Tax Rate</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 flex items-center gap-4"
        >
          <div class="p-3 rounded-xl bg-amber-500/10 text-amber-500">
            <Layers class="w-6 h-6" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalTaxRates }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Total Configured Rates</div>
          </div>
        </div>

        <div
          class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 flex items-center gap-4"
        >
          <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-500">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ activeCount }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Active Tax Rates</div>
          </div>
        </div>

        <div
          class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 flex items-center gap-4"
        >
          <div class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
            <ShieldCheck class="w-6 h-6" />
          </div>
          <div>
            <div class="text-base font-bold text-gray-900 dark:text-white truncate">
              {{ defaultTax ? `${defaultTax.name} (${defaultTax.rate}%)` : 'None Set' }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Default Applied Tax</div>
          </div>
        </div>
      </div>

      <!-- Filter / Search Toolbar -->
      <div
        class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between"
      >
        <div class="relative flex-1 max-w-md">
          <Search class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name, code or description..."
            class="w-full pl-10 pr-4 py-2 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
          />
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
          <button
            v-for="type in ['all', 'food', 'room', 'service'] as const"
            :key="type"
            @click="filterType = type"
            class="px-3 py-1.5 rounded-lg text-xs font-medium capitalize transition-colors"
            :class="
              filterType === type
                ? 'bg-amber-500 text-white shadow-sm'
                : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-zinc-700'
            "
          >
            {{ type }}
          </button>
        </div>
      </div>

      <!-- Table Section -->
      <div
        class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 overflow-hidden shadow-sm"
      >
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead
              class="bg-gray-50 dark:bg-zinc-800/50 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-zinc-800"
            >
              <tr>
                <th class="py-3.5 px-4 font-semibold">Tax Name & Code</th>
                <th class="py-3.5 px-4 font-semibold">Rate Value</th>
                <th class="py-3.5 px-4 font-semibold">Applies To</th>
                <th class="py-3.5 px-4 font-semibold">Status</th>
                <th class="py-3.5 px-4 font-semibold">Default</th>
                <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800/60">
              <tr
                v-for="rate in filteredRates"
                :key="rate.id"
                class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors"
              >
                <td class="py-4 px-4">
                  <div class="font-medium text-gray-900 dark:text-white">{{ rate.name }}</div>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span
                      class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-gray-400"
                    >
                      {{ rate.code }}
                    </span>
                    <span
                      v-if="rate.description"
                      class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs"
                    >
                      {{ rate.description }}
                    </span>
                  </div>
                </td>

                <td class="py-4 px-4">
                  <span class="text-base font-semibold text-gray-900 dark:text-white">
                    {{
                      rate.type === 'percentage'
                        ? `${rate.rate}%`
                        : `$${Number(rate.rate).toFixed(2)}`
                    }}
                  </span>
                  <span class="text-xs text-gray-400 ml-1">({{ rate.type }})</span>
                </td>

                <td class="py-4 px-4">
                  <span
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border capitalize"
                    :class="getAppliesBadgeClass(rate.applies_to)"
                  >
                    {{ rate.applies_to }}
                  </span>
                </td>

                <td class="py-4 px-4">
                  <button
                    @click="handleToggleStatus(rate)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
                    :class="
                      rate.is_active
                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/20'
                        : 'bg-rose-500/10 text-rose-500 border-rose-500/20 hover:bg-rose-500/20'
                    "
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full"
                      :class="rate.is_active ? 'bg-emerald-500' : 'bg-rose-500'"
                    ></span>
                    {{ rate.is_active ? 'Active' : 'Disabled' }}
                  </button>
                </td>

                <td class="py-4 px-4">
                  <span
                    v-if="rate.is_default"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-500/10 text-amber-500 border border-amber-500/20"
                  >
                    Default
                  </span>
                  <span v-else class="text-xs text-gray-400">—</span>
                </td>

                <td class="py-4 px-4 text-right">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      @click="openEditModal(rate)"
                      class="p-2 rounded-lg text-gray-500 hover:text-amber-500 hover:bg-amber-500/10 transition-colors"
                      title="Edit"
                    >
                      <Edit2 class="w-4 h-4" />
                    </button>
                    <button
                      @click="handleDelete(rate)"
                      class="p-2 rounded-lg text-gray-500 hover:text-rose-500 hover:bg-rose-500/10 transition-colors"
                      title="Delete"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredRates.length === 0">
                <td colspan="6" class="py-12 text-center">
                  <div class="flex flex-col items-center justify-center">
                    <Percent class="w-12 h-12 text-gray-300 dark:text-zinc-700 mb-3" />
                    <p class="text-base font-medium text-gray-600 dark:text-gray-400">
                      No tax rates found
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                      Create your first tax rate to get started
                    </p>
                    <button
                      @click="openCreateModal"
                      class="mt-4 px-4 py-2 rounded-xl bg-amber-500 text-white text-xs font-medium hover:bg-amber-600 transition-colors"
                    >
                      Create Tax Rate
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add / Edit Modal -->
      <div
        v-if="isModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
      >
        <div
          class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-2xl max-w-lg w-full overflow-hidden flex flex-col max-h-[90vh]"
        >
          <div
            class="px-6 py-4 border-b border-gray-200 dark:border-zinc-800 flex items-center justify-between"
          >
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
              {{ editingId ? 'Edit Tax Rate' : 'Add New Tax Rate' }}
            </h3>
            <button
              @click="closeModal"
              class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors"
            >
              ✕
            </button>
          </div>

          <form @submit.prevent="handleSubmit" class="p-6 space-y-4 overflow-y-auto">
            <div
              v-if="formError"
              class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-500 text-xs flex items-center gap-2"
            >
              <AlertCircle class="w-4 h-4 flex-shrink-0" />
              <span>{{ formError }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5"
                >
                  Tax Name *
                </label>
                <input
                  v-model="formData.name"
                  type="text"
                  placeholder="e.g. VAT (Standard)"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                />
              </div>

              <div>
                <label
                  class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5"
                >
                  Tax Code *
                </label>
                <input
                  v-model="formData.code"
                  type="text"
                  placeholder="e.g. VAT-15"
                  required
                  class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm text-gray-900 dark:text-white uppercase font-mono focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5"
                >
                  Calculation Type
                </label>
                <select
                  v-model="formData.type"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                >
                  <option value="percentage">Percentage (%)</option>
                  <option value="fixed">Fixed Amount</option>
                </select>
              </div>

              <div>
                <label
                  class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5"
                >
                  Tax Rate {{ formData.type === 'percentage' ? '(%)' : '($)' }} *
                </label>
                <input
                  v-model.number="formData.rate"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  placeholder="15"
                  class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm text-gray-900 dark:text-white font-semibold focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
                />
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5"
              >
                Applies To
              </label>
              <select
                v-model="formData.applies_to"
                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
              >
                <option value="all">All (Hotel Wide)</option>
                <option value="food">Food & Restaurant</option>
                <option value="beverage">Beverages</option>
                <option value="room">Room Bookings</option>
                <option value="service">Service & Amenities</option>
              </select>
            </div>

            <div>
              <label
                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5"
              >
                Description / Memo
              </label>
              <textarea
                v-model="formData.description"
                rows="2"
                placeholder="Optional description e.g. Standard 15% VAT mandated by tax authority"
                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500"
              ></textarea>
            </div>

            <div class="flex items-center gap-6 pt-2">
              <label class="flex items-center gap-2.5 cursor-pointer">
                <input
                  v-model="formData.is_active"
                  type="checkbox"
                  class="w-4 h-4 rounded text-amber-500 border-gray-300 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-800"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Is Active</span>
              </label>

              <label class="flex items-center gap-2.5 cursor-pointer">
                <input
                  v-model="formData.is_default"
                  type="checkbox"
                  class="w-4 h-4 rounded text-amber-500 border-gray-300 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-800"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300"
                  >Set as Default Tax</span
                >
              </label>
            </div>

            <div
              class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-800"
            >
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium shadow-lg shadow-amber-500/25 disabled:opacity-50 transition-all flex items-center gap-2"
              >
                <RefreshCw v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                <span>{{ editingId ? 'Update Tax' : 'Create Tax' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
