<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import GuestForm from '../../../components/guest/GuestForm.vue'

import { useGuestStore } from '../../../stores/guestStore'
import { useLanguageStore } from '@/stores/language'
import type { GuestForm as GuestFormType } from '../../../types/guest'

const router = useRouter()
const guestStore = useGuestStore()
const languageStore = useLanguageStore()

let form = ref<GuestFormType>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
})

const saveGuest = async () => {
  try {
    await guestStore.addGuest(form.value)

    alert(languageStore.t('guest_created_success', 'Guest created successfully.'))

    router.push('/guests')
  } catch (error) {
    console.error('[CreateGuest] Failed to save guest:', error)
  }
}

const cancel = () => {
  router.push('/guests')
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-6xl mx-auto bg-white dark:bg-slate-900 p-6 rounded-lg">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
          {{ languageStore.t('create_guest', 'Create Guest') }}
        </h1>

        <p class="text-gray-500 dark:text-slate-400 mt-2">
          {{ languageStore.t('register_new_guest', 'Register a new hotel guest.') }}
        </p>
      </div>

      <GuestForm
        v-model="form"
        :loading="guestStore.loading"
        @submit="saveGuest"
        @cancel="cancel"
      />
    </div>
  </DashboardLayout>
</template>
