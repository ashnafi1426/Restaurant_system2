# Responsive Design Patterns - Reference Guide

## Overview
This guide documents the responsive design patterns implemented across the restaurant system Vue components. Use these patterns as a reference when creating new components or updating existing ones.

---

## 📐 Core Responsive Patterns

### Pattern 1: Responsive Table/Card Dual Layout

**Use Case**: Data tables that should display as cards on mobile

```vue
<template>
  <div class="rounded-lg border border-slate-200 bg-white">
    <!-- Desktop Table View (md and up) -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50 border-b">
          <tr>
            <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-3.5 text-left text-xs font-semibold">
              Column
            </th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50">
            <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4">
              {{ item.name }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View (below md) -->
    <div class="md:hidden">
      <div v-for="item in items" :key="item.id" class="border-b p-4 sm:p-5">
        <div class="flex justify-between items-start">
          <h3 class="font-semibold text-sm text-slate-900">{{ item.name }}</h3>
          <span class="badge">{{ item.status }}</span>
        </div>
        <!-- Additional content -->
      </div>
    </div>
  </div>
</template>
```

**Key Points**:
- `hidden md:block` for desktop table
- `md:hidden` for mobile cards
- Consistent spacing within both layouts
- Table uses column hiding strategy
- Mobile shows only essential info

---

### Pattern 2: Responsive Grid Form

**Use Case**: Forms with multiple columns that stack on mobile

```vue
<template>
  <form class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Two Column Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold">
          Field 1 <span class="text-red-500">*</span>
        </label>
        <input
          type="text"
          placeholder="Placeholder text"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold">
          Field 2 <span class="text-red-500">*</span>
        </label>
        <input
          type="text"
          placeholder="Placeholder text"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
    </div>

    <!-- Full Width Field -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold">
        Full Width Field
      </label>
      <textarea
        rows="4"
        placeholder="Placeholder text"
        class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm resize-none"
      />
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3">
      <button type="button" class="px-4 sm:px-6 py-2 sm:py-2.5 border rounded-lg">
        Cancel
      </button>
      <button type="submit" class="px-4 sm:px-6 py-2 sm:py-2.5 bg-blue-600 text-white rounded-lg">
        Save
      </button>
    </div>
  </form>
</template>
```

**Breakpoint Progression**:
- **Mobile**: Single column (grid-cols-1)
- **Tablet**: 2 columns (sm:grid-cols-2)
- **Desktop**: 2 columns with larger gaps

---

### Pattern 3: Responsive Text & Spacing

**Use Case**: Typography that scales with screen size

```vue
<!-- Title -->
<h2 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900">
  Page Title
</h2>

<!-- Subtitle -->
<p class="text-xs sm:text-sm md:text-base text-slate-500 mt-1 sm:mt-2">
  Subtitle or description
</p>

<!-- Section spacing -->
<section class="p-4 sm:p-5 md:p-6 space-y-4 sm:space-y-5 md:space-y-6">
  <!-- Content -->
</section>

<!-- Responsive padding progression -->
<div class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4">
  <!-- Table cell, card content, etc. -->
</div>
```

**Spacing Progression**:
- Horizontal: px-3 → sm:px-4 → md:px-6
- Vertical: py-2 → sm:py-3 → md:py-4
- Gap: gap-2 → sm:gap-3 → md:gap-4

---

### Pattern 4: Responsive Button Groups

**Use Case**: Multiple buttons that adapt to screen size

```vue
<!-- Horizontal on desktop, Vertical on mobile -->
<div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3">
  <button class="flex-1 sm:flex-none px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm border rounded-lg">
    Cancel
  </button>
  <button class="flex-1 sm:flex-none px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm bg-blue-600 text-white rounded-lg">
    Save
  </button>
</div>

<!-- Single Full-Width Button -->
<button class="w-full px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm bg-blue-600 text-white rounded-lg">
  Action
</button>

<!-- Small Icon Button -->
<button class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-lg hover:bg-slate-100">
  ⋮
</button>
```

---

### Pattern 5: Responsive Input Fields

**Use Case**: Input fields that scale appropriately

```vue
<!-- Text Input -->
<input
  type="text"
  placeholder="Placeholder"
  class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
/>

<!-- Input with Prefix (currency, etc.) -->
<div class="relative">
  <span class="absolute left-3 sm:left-3.5 top-1/2 -translate-y-1/2 text-slate-500">₹</span>
  <input
    type="number"
    placeholder="Amount"
    class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 pl-6 sm:pl-7 py-2 sm:py-2.5 text-xs sm:text-sm"
  />
</div>

<!-- Select Dropdown -->
<select class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm bg-white">
  <option>-- Select Option --</option>
  <option>Option 1</option>
  <option>Option 2</option>
</select>

<!-- Textarea -->
<textarea
  rows="4"
  placeholder="Message"
  class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm resize-none"
/>
```

---

### Pattern 6: Responsive Status Badge

**Use Case**: Status indicators that scale with content

```vue
<!-- Active Status -->
<span class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
  <span class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-green-500 mr-1 sm:mr-1.5"></span>
  Active
</span>

<!-- Inactive Status -->
<span class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
  <span class="h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-red-500 mr-1 sm:mr-1.5"></span>
  Inactive
</span>
```

---

### Pattern 7: Responsive Card Layout

**Use Case**: Display cards in a grid that adapts to screen size

