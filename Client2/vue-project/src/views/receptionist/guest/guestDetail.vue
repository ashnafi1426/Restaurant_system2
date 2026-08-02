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
    <div v-if="guestStore.loading" class="text-center py-10 text-slate-600 dark:text-slate-400">Loading...</div>

    <div v-else-if="guestStore.guest" class="bg-white dark:bg-slate-800 rounded-xl shadow p-8">
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            {{ guestStore.guest.full_name }}
          </h1>

          <p class="text-gray-500 dark:text-slate-400">Guest Details</p>
        </div>

        <button @click="router.back()" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">Back</button>
      </div>

      <div class="grid grid-cols-2 gap-6">
        <div>
          <strong class="text-slate-900 dark:text-white">Email</strong>
          <p class="text-slate-600 dark:text-slate-400">{{ guestStore.guest.email || '-' }}</p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">Phone</strong>
          <p class="text-slate-600 dark:text-slate-400">{{ guestStore.guest.phone }}</p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">Nationality</strong>
          <p class="text-slate-600 dark:text-slate-400">{{ guestStore.guest.nationality || '-' }}</p>
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
