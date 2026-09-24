<script setup lang="ts">
import { ref } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

/*
|--------------------------------------------------------------------------
| Hotel Settings
|--------------------------------------------------------------------------
*/

const hotelSettings = ref({
  hotelName: 'Royal Horizon Hotel',
  phone: '+251900000000',
  email: 'info@royalhorizon.com',
  address: 'Addis Ababa Ethiopia',
})

/*
|--------------------------------------------------------------------------
| Restaurant Settings
|--------------------------------------------------------------------------
*/

const restaurantSettings = ref({
  openTime: '06:00',
  closeTime: '23:00',
  roomServiceAvailable: true,
  deliveryTime: 30,
})

/*
|--------------------------------------------------------------------------
| Notification Settings
|--------------------------------------------------------------------------
*/

const notificationSettings = ref({
  newReservation: true,
  newOrder: true,
  complaint: true,
  payment: true,
})

const saved = ref(false)

const saveSettings = () => {
  saved.value = true

  setTimeout(() => {
    saved.value = false
  }, 3000)
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">
      <!-- Header -->

      <div>
        <h1 class="text-3xl font-bold text-slate-800">{{ languageStore.t('manager_settings', 'Manager Settings') }}</h1>

        <p class="text-slate-500 mt-2">{{ languageStore.t('manage_hotel_operational_preferences', 'Manage hotel operational preferences') }}</p>
      </div>

      <!-- Hotel Information -->

      <div class="bg-white rounded-3xl shadow-sm p-8">
        <h2 class="text-xl font-bold mb-6">{{ languageStore.t('hotel_information', 'Hotel Information') }}</h2>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label class="text-sm text-slate-500"> {{ languageStore.t('hotel_name', 'Hotel Name') }} </label>

            <input v-model="hotelSettings.hotelName" class="mt-2 w-full rounded-xl border p-3" />
          </div>

          <div>
            <label class="text-sm text-slate-500"> {{ languageStore.t('phone', 'Phone') }} </label>

            <input v-model="hotelSettings.phone" class="mt-2 w-full rounded-xl border p-3" />
          </div>

          <div>
            <label class="text-sm text-slate-500"> {{ languageStore.t('email', 'Email') }} </label>

            <input v-model="hotelSettings.email" class="mt-2 w-full rounded-xl border p-3" />
          </div>

          <div>
            <label class="text-sm text-slate-500"> {{ languageStore.t('address', 'Address') }} </label>

            <input v-model="hotelSettings.address" class="mt-2 w-full rounded-xl border p-3" />
          </div>
        </div>
      </div>

      <!-- Restaurant Settings -->

      <div class="bg-white rounded-3xl shadow-sm p-8">
        <h2 class="text-xl font-bold mb-6">{{ languageStore.t('restaurant_settings', 'Restaurant Settings') }}</h2>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label> {{ languageStore.t('opening_time', 'Opening Time') }} </label>

            <input
              type="time"
              v-model="restaurantSettings.openTime"
              class="mt-2 w-full border rounded-xl p-3"
            />
          </div>

          <div>
            <label> {{ languageStore.t('closing_time', 'Closing Time') }} </label>

            <input
              type="time"
              v-model="restaurantSettings.closeTime"
              class="mt-2 w-full border rounded-xl p-3"
            />
          </div>

          <div>
            <label> {{ languageStore.t('delivery_time_minutes', 'Delivery Time (minutes)') }} </label>

            <input
              type="number"
              v-model="restaurantSettings.deliveryTime"
              class="mt-2 w-full border rounded-xl p-3"
            />
          </div>

          <div class="flex items-center gap-3 mt-8">
            <input type="checkbox" v-model="restaurantSettings.roomServiceAvailable" />

            <span> {{ languageStore.t('enable_room_service', 'Enable Room Service') }} </span>
          </div>
        </div>
      </div>

      <!-- Notification Settings -->

      <div class="bg-white rounded-3xl shadow-sm p-8">
        <h2 class="text-xl font-bold mb-6">{{ languageStore.t('notifications', 'Notifications') }}</h2>

        <div class="space-y-4">
          <label class="flex justify-between">
            <span> {{ languageStore.t('new_reservations', 'New Reservations') }} </span>

            <input type="checkbox" v-model="notificationSettings.newReservation" />
          </label>

          <label class="flex justify-between">
            <span> {{ languageStore.t('new_food_orders', 'New Food Orders') }} </span>

            <input type="checkbox" v-model="notificationSettings.newOrder" />
          </label>

          <label class="flex justify-between">
            <span> {{ languageStore.t('guest_complaints', 'Guest Complaints') }} </span>

            <input type="checkbox" v-model="notificationSettings.complaint" />
          </label>

          <label class="flex justify-between">
            <span> {{ languageStore.t('payments', 'Payments') }} </span>

            <input type="checkbox" v-model="notificationSettings.payment" />
          </label>
        </div>
      </div>

      <!-- Save -->

      <button
        @click="saveSettings"
        class="bg-blue-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-blue-700 transition"
      >
        {{ languageStore.t('save_settings', 'Save Settings') }}
      </button>

      <div v-if="saved" class="bg-green-100 text-green-700 px-5 py-3 rounded-xl">
        {{ languageStore.t('settings_saved_successfully', 'Settings saved successfully') }}
      </div>
    </div>
  </DashboardLayout>
</template>
