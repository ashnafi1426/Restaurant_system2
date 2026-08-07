# Mobile Responsive Patterns - Quick Reference

## 🎯 Common Responsive Patterns for Vue/Tailwind

### 1. **Stats Cards Grid**
```vue
<!-- Pattern: Progressive grid columns -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
  <div class="bg-white p-4 md:p-6 rounded-lg">
    <p class="text-xs md:text-sm text-gray-600">Label</p>
    <p class="text-2xl md:text-3xl font-bold">{{ value }}</p>
  </div>
</div>
```

**When to use:** Dashboard stats, metrics cards, feature highlights

---

### 2. **Two-Column Layout**
```vue
<!-- Pattern: Stack on mobile, side-by-side on desktop -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
  <div>Left content</div>
  <div>Right content</div>
</div>
```

**When to use:** Forms, content sections, split views

---

### 3. **Responsive Table → Cards**
```vue
<!-- Desktop: Table -->
<div class="hidden md:block overflow-x-auto">
  <table class="w-full">
    <thead>...</thead>
    <tbody>...</tbody>
  </table>
</div>

<!-- Mobile: Cards -->
<div class="md:hidden space-y-3">
  <div v-for="item in items" :key="item.id" class="bg-white rounded-lg p-4 border">
    <div class="flex justify-between items-start mb-3">
      <div class="font-semibold">{{ item.name }}</div>
      <span class="badge">{{ item.status }}</span>
    </div>
    <div class="grid grid-cols-2 gap-3 text-sm">
      <div>
        <div class="text-gray-500 text-xs">Label 1</div>
        <div class="font-medium">{{ item.value1 }}</div>
      </div>
      <div>
        <div class="text-gray-500 text-xs">Label 2</div>
        <div class="font-medium">{{ item.value2 }}</div>
      </div>
    </div>
    <div class="flex gap-2 mt-3 pt-3 border-t">
      <button class="btn-sm flex-1">Action 1</button>
      <button class="btn-sm flex-1">Action 2</button>
    </div>
  </div>
</div>
```

**When to use:** Data tables, lists with actions

---

### 4. **Responsive Modal/Dialog**
```vue
<div class="fixed inset-0 z-50 overflow-y-auto bg-black/50" @click.self="close">
  <!-- Center container -->
  <div class="min-h-screen px-4 flex items-center justify-center">
    <!-- Modal -->
    <div class="relative bg-white rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="p-4 md:p-6 border-b flex items-center justify-between">
        <h2 class="text-lg md:text-xl font-bold">Modal Title</h2>
        <button @click="close" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100">
          <X :size="20" />
        </button>
      </div>
      
      <!-- Body (scrollable) -->
      <div class="p-4 md:p-6 overflow-y-auto flex-1">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
          <!-- Form fields -->
        </div>
      </div>
      
      <!-- Footer (sticky) -->
      <div class="p-4 md:p-6 border-t bg-gray-50 flex flex-col-reverse sm:flex-row gap-3 justify-end">
        <button class="w-full sm:w-auto px-4 py-2">Cancel</button>
        <button class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white">Submit</button>
      </div>
    </div>
  </div>
</div>
```

**When to use:** Forms, confirmations, detailed views

---

### 5. **Responsive Buttons**
```vue
<!-- Full width on mobile, auto on desktop -->
<button class="w-full sm:w-auto px-4 py-2">Button</button>

<!-- Button group -->
<div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
  <button class="flex-1 sm:flex-none">Button 1</button>
  <button class="flex-1 sm:flex-none">Button 2</button>
</div>

<!-- Icon + text responsive -->
<button class="flex items-center justify-center gap-2 px-3 md:px-4 py-2">
  <Icon class="w-4 h-4 md:w-5 md:h-5" />
  <span class="text-sm md:text-base">Label</span>
</button>
```

**When to use:** CTAs, form submissions, action groups

---

### 6. **Responsive Header**
```vue
<header class="px-4 md:px-6 py-3 md:py-4 border-b">
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <!-- Title -->
    <div>
      <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">Page Title</h1>
      <p class="text-xs sm:text-sm text-gray-600 mt-1">Subtitle text</p>
    </div>
    
    <!-- Actions -->
    <div class="flex gap-2 w-full sm:w-auto">
      <button class="flex-1 sm:flex-none">Action</button>
    </div>
  </div>
</header>
```

**When to use:** Page headers, section headers

---

### 7. **Responsive Spacing**
```vue
<!-- Padding -->
<div class="p-3 sm:p-4 md:p-6 lg:p-8">

<!-- Margin -->
<div class="mb-4 md:mb-6 lg:mb-8">

<!-- Gap -->
<div class="flex gap-2 sm:gap-3 md:gap-4">

<!-- Grid gap -->
<div class="grid grid-cols-2 gap-3 md:gap-4 lg:gap-6">
```

---

### 8. **Responsive Typography**
```vue
<!-- Headings -->
<h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold">
<h2 class="text-xl sm:text-2xl md:text-3xl font-bold">
<h3 class="text-lg sm:text-xl md:text-2xl font-semibold">

<!-- Body text -->
<p class="text-sm md:text-base">
<p class="text-xs md:text-sm">

<!-- Line height -->
<p class="leading-relaxed md:leading-loose">
```

---

