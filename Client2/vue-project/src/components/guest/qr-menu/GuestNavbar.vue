<template>
  <div>
    <nav class="guest-navbar fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-b border-gray-100 shadow-sm">
      <div class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400/60 to-transparent"></div>
      <div class="relative container mx-auto px-4 md:px-6 h-16 md:h-20 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 shadow-md flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
            </div>
            
            <div class="hidden sm:block">
              <h1 class="text-sm md:text-lg font-bold text-slate-900 leading-tight tracking-wide">
                Royal Horizon
              </h1>
              <p class="text-[8px] md:text-[10px] text-amber-600 font-semibold tracking-[2px] uppercase leading-tight">
                Hotel & Resort
              </p>
            </div>
          </div>

        </div>
        <div class="hidden md:flex flex-col items-center">
          <h2 class="text-lg lg:text-xl font-serif font-bold text-slate-900">
            Our Menu
          </h2>
          <p class="text-xs text-slate-500 leading-tight">
            Delicious meals, delivered to your room
          </p>
        </div>
        <div class="flex items-center gap-2 md:gap-3">
          <div class="relative">
            <button
              @click="toggleRoomDropdown"
              class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 rounded-full border border-gray-200 bg-white/80 hover:border-amber-300 hover:bg-amber-50/50 transition-all duration-200"
            >
              <span class="text-sm md:text-base">🏨</span>
              <span class="text-xs md:text-sm font-medium text-slate-700 whitespace-nowrap">
                Room {{ currentRoom || 'Select' }}
              </span>
              <svg 
                class="w-3 h-3 md:w-4 md:h-4 text-slate-400 transition-transform duration-200"
                :class="{ 'rotate-180': showRoomDropdown }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <Transition name="dropdown">
              <div
                v-if="showRoomDropdown"
                class="absolute right-0 mt-2 w-48 md:w-56 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
              >
                <div class="px-4 py-2 bg-gradient-to-r from-amber-50 to-amber-100/50 border-b border-gray-100">
                  <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Select Room</h4>
                </div>
                
                <div v-if="roomsLoading" class="px-4 py-3 text-center text-sm text-slate-400">
                  Loading...
                </div>

                <button
                  v-for="room in availableRooms"
                  :key="room.id"
                  @click="selectRoom(room.room_number)"
                  class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-amber-50 transition-colors duration-150 border-b border-gray-50 last:border-0"
                >
                  <span class="text-base">🛏️</span>
                  <span class="text-sm font-medium text-slate-700">Room {{ room.room_number }}</span>
                  <span class="ml-auto text-xs text-slate-400 capitalize">{{ room.status }}</span>
                </button>

                <div v-if="availableRooms.length === 0 && !roomsLoading" class="px-4 py-3 text-center text-sm text-slate-400">
                  No rooms available
                </div>
              </div>
            </Transition>
          </div>
          <div class="relative">
            <button
              @click="toggleProfileDropdown"
              class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2 rounded-full bg-gradient-to-r from-amber-50 to-amber-100/50 border border-amber-200/50 hover:border-amber-300 hover:shadow-md transition-all duration-200"
            >
              <img 
                v-if="guestAvatar" 
                :src="guestAvatar" 
                :alt="guestName"
                class="w-6 h-6 md:w-7 md:h-7 rounded-full object-cover border border-amber-200"
              />
              <span v-else class="text-sm md:text-base">👤</span>
              
              <span class="text-xs md:text-sm font-medium text-slate-700 whitespace-nowrap">
                {{ guestName }}
              </span>
              
              <svg 
                class="w-3 h-3 md:w-4 md:h-4 text-slate-400 transition-transform duration-200"
                :class="{ 'rotate-180': showProfileDropdown }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>
            <Transition name="dropdown">
              <div
                v-if="showProfileDropdown"
                class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
              >
                <div class="px-4 py-4 bg-gradient-to-r from-amber-50 to-amber-100/50 border-b border-gray-100">
                  <div class="flex items-center gap-3">
                    <img 
                      v-if="guestAvatar" 
                      :src="guestAvatar" 
                      :alt="guestName"
                      class="w-12 h-12 rounded-full object-cover border-2 border-amber-300"
                    />
                    <div v-else class="w-12 h-12 rounded-full bg-amber-200 flex items-center justify-center text-2xl">
                      👤
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-800">{{ guestName }}</h4>
                      <p class="text-xs text-slate-500">{{ guestEmail }}</p>
                      <p class="text-xs text-amber-600 font-medium">Room {{ currentRoom || 'Not assigned' }}</p>
                    </div>
                  </div>
                </div>
                <div class="py-1">
                  <button
                    @click="handleViewProfile"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-amber-50 transition-colors duration-150"
                  >
                    <span class="text-lg">👤</span>
                    <span class="text-sm text-slate-700">My Profile</span>
                  </button>

                  <button
                    @click="handleMyOrders"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-amber-50 transition-colors duration-150"
                  >
                    <span class="text-lg">📋</span>
                    <span class="text-sm text-slate-700">My Orders</span>
                  </button>
                  <button
                    @click="handleSettings"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-amber-50 transition-colors duration-150"
                  >
                    <span class="text-lg">⚙️</span>
                    <span class="text-sm text-slate-700">Settings</span>
                  </button>
                  <button
                    @click="handlePreferences"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-amber-50 transition-colors duration-150"
                  >
                    <span class="text-lg">🎯</span>
                    <span class="text-sm text-slate-700">Preferences</span>
                  </button>

                  <div class="border-t border-gray-100 my-1"></div>

                  <button
                    @click="handleLogout"
                    class="flex items-center gap-3 w-full px-4 py-2.5 text-left hover:bg-red-50 transition-colors duration-150 text-red-600"
                  >
                    <span class="text-lg">🚪</span>
                    <span class="text-sm font-medium">Logout</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
          <button 
            @click="toggleMobileMenu"
            class="md:hidden flex items-center justify-center w-8 h-8 rounded-full hover:bg-amber-50 transition-colors"
          >
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>
      </div>
      <Transition name="slide-down">
        <div v-if="showMobileMenu" class="md:hidden border-t border-gray-100 bg-white/95 backdrop-blur-xl">
          <div class="container mx-auto px-4 py-3 space-y-2">
            <!-- Mobile Profile Header -->
            <div class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-amber-50 to-amber-100/50 rounded-xl mb-2">
              <img 
                v-if="guestAvatar" 
                :src="guestAvatar" 
                :alt="guestName"
                class="w-10 h-10 rounded-full object-cover border-2 border-amber-300"
              />
              <div v-else class="w-10 h-10 rounded-full bg-amber-200 flex items-center justify-center text-xl">
                👤
              </div>
              <div>
                <p class="text-sm font-semibold text-slate-800">{{ guestName }}</p>
                <p class="text-xs text-slate-500">Room {{ currentRoom || 'Not assigned' }}</p>
              </div>
            </div>

            <button
              @click="handleViewProfile"
              class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl hover:bg-amber-50 transition-colors"
            >
              <span class="text-lg">👤</span>
              <span class="text-sm text-slate-700">My Profile</span>
            </button>

            <button
              @click="handleMyOrders"
              class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl hover:bg-amber-50 transition-colors"
            >
              <span class="text-lg">📋</span>
              <span class="text-sm text-slate-700">My Orders</span>
            </button>

            <button
              @click="handleSearchClick"
              class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl hover:bg-amber-50 transition-colors"
            >
              <span class="text-lg">🔍</span>
              <span class="text-sm text-slate-700">Search Menu</span>
            </button>

            <button
              @click="handleSettings"
              class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl hover:bg-amber-50 transition-colors"
            >
              <span class="text-lg">⚙️</span>
              <span class="text-sm text-slate-700">Settings</span>
            </button>

            <div class="border-t border-gray-100 my-2"></div>

            <button
              @click="handleLogout"
              class="flex items-center gap-3 w-full px-4 py-2.5 rounded-xl hover:bg-red-50 transition-colors text-red-600"
            >
              <span class="text-lg">🚪</span>
              <span class="text-sm font-medium">Logout</span>
            </button>
          </div>
        </div>
      </Transition>

    </nav>

    <!-- Navbar Spacer -->
    <div class="h-16 md:h-20"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import api from '../../../api/auth'

