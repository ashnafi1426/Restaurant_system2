<template>
  <div class="flex items-center justify-center p-4">
    <div class="relative w-12 h-12">
      <!-- Outer spinning circle -->
      <div class="absolute inset-0 rounded-full border-4 border-slate-200"></div>
      <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-500 border-r-blue-500 animate-spin"></div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
