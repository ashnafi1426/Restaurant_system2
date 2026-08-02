<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
    <router-view />
  </div>
</template>

<style>
html {
  transition: background-color 0.3s ease, color 0.3s ease;
}

html.dark {
  background-color: #020617;
  color: #f1f5f9;
}

html:not(.dark) {
  background-color: #ffffff;
  color: #1f2937;
}

/* Global scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
html.dark ::-webkit-scrollbar-thumb {
  background: #475569;
}
html.dark ::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