interface Room {
  id: string
  room_number: string
  status: string
  is_active: boolean
}

interface Props {
  guestName?: string
  guestEmail?: string
  guestAvatar?: string
  initialRoom?: string | number
}

const props = withDefaults(defineProps<Props>(), {
  guestName: '',
  guestEmail: '',
  guestAvatar: '',
  initialRoom: ''
})

const emit = defineEmits<{
  search: []
  logout: []
  settings: []
  orders: []
  'view-profile': []
  'room-selected': [room: string | number]
}>()

const router = useRouter()
const authStore = useAuthStore()

// State
const currentRoom = ref<string | number>(props.initialRoom || '')
const showRoomDropdown = ref(false)
const showProfileDropdown = ref(false)
const showMobileMenu = ref(false)
const availableRooms = ref<Room[]>([])
const roomsLoading = ref(false)

// Computed
const guestName = computed(() => {
  if (props.guestName) return props.guestName
  return authStore.user?.name || 'Guest User'
})

const guestEmail = computed(() => {
  if (props.guestEmail) return props.guestEmail
  return authStore.user?.email || 'guest@royalhorizon.com'
})

const guestAvatar = computed(() => {
  if (props.guestAvatar) return props.guestAvatar
  return authStore.user?.avatar || ''
})
async function fetchAvailableRooms() {
  roomsLoading.value = true
  try {
    const response = await api.get('/rooms', {
      params: { status: 'available', per_page: 100 }
    })
    if (response.data?.data) {
      availableRooms.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching rooms:', error)
    availableRooms.value = []
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
  showRoomDropdown.value = false
  showProfileDropdown.value = false
}

function closeAllDropdowns() {
  showRoomDropdown.value = false
  showProfileDropdown.value = false
  showMobileMenu.value = false
}

function selectRoom(room: string) {
  currentRoom.value = room
  showRoomDropdown.value = false
  emit('room-selected', room)
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

function handlePreferences() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  router.push('/preferences')
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
    showMobileMenu.value = false
  }
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

.dropdown-enter-to,
.dropdown-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

/* Slide Down Animation for Mobile */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.25s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-down-enter-to,
.slide-down-leave-from {
  opacity: 1;
  transform: translateY(0);
}

/* Guest Navbar Specific */
.guest-navbar {
  transition: box-shadow 0.3s ease;
}

.guest-navbar:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

/* Responsive Text */
@media (max-width: 640px) {
  .guest-navbar .container {
    padding-left: 12px;
    padding-right: 12px;
  }
}
</style>