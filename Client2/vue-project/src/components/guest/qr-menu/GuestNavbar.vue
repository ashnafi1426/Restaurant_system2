<template>
  <div>
    <!-- Main Navbar -->
    <nav
      class="guest-navbar fixed top-0 left-0 right-0 z-50 bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700 shadow-sm dark:shadow-slate-950/50 transition-colors"
    >
      <div
        class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400/60 dark:via-amber-500/40 to-transparent"
      ></div>

      <!-- Navbar Content -->
      <div class="px-4 md:px-6 h-16 md:h-20 flex items-center justify-between">
        <!-- Left: Logo -->
        <div class="flex items-center gap-2">
          <div
            class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 shadow-md flex items-center justify-center flex-shrink-0"
          >
            <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
              />
            </svg>
          </div>
          <div class="hidden sm:block">
            <h1 class="text-sm md:text-lg font-bold text-slate-900 dark:text-slate-100 transition-colors">Royal Horizon</h1>
            <p
              class="text-[8px] md:text-[10px] text-amber-600 dark:text-amber-400 font-semibold tracking-[2px] uppercase transition-colors"
            >
              Hotel & Resort
            </p>
          </div>
        </div>

        <!-- Center: Title (Desktop Only) -->
        <div class="hidden md:flex flex-col items-center">
          <h2 class="text-lg lg:text-xl font-serif font-bold text-slate-900 dark:text-slate-100 transition-colors">Our Menu</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Delicious meals, delivered to your room</p>
        </div>

        <!-- Right: Controls -->
        <div class="flex items-center gap-2 md:gap-3">
          <!-- Desktop: Room + Profile Dropdowns -->
          <div class="hidden md:flex items-center gap-3">
            <!-- Theme Toggle Button -->
            <button
              @click="handleThemeToggle"
              class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 hover:border-amber-300 dark:hover:border-amber-600 hover:bg-amber-50 dark:hover:bg-slate-700/80 transition-all"
              :title="theme.isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
            >
              <Sun
                v-if="!theme.isDarkMode"
                class="w-5 h-5 text-amber-600"
              />
              <Moon
                v-else
                class="w-5 h-5 text-amber-400"
              />
            </button>

            <!-- Room Selector -->
            <div class="relative">
              <button
                @click="toggleRoomDropdown"
                class="flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 hover:border-amber-300 dark:hover:border-amber-600 hover:bg-amber-50 dark:hover:bg-slate-700 transition-all"
              >
                <span class="text-sm">🏨</span>
                <span class="text-xs font-medium text-slate-700 dark:text-slate-200"
                  >Room {{ currentRoom || 'Select' }}</span
                >
                <svg
                  class="w-3 h-3 text-slate-400 dark:text-slate-500 transition-transform"
                  :class="{ 'rotate-180': showRoomDropdown }"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>
              <Transition name="dropdown">
                <div
                  v-if="showRoomDropdown"
                  class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 z-50 transition-colors"
                >
                  <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/30 border-b border-gray-100 dark:border-slate-700">
                    <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Select Room</h4>
                  </div>
                  <div v-if="roomsLoading" class="px-4 py-3 text-center text-sm text-slate-400 dark:text-slate-500">
                    Loading...
                  </div>
                  <button
                    v-for="room in availableRooms"
                    :key="room.id"
                    @click="selectRoom(room.room_number)"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-amber-50 dark:hover:bg-slate-800 border-b border-gray-50 dark:border-slate-700 last:border-0 transition-colors"
                  >
                    <span>🛏️</span>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200"
                      >Room {{ room.room_number }}</span
                    >
                  </button>
                </div>
              </Transition>
            </div>

            <!-- Profile Button -->
            <button
              @click="toggleProfileDropdown"
              class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 hover:border-amber-300 dark:hover:border-amber-600 transition-all"
            >
              <img
                v-if="guestAvatar"
                :src="guestAvatar"
                :alt="guestName"
                class="w-6 h-6 rounded-full object-cover border border-amber-200 dark:border-amber-700"
              />
              <span v-else class="text-sm">👤</span>
              <span class="text-xs font-medium text-slate-700 dark:text-slate-200">{{ guestName }}</span>
              <svg
                class="w-3 h-3 text-slate-400 dark:text-slate-500 transition-transform"
                :class="{ 'rotate-180': showProfileDropdown }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </button>

            <!-- Profile Dropdown -->
            <Transition name="dropdown">
              <div
                v-if="showProfileDropdown"
                class="absolute right-6 mt-2 top-full w-64 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 z-50 transition-colors"
              >
                <div class="px-4 py-3 bg-amber-50 dark:bg-amber-900/30 border-b border-gray-100 dark:border-slate-700">
                  <div class="flex items-center gap-2">
                    <img
                      v-if="guestAvatar"
                      :src="guestAvatar"
                      :alt="guestName"
                      class="w-10 h-10 rounded-full border-2 border-amber-300 dark:border-amber-600"
                    />
                    <div
                      v-else
                      class="w-10 h-10 rounded-full bg-amber-200 dark:bg-amber-900/40 flex items-center justify-center text-lg"
                    >
                      👤
                    </div>
                    <div>
                      <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ guestName }}</p>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Room {{ currentRoom || '-' }}</p>
                    </div>
                  </div>
                </div>
                <button
                  @click="handleViewProfile"
                  class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
                >
                  <span>👤</span>
                  <span class="text-sm">My Profile</span>
                </button>
                <button
                  @click="handleMyOrders"
                  class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
                >
                  <span>📋</span>
                  <span class="text-sm">My Orders</span>
                </button>
                <button
                  @click="handleSettings"
                  class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
                >
                  <span>⚙️</span>
                  <span class="text-sm">Settings</span>
                </button>
                <div class="border-t border-gray-100 dark:border-slate-700 my-1"></div>
                <button
                  @click="handleLogout"
                  class="flex items-center gap-3 w-full px-4 py-2.5 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors"
                >
                  <span>🚪</span>
                  <span class="text-sm font-medium">Logout</span>
                </button>
              </div>
            </Transition>
          </div>

          <!-- Mobile: Hamburger Menu Button -->
          <button
            @click="toggleMobileMenu"
            class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-amber-50 dark:hover:bg-slate-800 transition-colors"
            :title="showMobileMenu ? 'Close menu' : 'Open menu'"
          >
            <svg
              v-if="!showMobileMenu"
              class="w-6 h-6 text-slate-600 dark:text-slate-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
            <svg
              v-else
              class="w-6 h-6 text-slate-600 dark:text-slate-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </nav>

    <!-- Mobile Menu Backdrop -->
    <Transition name="fade">
      <div
        v-if="showMobileMenu"
        class="fixed inset-0 bg-black/20 md:hidden z-40 transition-colors"
        @click="closeMobileMenu"
        style="top: 64px"
      ></div>
    </Transition>

    <!-- Mobile Menu -->
    <Transition name="slide-in">
      <div
        v-if="showMobileMenu"
        class="fixed top-16 left-0 right-0 bottom-0 bg-white dark:bg-slate-900 md:hidden z-45 overflow-y-auto transition-colors"
      >
        <!-- Tab Navigation -->
        <div class="sticky top-0 bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700 p-3 flex gap-2 z-10 transition-colors">
          <button
            @click="mobileMenuTab = 'categories'"
            class="flex-1 py-2 px-3 rounded-lg font-medium text-sm transition-all"
            :class="[
              mobileMenuTab === 'categories'
                ? 'bg-amber-500 text-white'
                : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700',
            ]"
          >
            📁 Categories
          </button>
          <button
            @click="mobileMenuTab = 'profile'"
            class="flex-1 py-2 px-3 rounded-lg font-medium text-sm transition-all"
            :class="[
              mobileMenuTab === 'profile'
                ? 'bg-amber-500 text-white'
                : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-700',
            ]"
          >
            👤 Profile
          </button>
        </div>

        <!-- Tab Content -->
        <div class="p-4 space-y-3">
          <!-- Categories Tab -->
          <div v-if="mobileMenuTab === 'categories'" class="space-y-2">
            <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/30 rounded-lg border border-amber-200 dark:border-amber-700 mb-3">
              <p class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">
                Browse Categories
              </p>
            </div>
            <div class="space-y-1">
              <button
                v-for="category in mobileCategories"
                :key="category.id"
                @click="selectCategoryMobile(category.id)"
                class="flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-amber-50 dark:hover:bg-slate-800 transition-colors text-left border-l-4"
                :class="[
                  selectedMobileCategory === category.id
                    ? 'bg-amber-100 dark:bg-amber-900/40 border-l-amber-500 text-slate-900 dark:text-slate-100'
                    : 'border-l-transparent text-slate-700 dark:text-slate-300',
                ]"
              >
                <span class="text-lg w-6 flex items-center justify-center flex-shrink-0">
                  <component :is="getIconComponent(category.id)" :size="18" :stroke-width="1.5" />
                </span>
                <span class="text-sm font-medium flex-1">{{ category.name }}</span>
                <span
                  v-if="category.count"
                  class="text-xs bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-2 py-0.5 rounded-full"
                >
                  {{ category.count }}
                </span>
              </button>
            </div>
          </div>

          <!-- Profile Tab -->
          <div v-if="mobileMenuTab === 'profile'" class="space-y-2">
            <!-- Profile Header Card -->
            <div
              class="flex items-center gap-3 px-4 py-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg border border-amber-200 dark:border-amber-700 mb-3"
            >
              <img
                v-if="guestAvatar"
                :src="guestAvatar"
                :alt="guestName"
                class="w-12 h-12 rounded-full object-cover border-2 border-amber-300 dark:border-amber-600 flex-shrink-0"
              />
              <div
                v-else
                class="w-12 h-12 rounded-full bg-amber-200 dark:bg-amber-900/40 flex items-center justify-center text-xl flex-shrink-0"
              >
                👤
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">{{ guestName }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                  Room {{ currentRoom || 'Not assigned' }}
                </p>
              </div>
            </div>

            <!-- Theme Toggle in Mobile Menu -->
            <button
              @click="handleThemeToggle"
              class="flex items-center justify-center gap-2 w-full rounded-lg px-4 py-3 font-medium text-slate-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700 hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
            >
              <Sun v-if="!theme.isDarkMode" class="w-5 h-5" />
              <Moon v-else class="w-5 h-5" />
              <span>{{ theme.isDarkMode ? 'Light Mode' : 'Dark Mode' }}</span>
            </button>

            <!-- Profile Menu Items -->
            <button
              @click="handleViewProfile"
              class="flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
            >
              <span class="text-lg">👤</span>
              <span class="text-sm">My Profile</span>
            </button>

            <button
              @click="handleMyOrders"
              class="flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
            >
              <span class="text-lg">📋</span>
              <span class="text-sm">My Orders</span>
            </button>

            <button
              @click="handleSearchClick"
              class="flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
            >
              <span class="text-lg">🔍</span>
              <span class="text-sm">Search Menu</span>
            </button>

            <button
              @click="handleSettings"
              class="flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-amber-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors"
            >
              <span class="text-lg">⚙️</span>
              <span class="text-sm">Settings</span>
            </button>

            <div class="border-t border-gray-200 dark:border-slate-700 my-2"></div>

            <button
              @click="handleLogout"
              class="flex items-center gap-3 w-full px-4 py-3 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-red-600 dark:text-red-400"
            >
              <span class="text-lg">🚪</span>
              <span class="text-sm font-medium">Logout</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Navbar Spacer -->
    <div class="h-16 md:h-20"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import { useThemeStore } from '../../../stores/themeStore'
