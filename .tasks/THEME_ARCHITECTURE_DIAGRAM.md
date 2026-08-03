# Theme Toggle Architecture & Data Flow Diagrams

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Vue Application                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │              main.ts (App Entry)                    │    │
│  │  ┌──────────────────────────────────────────────┐  │    │
│  │  │ 1. Create Vue App                            │  │    │
│  │  │ 2. Setup Pinia Store                         │  │    │
│  │  │ 3. Initialize themeStore.initializeTheme()  │  │    │
│  │  │ 4. Mount to #app                            │  │    │
│  │  └──────────────────────────────────────────────┘  │    │
│  └────────────────────────────────────────────────────┘    │
│                          ↓                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │            DashboardLayout (Global)                │    │
│  │                                                    │    │
│  │  ┌──────────────────────────────────────────────┐ │    │
│  │  │          Navbar Component                    │ │    │
│  │  │  ┌────────────────────────────────────────┐ │ │    │
│  │  │  │ Theme Toggle Button (Sun/Moon)         │ │ │    │
│  │  │  │ @click="handleThemeToggle"             │ │ │    │
│  │  │  └────────────────────────────────────────┘ │ │    │
│  │  │         ↓                                   │ │    │
│  │  │  theme.toggleTheme()                       │ │    │
│  │  └──────────────────────────────────────────────┘ │    │
│  │                                                    │    │
│  │  ┌──────────────────────────────────────────────┐ │    │
│  │  │       Main Content (Page-specific)          │ │    │
│  │  │    - Manager Dashboard                     │ │    │
│  │  │    - Waiter Pages                          │ │    │
│  │  │    - Kitchen Pages                         │ │    │
│  │  │    - etc.                                  │ │    │
│  │  └──────────────────────────────────────────────┘ │    │
│  └────────────────────────────────────────────────────┘    │
│                          ↑                                  │
│                          │ (watches isDarkMode)            │
│                          │                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │         themeStore (Pinia)                        │    │
│  │                                                    │    │
│  │  State:                                           │    │
│  │  - isDarkMode: ref<boolean>                      │    │
│  │                                                    │    │
│  │  Methods:                                         │    │
│  │  - initializeTheme()  ← Called on app startup   │    │
│  │  - toggleTheme()      ← Called on button click   │    │
│  │  - setDarkMode()      ← Manual set              │    │
│  │  - applyTheme()       ← Applies to DOM          │    │
│  │                                                    │    │
│  │  Watchers:                                        │    │
│  │  - watch(isDarkMode, () => applyTheme())        │    │
│  └────────────────────────────────────────────────────┘    │
│                          ↓                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │       Browser DOM & CSS                          │    │
│  │                                                    │    │
│  │  ┌──────────────────────────────────────────────┐ │    │
│  │  │ <html class="dark"> (if isDarkMode = true) │ │    │
│  │  │ or                                          │ │    │
│  │  │ <html> (if isDarkMode = false)             │ │    │
│  │  └──────────────────────────────────────────────┘ │    │
│  │                                                    │    │
│  │  Tailwind CSS:                                   │    │
│  │  - .dark .element-dark-class { ... }            │    │
│  │  - All dark: classes activate based on above    │    │
│  └────────────────────────────────────────────────────┘    │
│                          ↓                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │      Persistent Storage                          │    │
│  │                                                    │    │
│  │  localStorage:                                    │    │
│  │  - Key: 'app-theme'                             │    │
│  │  - Value: 'light' or 'dark'                     │    │
│  │                                                    │    │
│  │  Loaded on next visit via                        │    │
│  │  initializeTheme()                              │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Theme Toggle Flow (User Clicks Button)

