<template>
  <nav class="navbar">
    <div class="navbar-container">
      <!-- Logo -->
      <div class="logo">
        <span class="logo-text">LUXE HERITAGE</span>
      </div>

      <!-- Menu Items -->
      <ul class="nav-menu" :class="{ active: menuOpen }">
        <li class="nav-item">
          <a href="#home" class="nav-link">HOME</a>
        </li>
        <li class="nav-item">
          <a href="#rooms" class="nav-link">ROOMS</a>
        </li>
        <li class="nav-item">
          <a href="#about" class="nav-link">ABOUT US</a>
        </li>
        <li class="nav-item">
          <a href="#contact" class="nav-link">CONTACT</a>
        </li>
      </ul>

      <!-- Theme Toggle & Book Button -->
      <div class="button-group">
        <!-- Theme Toggle Button -->
        <button class="theme-btn" @click="handleThemeToggle" :title="theme.isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'">
          <span v-if="!theme.isDarkMode" class="theme-icon">☀️</span>
          <span v-else class="theme-icon">🌙</span>
        </button>

        <!-- Book Now Button -->
        <button class="book-btn">BOOK NOW</button>
      </div>

      <!-- Hamburger Menu -->
      <div class="hamburger" :class="{ active: menuOpen }" @click="toggleMenu">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useThemeStore } from '../../stores/themeStore'

const menuOpen = ref(false)
const router = useRouter()
const theme = useThemeStore()

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
}

const navigateToRooms = () => {
  router.push('/roomsPage')
  menuOpen.value = false
}

const handleThemeToggle = () => {
  console.log('[LandingNavbar] 🎨 Theme toggle clicked')
  theme.toggleTheme()
  console.log('[LandingNavbar] 🎨 New theme:', theme.isDarkMode ? 'dark' : 'light')
}
</script>

