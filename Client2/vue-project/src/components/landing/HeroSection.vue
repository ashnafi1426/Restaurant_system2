<template>
  <section class="hero" id="home">
    <div class="hero-background">
      <img
        src="https://images.unsplash.com/photo-1631049307038-da0ec493d30d?w=1600&h=900&fit=crop"
        alt="Luxury Hotel Lobby"
        class="hero-image"
      />
      <div class="hero-overlay"></div>
    </div>

    <div class="hero-content">
      <p class="hero-subtitle">SINCE 1924</p>
      <h1 class="hero-title">Luxury Hotel Experience</h1>
      <p class="hero-description">
        Experience the height of heritage and modern sophistication in our meticulously curated suites. Book your perfect
        stay today.
      </p>

      <div class="hero-buttons">
        <button class="btn btn-primary">SEARCH ROOMS</button>
        <button class="btn btn-secondary">EXPLORE GALLERY</button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts"></script>

<style scoped>
.hero {
  height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.hero-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.hero-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.3);
}

.hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
  max-width: 800px;
  padding: 2rem;
}

.hero-subtitle {
  font-size: 0.95rem;
  letter-spacing: 3px;
  font-weight: 400;
  margin-bottom: 1rem;
  opacity: 0.9;
}

.hero-title {
  font-size: 4rem;
  font-weight: 300;
  letter-spacing: 1px;
  margin-bottom: 1.5rem;
  line-height: 1.2;
}

.hero-description {
  font-size: 1.1rem;
  line-height: 1.6;
  margin-bottom: 2.5rem;
  opacity: 0.95;
  font-weight: 300;
}

.hero-buttons {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  padding: 1rem 2.5rem;
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 1.5px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  border-radius: 2px;
}

.btn-primary {
  background-color: white;
  color: #1a1a1a;
}

.btn-primary:hover {
  background-color: #f0f0f0;
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
  background-color: transparent;
  color: white;
  border: 2px solid white;
}

.btn-secondary:hover {
  background-color: white;
  color: #1a1a1a;
  transform: translateY(-2px);
}

@media (max-width: 768px) {
  .hero {
    height: 80vh;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-subtitle {
    font-size: 0.85rem;
  }

  .hero-description {
    font-size: 0.95rem;
  }

  .hero-buttons {
    flex-direction: column;
    gap: 1rem;
  }

  .btn {
    width: 100%;
  }
}
</style>
