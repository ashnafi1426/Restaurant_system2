<template>
  <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-12 text-center">
    <h2 class="text-3xl font-bold text-white mb-4">Ready to book?</h2>
    <p class="text-blue-100 mb-8 text-lg">
      Choose your perfect room and secure your stay with our hassle-free booking process
    </p>
    <button
      @click="scrollToRooms"
      class="px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition"
    >
      Browse Rooms
    </button>
  </div>
</template>

<script setup lang="ts">
function scrollToRooms() {
  const roomsSection = document.getElementById('rooms-section')
  if (roomsSection) {
    roomsSection.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>