<style scoped>
.navbar {
  background-color: rgba(245, 245, 245, 0.98);
  padding: 0.75rem 0;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

/* Dark mode */
:global(.dark) .navbar {
  background-color: rgba(15, 23, 42, 0.98);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Small mobile (sm:640px) */
@media (min-width: 640px) {
  .navbar {
    padding: 1rem 0;
  }
}

/* Tablet (md:768px) */
@media (min-width: 768px) {
  .navbar {
    padding: 1.25rem 0;
  }
}

/* Desktop (lg:1024px) */
@media (min-width: 1024px) {
  .navbar {
    padding: 1.5rem 0;
  }
}

.navbar-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

@media (min-width: 640px) {
  .navbar-container {
    padding: 0 1.5rem;
  }
}

@media (min-width: 768px) {
  .navbar-container {
    padding: 0 2rem;
  }
}

@media (min-width: 1024px) {
  .navbar-container {
    padding: 0 2.5rem;
  }
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

@media (min-width: 640px) {
  .logo {
    gap: 0.5rem;
  }
}

@media (min-width: 768px) {
  .logo {
    gap: 0.75rem;
  }
}

.logo-text {
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 1px;
  color: #1a1a1a;
  transition: color 0.3s ease;
}

:global(.dark) .logo-text {
  color: #f1f5f9;
}

@media (min-width: 640px) {
  .logo-text {
    font-size: 1.1rem;
    letter-spacing: 1.5px;
  }
}

@media (min-width: 768px) {
  .logo-text {
    font-size: 1.3rem;
    letter-spacing: 2px;
  }
}

@media (min-width: 1024px) {
  .logo-text {
    font-size: 1.5rem;
    letter-spacing: 2.5px;
  }
}

.nav-menu {
  display: none;
  list-style: none;
  gap: 1.5rem;
  margin: 0;
  padding: 0;
}

@media (min-width: 768px) {
  .nav-menu {
    display: flex;
    gap: 2rem;
  }
}

@media (min-width: 1024px) {
  .nav-menu {
    gap: 2.5rem;
  }
}

.nav-item {
  position: relative;
}

.nav-link {
  text-decoration: none;
  color: #333;
  font-size: 0.8rem;
  font-weight: 500;
  letter-spacing: 0.5px;
  transition: color 0.3s ease;
  display: block;
}

:global(.dark) .nav-link {
  color: #cbd5e1;
}

@media (min-width: 768px) {
  .nav-link {
    font-size: 0.9rem;
    letter-spacing: 1px;
  }
}

@media (min-width: 1024px) {
  .nav-link {
    font-size: 0.95rem;
    letter-spacing: 1.2px;
  }
}

.nav-link:hover {
  color: #999;
}

:global(.dark) .nav-link:hover {
  color: #e2e8f0;
}

.button-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

@media (min-width: 768px) {
  .button-group {
    gap: 1rem;
  }
}

.theme-btn {
  background-color: transparent;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  padding: 0.5rem 0.7rem;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
}

:global(.dark) .theme-btn {
  border-color: #475569;
  color: #cbd5e1;
  background-color: rgba(30, 41, 59, 0.5);
}

.theme-btn:hover {
  background-color: #f3f4f6;
  border-color: #d1d5db;
}

:global(.dark) .theme-btn:hover {
  background-color: rgba(51, 65, 85, 0.5);
  border-color: #64748b;
}

.theme-icon {
  font-size: 1.2rem;
}

.book-btn {
  background-color: #1a1a1a;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 2px;
  display: none;
}

:global(.dark) .book-btn {
  background-color: #e5e7eb;
  color: #1f2937;
}

@media (min-width: 640px) {
  .book-btn {
    padding: 0.7rem 1.5rem;
    font-size: 0.8rem;
    display: block;
  }
}

@media (min-width: 768px) {
  .book-btn {
    padding: 0.8rem 1.8rem;
    font-size: 0.85rem;
    letter-spacing: 1px;
  }
}

@media (min-width: 1024px) {
  .book-btn {
    padding: 0.9rem 2rem;
    font-size: 0.9rem;
    letter-spacing: 1.2px;
  }
}

.book-btn:hover {
  background-color: #333;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transform: translateY(-2px);
}

:global(.dark) .book-btn:hover {
  background-color: #d1d5db;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.hamburger {
  display: flex;
  flex-direction: column;
  cursor: pointer;
  gap: 5px;
}

@media (min-width: 768px) {
  .hamburger {
    display: none;
  }
}

.hamburger span {
  width: 22px;
  height: 2.5px;
  background-color: #1a1a1a;
  transition: all 0.3s ease;
}

:global(.dark) .hamburger span {
  background-color: #e5e7eb;
}

@media (min-width: 640px) {
  .hamburger span {
    width: 25px;
    height: 3px;
  }
}

.hamburger.active span:nth-child(1) {
  transform: rotate(45deg) translate(10px, 10px);
}

.hamburger.active span:nth-child(2) {
  opacity: 0;
}

.hamburger.active span:nth-child(3) {
  transform: rotate(-45deg) translate(8px, -8px);
}

/* Mobile menu styles */
@media (max-width: 767px) {
  .nav-menu {
    position: fixed;
    left: -100%;
    top: 60px;
    flex-direction: column;
    background-color: white;
    width: 100%;
    text-align: center;
    transition: 0.3s;
    padding: 1.5rem 0;
    gap: 0;
    display: flex;
  }

  :global(.dark) .nav-menu {
    background-color: #1e293b;
  }

  @media (min-width: 640px) {
    .nav-menu {
      top: 70px;
      padding: 2rem 0;
    }
  }

  .nav-menu.active {
    left: 0;
  }

  .nav-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
  }

  :global(.dark) .nav-item {
    border-bottom: 1px solid #334155;
  }

  @media (min-width: 640px) {
    .nav-item {
      padding: 1rem 0;
    }
  }
}
</style>
