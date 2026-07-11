<template>
  <section class="grandeur-gallery">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Experience the Grandeur</h2>
      </div>

      <div class="gallery-grid">
        <div class="gallery-item item-1" v-for="(image, index) in galleryImages" :key="index">
          <img :src="image.src" :alt="image.alt" />
          <div class="overlay">
            <p class="overlay-text">{{ image.title }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const galleryImages = ref([
  {
    src: 'https://images.unsplash.com/photo-1631049307038-da0ec493d30d?w=400&h=400&fit=crop',
    alt: 'Luxury Lobby',
    title: 'Grand Lobby',
  },
  {
    src: 'https://images.unsplash.com/photo-1578683078519-cf0a61e95cdb?w=400&h=400&fit=crop',
    alt: 'Modern Room',
    title: 'Guest Room',
  },
  {
    src: 'https://images.unsplash.com/photo-1566665556112-652023ec61e4?w=400&h=400&fit=crop',
    alt: 'Suite Interior',
    title: 'Presidential Suite',
  },
  {
    src: 'https://images.unsplash.com/photo-1554080221-cbf40fbb9823?w=400&h=400&fit=crop',
    alt: 'Restaurant',
    title: 'Fine Dining',
  },
])
</script>

<style scoped>
.grandeur-gallery {
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
}

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.gallery-item {
  position: relative;
  height: 350px;
  overflow: hidden;
  cursor: pointer;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.gallery-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.gallery-item:hover img {
  transform: scale(1.08);
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  transition: background 0.3s ease;
  padding: 2rem;
}

.gallery-item:hover .overlay {
  background: rgba(0, 0, 0, 0.4);
}

.overlay-text {
  color: white;
  font-size: 1.2rem;
  font-weight: 400;
  letter-spacing: 1px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.gallery-item:hover .overlay-text {
  opacity: 1;
}

@media (max-width: 768px) {
  .grandeur-gallery {
    padding: 3rem 1rem;
  }

  .section-title {
    font-size: 1.8rem;
  }

  .gallery-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .gallery-item {
    height: 250px;
  }
}
</style>
