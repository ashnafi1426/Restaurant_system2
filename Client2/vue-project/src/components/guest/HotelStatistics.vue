<script setup lang="ts">
import { BedDouble, Star, Award, Users } from 'lucide-vue-next'

const statistics = [
  {
    value: '150+',
    label: 'Luxury Suites & Rooms',
    icon: BedDouble,
    color: 'text-amber-400'
  },
  {
    value: '98%',
    label: 'Guest Satisfaction Rate',
    icon: Star,
    color: 'text-emerald-400'
  },
  {
    value: '25+',
    label: 'Years of Excellence',
    icon: Award,
    color: 'text-blue-400'
  },
  {
    value: '50+',
    label: 'Professional Staff',
    icon: Users,
    color: 'text-purple-400'
  },
]
</script>

<template>
  <section class="bg-slate-900 border-y border-slate-800 py-12 sm:py-16 text-white font-sans">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
        <div
          v-for="stat in statistics"
          :key="stat.label"
          class="p-6 rounded-3xl bg-slate-950/60 border border-slate-800 flex flex-col items-center justify-center space-y-3"
        >
          <div class="p-3 bg-slate-800/80 rounded-2xl border border-slate-700">
            <component :is="stat.icon" :class="['w-7 h-7', stat.color]" />
          </div>
          <h3 class="text-3xl sm:text-4xl font-black tracking-tight text-white">
            {{ stat.value }}
          </h3>
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
            {{ stat.label }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
