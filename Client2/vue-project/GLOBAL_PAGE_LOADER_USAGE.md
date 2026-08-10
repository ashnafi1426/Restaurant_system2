# Global Page Loader Implementation

## Overview
A comprehensive full-page loading system with your hotel logo, animated spinner, and progress bar that displays during page navigation and async operations.

## Features
- ✅ Automatic loading on route navigation
- ✅ Beautiful animated hotel logo with spinning ring
- ✅ Progress bar with smooth animations
- ✅ Customizable loading text
- ✅ Dark mode support
- ✅ Initial HTML loader (before Vue loads)
- ✅ Vue-based loader (during app runtime)
- ✅ Easy-to-use composable API

## Files Created

### 1. Components
- `src/components/loading/GlobalPageLoader.vue` - Main loader component
- Updated `src/App.vue` - Added GlobalPageLoader

### 2. Store
- `src/stores/pageLoaderStore.ts` - Pinia store for loader state

### 3. Composable
- `src/composables/usePageLoader.ts` - Easy-to-use hook for manual control

### 4. Router Integration
- Updated `src/router/index.ts` - Automatic loading on navigation

### 5. App Initialization
- Updated `src/main.ts` - Initial app loading
- Updated `index.html` - Pre-Vue HTML loader

## How It Works

### 1. Initial Page Load
When the user first visits your site, an HTML/CSS loader is shown (in `index.html`) before Vue even loads. This ensures users always see something beautiful while your app initializes.

### 2. Route Navigation
Every time a user navigates between pages, the loader automatically appears and disappears thanks to Vue Router guards.

### 3. Manual Control
You can manually show/hide the loader for async operations using the composable.

## Usage Examples

### Example 1: Automatic (Already Working!)
The loader automatically appears on every route navigation. No code needed!

```typescript
// Just navigate normally - loader shows automatically
router.push('/manager/dashboard')
```

### Example 2: Manual Control in Components

```vue
<script setup lang="ts">
import { usePageLoader } from '@/composables/usePageLoader'

const { showLoader, hideLoader, setProgress, setLoadingText } = usePageLoader()

const loadData = async () => {
  // Show loader with custom text
  showLoader('Loading Dashboard Data...')
  
  try {
    // Your async operation
    await fetchDashboardData()
  } finally {
    hideLoader()
  }
}
</script>
```

### Example 3: With Progress Updates

```vue
<script setup lang="ts">
import { usePageLoader } from '@/composables/usePageLoader'

const { showLoader, hideLoader, setProgress, setLoadingText } = usePageLoader()

const uploadFile = async (file: File) => {
  showLoader('Uploading file...')
  
  try {
    // Simulate progress
    for (let i = 0; i <= 100; i += 10) {
      setProgress(i)
      setLoadingText(`Uploading... ${i}%`)
      await new Promise(resolve => setTimeout(resolve, 200))
    }
  } finally {
    hideLoader()
  }
}
</script>
```

### Example 4: Wrap Any Async Operation

```vue
<script setup lang="ts">
import { usePageLoader } from '@/composables/usePageLoader'

const { withLoader } = usePageLoader()

const saveSettings = async () => {
  // Automatically shows/hides loader
  await withLoader(
    async () => {
      await api.updateSettings(settings)
    },
    'Saving settings...'
  )
}
</script>
```

### Example 5: In Store Actions

```typescript
// stores/managerStore.ts
import { usePageLoader } from '@/composables/usePageLoader'

export const useManagerStore = defineStore('manager', () => {
  const { withLoader } = usePageLoader()
  
  const fetchDashboard = async () => {
    return await withLoader(
      async () => {
        const response = await api.get('/manager/dashboard')
        dashboardData.value = response.data
        return response.data
      },
      'Loading Dashboard...'
    )
  }
  
  return { fetchDashboard }
})
```

### Example 6: Multiple Steps with Progress

```typescript
const importData = async () => {
  showLoader('Starting import...')
  
  try {
    setProgress(0)
    setLoadingText('Validating data...')
    await validateData()
    
    setProgress(33)
    setLoadingText('Processing records...')
    await processRecords()
    
    setProgress(66)
    setLoadingText('Saving to database...')
    await saveToDatabase()
    
    setProgress(100)
    setLoadingText('Import complete!')
    await new Promise(resolve => setTimeout(resolve, 500))
  } finally {
    hideLoader()
  }
}
```

