<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import GuestForm from '../../../components/guest/GuestForm.vue'

import { useGuestStore } from '../../../stores/guestStore'
import { useLanguageStore } from '@/stores/language'
import type { GuestForm as GuestFormType } from '../../../types/guest'

const route = useRoute()
const router = useRouter()
const guestStore = useGuestStore()
const languageStore = useLanguageStore()

const guestId = route.params.id as string
const loading = ref(true)
const form = ref<GuestFormType>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
  nationality: '',
  passport_number: '',
  date_of_birth: '',
  preferences: [],
})

const loadGuest = async () => {
  try {
    await guestStore.fetchGuest(guestId)

    if (guestStore.guest) {
      form.value = {
        first_name: guestStore.guest.first_name,
        last_name: guestStore.guest.last_name,
        email: guestStore.guest.email ?? '',
        phone: guestStore.guest.phone,
        address: guestStore.guest.address ?? '',
        nationality: guestStore.guest.nationality ?? '',
        passport_number: guestStore.guest.passport_number ?? '',
        date_of_birth: guestStore.guest.date_of_birth ?? '',
        preferences: guestStore.guest.preferences ?? [],
      }
    }
  } catch (error) {
    console.error('[EditGuest] Failed to load guest:', error)
  } finally {
    loading.value = false
  }
}

const updateGuest = async () => {
  try {
    await guestStore.editGuest(guestId, form.value)

    alert(languageStore.t('guest_updated_success', 'Guest updated successfully.'))

    router.push('/guests')
  } catch (error) {
    console.error('[EditGuest] Failed to update guest:', error)
  }
}
const cancel = () => {
  router.push('/admin/guests')
}

onMounted(loadGuest)
</script>

<template>
  <DashboardLayout>
    <div class="max-w-6xl mx-auto bg-white dark:bg-slate-900 p-6 rounded-lg">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">{{ languageStore.t('edit_guest', 'Edit Guest') }}</h1>

        <p class="text-gray-500 dark:text-slate-400 mt-2">{{ languageStore.t('update_guest_info', 'Update guest information.') }}</p>
      </div>

      <div v-if="loading" class="bg-white dark:bg-slate-800 rounded-xl shadow p-10 text-center text-gray-800 dark:text-white font-medium">
        {{ languageStore.t('loading', 'Loading...') }}
      </div>

      <GuestForm
        v-else
        v-model="form"
        :loading="guestStore.loading"
        @submit="updateGuest"
        @cancel="cancel"
      />
    </div>
  </DashboardLayout>
</template>