```
┌─────────────────────┐
│  User Clicks Button │
│  (Sun or Moon Icon) │
└──────────┬──────────┘
           ↓
┌─────────────────────────────────────┐
│  Navbar.vue                         │
│  handleThemeToggle() triggered      │
│  [Navbar] 🎨 Theme toggle clicked   │
└──────────┬──────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  themeStore.toggleTheme()           │
│  isDarkMode.value = !isDarkMode     │
│  [themeStore] 🔄 Theme toggled      │
└──────────┬──────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  Watcher Detects Change             │
│  watch(isDarkMode, () => ...)       │
└──────────┬──────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  applyTheme() Called                │
│                                     │
│  if (isDarkMode) {                 │
│    html.classList.add('dark')      │
│    [themeStore] 🌙 Applying dark   │
│  } else {                          │
│    html.classList.remove('dark')   │
│    [themeStore] ☀️ Applying light  │
│  }                                 │
│                                     │
│  localStorage.setItem(              │
│    'app-theme',                    │
│    isDarkMode ? 'dark' : 'light'   │
│  )                                  │
└──────────┬──────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  CSS Updates Automatically          │
│                                     │
│  <html class="dark">               │
│      ↓                             │
│  All dark: classes activate        │
│      ↓                             │
│  Colors change instantly           │
└──────────┬──────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│  UI Updates Live                    │
│  - Background changes              │
│  - Text colors change              │
│  - Borders update                  │
│  - Icons switch (☀️ ↔ 🌙)          │
│                                     │
│  [Navbar] 🎨 New theme: dark       │
└─────────────────────────────────────┘
```

---

## 📊 App Initialization Flow

```
┌──────────────────────────────────────────────────────────┐
│  Browser loads https://localhost:5173                    │
└──────────────────────────┬───────────────────────────────┘
                           ↓
┌──────────────────────────────────────────────────────────┐
│  main.ts executes                                        │
└──────────────────────────┬───────────────────────────────┘
                           ↓
                ┌──────────────────────┐
                │ const app =          │
                │ createApp(App)       │
                └──────────────────────┘
                           ↓
                ┌──────────────────────┐
                │ const pinia =        │
                │ createPinia()        │
                └──────────────────────┘
                           ↓
                ┌──────────────────────┐
                │ app.use(pinia)       │
                └──────────────────────┘
                           ↓
                ┌──────────────────────┐
                │ app.use(router)      │
                └──────────────────────┘
                           ↓
    ┌──────────────────────────────────────────┐
    │  const themeStore =                      │
    │  useThemeStore()                         │
    │  ⭐ CREATE THEME STORE INSTANCE         │
    └──────────────────────┬───────────────────┘
                           ↓
    ┌──────────────────────────────────────────┐
    │  themeStore.initializeTheme()            │
    │  ⭐ INITIALIZE BEFORE MOUNTING           │
    │                                          │
    │  ├─ Check localStorage('app-theme')     │
    │  │  ├─ Found 'dark'?                   │
    │  │  │  └─ isDarkMode = true            │
    │  │  └─ Found 'light'?                  │
    │  │     └─ isDarkMode = false           │
    │  │                                      │
    │  ├─ Not found in localStorage?         │
    │  │  ├─ Check system preference         │
    │  │  │  └─ prefers-color-scheme: dark  │
    │  │  └─ isDarkMode = matches dark      │
    │  │                                      │
    │  └─ Apply theme immediately            │
    │     └─ Add/remove 'dark' class on HTML │
    │                                          │
    │  [themeStore] 🎨 Theme initialized      │
    └──────────────────────┬───────────────────┘
                           ↓
            ┌──────────────────────────────┐
            │ <html class="dark"> or <>    │
            │ (Theme applied before render)│
            │ ✅ NO FLASH!                 │
            └──────────────────────────────┘
                           ↓
                ┌──────────────────────┐
                │ app.mount('#app')    │
                │ Vue renders with     │
                │ correct theme        │
                └──────────────────────┘
                           ↓
┌──────────────────────────────────────────────────────────┐
│  Page fully loaded with correct theme                    │
│  - Background color correct                             │
│  - Text color correct                                   │
│  - Borders correct                                      │
│  - Icons correct (☀️ or 🌙)                              │
│  - No flicker or flash                                  │
└──────────────────────────────────────────────────────────┘
```

---

## 🎨 CSS Class Application Flow