import api from '../../../api/auth'
import { Sun, Moon } from 'lucide-vue-next'
import {
  Clock,
  Utensils,
  Leaf,
  Soup,
  Salad,
  UtensilsCrossed,
  Layers,
  Pizza,
  Sandwich,
  Cake,
  Wine,
  Menu,
} from 'lucide-vue-next'

interface Room {
  id: string
  room_number: string
  status: string
  is_active: boolean
}

interface Category {
  id: string | null
  name: string
  icon: string
  count?: number
}

interface Props {
  guestName?: string
  guestEmail?: string
  guestAvatar?: string
  initialRoom?: string | number
  categories?: Category[]
}

const props = withDefaults(defineProps<Props>(), {
  guestName: '',
  guestEmail: '',
  guestAvatar: '',
  initialRoom: '',
  categories: () => [
    { id: null, name: 'All Categories', icon: 'menu', count: 0 },
    { id: 'breakfast', name: 'Breakfast', icon: 'clock', count: 24 },
    { id: 'lunch', name: 'Lunch', icon: 'utensils', count: 18 },
    { id: 'appetizers', name: 'Appetizers', icon: 'leaf', count: 18 },
    { id: 'soups', name: 'Soups', icon: 'soup', count: 12 },
    { id: 'salads', name: 'Salads', icon: 'salad', count: 16 },
    { id: 'main-course', name: 'Main Course', icon: 'utensils-crossed', count: 36 },
    { id: 'pasta', name: 'Pasta', icon: 'layers', count: 14 },
    { id: 'pizza', name: 'Pizza', icon: 'pizza', count: 12 },
    { id: 'burgers', name: 'Burgers', icon: 'sandwich', count: 10 },
    { id: 'desserts', name: 'Desserts', icon: 'cake', count: 14 },
    { id: 'beverages', name: 'Beverages', icon: 'wine', count: 22 },
  ],
})

