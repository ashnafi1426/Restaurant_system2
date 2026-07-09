<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import GuestForm from '../../../components/guest/guestForm.vue'

import { useGuestStore } from '../../../stores/guestStore'
import type { GuestForm as GuestFormType } from '../../../types/guest'

const router = useRouter()
const guestStore = useGuestStore()

const form = ref<GuestFormType>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
})

const saveGuest = async () => {
  try {
    await guestStore.addGuest(form.value)

    alert('Guest created successfully.')

    router.push('/guests')
  } catch (error) {
    console.error(error)
  }
}

const cancel = () => {
  router.push('/guests')
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-6xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Create Guest</h1>

        <p class="text-gray-500 mt-2">Register a new hotel guest.</p>
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
