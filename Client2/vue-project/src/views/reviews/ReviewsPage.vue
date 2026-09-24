<template>
  <DashboardLayout>
    <template #header>
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ languageStore.t('my_reviews', 'My Reviews') }}</h1>
        <p class="text-slate-600">{{ languageStore.t('manage_reviews_and_account', 'Manage and view all your menu item reviews') }}</p>
      </div>
    </template>

    <div class="max-w-4xl">
      <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6">
        <div class="text-center py-16">
          <div class="flex justify-center mb-6">
            <div class="p-6 bg-blue-100 rounded-full">
              <MessageSquare :size="48" class="text-blue-600" />
            </div>
          </div>
          <p class="text-slate-900 mb-3 text-2xl font-bold">{{ languageStore.t('no_reviews_written_yet', "You haven't written any reviews yet.") }}</p>
          <p class="text-slate-600 mb-8 text-lg max-w-lg mx-auto">{{ languageStore.t('start_exploring_menu_desc', 'Start by exploring our menu and sharing your dining experience with other guests. Your feedback helps us improve our service!') }}</p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <router-link
              to="/menu"
              class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all hover:shadow-lg font-semibold"
            >
              <UtensilsCrossed :size="20" />
              {{ languageStore.t('browse_menu_write_review', 'Browse Menu & Write a Review') }}
            </router-link>
            <router-link
              to="/reviews/analytics"
              class="inline-flex items-center justify-center gap-2 px-6 py-3 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-all font-semibold"
            >
              <BarChart3 :size="20" />
              {{ languageStore.t('view_analytics', 'View Analytics') }}
            </router-link>
          </div>
        </div>
      </div>

      <!-- Additional Info Section -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-6">
          <div class="flex items-center gap-3 mb-3">
            <Star :size="24" class="text-blue-600" />
            <h3 class="font-semibold text-slate-900">{{ languageStore.t('share_your_experience', 'Share Your Experience') }}</h3>
          </div>
          <p class="text-sm text-slate-700">{{ languageStore.t('share_experience_card_desc', 'Write honest reviews and rate dishes to help other guests make informed choices.') }}</p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg border border-green-200 p-6">
          <div class="flex items-center gap-3 mb-3">
            <Trophy :size="24" class="text-green-600" />
            <h3 class="font-semibold text-slate-900">{{ languageStore.t('earn_recognition', 'Earn Recognition') }}</h3>
          </div>
          <p class="text-sm text-slate-700">{{ languageStore.t('earn_recognition_desc', 'Your reviews may be featured and your contributions help our restaurant improve service.') }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg border border-amber-200 p-6">
          <div class="flex items-center gap-3 mb-3">
            <Lightbulb :size="24" class="text-amber-600" />
            <h3 class="font-semibold text-slate-900">{{ languageStore.t('make_an_impact', 'Make an Impact') }}</h3>
          </div>
          <p class="text-sm text-slate-700">{{ languageStore.t('make_an_impact_desc', 'Your feedback directly influences menu changes and service improvements at our restaurant.') }}</p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  MessageSquare,
  UtensilsCrossed,
  BarChart3,
  Star,
  Trophy,
  Lightbulb,
} from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()
</script>

<style scoped>
</style>
