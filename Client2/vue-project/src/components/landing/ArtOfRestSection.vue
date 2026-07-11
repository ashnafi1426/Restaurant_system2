<template>
  <section class="art-of-rest" id="rooms">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">The Art of Rest</h2>
        <p class="section-subtitle">
          Indulge in the finest amenities & immaculate attention to detail.
        </p>
      </div>

      <div class="room-cards">
        <div class="room-card" v-for="room in rooms" :key="room.id">
          <div class="room-image">
            <img :src="room.image" :alt="room.title" />
          </div>
          <div class="room-info">
            <h3 class="room-title">{{ room.title }}</h3>
            <p class="room-description">{{ room.description }}</p>
            <a href="#" class="room-link">LEARN MORE →</a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const rooms = ref([
  {
    id: 1,
    title: 'Classic Suites',
    description: 'Elegantly designed rooms with modern amenities and stunning city views.',
    image:
      'https://images.unsplash.com/photo-1631049307038-da0ec493d30d?w=500&h=400&fit=crop',
  },
  {
    id: 2,
    title: 'Deluxe Rooms',
    description: 'Spacious accommodations with premium furnishings and exclusive services.',
    image:
      'https://images.unsplash.com/photo-1578683078519-cf0a61e95cdb?w=500&h=400&fit=crop',
  },
  {
    id: 3,
    title: 'Presidential Suite',
    description:
      'Our most luxurious offering with personalized concierge and private amenities.',
    image:
      'https://images.unsplash.com/photo-1566665556112-652023ec61e4?w=500&h=400&fit=crop',
  },
])
</script>

<style scoped>
.art-of-rest {
  padding: 5rem 2rem;
  background-color: #fafaf8;
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
  margin-bottom: 1rem;
}

.section-subtitle {
  font-size: 1rem;
  color: #666;
  font-weight: 300;
}

.room-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.room-card {
  background-color: white;
  border-radius: 4px;
  overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  cursor: pointer;
}

.room-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.room-image {
  width: 100%;
  height: 250px;
  overflow: hidden;
}

.room-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.room-card:hover .room-image img {
  transform: scale(1.05);
}

.room-info {
  padding: 2rem;
}

.room-title {
  font-size: 1.3rem;
  font-weight: 400;
  color: #1a1a1a;
  margin-bottom: 0.8rem;
  letter-spacing: 0.5px;
}

.room-description {
  font-size: 0.95rem;
  color: #666;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  font-weight: 300;
}

.room-link {
  text-decoration: none;
  color: #1a1a1a;
  font-weight: 500;
  font-size: 0.85rem;
  letter-spacing: 1px;
  transition: all 0.3s ease;
  display: inline-block;
}

.room-link:hover {
  color: #999;
  transform: translateX(5px);
}

@media (max-width: 768px) {
  .art-of-rest {
    padding: 3rem 1rem;
  }

  .section-title {
    font-size: 1.8rem;
  }

  .room-cards {
    grid-template-columns: 1fr;
  }
}
</style>