```
┌─────────────────────────────────────────────────────┐
│  Component with Theme-Aware Styling                 │
├─────────────────────────────────────────────────────┤
│                                                      │
│  <div class="bg-white dark:bg-slate-900           │
│             text-slate-900 dark:text-slate-100   │
│             border border-slate-200              │
│             dark:border-slate-700">              │
│    Content                                         │
│  </div>                                           │
│                                                      │
├─────────────────────────────────────────────────────┤
│  What happens:                                      │
│                                                      │
│  When <html> has NO "dark" class:                 │
│  ├─ bg-white APPLIED         ✓                   │
│  ├─ dark:bg-slate-900 SKIPPED                    │
│  ├─ text-slate-900 APPLIED   ✓                   │
│  ├─ dark:text-slate-100 SKIPPED                  │
│  ├─ border-slate-200 APPLIED ✓                   │
│  └─ dark:border-slate-700 SKIPPED                │
│                                                      │
│  Result: Light mode colors                        │
│  ────────────────────────────────────             │
│  Background: white          ✓                    │
│  Text: dark                 ✓                    │
│  Borders: light gray        ✓                    │
│                                                      │
├─────────────────────────────────────────────────────┤
│                                                      │
│  When <html> HAS "dark" class:                    │
│  ├─ bg-white SKIPPED                             │
│  ├─ dark:bg-slate-900 APPLIED      ✓            │
│  ├─ text-slate-900 SKIPPED                       │
│  ├─ dark:text-slate-100 APPLIED    ✓            │
│  ├─ border-slate-200 SKIPPED                     │
│  └─ dark:border-slate-700 APPLIED  ✓            │
│                                                      │
│  Result: Dark mode colors                         │
│  ───────────────────────────────────             │
│  Background: dark slate (#0f172a) ✓            │
│  Text: light (#f1f5f9)           ✓            │
│  Borders: dark gray (#3f4655)    ✓            │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 💾 localStorage Lifecycle

```
┌──────────────────────────────────┐
│  First Visit (No localStorage)   │
└──────────────────┬───────────────┘
                   ↓
        ┌──────────────────────┐
        │ Check system pref    │
        │ prefers-color:dark?  │
        └──────────┬───────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ Set isDarkMode based on system   │
        │ Apply theme                      │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ Save to localStorage             │
        │ localStorage['app-theme'] =      │
        │   isDarkMode ? 'dark' : 'light' │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ Page fully loaded with theme     │
        └──────────────────────────────────┘


┌──────────────────────────────────┐
│  User Clicks Theme Toggle        │
└──────────────────┬───────────────┘
                   ↓
        ┌──────────────────────┐
        │ toggleTheme()        │
        │ isDarkMode flipped   │
        └──────────┬───────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ watcher calls applyTheme()       │
        │                                  │
        │ localStorage.setItem()           │
        │ localStorage['app-theme'] =      │
        │   isDarkMode ? 'dark' : 'light' │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ Theme changes instantly          │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ localStorage updated             │
        │ Ready for next visit              │
        └──────────────────────────────────┘


┌──────────────────────────────────┐
│  User Refreshes Page             │
└──────────────────┬───────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ initializeTheme() runs           │
        │                                  │
        │ Check localStorage:              │
        │ Found 'app-theme' = 'dark'      │
        │                                  │
        │ isDarkMode = true               │
        │ Apply theme immediately          │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ Page loads with SAME theme       │
        │ No flash or change               │
        │ User preference maintained       │
        └──────────────────────────────────┘
