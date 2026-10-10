<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useGuestStore } from '../../../stores/guestStore'
import { useLanguageStore } from '@/stores/language'

const route = useRoute()
const router = useRouter()
const guestStore = useGuestStore()
const languageStore = useLanguageStore()

const guestId = route.params.id as string

onMounted(async () => {
  await guestStore.fetchGuest(guestId)
})
</script>

<template>
  <DashboardLayout>
    <div
      v-if="guestStore.loading"
      class="text-center py-10 text-slate-600 dark:text-slate-400 font-medium"
    >
      {{ languageStore.t('loading', 'Loading...') }}
    </div>

    <div
      v-else-if="guestStore.guest"
      class="bg-white dark:bg-slate-800 rounded-xl shadow p-8 border border-slate-200 dark:border-slate-700"
    >
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white">
            {{ guestStore.guest.full_name }}
          </h1>

          <p class="text-gray-500 dark:text-slate-400">
            {{ languageStore.t('guest_details', 'Guest Details') }}
          </p>
        </div>

        <button
          @click="router.back()"
          class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer"
        >
          {{ languageStore.t('back', 'Back') }}
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <strong class="text-slate-900 dark:text-white">{{
            languageStore.t('email', 'Email')
          }}</strong>
          <p class="text-slate-600 dark:text-slate-400 mt-1">{{ guestStore.guest.email || '-' }}</p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">{{
            languageStore.t('phone', 'Phone')
          }}</strong>
          <p class="text-slate-600 dark:text-slate-400 mt-1">{{ guestStore.guest.phone }}</p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">{{
            languageStore.t('nationality', 'Nationality')
          }}</strong>
          <p class="text-slate-600 dark:text-slate-400 mt-1">
            {{ guestStore.guest.nationality || '-' }}
          </p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">{{
            languageStore.t('passport', 'Passport')
          }}</strong>
          <p class="text-slate-600 dark:text-slate-400 mt-1">
            {{ guestStore.guest.passport_number || '-' }}
          </p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">{{
            languageStore.t('date_of_birth', 'Date of Birth')
          }}</strong>
          <p class="text-slate-600 dark:text-slate-400 mt-1">
            {{ guestStore.guest.date_of_birth || '-' }}
          </p>
        </div>

        <div>
          <strong class="text-slate-900 dark:text-white">{{
            languageStore.t('address', 'Address')
          }}</strong>
          <p class="text-slate-600 dark:text-slate-400 mt-1">
            {{ guestStore.guest.address || '-' }}
          </p>
        </div>
      </div>

      <div class="mt-8">
        <strong class="text-slate-900 dark:text-white">{{
          languageStore.t('preferences', 'Preferences')
        }}</strong>

        <div class="flex flex-wrap gap-2 mt-3">
          <span
            v-for="item in guestStore.guest.preferences"
            :key="item"
            class="px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold"
          >
            {{ item }}
          </span>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
