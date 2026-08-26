<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ShieldCheck, Mail, Phone, MapPin, ExternalLink, LogIn } from 'lucide-vue-next'
</script>

<template>
  <footer class="bg-slate-900 border-t border-slate-800 text-slate-300 font-sans transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 space-y-12">
      <!-- Top Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <!-- Col 1: Brand Info -->
        <div class="space-y-4">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-lg shadow-md shadow-amber-500/20">
              H
            </div>
            <div>
              <span class="text-base font-black text-white tracking-wider uppercase block">Grand Horizon</span>
              <span class="text-[9px] font-extrabold text-amber-400 uppercase tracking-widest block">Luxury Hotel & Resort</span>
            </div>
          </div>

          <p class="text-xs text-slate-400 leading-relaxed font-medium">
            Experience world-class luxury, authentic Ethiopian hospitality, and unforgettable stays in our fine suites and dining venues.
          </p>

          <div class="space-y-2 text-xs font-medium text-slate-400">
            <div class="flex items-center gap-2">
              <MapPin class="w-4 h-4 text-amber-400 flex-shrink-0" />
              <span>Bolé Road, Addis Ababa, Ethiopia</span>
            </div>
            <div class="flex items-center gap-2">
              <Phone class="w-4 h-4 text-amber-400 flex-shrink-0" />
              <span>+251 11 555 1234 / +251 91 100 2200</span>
            </div>
            <div class="flex items-center gap-2">
              <Mail class="w-4 h-4 text-amber-400 flex-shrink-0" />
              <span>concierge@grandhorizon.com</span>
            </div>
          </div>
        </div>

        <!-- Col 2: Quick Links -->
        <div class="space-y-4">
          <h3 class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            Navigation
          </h3>
          <ul class="space-y-2.5 text-xs font-bold text-slate-400">
            <li>
              <RouterLink to="/" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Home</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/rooms" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Luxury Rooms & Suites</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/gallery" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Photo Gallery</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/about" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>About Our Resort</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Contact & Location</span>
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Col 3: Services -->
        <div class="space-y-4">
          <h3 class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            Services & Amenities
          </h3>
          <ul class="space-y-2.5 text-xs font-bold text-slate-400">
            <li>
              <RouterLink to="/rooms" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Online Room Reservation</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Fine Dining Reservations</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Conferences & Wedding Events</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Spa & Wellness Packages</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/contact" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>Airport Shuttle Transfers</span>
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Col 4: System Access -->
        <div class="space-y-4">
          <h3 class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            Staff & System Portal
          </h3>
          <p class="text-xs text-slate-400 leading-relaxed font-medium">
            Authorized staff, receptionists, managers, and administrators can log in to access property management tools.
          </p>

          <RouterLink
            to="/login"
            class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20 transition cursor-pointer"
          >
            <LogIn class="w-4 h-4" />
            <span>Staff Portal Login</span>
          </RouterLink>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-medium text-slate-500">
        <p>&copy; {{ new Date().getFullYear() }} Grand Horizon Hotel. All rights reserved.</p>
        <div class="flex items-center gap-4 text-slate-400">
          <RouterLink to="/contact" class="hover:text-white transition">Privacy Policy</RouterLink>
          <span>•</span>
          <RouterLink to="/contact" class="hover:text-white transition">Terms of Service</RouterLink>
          <span>•</span>
          <RouterLink to="/contact" class="hover:text-white transition">Support</RouterLink>
        </div>
      </div>
    </div>
  </footer>
</template>
