# Theme Toggle - Quick Start Guide

## 🎨 What You Get

A **Light/Dark Mode Toggle** button in the navbar that:
- Shows **☀️ Sun icon** in light mode
- Shows **🌙 Moon icon** in dark mode
- Saves preference to browser storage
- Works on **ALL pages** automatically
- Respects system preference on first load

---

## 🚀 How to Use

### For End Users
1. Click the **Sun/Moon button** in the top navbar
2. Theme switches instantly
3. Your preference is saved automatically
4. It will be the same next time you visit

### For Developers
**Apply dark mode to a new component:**

```vue
<!-- Light mode styles | Dark mode styles -->
<div class="bg-white dark:bg-slate-900
           text-slate-900 dark:text-slate-100
           border border-slate-200 dark:border-slate-700">
  <!-- Content -->
</div>
```

**Import and use theme store:**
```typescript
import { useThemeStore } from '@/stores/themeStore'
const theme = useThemeStore()

// Access current theme
console.log(theme.isDarkMode)  // true or false

// Toggle theme
theme.toggleTheme()

// Set specific theme
theme.setDarkMode(true)
```

---

## 📁 Files Changed

| File | Change |
|------|--------|
| `src/stores/themeStore.ts` | ✨ NEW - Theme state management |
| `src/components/dashboard/Navbar.vue` | 🔄 UPDATED - Added theme toggle button + dark styles |
| `src/main.ts` | 🔄 UPDATED - Initialize theme on startup |

---

## 🎯 Button Location

In the navbar (top of every page):

```
← Dashboard        Search ... [🔔] [⭐] [⚙️] [👤] →
                           Notifications
                           │
                           Theme Toggle (NEW!)
```

---

## ✨ Theme Colors

### Light Mode
- Background: White (`bg-white`)
- Text: Dark (`text-slate-900`)
- Borders: Light gray (`border-slate-200`)

### Dark Mode
- Background: Dark slate (`dark:bg-slate-900`)
- Text: Light (`dark:text-slate-100`)
- Borders: Dark gray (`dark:border-slate-700`)

---

## 🧪 Test It

1. **Click the button** - Theme should switch instantly
2. **Refresh page** - Theme should persist
3. **Clear data → Refresh** - Should use system preference
4. **Check all pages** - Theme toggle appears everywhere

---

## 🔍 Browser DevTools

**Check localStorage:**
```
DevTools → Application → Storage → LocalStorage
Look for: app-theme = "light" or "dark"
```

**Check HTML element:**
```
DevTools → Inspector → <html> element
When dark mode: class="dark"
When light mode: no class
```

**Check console logs:**
```
[themeStore] 🎨 Theme loaded from localStorage: dark
[Navbar] 🎨 Theme toggle clicked
[themeStore] 🔄 Theme toggled: light
```

---

## 🎨 Color Reference

Common dark mode class pairs:

```
Light          →  Dark
bg-white       →  dark:bg-slate-900
bg-slate-50    →  dark:bg-slate-800
text-slate-900 →  dark:text-slate-100
text-slate-600 →  dark:text-slate-400
border-slate-200 → dark:border-slate-700
hover:bg-slate-100 → dark:hover:bg-slate-800
```

---

## 💾 Persistence

**First Visit:**
- Checks browser's system preference
- Saves to localStorage
- Applies theme

**Subsequent Visits:**
- Loads from localStorage
- Applies saved preference
- No flash or flicker

**Manual Change:**
- Click button
- Theme toggles immediately
- New preference saved
- Persists across sessions

---

## 🚨 Troubleshooting

| Issue | Solution |
|-------|----------|
| Button doesn't work | Check console for errors, reload page |
| Theme doesn't persist | Check localStorage in DevTools |
| Colors look wrong | Verify dark: classes are used |
| Flash of wrong theme | Already fixed (theme loads before mount) |
| Icons don't change | Check Sun/Moon components imported |

---

## 📋 Implementation Summary

```
┌─────────────────────────────────────┐
│    App Loads                        │
├─────────────────────────────────────┤
│ 1. Create Pinia Store              │
│ 2. Initialize themeStore           │
│ 3. Check localStorage OR           │
│    system preference               │
│ 4. Apply theme (add/remove "dark") │
│ 5. Mount app                       │
└─────────────────────────────────────┘
              │
              ↓
    ┌─────────────────────┐
    │   User sees page    │
    │  with correct theme │
    │  (no flash)         │
    └─────────────────────┘
              │
              ↓
    ┌─────────────────────┐
    │ User clicks button  │
    └─────────────────────┘
              │
              ↓
    ┌─────────────────────┐
    │ toggleTheme()       │
    │ isDarkMode flips    │
    │ Watcher triggers    │
    │ applyTheme()        │
    └─────────────────────┘
              │
              ↓
    ┌─────────────────────┐
    │ Add/remove "dark"   │
    │ class on <html>     │
    │ Save to localStorage│
    │ Theme updates live  │
    └─────────────────────┘
```

---

## ✅ Checklist

- [x] Theme store created
- [x] Theme toggle button added to navbar
- [x] Dark mode styles applied
- [x] Theme initialization on startup
- [x] localStorage persistence
- [x] System preference support
- [x] Console logging for debugging
- [x] Works on all pages
- [x] Icons switch (Sun ↔ Moon)
- [x] Transitions are smooth

---

**Status: ✅ READY TO USE**

Just click the button and enjoy dark mode! 🌙
