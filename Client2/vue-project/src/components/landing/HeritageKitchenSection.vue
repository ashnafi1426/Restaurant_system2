<template>
  <section class="heritage-kitchen" id="about">
    <div class="container">
      <h2 class="section-title">The Heritage Kitchen</h2>

      <div class="kitchen-content">
        <div class="kitchen-text">
          <p class="kitchen-intro">
            Our culinary journey spans generations, blending traditional recipes with contemporary techniques. Each dish
            is a celebration of flavors, prepared with the finest ingredients sourced globally.
          </p>

          <div class="kitchen-highlights">
            <div class="highlight-item">
              <span class="highlight-number">100+</span>
              <p class="highlight-text">Premium Menu Items</p>
            </div>
            <div class="highlight-item">
              <span class="highlight-number">50+</span>
              <p class="highlight-text">Expert Chefs</p>
            </div>
            <div class="highlight-item">
              <span class="highlight-number">25+</span>
              <p class="highlight-text">Years of Excellence</p>
            </div>
          </div>
        </div>

        <div class="kitchen-images">
          <div class="kitchen-image-grid">
            <div class="kitchen-img main-img">
              <img
                src="https://images.unsplash.com/photo-1554080221-cbf40fbb9823?w=500&h=400&fit=crop"
                alt="Fine Dining"
              />
            </div>
            <div class="kitchen-img small-img">
              <img
                src="https://images.unsplash.com/photo-1495598323787-d71145b34cc0?w=300&h=300&fit=crop"
                alt="Chef Cooking"
              />
            </div>
            <div class="kitchen-img small-img">
              <img
                src="https://images.unsplash.com/photo-1414235077418-3c453bda33d0?w=300&h=300&fit=crop"
                alt="Plating"
              />
            </div>
            <div class="kitchen-img small-img">
              <img
                src="https://images.unsplash.com/photo-1476124369162-f4978e1a212c?w=300&h=300&fit=crop"
                alt="Restaurant Ambiance"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts"></script>

<style scoped>
.heritage-kitchen {
  padding: 5rem 2rem;
  background-color: white;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 300;
  letter-spacing: 1px;
  color: #1a1a1a;
  text-align: center;
  margin-bottom: 4rem;
}

.kitchen-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.kitchen-text {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.kitchen-intro {
  font-size: 1.05rem;
  line-height: 1.8;
  color: #555;
  font-weight: 300;
}

.kitchen-highlights {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.highlight-item {
  text-align: center;
  padding: 1.5rem;
  background-color: #fafaf8;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.highlight-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.highlight-number {
  display: block;
  font-size: 2rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.highlight-text {
  font-size: 0.9rem;
  color: #666;
  font-weight: 300;
  margin: 0;
}

.kitchen-images {
  display: flex;
  align-items: center;
  justify-content: center;
}

.kitchen-image-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  width: 100%;
}

.kitchen-img {
  overflow: hidden;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.main-img {
  grid-column: span 1;
  grid-row: span 2;
  height: 100%;
}

.main-img img,
.small-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.kitchen-img:hover img {
  transform: scale(1.05);
}

.small-img {
  height: 200px;
}

@media (max-width: 768px) {
  .heritage-kitchen {
    padding: 3rem 1rem;
  }

  .section-title {
    font-size: 1.8rem;
  }

  .kitchen-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .kitchen-highlights {
    grid-template-columns: 1fr;
  }

  .kitchen-image-grid {
    grid-template-columns: 1fr;
  }

  .main-img {
    grid-column: span 1;
    grid-row: span 1;
    height: 250px;
  }

  .small-img {
    height: 150px;
  }
}
</style>