### 9. **Responsive Container**
```vue
<!-- Standard content container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <!-- Content -->
</div>

<!-- Form container -->
<div class="max-w-4xl mx-auto px-4 md:px-6">
  <!-- Form -->
</div>

<!-- Narrow content -->
<div class="max-w-2xl mx-auto px-4">
  <!-- Content -->
</div>
```

---

### 10. **Responsive Navigation**
```vue
<!-- Tabs/Pills Navigation -->
<div class="flex overflow-x-auto pb-2 -mb-2 gap-2">
  <button 
    v-for="tab in tabs" 
    :key="tab.id"
    class="flex-shrink-0 px-3 md:px-4 py-2 rounded-lg text-sm md:text-base whitespace-nowrap"
  >
    {{ tab.label }}
  </button>
</div>

<!-- Breadcrumbs -->
<nav class="flex items-center space-x-2 text-sm overflow-x-auto">
  <span class="whitespace-nowrap">Home</span>
  <span>/</span>
  <span class="whitespace-nowrap">Category</span>
  <span>/</span>
  <span class="whitespace-nowrap truncate">Current Page</span>
</nav>
```

---

## 📏 Common Breakpoint Combinations

```css
/* Stack on mobile, 2 cols on tablet, 3 on desktop */
grid-cols-1 md:grid-cols-2 lg:grid-cols-3

/* Stack on mobile, 2 cols on small, 4 on desktop */
grid-cols-1 sm:grid-cols-2 lg:grid-cols-4

/* Even distribution */
grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5

/* Dashboard stats common pattern */
grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6

/* Sidebar + content */
grid-cols-1 lg:grid-cols-[240px_1fr]  /* Fixed sidebar */
grid-cols-1 lg:grid-cols-[1fr_3fr]    /* Proportional */
```

---

## 🎨 Responsive Design Utilities

### Hide/Show Elements
```vue
<!-- Hide on mobile -->
<div class="hidden md:block">Desktop only</div>

<!-- Show on mobile only -->
<div class="md:hidden">Mobile only</div>

<!-- Show on tablet and up -->
<div class="hidden lg:block">Tablet+ only</div>

<!-- Complex visibility -->
<div class="block sm:hidden lg:block">Mobile and Desktop, not tablet</div>
```

### Flex Direction
```vue
<!-- Stack on mobile, row on desktop -->
<div class="flex flex-col sm:flex-row gap-3">

<!-- Reverse order on mobile -->
<div class="flex flex-col-reverse sm:flex-row">
```

### Text Alignment
```vue
<div class="text-center sm:text-left">
<div class="text-left md:text-right">
```

### Overflow Handling
```vue
<!-- Horizontal scroll on mobile -->
<div class="overflow-x-auto md:overflow-x-visible">

<!-- Scroll container -->
<div class="max-h-96 overflow-y-auto">

<!-- Truncate long text -->
<p class="truncate md:line-clamp-2">
```

---

## ⚡ Performance Tips

1. **Use CSS instead of JS for responsiveness**
   - Tailwind breakpoints are CSS-based (performant)
   - Avoid JavaScript window.innerWidth checks for layout

2. **Lazy load heavy components**
   ```vue
   <component :is="isMobile ? 'MobileView' : 'DesktopView'" />
   ```

3. **Optimize images for mobile**
   ```vue
   <picture>
     <source media="(max-width: 640px)" srcset="image-mobile.jpg">
     <source media="(max-width: 1024px)" srcset="image-tablet.jpg">
     <img src="image-desktop.jpg" alt="...">
   </picture>
   ```

4. **Use loading states for mobile**
   - Mobile networks are slower
   - Show spinners and skeleton screens

---

## ✅ Mobile Responsiveness Checklist

### Every Component Should Have:
- [ ] Touch targets minimum 44x44px
- [ ] Adequate padding (minimum 1rem on mobile)
- [ ] Readable text sizes (minimum 16px body)
- [ ] No horizontal scroll (unless intentional)
- [ ] Responsive breakpoints tested
- [ ] Works in portrait and landscape
- [ ] Fast loading on 3G
- [ ] Keyboard accessible
- [ ] Works without JavaScript (progressive enhancement)

### Test At These Widths:
- [ ] 375px (iPhone SE)
- [ ] 390px (iPhone 12/13/14)
- [ ] 768px (iPad portrait)
- [ ] 1024px (iPad landscape)
- [ ] 1440px (Common desktop)

---

## 🔧 Debugging Tips

### Tailwind DevTools
```bash
# Install Tailwind CSS IntelliSense extension in VS Code
# Shows class names and their compiled CSS on hover
```

### Browser DevTools
```
Chrome: F12 → Toggle device toolbar (Ctrl+Shift+M)
- Test different devices
- Throttle network to simulate 3G
- Use responsive mode to test custom widths
```

### Tailwind Arbitrary Values
```vue
<!-- When standard breakpoints don't work -->
<div class="w-full lg:w-[450px]">
<div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))]">
```

---

## 📚 Resources

- [Tailwind Breakpoints](https://tailwindcss.com/docs/responsive-design)
- [Tailwind Grid](https://tailwindcss.com/docs/grid-template-columns)
- [Tailwind Flexbox](https://tailwindcss.com/docs/flex)
- [Mobile First Design](https://www.uxpin.com/studio/blog/mobile-first-design/)
- [Touch Target Sizes](https://www.w3.org/WAI/WCAG21/Understanding/target-size.html)

---

**Last Updated:** August 7, 2026
**Project:** Restaurant Management System