const emit = defineEmits<{
  search: []
  logout: []
  settings: []
  orders: []
  'view-profile': []
  'room-selected': [room: string | number]
  'category-selected': [categoryId: string | null]
}>()

const router = useRouter()
const authStore = useAuthStore()
const theme = useThemeStore()

// State
const currentRoom = ref<string | number>(props.initialRoom || '')
const showRoomDropdown = ref(false)
const showProfileDropdown = ref(false)
const showMobileMenu = ref(false)
const availableRooms = ref<Room[]>([])
const roomsLoading = ref(false)
const mobileMenuTab = ref<'categories' | 'profile'>('categories')
const selectedMobileCategory = ref<string | null>(null)

// Icon mapping
const getIconComponent = (categoryId: string | null) => {
  const iconMap: { [key: string]: any } = {
    menu: Menu,
    clock: Clock,
    breakfast: Clock,
    utensils: Utensils,
    lunch: Utensils,
    leaf: Leaf,
    appetizers: Leaf,
    soup: Soup,
    soups: Soup,
    salad: Salad,
    salads: Salad,
    'utensils-crossed': UtensilsCrossed,
    'main-course': UtensilsCrossed,
    layers: Layers,
    pasta: Layers,
    pizza: Pizza,
    sandwich: Sandwich,
    burger: Sandwich,
    burgers: Sandwich,
    cake: Cake,
    desserts: Cake,
    wine: Wine,
    beverages: Wine,
    drinks: Wine,
  }
  return iconMap[categoryId as string] || Menu
}

