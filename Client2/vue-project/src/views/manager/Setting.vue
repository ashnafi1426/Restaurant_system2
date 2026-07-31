<script setup lang="ts">
import { ref } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'

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
  /*
    Later:

    axios.put('/manager/settings')

    */

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
        <h1 class="text-3xl font-bold text-slate-800">Manager Settings</h1>

        <p class="text-slate-500 mt-2">Manage hotel operational preferences</p>
      </div>

      <!-- Hotel Information -->

      <div class="bg-white rounded-3xl shadow-sm p-8">
        <h2 class="text-xl font-bold mb-6">Hotel Information</h2>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label class="text-sm text-slate-500"> Hotel Name </label>

            <input v-model="hotelSettings.hotelName" class="mt-2 w-full rounded-xl border p-3" />
          </div>

          <div>
            <label class="text-sm text-slate-500"> Phone </label>

            <input v-model="hotelSettings.phone" class="mt-2 w-full rounded-xl border p-3" />
          </div>

          <div>
            <label class="text-sm text-slate-500"> Email </label>

            <input v-model="hotelSettings.email" class="mt-2 w-full rounded-xl border p-3" />
          </div>

          <div>
            <label class="text-sm text-slate-500"> Address </label>

            <input v-model="hotelSettings.address" class="mt-2 w-full rounded-xl border p-3" />
          </div>
        </div>
      </div>

      <!-- Restaurant Settings -->

      <div class="bg-white rounded-3xl shadow-sm p-8">
        <h2 class="text-xl font-bold mb-6">Restaurant Settings</h2>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label> Opening Time </label>

            <input
              type="time"
              v-model="restaurantSettings.openTime"
              class="mt-2 w-full border rounded-xl p-3"
            />
          </div>

          <div>
            <label> Closing Time </label>

            <input
              type="time"
              v-model="restaurantSettings.closeTime"
              class="mt-2 w-full border rounded-xl p-3"
            />
          </div>

          <div>
            <label> Delivery Time (minutes) </label>

            <input
              type="number"
              v-model="restaurantSettings.deliveryTime"
              class="mt-2 w-full border rounded-xl p-3"
            />
          </div>

          <div class="flex items-center gap-3 mt-8">
            <input type="checkbox" v-model="restaurantSettings.roomServiceAvailable" />

            <span> Enable Room Service </span>
          </div>
        </div>
      </div>

      <!-- Notification Settings -->

      <div class="bg-white rounded-3xl shadow-sm p-8">
        <h2 class="text-xl font-bold mb-6">Notifications</h2>

        <div class="space-y-4">
          <label class="flex justify-between">
            <span> New Reservations </span>

            <input type="checkbox" v-model="notificationSettings.newReservation" />
          </label>

          <label class="flex justify-between">
            <span> New Food Orders </span>

            <input type="checkbox" v-model="notificationSettings.newOrder" />
          </label>

          <label class="flex justify-between">
            <span> Guest Complaints </span>

            <input type="checkbox" v-model="notificationSettings.complaint" />
          </label>

          <label class="flex justify-between">
            <span> Payments </span>

            <input type="checkbox" v-model="notificationSettings.payment" />
          </label>
        </div>
      </div>

      <!-- Save -->

      <button
        @click="saveSettings"
        class="bg-blue-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-blue-700 transition"
      >
        Save Settings
      </button>

      <div v-if="saved" class="bg-green-100 text-green-700 px-5 py-3 rounded-xl">
        Settings saved successfully
      </div>
    </div>
  </DashboardLayout>
</template>