## API Reference

### Composable: `usePageLoader()`

#### Methods

**`showLoader(text?: string)`**
- Shows the loader with optional custom text
- Default: "LOADING HERITAGE..."

**`hideLoader()`**
- Hides the loader with smooth transition

**`setProgress(value: number)`**
- Updates progress bar (0-100)

**`setLoadingText(text: string)`**
- Changes the loading message

**`withLoader(operation, text?)`**
- Wraps an async operation with automatic loader show/hide
- Returns the operation result

### Store: `usePageLoaderStore()`

#### State
- `isLoading: boolean` - Current loading state
- `loadingText: string` - Current loading message
- `progress: number` - Current progress (0-100)

#### Actions
- `showLoader(text?)` - Show loader
- `hideLoader()` - Hide loader
- `updateProgress(value)` - Update progress
- `updateText(text)` - Update text

## Customization

### Change Loading Text
```typescript
// In router/index.ts
router.beforeEach((to) => {
  const loaderStore = usePageLoaderStore()
  
  // Custom text based on route
  if (to.path.includes('manager')) {
    loaderStore.showLoader('Loading Manager Dashboard...')
  } else if (to.path.includes('waiter')) {
    loaderStore.showLoader('Loading Waiter Panel...')
  } else {
    loaderStore.showLoader('LOADING HERITAGE...')
  }
  
  // ... rest of guard
})
```

### Change Colors
Edit `src/components/loading/GlobalPageLoader.vue`:
```vue
<!-- Change red-600 to your preferred color -->
<div class="border-t-red-600">  <!-- Change this -->
```

### Change Animation Speed
Edit the styles in GlobalPageLoader.vue:
```css
/* Change spin speed */
.animate-spin {
  animation: spin 1.5s linear infinite; /* Change 1.5s */
}
```

## When to Use Manual Control

Use manual loader control for:
- ✅ Form submissions
- ✅ File uploads
- ✅ Data imports/exports
- ✅ Heavy computations
- ✅ API calls that take time
- ✅ Multi-step processes

Don't use for:
- ❌ Route navigation (automatic)
- ❌ Quick operations (<300ms)
- ❌ Background polling

## Best Practices

1. **Always use try/finally** to ensure hideLoader() is called
   ```typescript
   try {
     showLoader()
     await operation()
   } finally {
     hideLoader()
   }
   ```

2. **Use withLoader() for simple cases** - cleaner code
   ```typescript
   await withLoader(() => api.call(), 'Loading...')
   ```

3. **Provide meaningful text** to keep users informed
   ```typescript
   showLoader('Processing your order...')
   ```

4. **Update progress for long operations** to show activity
   ```typescript
   setProgress(50)
   ```

## Testing

To test the loader:

```typescript
// In browser console
import { usePageLoaderStore } from '@/stores/pageLoaderStore'
const loader = usePageLoaderStore()

// Show loader
loader.showLoader('Test message')

// Update progress
loader.updateProgress(50)

// Hide loader
loader.hideLoader()
```

## Troubleshooting

### Loader doesn't show
- Check if `GlobalPageLoader` is in `App.vue`
- Verify store is imported in router

### Loader doesn't hide
- Check for errors in your async operation
- Ensure `hideLoader()` is in finally block

### Progress bar doesn't update
- Call `setProgress()` with values 0-100
- Check store state in Vue DevTools

## Dark Mode Support

The loader automatically adapts to dark mode:
- Light backgrounds become dark (`bg-slate-950`)
- Colors adjust for better contrast
- All animations remain smooth

## Performance

The loader is highly optimized:
- Uses CSS transforms (GPU accelerated)
- Lazy-loaded animations
- Minimal DOM manipulation
- No external dependencies

## Browser Support

Works on all modern browsers:
- ✅ Chrome/Edge (80+)
- ✅ Firefox (75+)
- ✅ Safari (13+)
- ✅ Mobile browsers

## Summary

You now have a professional, fully-functional page loading system that:
1. **Automatically** shows during navigation
2. Can be **manually controlled** for async operations
3. Supports **progress tracking**
4. Has **dark mode** support
5. Features your **hotel branding**

Just use `usePageLoader()` in any component when you need manual control!
