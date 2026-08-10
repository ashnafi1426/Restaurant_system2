<template>
  <div class="p-8 space-y-6">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
      Global Page Loader Examples
    </h1>

    <!-- Example 1: Basic Usage -->
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">Example 1: Basic Usage</h2>
      <button @click="example1" class="btn-primary">
        Show Loader for 3 seconds
      </button>
    </div>

    <!-- Example 2: With Custom Text -->
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">Example 2: Custom Loading Text</h2>
      <button @click="example2" class="btn-primary">
        Load with Custom Message
      </button>
    </div>

    <!-- Example 3: With Progress -->
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">Example 3: With Progress Bar</h2>
      <button @click="example3" class="btn-primary">
        Simulate Upload (with progress)
      </button>
    </div>

    <!-- Example 4: Using withLoader -->
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">Example 4: Wrap Async Function</h2>
      <button @click="example4" class="btn-primary">
        Load Data with Auto Hide
      </button>
    </div>

    <!-- Example 5: Multi-step Process -->
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">Example 5: Multi-step Process</h2>
      <button @click="example5" class="btn-primary">
        Run Multi-step Import
      </button>
    </div>

    <!-- Example 6: Simulating Form Submission -->
    <div class="card">
      <h2 class="text-xl font-semibold mb-4">Example 6: Form Submission</h2>
      <form @submit.prevent="example6" class="space-y-4">
        <input 
          v-model="formData.name"
          type="text" 
          placeholder="Enter name"
          class="input"
        />
        <button type="submit" class="btn-primary">
          Submit Form
        </button>
      </form>
    </div>

    <!-- Results Display -->
    <div v-if="result" class="card bg-green-50 dark:bg-green-900/20">
      <h3 class="font-semibold text-green-800 dark:text-green-200">Result:</h3>
      <p class="text-green-700 dark:text-green-300">{{ result }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { usePageLoader } from '@/composables/usePageLoader'

const { showLoader, hideLoader, setProgress, setLoadingText, withLoader } = usePageLoader()
const result = ref('')
const formData = ref({ name: '' })

// Example 1: Basic show/hide
const example1 = async () => {
  showLoader()
  await new Promise(resolve => setTimeout(resolve, 3000))
  hideLoader()
  result.value = 'Example 1 completed!'
}

// Example 2: Custom text
const example2 = async () => {
  showLoader('Fetching dashboard data...')
  await new Promise(resolve => setTimeout(resolve, 2000))
  hideLoader()
  result.value = 'Example 2 completed with custom text!'
}

// Example 3: With progress
const example3 = async () => {
  showLoader('Uploading file...')
  
  for (let i = 0; i <= 100; i += 10) {
    setProgress(i)
    setLoadingText(`Uploading... ${i}%`)
    await new Promise(resolve => setTimeout(resolve, 300))
  }
  
  hideLoader()
  result.value = 'Example 3 completed with progress updates!'
}

// Example 4: Using withLoader wrapper
const example4 = async () => {
  const data = await withLoader(
    async () => {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 2000))
      return { message: 'Data loaded successfully!' }
    },
    'Loading data...'
  )
  
  result.value = `Example 4: ${data.message}`
}

// Example 5: Multi-step process
const example5 = async () => {
  showLoader('Starting import...')
  
  try {
    // Step 1
    setProgress(0)
    setLoadingText('Validating data...')
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Step 2
    setProgress(33)
    setLoadingText('Processing records...')
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Step 3
    setProgress(66)
    setLoadingText('Saving to database...')
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Step 4
    setProgress(100)
    setLoadingText('Import complete!')
    await new Promise(resolve => setTimeout(resolve, 500))
    
    result.value = 'Example 5: Multi-step import completed!'
  } finally {
    hideLoader()
  }
}

// Example 6: Form submission
const example6 = async () => {
  if (!formData.value.name) {
    result.value = 'Please enter a name'
    return
  }
  
  await withLoader(
    async () => {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500))
      result.value = `Form submitted for: ${formData.value.name}`
      formData.value.name = ''
    },
    'Submitting form...'
  )
}
</script>

<style scoped>
.card {
  @apply bg-white dark:bg-slate-800 rounded-lg shadow-md p-6;
}

.btn-primary {
  @apply px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg 
         transition-colors duration-200 font-medium;
}

.input {
  @apply w-full px-4 py-2 border border-gray-300 dark:border-gray-600 
         rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white
         focus:ring-2 focus:ring-red-500 focus:border-transparent;
}
</style>