// Computed
const guestName = computed(() => props.guestName || authStore.user?.name || 'Guest User')
const guestEmail = computed(
  () => props.guestEmail || authStore.user?.email || 'guest@royalhorizon.com',
)
const guestAvatar = computed(() => props.guestAvatar || authStore.user?.avatar || '')
const mobileCategories = computed(() =>
  props.categories && props.categories.length > 0 ? props.categories : [],
)

// Methods
async function fetchAvailableRooms() {
  roomsLoading.value = true
  try {
    const response = await api.get('/rooms', { params: { status: 'available', per_page: 100 } })
    if (response.data?.data) availableRooms.value = response.data.data
  } catch (error) {
    console.error('Error fetching rooms:', error)
  } finally {
    roomsLoading.value = false
  }
}

function toggleRoomDropdown() {
  showRoomDropdown.value = !showRoomDropdown.value
  showProfileDropdown.value = false
}

function toggleProfileDropdown() {
  showProfileDropdown.value = !showProfileDropdown.value
  showRoomDropdown.value = false
}

function toggleMobileMenu() {
  showMobileMenu.value = !showMobileMenu.value
}

function closeMobileMenu() {
  showMobileMenu.value = false
}

function selectRoom(room: string) {
  currentRoom.value = room
  showRoomDropdown.value = false
  emit('room-selected', room)
}

function selectCategoryMobile(categoryId: string | null) {
  selectedMobileCategory.value = categoryId
  emit('category-selected', categoryId)
  console.log(`[GuestNavbar] Category selected: ${categoryId}`)
}

function handleViewProfile() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  emit('view-profile')
  router.push('/profile')
}

function handleMyOrders() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  emit('orders')
  router.push('/orders')
}

function handleSettings() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  emit('settings')
  router.push('/settings')
}

function handleSearchClick() {
  showMobileMenu.value = false
  emit('search')
}

function handleLogout() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  authStore.logout()
  emit('logout')
  router.push('/login')
}

function handleOutsideClick(event: MouseEvent) {
  const target = event.target as HTMLElement
  if (!target.closest('.guest-navbar')) {
    showRoomDropdown.value = false
    showProfileDropdown.value = false
  }
}

const handleThemeToggle = () => {
  console.log('[QRMenuNavbar] 🎨 Theme toggle clicked')
  theme.toggleTheme()
  console.log('[QRMenuNavbar] 🎨 New theme:', theme.isDarkMode ? 'dark' : 'light')
}

onMounted(() => {
  window.addEventListener('click', handleOutsideClick)
  fetchAvailableRooms()
})

onBeforeUnmount(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-8px);
}

/* Fade Animation */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide In Animation for Mobile Menu */
.slide-in-enter-active,
.slide-in-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-in-enter-from,
.slide-in-leave-to {
  transform: translateY(-100%);
}

.guest-navbar {
  transition: box-shadow 0.3s ease;
}

.guest-navbar:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
</style>