```vue
<!-- Responsive Grid Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-5">
  <div v-for="item in items" :key="item.id" class="bg-white rounded-lg p-3 sm:p-4 md:p-5 border hover:shadow-lg transition">
    <h3 class="text-xs sm:text-sm font-semibold text-slate-900 mb-2">{{ item.title }}</h3>
    <p class="text-xs sm:text-sm text-slate-500">{{ item.description }}</p>
    
    <!-- Card footer with action -->
    <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t">
      <button class="text-xs sm:text-sm text-blue-600 hover:text-blue-700">
        View Details →
      </button>
    </div>
  </div>
</div>
```

---

### Pattern 8: Responsive Checkbox / Toggle

**Use Case**: Checkboxes and toggles that are touch-friendly

```vue
<!-- Checkbox with Label -->
<div class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 border border-slate-200 rounded-lg">
  <input
    type="checkbox"
    id="checkbox-id"
    class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 sm:mt-0 accent-blue-600 cursor-pointer"
  />
  <div class="flex-1">
    <label for="checkbox-id" class="text-xs sm:text-sm font-semibold text-slate-900 cursor-pointer">
      Option Label
    </label>
    <p class="text-xs text-slate-500 mt-0.5">Description of this option</p>
  </div>
</div>
```

---

## 🎯 Spacing System Reference

### Horizontal Padding (px)
```
Mobile:  px-3   (12px)
Tablet:  sm:px-4   (16px) / sm:px-3.5 (14px)
Desktop: md:px-6   (24px) / md:px-4 (16px) for components
```

### Vertical Padding (py)
```
Mobile:  py-2   (8px)
Tablet:  sm:py-3   (12px) / sm:py-2.5 (10px)
Desktop: md:py-4   (16px) / md:py-3.5 (14px)
```

### Gap (Spacing between items)
```
Mobile:  gap-2   (8px)
Tablet:  sm:gap-3   (12px)
Desktop: md:gap-4   (16px) / md:gap-5 (20px)
```

### Margin/Spacing
```
Mobile:  mb-1 (4px), mb-2 (8px), mt-1 (4px)
Tablet:  sm:mb-2, sm:mb-3 (12px)
Desktop: md:mb-4, md:mb-6 (24px)
```

---

## 📝 Typography System Reference

### Font Sizes
```
Mobile:  text-xs (12px), text-sm (14px)
Tablet:  sm:text-sm, sm:text-base (16px)
Desktop: md:text-base, md:text-lg (18px), md:text-xl (20px)
```

### Font Weights
```
Regular:   font-normal (400)
Medium:    font-medium (500)
Semibold:  font-semibold (600)
Bold:      font-bold (700)
```

### Line Height
```
Tight:   leading-5 (20px)
Normal:  leading-6 (24px)
Relaxed: leading-7 (28px)
Loose:   leading-8 (32px)
```

---

## 🎨 Color & Border Reference

### Background Colors
```
Primary:   bg-white
Secondary: bg-slate-50
Hover:     hover:bg-slate-100
Active:    bg-blue-600, bg-green-600

Alerts:
  Error:   bg-red-50
  Success: bg-green-50
  Warning: bg-yellow-50
  Info:    bg-blue-50
```

### Border & Rings
```
Default:      border border-slate-300
Error:        border-red-500 ring-2 ring-red-200
Focus:        focus:ring-2 focus:ring-blue-500 focus:border-transparent
Hover:        hover:border-slate-400
```

---

## ⚡ Quick Copy-Paste Examples

### Responsive Container
```vue
<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-6 md:py-8">
  <!-- Content -->
</div>
```

### Responsive Form Section
```vue
<div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5 md:p-6">
  <!-- Form content -->
</div>
```

### Responsive Two-Column Layout
```vue
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
  <!-- Left column -->
  <!-- Right column -->
</div>
```

### Responsive Section with Title
```vue
<section class="p-4 sm:p-5 md:p-6">
  <h2 class="text-lg sm:text-xl md:text-2xl font-bold mb-3 sm:mb-4 md:mb-6">
    Section Title
  </h2>
  <!-- Content -->
</section>
```

---

## ✅ Mobile Testing Checklist

When implementing responsive design:

- [ ] **Mobile (320px)**: Single column, readable text, proper spacing
- [ ] **Mobile Landscape (568px)**: 2 columns if needed, still readable
- [ ] **Tablet (768px)**: Full tablet layout, tables can appear
- [ ] **Desktop (1024px+)**: Full features, all columns visible
- [ ] **Touch Targets**: Minimum 44x44px for buttons
- [ ] **Text Legibility**: Readable without zoom on all sizes
- [ ] **Images**: Properly scaled, no overflow
- [ ] **Buttons**: Easily tappable on mobile

---

## 🚀 Best Practices

1. **Start Mobile-First**
   - Write base styles for mobile
   - Add breakpoints for larger screens

2. **Consistent Spacing**
   - Use the spacing progression: 3 → 4 → 6
   - Apply to padding, margins, and gaps

3. **Typography Hierarchy**
   - Scale text proportionally
   - Use consistent font weights
   - Maintain readability

4. **Touch-Friendly**
   - 44x44px minimum for interactive elements
   - Adequate spacing between touch targets
   - Larger buttons on mobile

5. **Test Thoroughly**
   - Test on actual devices
   - Test with real content
   - Test touch interactions
   - Test at all breakpoints

---

## 📱 Breakpoint Quick Reference

```
xs:  < 640px   (Mobile)
sm:  640px+    (Mobile landscape / Small tablet)
md:  768px+    (Tablet)
lg:  1024px+   (Desktop)
xl:  1280px+   (Large desktop)
2xl: 1536px+   (Extra large)
```

---

**Last Updated**: July 10, 2026
**Status**: Reference guide for responsive patterns
