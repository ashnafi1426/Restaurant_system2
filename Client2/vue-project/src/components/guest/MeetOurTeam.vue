<script setup lang="ts">
interface TeamMember {
  id: number
  name: string
  position: string
  image: string
  bio: string
}

const teamMembers: TeamMember[] = [
  {
    id: 1,
    name: 'John Smith',
    position: 'General Manager',
    image: '/images/team/manager.jpg',
    bio: 'With 20+ years in luxury hospitality, John leads our team with vision and excellence.'
  },
  {
    id: 2,
    name: 'Chef Michael',
    position: 'Executive Chef',
    image: '/images/team/chef.jpg',
    bio: 'Award-winning chef specializing in international cuisine and culinary innovation.'
  },
  {
    id: 3,
    name: 'Sarah Johnson',
    position: 'Front Desk Manager',
    image: '/images/team/reception.jpg',
    bio: 'Dedicated to providing exceptional guest service and personalized attention.'
  },
  {
    id: 4,
    name: 'Emma Wilson',
    position: 'Guest Relations Manager',
    image: '/images/team/relations.jpg',
    bio: 'Ensures every guest experience is memorable and exceeds expectations.'
  }
]
</script>

<template>
  <section class="bg-slate-50 py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">

      <!-- Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600">
          Our Team
        </p>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          Meet Our Leadership
        </h2>
        <p class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500">
          Our dedicated team of professionals is committed to delivering
          exceptional service and creating unforgettable experiences for every guest.
        </p>
      </div>

      <!-- Team Grid -->
      <div class="grid gap-6 sm:gap-8 md:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="member in teamMembers"
          :key="member.id"
          class="text-center group"
        >
          <!-- Image -->
          <div class="relative mb-4 sm:mb-6 overflow-hidden rounded-lg sm:rounded-2xl">
            <img
              :src="member.image"
              :alt="member.name"
              class="h-56 sm:h-64 md:h-72 lg:h-80 w-full object-cover transition duration-500 group-hover:scale-110"
            >
            <!-- Hover Overlay -->
            <div class="absolute inset-0 bg-black/40 opacity-0 transition duration-300 group-hover:opacity-100 flex items-center justify-center p-4">
              <p class="text-white text-xs sm:text-sm leading-relaxed">
                {{ member.bio }}
              </p>
            </div>
          </div>

          <!-- Info -->
          <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900">
            {{ member.name }}
          </h3>
          <p class="mt-1 sm:mt-2 text-xs sm:text-sm md:text-base text-amber-600 font-medium">
            {{ member.position }}
          </p>
        </div>
      </div>

    </div>
  </section>
</template>
