<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useGuestStore } from '../../../stores/guestStore'

const route = useRoute()
const router = useRouter()
const guestStore = useGuestStore()

const guestId = route.params.id as string

onMounted(async () => {
  await guestStore.fetchGuest(guestId)
})
</script>

<template>
  <DashboardLayout>
    <div v-if="guestStore.loading" class="text-center py-10">Loading...</div>

    <div v-else-if="guestStore.guest" class="bg-white rounded-xl shadow p-8">
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold">
            {{ guestStore.guest.full_name }}
          </h1>

          <p class="text-gray-500">Guest Details</p>
        </div>

        <button @click="router.back()" class="px-4 py-2 rounded-lg border">Back</button>
      </div>

      <div class="grid grid-cols-2 gap-6">
        <div>
          <strong>Email</strong>
          <p>{{ guestStore.guest.email || '-' }}</p>
        </div>

        <div>
          <strong>Phone</strong>
          <p>{{ guestStore.guest.phone }}</p>
        </div>

        <div>
          <strong>Nationality</strong>
          <p>{{ guestStore.guest.nationality || '-' }}</p>
        </div>

        <div>
          <strong>Passport</strong>
          <p>{{ guestStore.guest.passport_number || '-' }}</p>
        </div>

        <div>
          <strong>Date of Birth</strong>
          <p>{{ guestStore.guest.date_of_birth || '-' }}</p>
        </div>

        <div>
          <strong>Address</strong>
          <p>{{ guestStore.guest.address || '-' }}</p>
        </div>
      </div>

      <div class="mt-8">
        <strong>Preferences</strong>

        <div class="flex flex-wrap gap-2 mt-3">
          <span
            v-for="item in guestStore.guest.preferences"
            :key="item"
            class="px-3 py-1 rounded-full bg-blue-100 text-blue-700"
          >
            {{ item }}
          </span>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
