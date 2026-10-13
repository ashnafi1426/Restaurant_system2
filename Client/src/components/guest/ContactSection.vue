<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { publicAxios } from '@/services/axios'
import { Loader2, CheckCircle2, AlertCircle } from 'lucide-vue-next'

const guestHotelStore = useGuestHotelStore()

const loading = ref(false)
const submitted = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

async function submitForm() {
  loading.value = true
  errorMessage.value = ''
  submitted.value = false
  successMessage.value = ''

  try {
    const hotelTarget =
      guestHotelStore.hotelId ||
      guestHotelStore.currentHotel?.slug ||
      localStorage.getItem('guest_hotel_id') ||
      'executive-horizon'

    const res = await publicAxios.post('/guest/contact-messages', {
      hotel_id: hotelTarget,
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone || undefined,
      subject: form.value.subject || 'General Inquiry',
      message: form.value.message,
    })

    if (res.data?.success) {
      submitted.value = true
      successMessage.value =
        res.data.message || `Thank you! Your message has been sent to ${guestHotelStore.hotelName}.`
      form.value = {
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
      }
    } else {
      errorMessage.value = res.data?.message || 'Failed to send message.'
    }
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message || 'Error occurred while sending your message. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="bg-[#f8f5f0] py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-10">
      <div class="mb-16 text-center">
        <p class="mb-4 uppercase tracking-[6px] text-amber-600">Contact Us</p>

        <h2 class="text-5xl font-light text-slate-900">We'd Love To Hear From You</h2>

        <p class="mx-auto mt-6 max-w-3xl text-lg text-slate-500">
          Whether you're planning your next vacation, have questions about reservations, or need
          assistance during your stay at {{ guestHotelStore.hotelName }}, our team is ready to help.
        </p>
      </div>

      <div class="grid gap-12 lg:grid-cols-2">
        <div>
          <div class="rounded-3xl bg-white p-10 shadow-lg">
            <h3 class="text-3xl font-semibold text-slate-900">Hotel Information</h3>

            <div class="mt-10 space-y-8">
              <div class="flex">
                <div class="text-3xl">📍</div>

                <div class="ml-5">
                  <h4 class="font-semibold text-slate-900">Address</h4>

                  <p class="mt-2 text-slate-500">
                    {{ guestHotelStore.hotelAddress }}
                  </p>
                </div>
              </div>

              <div class="flex">
                <div class="text-3xl">☎️</div>

                <div class="ml-5">
                  <h4 class="font-semibold text-slate-900">Phone</h4>

                  <p class="mt-2 text-slate-500">
                    {{ guestHotelStore.hotelPhone }}
                  </p>
                  <p class="text-xs text-slate-400 mt-1">
                    Hotline: {{ guestHotelStore.reservationPhone }}
                  </p>
                </div>
              </div>

              <div class="flex">
                <div class="text-3xl">✉️</div>

                <div class="ml-5">
                  <h4 class="font-semibold text-slate-900">Email</h4>

                  <p class="mt-2 text-slate-500">{{ guestHotelStore.hotelEmail }}</p>
                  <p class="text-xs text-slate-400 mt-1">{{ guestHotelStore.reservationEmail }}</p>
                </div>
              </div>

              <div class="flex">
                <div class="text-3xl">🕒</div>

                <div class="ml-5">
                  <h4 class="font-semibold text-slate-900">Reception & Dining</h4>

                  <p class="mt-2 text-slate-500">{{ guestHotelStore.frontDeskHours }}</p>
                  <p class="text-xs text-slate-400 mt-1">{{ guestHotelStore.diningHours }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <form class="rounded-3xl bg-white p-10 shadow-lg" @submit.prevent="submitForm">
            <h3 class="text-3xl font-semibold text-slate-900">Send Message</h3>

            <div
              v-if="submitted"
              class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3"
            >
              <CheckCircle2 class="w-5 h-5 flex-shrink-0 text-emerald-600" />
              <span>{{ successMessage }}</span>
            </div>

            <div
              v-if="errorMessage"
              class="mt-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3"
            >
              <AlertCircle class="w-5 h-5 flex-shrink-0 text-rose-600" />
              <span>{{ errorMessage }}</span>
            </div>

            <div class="mt-8 space-y-6">
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Full Name *"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              />

              <input
                v-model="form.email"
                type="email"
                required
                placeholder="Email Address *"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              />

              <input
                v-model="form.phone"
                type="text"
                placeholder="Phone Number"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              />

              <input
                v-model="form.subject"
                type="text"
                required
                placeholder="Subject *"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              />

              <textarea
                v-model="form.message"
                rows="6"
                required
                placeholder="Write your message... *"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              />

              <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-full bg-amber-600 py-4 text-lg font-semibold text-white transition hover:bg-amber-700 flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
              >
                <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                <span>{{ loading ? 'Sending...' : 'Send Message' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="mt-20 rounded-3xl bg-slate-900 px-10 py-16 text-center text-white">
        <h2 class="text-4xl font-light">Ready For Your Next Stay?</h2>

        <p class="mx-auto mt-6 max-w-3xl text-lg text-slate-300">
          Book your luxury experience today and discover comfort, elegance, and world-class
          hospitality at {{ guestHotelStore.hotelName }}.
        </p>

        <RouterLink
          to="/reservation"
          class="mt-10 inline-flex rounded-full bg-amber-500 px-10 py-4 font-semibold text-white transition hover:bg-amber-600"
        >
          Book Your Stay
        </RouterLink>
      </div>
    </div>
  </section>
</template>