```

---

## 🔧 Component Integration

```
┌────────────────────────────────────┐
│  App.vue (Root)                    │
└────────────────────┬───────────────┘
                     ↓
        ┌────────────────────────┐
        │ DashboardLayout.vue    │
        │ (Global Layout)        │
        └────────────┬───────────┘
                     ↓
        ┌────────────────────────────────────┐
        │         Navbar.vue (Updated)       │
        │  ┌──────────────────────────────┐ │
        │  │ Theme Toggle Button (NEW!)  │ │
        │  │ - Shows Sun or Moon icon    │ │
        │  │ - Calls handleThemeToggle() │ │
        │  │ - Uses themeStore.isDarkMode│ │
        │  │ - Dark:* classes applied    │ │
        │  └──────────────────────────────┘ │
        │                                    │
        │  ┌──────────────────────────────┐ │
        │  │ Page Content (dark: styled)  │ │
        │  │ - Inherits dark classes      │ │
        │  │ - Respects parent <html>     │ │
        │  └──────────────────────────────┘ │
        └────────────────────────────────────┘
                     ↑
                     │
        ┌────────────────────────────┐
        │  themeStore (Pinia)        │
        │  - isDarkMode state        │
        │  - toggleTheme()           │
        │  - applyTheme()            │
        └────────────────────────────┘
```

---

## 📱 Responsive Button Layout

```
Mobile (< 768px)          Tablet (768-1024px)       Desktop (> 1024px)
┌─────────────────┐      ┌──────────────────┐      ┌─────────────────────┐
│ ☰ Dashboard     │      │ ☰ Dashboard      │      │ ☰ Dashboard  Search  │
│   [🔔][☀️][👤]  │      │   [🔔][☀️][⚙️][👤]  │      │   [🔔][☀️][⚙️][👤]    │
└─────────────────┘      └──────────────────┘      └─────────────────────┘
- Sun/Moon icon         - Sun/Moon icon          - Sun/Moon icon
- Small size            - Medium size            - Medium size
- Compact layout        - More spacing           - Full spacing
```

---

## 🎯 State Management Diagram

```
┌─────────────────────────────────────────────────────┐
│              themeStore (Pinia State)               │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  State (reactive data)                      │    │
│  │                                              │    │
│  │  isDarkMode: ref<boolean>                  │    │
│  │  ├─ true → dark mode active                │    │
│  │  └─ false → light mode active              │    │
│  │                                              │    │
│  └────────────────────────────────────────────┘    │
│                       ↑                             │
│        (used by components, auto-updates)          │
│                       ↓                             │
│  ┌────────────────────────────────────────────┐    │
│  │  Getters (computed properties)             │    │
│  │  (none currently, but can add)             │    │
│  └────────────────────────────────────────────┘    │
│                       ↑                             │
│                       │                             │
│  ┌────────────────────────────────────────────┐    │
│  │  Actions (methods)                         │    │
│  │                                             │    │
│  │  initializeTheme() {                       │    │
│  │    ├─ Load from localStorage               │    │
│  │    ├─ Check system preference              │    │
│  │    └─ Apply theme to DOM                  │    │
│  │  }                                          │    │
│  │                                             │    │
│  │  toggleTheme() {                           │    │
│  │    └─ isDarkMode = !isDarkMode            │    │
│  │  }                                          │    │
│  │                                             │    │
│  │  setDarkMode(value) {                      │    │
│  │    └─ isDarkMode = value                  │    │
│  │  }                                          │    │
│  │                                             │    │
│  │  applyTheme() {                            │    │
│  │    ├─ Add/remove 'dark' class              │    │
│  │    └─ Save to localStorage                 │    │
│  │  }                                          │    │
│  │                                             │    │
│  └────────────────────────────────────────────┘    │
│                       ↑                             │
│              (called by watchers/UI)                │
│                                                      │
├─────────────────────────────────────────────────────┤
│  Watchers (react to state changes)                  │
│                                                      │
│  watch(isDarkMode, () => {                         │
│    applyTheme()  ← Auto-called when isDarkMode    │
│  })             changes                            │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## ✨ Summary

The theme system follows a clean architecture:

1. **Store (themeStore)** - Manages all theme logic
2. **Component (Navbar)** - Provides UI to toggle theme
3. **Watchers** - React to state changes automatically
4. **DOM** - Updated via 'dark' class on `<html>`
5. **CSS** - Tailwind dark: classes respond to DOM changes
6. **Storage** - Persists preference to localStorage

All connected through Pinia's reactive state system! 🎨
