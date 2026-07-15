<script setup lang="ts">
interface Props {
  roomNumber: string
  qrToken: string
}

withDefaults(defineProps<Props>(), {
  roomNumber: '',
  qrToken: '',
})
</script>

<template>
  <section class="py-6 px-6">
    <div class="max-w-4xl mx-auto">
      <div class="rounded-2xl bg-gradient-to-r from-teal-50 to-blue-50 border border-teal-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-teal-600 text-white flex items-center justify-center text-xl">
            🔐
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-slate-900">Room Information</h3>
            <p class="text-sm text-slate-600">
              <span v-if="roomNumber">Room {{ roomNumber }}</span>
              <span v-if="roomNumber && qrToken"> • </span>
              <span v-if="qrToken" class="text-xs text-slate-500">Secure QR Session</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
