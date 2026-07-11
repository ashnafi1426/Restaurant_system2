<template>
  <section class="curated-comfort">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Curated Comfort</h2>
      </div>

      <div class="amenities-grid">
        <div class="amenity-item" v-for="amenity in amenities" :key="amenity.id">
          <div class="amenity-icon">
            <i :class="amenity.icon"></i>
          </div>
          <h3 class="amenity-title">{{ amenity.title }}</h3>
          <p class="amenity-desc">{{ amenity.description }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const amenities = ref([
  {
    id: 1,
    icon: 'fas fa-spa',
    title: 'Wellness Spa',
    description: 'Rejuvenate with world-class spa treatments and wellness services.',
  },
  {
    id: 2,
    icon: 'fas fa-utensils',
    title: 'Fine Dining',
    description: 'Exquisite culinary experiences crafted by renowned chefs.',
  },
  {
    id: 3,
    icon: 'fas fa-swimming-pool',
    title: 'Olympic Pool',
    description: 'State-of-the-art swimming facilities for ultimate relaxation.',
  },
  {
    id: 4,
    icon: 'fas fa-dumbbell',
    title: 'Fitness Center',
    description: 'Modern gym equipment and personal training services available.',
  },
  {
    id: 5,
    icon: 'fas fa-wifi',
    title: 'High-Speed WiFi',
    description: 'Complimentary internet throughout the entire property.',
  },
  {
    id: 6,
    icon: 'fas fa-concierge-bell',
    title: '24/7 Concierge',
    description: 'Dedicated support for all your needs, anytime.',
  },
])
</script>

<style scoped>
.curated-comfort {
  padding: 5rem 2rem;
  background-color: white;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-header {
  text-align: center;
  margin-bottom: 4rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 300;
  letter-spacing: 1px;
  color: #1a1a1a;
}

.amenities-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 3rem;
}

.amenity-item {
  text-align: center;
  padding: 2rem 1rem;
  transition: all 0.3s ease;
}

.amenity-item:hover {
  transform: translateY(-5px);
}

.amenity-icon {
  font-size: 3rem;
  color: #1a1a1a;
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
}

.amenity-item:hover .amenity-icon {
  color: #999;
  transform: scale(1.1);
}

.amenity-title {
  font-size: 1.2rem;
  font-weight: 400;
  color: #1a1a1a;
  margin-bottom: 0.8rem;
  letter-spacing: 0.5px;
}

.amenity-desc {
  font-size: 0.9rem;
  color: #666;
  line-height: 1.6;
  font-weight: 300;
}

@media (max-width: 768px) {
  .curated-comfort {
    padding: 3rem 1rem;
  }

  .section-title {
    font-size: 1.8rem;
  }

  .amenities-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
  }

  .amenity-icon {
    font-size: 2rem;
  }
}
</style>
