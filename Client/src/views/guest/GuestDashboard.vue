<template>
  <div class="guest-dashboard min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ languageStore.t('welcome', 'Welcome') }}, {{ currentUser?.first_name }}
        </h1>
        <p class="text-gray-600">
          {{
            languageStore.t(
              'manage_reviews_and_account',
              'Manage your reviews, orders, and account settings',
            )
          }}
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">{{ languageStore.t('my_reviews', 'My Reviews') }}</p>
              <p class="text-3xl font-bold text-purple-600">{{ myReviewsCount }}</p>
            </div>
            <div class="text-4xl">⭐</div>
          </div>
          <button
            @click="$router.push('/reviews')"
            class="mt-4 w-full text-sm text-purple-600 hover:text-purple-700 font-semibold cursor-pointer"
          >
            {{ languageStore.t('view_all', 'View All') }}
          </button>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">
                {{ languageStore.t('pending_approval', 'Pending Approval') }}
              </p>
              <p class="text-3xl font-bold text-yellow-600">{{ pendingReviewsCount }}</p>
            </div>
            <div class="text-4xl">⏳</div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">
                {{ languageStore.t('notifications', 'Notifications') }}
              </p>
              <p class="text-3xl font-bold text-blue-600">{{ unreadNotifications }}</p>
            </div>
            <div class="text-4xl">🔔</div>
          </div>
          <button
            @click="showNotifications = !showNotifications"
            class="mt-4 w-full text-sm text-blue-600 hover:text-blue-700 font-semibold cursor-pointer"
          >
            {{
              showNotifications ? languageStore.t('hide', 'Hide') : languageStore.t('view', 'View')
            }}
          </button>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm">
                {{ languageStore.t('avg_rating_given', 'Avg Rating Given') }}
              </p>
              <div class="flex items-center gap-1 mt-1">
                <span class="text-2xl font-bold text-green-600">{{ avgRating }}</span>
                <span class="text-lg text-yellow-400">★</span>
              </div>
            </div>
            <div class="text-4xl"></div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <div v-if="showNotifications" class="bg-white rounded-lg shadow">
            <div class="border-b border-gray-200 p-6">
              <h2 class="text-xl font-bold text-gray-900">
                {{ languageStore.t('review_notifications', 'Review Notifications') }}
              </h2>
            </div>

            <div v-if="notificationsLoading" class="p-6">
              <div class="space-y-4">
                <div v-for="i in 3" :key="i" class="animate-pulse h-16 bg-gray-200 rounded"></div>
              </div>
            </div>

            <div v-else-if="notifications.length === 0" class="p-6 text-center">
              <p class="text-gray-600">
                {{ languageStore.t('no_notifications_yet', 'No notifications yet') }}
              </p>
            </div>

            <div v-else class="divide-y divide-gray-200">
              <div
                v-for="notification in notifications.slice(0, 5)"
                :key="notification.id"
                class="p-6 hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-start gap-4">
                  <div class="text-2xl flex-shrink-0">
                    <span v-if="notification.notification_type === 'new_review'">📝</span>
                    <span v-else-if="notification.notification_type === 'review_approved'"></span>
                    <span v-else>⚠️</span>
                  </div>

                  <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900">
                      <span v-if="notification.notification_type === 'new_review'">{{
                        languageStore.t('new_review_submitted', 'New Review Submitted')
                      }}</span>
                      <span v-else-if="notification.notification_type === 'review_approved'">{{
                        languageStore.t('review_approved', 'Review Approved')
                      }}</span>
                      <span v-else>{{
                        languageStore.t('review_rejected', 'Review Rejected')
                      }}</span>
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
                    <p class="text-xs text-gray-500 mt-2">
                      {{ formatTime(notification.created_at) }}
                    </p>
                  </div>

                  <div v-if="!notification.is_read" class="flex-shrink-0">
                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="notifications.length > 5" class="p-4 border-t border-gray-200 text-center">
              <button
                @click="$router.push('/notifications')"
                class="text-blue-600 hover:text-blue-700 font-semibold text-sm cursor-pointer"
              >
                {{ languageStore.t('view_all_notifications', 'View All Notifications') }}
              </button>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-bold text-gray-900">
                {{ languageStore.t('items_available_to_review', 'Items Available to Review') }}
              </h2>
              <span class="text-sm text-gray-600">({{ eligibleItems.length }})</span>
            </div>

            <div v-if="eligibleItemsLoading" class="space-y-3">
              <div v-for="i in 3" :key="i" class="animate-pulse h-20 bg-gray-200 rounded"></div>
            </div>

            <div v-else-if="eligibleItems.length === 0" class="text-center py-8">
              <p class="text-gray-600">
                {{ languageStore.t('no_items_review_yet', 'No items available for review yet') }}
              </p>
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="item in eligibleItems.slice(0, 3)"
                :key="item.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
              >
                <div>
                  <h3 class="font-semibold text-gray-900">{{ item.name }}</h3>
                  <p class="text-sm text-gray-600">
                    {{ languageStore.t('order_number', 'Order') }} #{{ item.order_number }}
                  </p>
                </div>
                <button
                  @click="reviewItem(item)"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition-colors text-sm cursor-pointer"
                >
                  {{ languageStore.t('review', 'Review') }}
                </button>
              </div>

              <div v-if="eligibleItems.length > 3" class="pt-2">
                <button
                  @click="$router.push('/reviews')"
                  class="w-full text-blue-600 hover:text-blue-700 font-semibold text-sm py-2 cursor-pointer"
                >
                  {{ languageStore.t('view_all_items', 'View All Items') }} ({{
                    eligibleItems.length
                  }})
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              {{ languageStore.t('quick_actions', 'Quick Actions') }}
            </h3>
            <div class="space-y-3">
              <button
                @click="$router.push('/reviews')"
                class="w-full flex items-center gap-2 p-3 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold rounded-lg transition-colors text-left cursor-pointer"
              >
                <span>📝</span>
                <span>{{ languageStore.t('my_reviews', 'My Reviews') }}</span>
              </button>

              <button
                @click="$router.push('/menu-items')"
                class="w-full flex items-center gap-2 p-3 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold rounded-lg transition-colors text-left cursor-pointer"
              >
                <span>🍽️</span>
                <span>{{ languageStore.t('browse_menu', 'Browse Menu') }}</span>
              </button>

              <button
                @click="$router.push('/orders')"
                class="w-full flex items-center gap-2 p-3 bg-green-50 hover:bg-green-100 text-green-700 font-semibold rounded-lg transition-colors text-left cursor-pointer"
              >
                <span>📦</span>
                <span>{{ languageStore.t('my_orders', 'My Orders') }}</span>
              </button>

              <button
                @click="$router.push('/account')"
                class="w-full flex items-center gap-2 p-3 bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold rounded-lg transition-colors text-left cursor-pointer"
              >
                <span>⚙️</span>
                <span>{{ languageStore.t('account_settings', 'Account Settings') }}</span>
              </button>
            </div>
          </div>

          <div
            class="bg-gradient-to-br from-purple-50 to-blue-50 rounded-lg shadow p-6 border border-purple-200"
          >
            <h3 class="text-lg font-bold text-gray-900 mb-3">
              {{ languageStore.t('about_reviews', 'About Reviews') }}
            </h3>
            <ul class="space-y-2 text-sm text-gray-700">
              <li class="flex items-start gap-2">
                <span class="text-lg text-emerald-600">✓</span>
                <span>{{
                  languageStore.t(
                    'help_guests_feedback',
                    'Help other guests with your honest feedback',
                  )
                }}</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-lg text-emerald-600">✓</span>
                <span>{{
                  languageStore.t('earn_badges_reviews', 'Earn badges for helpful reviews')
                }}</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-lg text-emerald-600">✓</span>
                <span>{{
                  languageStore.t('receive_responses_mgmt', 'Receive responses from management')
                }}</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-lg text-emerald-600">✓</span>
                <span>{{
                  languageStore.t('reviews_moderated_quality', 'Reviews are moderated for quality')
                }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useReviewStore } from '@/stores/reviewStore'
