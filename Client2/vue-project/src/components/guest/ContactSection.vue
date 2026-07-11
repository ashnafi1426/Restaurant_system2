<script setup lang="ts">
import { reactive } from 'vue'

const form = reactive({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

function submitForm() {
  console.log(form)

  alert('Thank you! Your message has been sent.')
}
</script>

<template>
  <section class="bg-[#f8f5f0] py-24">

    <div class="mx-auto max-w-7xl px-6 lg:px-10">

      <!-- ====================================== -->
      <!-- Section Heading -->
      <!-- ====================================== -->

      <div class="mb-16 text-center">

        <p class="mb-4 uppercase tracking-[6px] text-amber-600">

          Contact Us

        </p>

        <h2 class="text-5xl font-light text-slate-900">

          We'd Love To Hear From You

        </h2>

        <p class="mx-auto mt-6 max-w-3xl text-lg text-slate-500">

          Whether you're planning your next vacation,
          have questions about reservations,
          or need assistance during your stay,
          our team is ready to help.

        </p>

      </div>

      <div class="grid gap-12 lg:grid-cols-2">

        <!-- ====================================== -->
        <!-- Contact Information -->
        <!-- ====================================== -->

        <div>

          <div class="rounded-3xl bg-white p-10 shadow-lg">

            <h3 class="text-3xl font-semibold text-slate-900">

              Hotel Information

            </h3>

            <div class="mt-10 space-y-8">

              <div class="flex">

                <div class="text-3xl">

                  📍

                </div>

                <div class="ml-5">

                  <h4 class="font-semibold">

                    Address

                  </h4>

                  <p class="mt-2 text-slate-500">

                    123 Luxury Avenue<br>

                    Addis Ababa, Ethiopia

                  </p>

                </div>

              </div>

              <div class="flex">

                <div class="text-3xl">

                  ☎️

                </div>

                <div class="ml-5">

                  <h4 class="font-semibold">

                    Phone

                  </h4>

                  <p class="mt-2 text-slate-500">

                    +251 911 000 000

                  </p>

                </div>

              </div>

              <div class="flex">

                <div class="text-3xl">

                  ✉️

                </div>

                <div class="ml-5">

                  <h4 class="font-semibold">

                    Email

                  </h4>

                  <p class="mt-2 text-slate-500">

                    info@grandhorizon.com

                  </p>

                </div>

              </div>

              <div class="flex">

                <div class="text-3xl">

                  🕒

                </div>

                <div class="ml-5">

                  <h4 class="font-semibold">

                    Reception

                  </h4>

                  <p class="mt-2 text-slate-500">

                    Open 24 Hours

                  </p>

                </div>

              </div>

            </div>

          </div>

          <!-- Map -->

          <div class="mt-8 overflow-hidden rounded-3xl shadow-lg">

            <iframe
              src="https://www.google.com/maps/embed?pb="
              class="h-80 w-full"
              loading="lazy"
            />

          </div>

        </div>

        <!-- ====================================== -->
        <!-- Contact Form -->
        <!-- ====================================== -->

        <div>

          <form
            class="rounded-3xl bg-white p-10 shadow-lg"
            @submit.prevent="submitForm"
          >

            <h3 class="text-3xl font-semibold text-slate-900">

              Send Message

            </h3>

            <div class="mt-8 space-y-6">

              <input
                v-model="form.name"
                type="text"
                placeholder="Full Name"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              >

              <input
                v-model="form.email"
                type="email"
                placeholder="Email Address"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              >

              <input
                v-model="form.phone"
                type="text"
                placeholder="Phone Number"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              >

              <input
                v-model="form.subject"
                type="text"
                placeholder="Subject"
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              >

              <textarea
                v-model="form.message"
                rows="6"
                placeholder="Write your message..."
                class="w-full rounded-xl border p-4 focus:border-amber-500 focus:outline-none"
              />

              <button
                class="w-full rounded-full bg-amber-600 py-4 text-lg font-semibold text-white transition hover:bg-amber-700"
              >
                Send Message
              </button>

            </div>

          </form>

        </div>

      </div>

      <!-- ====================================== -->
      <!-- CTA -->
      <!-- ====================================== -->

      <div
        class="mt-20 rounded-3xl bg-slate-900 px-10 py-16 text-center text-white"
      >

        <h2 class="text-4xl font-light">

          Ready For Your Next Stay?

        </h2>

        <p class="mx-auto mt-6 max-w-3xl text-lg text-slate-300">

          Book your luxury experience today and discover comfort,
          elegance, and world-class hospitality.

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