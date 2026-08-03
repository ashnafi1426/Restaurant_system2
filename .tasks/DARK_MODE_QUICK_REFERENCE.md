# 🌙 Dark Mode - Quick Reference Guide

---

## 🚀 Quick Start for Developers

### How to Enable Dark Mode in a New Component

1. **Import theme store:**
```typescript
import { useThemeStore } from '@/stores/themeStore'
const themeStore = useThemeStore()
```

2. **Check dark mode status:**
```typescript
if (themeStore.isDarkMode) {
  // Dark mode is ON
}
```

3. **Add dark mode classes to your component:**
```vue
<!-- Background -->
<div class="bg-white dark:bg-slate-800">

<!-- Text -->
<p class="text-slate-900 dark:text-white">

<!-- Borders -->
<div class="border border-slate-200 dark:border-slate-700">

<!-- Cards -->
<div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
```

---

## 🎨 Color Palette

### Light Mode
| Element | Class | Color |
|---------|-------|-------|
| Background | `bg-white` | White |
| Primary Text | `text-slate-900` | Dark gray |
| Secondary Text | `text-slate-600` | Medium gray |
| Borders | `border-slate-200` | Light gray |
| Cards | `bg-white` | White |

### Dark Mode
| Element | Class | Color |
|---------|-------|-------|
| Background | `dark:bg-slate-950` | Very dark gray |
| Primary Text | `dark:text-white` | White |
| Secondary Text | `dark:text-slate-400` | Light gray |
| Borders | `dark:border-slate-700` | Dark gray |
| Cards | `dark:bg-slate-800` | Dark gray |

---

## 📦 Loading Spinners

### Standard Spinner
```vue
<div class="relative w-16 h-16">
  <div class="absolute inset-0 rounded-full border-4 border-white dark:border-white"></div>
  <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-yellow-300 dark:border-t-yellow-200 border-r-yellow-300 dark:border-r-yellow-200 animate-spin"></div>
</div>
```

### Full Page Loading
```vue
<FullPageLoader loadingText="Loading data..." />
```

### Skeleton with Spinner
```vue
<SkeletonLoaders type="spinner-with-text" loadingText="Please wait..." />
```

---

## 🎯 Common Patterns

### Card Component
```vue
<div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm p-6">
  <h2 class="text-lg font-bold text-slate-900 dark:text-white">Title</h2>
  <p class="text-sm text-slate-600 dark:text-slate-400">Description</p>
</div>
```

### Table Component
```vue
<table class="w-full bg-white dark:bg-slate-800">
  <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
    <tr>
      <th class="text-left text-slate-700 dark:text-slate-300">Header</th>
    </tr>
  </thead>
  <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
      <td class="text-slate-900 dark:text-white">Data</td>
    </tr>
  </tbody>
</table>
```

### Button Component
```vue
<button class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-800 transition">
  Click me
</button>
```

---

## 🔄 Theme Toggle

### In Component
```vue
<script setup>
import { useThemeStore } from '@/stores/themeStore'
const themeStore = useThemeStore()
</script>

<button @click="themeStore.toggleTheme()">
  Toggle Theme
</button>
```

### In Store
```typescript
// From anywhere in the app
const themeStore = useThemeStore()
themeStore.toggleTheme()     // Switch theme
themeStore.setDarkMode(true) // Set specific mode
```

---

## 🧪 Testing Checklist

- [ ] Component displays correctly in light mode
- [ ] Component displays correctly in dark mode
- [ ] Text is readable in both modes
- [ ] Borders are visible in both modes
- [ ] No white backgrounds visible in dark mode
- [ ] Hover states work in both modes
- [ ] Icons are visible in both modes
- [ ] Buttons are accessible in both modes

---

## 📋 Files to Remember

| File | Purpose |
|------|---------|
| `src/stores/themeStore.ts` | Theme state management |
| `src/Layouts/DashboardLayout.vue` | Theme initialization |
| `src/components/dashboard/Navbar.vue` | Theme toggle button |
| `src/components/waiter/FullPageLoader.vue` | Full page loading spinner |
| `src/components/waiter/LoadingSpinner.vue` | Inline loading spinner |
| `src/components/waiter/SkeletonLoaders.vue` | Skeleton loaders with spinner |
| `tailwind.config.js` | Tailwind configuration |

---

## ⚠️ Common Mistakes to Avoid

### ❌ Missing `dark:` prefix
```vue
<!-- WRONG -->
<div class="bg-slate-900">Content</div>

<!-- CORRECT -->
<div class="bg-white dark:bg-slate-900">Content</div>
```

### ❌ Using wrong color in dark mode
```vue
<!-- WRONG - text too dark -->
<p class="text-slate-900 dark:text-slate-900">Content</p>

<!-- CORRECT - text bright in dark mode -->
<p class="text-slate-900 dark:text-white">Content</p>
```

### ❌ Forgetting dark mode on borders
```vue
<!-- WRONG - border barely visible -->
<div class="border border-slate-200">Content</div>

<!-- CORRECT - border visible in both modes -->
<div class="border border-slate-200 dark:border-slate-700">Content</div>
```

### ❌ Using bright colors in light mode
```vue
<!-- WRONG - too bright in light mode -->
<p class="dark:text-white">Content</p>

<!-- CORRECT - readable in both modes -->
<p class="text-slate-900 dark:text-white">Content</p>
```

---

## 🌟 Dark Mode Classes Reference

### Backgrounds
- Light: `bg-white`, `bg-slate-50`
- Dark: `dark:bg-slate-800`, `dark:bg-slate-900`, `dark:bg-slate-950`

### Text
- Light: `text-slate-900`, `text-slate-700`, `text-slate-600`
- Dark: `dark:text-white`, `dark:text-slate-300`, `dark:text-slate-400`

### Borders
- Light: `border-slate-200`, `border-slate-300`
- Dark: `dark:border-slate-700`, `dark:border-slate-600`

### Shadows
- Light: `shadow-sm`, `shadow-md`
- Dark: `dark:shadow-sm` (shadows appear darker in dark mode)

### Hover States
- Light: `hover:bg-slate-50`
- Dark: `dark:hover:bg-slate-700`

---

## 🔍 Debugging Dark Mode Issues

### Problem: Component not switching theme
**Solution:** Import and initialize theme store in main.ts

### Problem: Text not visible in dark mode
**Solution:** Add `dark:text-white` or `dark:text-slate-300` class

### Problem: Spinners barely visible
**Solution:** Ensure using `border-white dark:border-white` and `border-t-yellow-300 dark:border-t-yellow-200`

### Problem: Changes not persisting
**Solution:** Check localStorage key is `app-theme` and values are `'light'` or `'dark'`

### Problem: White flash on page load
**Solution:** Ensure theme store initialized in main.ts BEFORE `app.mount()`

---

## 📚 Resources

- **Tailwind Dark Mode:** https://tailwindcss.com/docs/dark-mode
- **Pinia Store:** https://pinia.vuejs.org/
- **Vue 3 Composition API:** https://vuejs.org/guide/extras/composition-api-faq.html
- **localStorage API:** https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage

---

## 💡 Pro Tips

1. **Use CSS variables:** Consider extracting color values to CSS variables for easier maintenance
2. **Create color utilities:** Create custom Tailwind color palette in `theme.extend`
3. **Test with DevTools:** Use browser DevTools to manually toggle dark class for testing
4. **Performance:** Use Pinia watchers instead of computed properties for theme changes
5. **Accessibility:** Always verify contrast ratios meet WCAG AA standards

---

## 🎉 You're Ready!

Start adding dark mode to new components using this quick reference. The system is fully set up and ready to go!