import { useLanguageStore } from '@/stores/language'
import reviewService from '@/services/reviewService'
import type { EligibleMenuItem, ReviewNotification } from '@/types/review'

const router = useRouter()
const authStore = useAuthStore()
const reviewStore = useReviewStore()
const languageStore = useLanguageStore()

const currentUser = authStore.user
const showNotifications = ref(false)
const notifications = ref<ReviewNotification[]>([])
const notificationsLoading = ref(false)
const eligibleItems = ref<EligibleMenuItem[]>([])
const eligibleItemsLoading = ref(false)
const myReviewsCount = ref(0)
const pendingReviewsCount = ref(0)
const unreadNotifications = ref(0)
const avgRating = ref('0.0')

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(hours / 24)

  if (hours < 1) return 'Just now'
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const reviewItem = (item: EligibleMenuItem) => {
  // Navigate to review submission with item pre-filled
  router.push({
    name: 'reviews.index',
    params: { itemId: item.id },
  })
}

const loadNotifications = async () => {
  notificationsLoading.value = true
  try {
    const data = await reviewService.getReviewNotifications(1, 10)
    notifications.value = (data.data as ReviewNotification[]) || []
    unreadNotifications.value = await reviewService.getUnreadNotificationCount()
  } catch (error) {
    console.error('Failed to load notifications:', error)
  } finally {
    notificationsLoading.value = false
  }
}

const loadEligibleItems = async () => {
  if (!currentUser?.id) return

  eligibleItemsLoading.value = true
  try {
    eligibleItems.value = await reviewService.getEligibleItems(currentUser.id)
  } catch (error) {
    console.error('Failed to load eligible items:', error)
  } finally {
    eligibleItemsLoading.value = false
  }
}

const loadStats = async () => {
  try {
    // This would be calculated from the reviews in the store
    // For now, using placeholder logic
    myReviewsCount.value = reviewStore.guestReviews.length
    pendingReviewsCount.value = reviewStore.guestReviews.filter(
      (r) => r.status === 'pending',
    ).length

    if (reviewStore.guestReviews.length > 0) {
      const totalRating = reviewStore.guestReviews.reduce((sum, r) => sum + r.rating, 0)
      avgRating.value = (totalRating / reviewStore.guestReviews.length).toFixed(1)
    }
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

onMounted(() => {
  loadNotifications()
  loadEligibleItems()
  loadStats()
})
</script>

<style scoped>
.guest-dashboard {
  background-color: #f3f4f6;
}
</style>
