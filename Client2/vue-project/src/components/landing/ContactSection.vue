<template>
  <section
    class="py-12 sm:py-16 lg:py-20 bg-slate-50 dark:bg-slate-950 transition-colors duration-300 min-h-screen font-sans"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      <div class="text-center max-w-3xl mx-auto space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ languageStore.t('get_in_touch', 'Get In Touch') }}
        </span>
        <h1
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('connect_with_us', 'Connect With Us') }}
        </h1>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'contact_desc',
              'Have questions or special requests? Our concierge and reservation teams at ' +
                guestHotelStore.hotelName +
                ' are available 24/7 to ensure your experience is extraordinary.',
            )
          }}
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-5 space-y-4">
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs hover:border-amber-500/40 transition flex items-start gap-4"
          >
            <div
              class="p-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-2xl border border-amber-500/20 flex-shrink-0"
            >
              <MapPin class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">
                {{ languageStore.t('hotel_location', 'Hotel Location') }}
              </h3>
              <p class="text-sm font-extrabold text-slate-900 dark:text-white mt-1">
                {{ guestHotelStore.hotelName }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ guestHotelStore.hotelAddress }}
              </p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs hover:border-blue-500/40 transition flex items-start gap-4"
          >
            <div
              class="p-3 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-2xl border border-blue-500/20 flex-shrink-0"
            >
              <Phone class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">
                {{ languageStore.t('direct_phone_lines', 'Direct Phone Lines') }}
              </h3>
              <p class="text-sm font-extrabold text-slate-900 dark:text-white mt-1">
                {{ guestHotelStore.hotelPhone }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t('reservations_hotline', 'Reservations & Concierge (24/7 Hotline)')
                }}
              </p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs hover:border-emerald-500/40 transition flex items-start gap-4"
          >
            <div
              class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-2xl border border-emerald-500/20 flex-shrink-0"
            >
              <Mail class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">
                {{ languageStore.t('email_enquiries', 'Email Enquiries') }}
              </h3>
              <p class="text-sm font-extrabold text-slate-900 dark:text-white mt-1">
                {{ guestHotelStore.hotelEmail }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                reservations@{{ guestHotelStore.currentHotel?.slug || 'hotel' }}.com
              </p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs hover:border-purple-500/40 transition flex items-start gap-4"
          >
            <div
              class="p-3 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-2xl border border-purple-500/20 flex-shrink-0"
            >
              <Clock class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">
                {{ languageStore.t('hours_of_service', 'Hours of Service') }}
              </h3>
              <p class="text-sm font-extrabold text-slate-900 dark:text-white mt-1">
                {{ languageStore.t('front_desk_hours', 'Front Desk: Open 24/7 / 365 Days') }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ languageStore.t('dining_hours', 'Dining & Kitchen: 06:00 AM – 11:00 PM Daily') }}
              </p>
            </div>
          </div>
        </div>

        <div
          class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6"
        >
          <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">
              {{ languageStore.t('send_us_a_message', 'Send Us a Message') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{
                languageStore.t(
                  'send_message_desc',
                  'Fill in the details below and our guest relations manager will respond within 2 hours.',
                )
              }}
            </p>
          </div>

          <div
            v-if="submitted"
            class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-extrabold flex items-center gap-3"
          >
            <CheckCircle2 class="w-5 h-5 flex-shrink-0" />
            <span>{{
              languageStore.t(
                'message_sent_success',
                'Thank you! Your message has been received. Our concierge team will contact you shortly.',
              )
            }}</span>
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('your_name', 'Your Name') }} *</label
                >
                <input
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="e.g. Haile Bikila"
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                />
              </div>

              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('email_address', 'Email Address') }} *</label
                >
                <input
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="name@example.com"
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('phone_number', 'Phone Number') }}</label
                >
                <input
                  v-model="form.phone"
                  type="tel"
                  placeholder="+251 91 123 4567"
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition"
                />
              </div>

              <div>
                <label
                  class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                  >{{ languageStore.t('inquiry_subject', 'Inquiry Subject') }} *</label
                >
                <select
                  v-model="form.subject"
                  required
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-amber-500 transition cursor-pointer"
                >
                  <option value="reservation">
                    {{ languageStore.t('subj_reservation', 'Room Reservation & Availability') }}
                  </option>
                  <option value="dining">
                    {{ languageStore.t('subj_dining', 'Restaurant & Dining Reservation') }}
                  </option>
                  <option value="events">
                    {{ languageStore.t('subj_events', 'Weddings, Conferences & Events') }}
                  </option>
                  <option value="general">
                    {{ languageStore.t('subj_general', 'General Inquiry & Feedback') }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label
                class="block text-[10px] font-extrabold uppercase text-slate-500 dark:text-slate-400 mb-1"
                >{{ languageStore.t('your_message', 'Your Message') }} *</label
              >
              <textarea
                v-model="form.message"
                rows="5"
                required
                :placeholder="
                  languageStore.t(
                    'message_placeholder',
                    'How can we assist you with your upcoming stay or event?',
                  )
                "
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white font-medium focus:outline-none focus:border-amber-500 transition"
              ></textarea>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full py-3.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
              <Send v-else class="w-4 h-4" />
              <span>{{
                loading
                  ? languageStore.t('sending_message', 'Sending Message...')
                  : languageStore.t('send_message', 'SEND MESSAGE')
              }}</span>
            </button>
          </form>
        </div>
      </div>

      <div
        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6"
      >
        <div class="text-center max-w-xl mx-auto space-y-1">
          <h2 class="text-xl font-black text-slate-900 dark:text-white">
            {{ languageStore.t('faq_title', 'Frequently Asked Questions') }}
          </h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{
              languageStore.t(
                'faq_desc',
                'Quick answers to common questions about ' + guestHotelStore.hotelName + '.',
              )
            }}
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
          >
            <h3 class="text-xs font-black text-slate-900 dark:text-white">
              {{ languageStore.t('faq_q1', 'What are Check-In & Check-Out times?') }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
              {{
                languageStore.t(
                  'faq_a1',
                  'Standard Check-In is at 02:00 PM and Check-Out is at 12:00 PM. Early check-in or late check-out can be requested based on availability.',
                )
              }}
            </p>
          </div>

          <div
            class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
          >
            <h3 class="text-xs font-black text-slate-900 dark:text-white">
              {{ languageStore.t('faq_q2', 'Is Airport Shuttle Service Available?') }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
              {{
                languageStore.t(
                  'faq_a2',
                  'Yes, complimentary luxury airport pickup and drop-off are provided for all suite guests upon request at least 24 hours prior.',
                )
              }}
            </p>
          </div>

          <div
            class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2"
          >
            <h3 class="text-xs font-black text-slate-900 dark:text-white">
              {{ languageStore.t('faq_q3', 'Are Dining & Room Service 24 Hours?') }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
              {{
                languageStore.t(
                  'faq_a3',
                  'Room service food ordering is available 24/7 directly through your room QR code menu or by calling front desk reception.',
                )
              }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { MapPin, Phone, Mail, Clock, Send, Loader2, CheckCircle2 } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const loading = ref(false)
const submitted = ref(false)

const form = ref({
  name: '',
  email: '',
  phone: '',
  subject: 'reservation',
  message: '',
})

const handleSubmit = async () => {
  loading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 800))
    submitted.value = true
    form.value = {
      name: '',
      email: '',
      phone: '',
      subject: 'reservation',
      message: '',
    }
  } finally {
    loading.value = false
  }
}
</script>
