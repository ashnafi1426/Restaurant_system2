<template>
  <section class="testimonials">
    <div class="testimonial-content">
      <div class="quote-icon">"</div>
      <p class="quote-text">
        "Our stay at Luxe Heritage was absolutely unforgettable. From the impeccable service to the exquisite dining
        experience, every moment was perfection. We can't wait to return."
      </p>
      <p class="quote-author">— Sarah Mitchell, CEO</p>
    </div>

    <div class="testimonial-nav">
      <button
        v-for="(dot, index) in 3"
        :key="index"
        class="nav-dot"
        :class="{ active: index === 0 }"
        @click="selectTestimonial(index)"
      ></button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const currentTestimonial = ref(0)

const selectTestimonial = (index: number) => {
  currentTestimonial.value = index
}
</script>

<style scoped>
.testimonials {
  padding: 6rem 2rem;
  background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
  color: white;
  text-align: center;
}

.testimonial-content {
  max-width: 800px;
  margin: 0 auto 3rem;
}

.quote-icon {
  font-size: 4rem;
  font-weight: 300;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.quote-text {
  font-size: 1.5rem;
  font-weight: 300;
  line-height: 1.8;
  margin-bottom: 1.5rem;
  letter-spacing: 0.5px;
}

.quote-author {
  font-size: 0.95rem;
  font-weight: 400;
  letter-spacing: 1px;
  opacity: 0.9;
}

.testimonial-nav {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.nav-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid white;
  background-color: transparent;
  cursor: pointer;
  transition: all 0.3s ease;
}

.nav-dot.active {
  background-color: white;
}

.nav-dot:hover {
  transform: scale(1.2);
}

@media (max-width: 768px) {
  .testimonials {
    padding: 3rem 1rem;
  }

  .quote-text {
    font-size: 1.2rem;
  }

  .quote-icon {
    font-size: 3rem;
  }
}
</style>
