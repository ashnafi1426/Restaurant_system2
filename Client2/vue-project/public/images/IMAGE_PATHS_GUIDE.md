# Image Paths Reference Guide

Use these paths in your Vue components to reference images from the public folder.

## Basic Usage in Vue Components

```vue
<img src="/images/hero/hero.jpg" alt="Hotel Hero" />
```

## Complete Path Reference

### Hero Section Images
```
/images/hero/hero.jpg
```

### Room Images
```
/images/rooms/deluxe.jpg
/images/rooms/suite.jpg
/images/rooms/family.jpg
```

### Facilities Images
```
/images/facilities/restaurant.jpg
/images/facilities/pool.jpg
/images/facilities/spa.jpg
/images/facilities/gym.jpg
/images/facilities/conference.jpg
/images/facilities/garden.jpg
/images/facilities/lobby.jpg
```

### Gallery Images
```
/images/gallery/gallery1.jpg
/images/gallery/gallery2.jpg
/images/gallery/gallery3.jpg
/images/gallery/gallery4.jpg
/images/gallery/gallery5.jpg
/images/gallery/gallery6.jpg
```

### Team Images
```
/images/team/manager.jpg
/images/team/chef.jpg
/images/team/reception.jpg
/images/team/relations.jpg
```

### Testimonial Images
```
/images/testimonials/guest1.jpg
/images/testimonials/guest2.jpg
/images/testimonials/guest3.jpg
```

### Food Images
```
/images/food/burger.jpg
/images/food/pizza.jpg
/images/food/coffee.jpg
/images/food/dessert.jpg
```

## Example Vue Component Usage

```vue
<template>
  <!-- Hero Section -->
  <div class="hero">
    <img src="/images/hero/hero.jpg" alt="Luxury Hotel" class="hero-image" />
  </div>

  <!-- Room Gallery -->
  <div class="rooms-grid">
    <img src="/images/rooms/deluxe.jpg" alt="Deluxe Room" />
    <img src="/images/rooms/suite.jpg" alt="Executive Suite" />
    <img src="/images/rooms/family.jpg" alt="Family Room" />
  </div>

  <!-- Facilities -->
  <div class="facilities">
    <img src="/images/facilities/restaurant.jpg" alt="Restaurant" />
    <img src="/images/facilities/pool.jpg" alt="Swimming Pool" />
    <img src="/images/facilities/spa.jpg" alt="Spa" />
  </div>

  <!-- Testimonials -->
  <div class="testimonials">
    <img src="/images/testimonials/guest1.jpg" alt="Guest 1" class="guest-avatar" />
    <img src="/images/testimonials/guest2.jpg" alt="Guest 2" class="guest-avatar" />
    <img src="/images/testimonials/guest3.jpg" alt="Guest 3" class="guest-avatar" />
  </div>

  <!-- Team -->
  <div class="team">
    <img src="/images/team/manager.jpg" alt="General Manager" />
    <img src="/images/team/chef.jpg" alt="Executive Chef" />
    <img src="/images/team/reception.jpg" alt="Reception Manager" />
  </div>
</template>
```

## Dynamic Image Loading

If you want to use these paths dynamically:

```ts
// In your component script or store
const imageBase = '/images'

const rooms = [
  { name: 'Deluxe', image: `${imageBase}/rooms/deluxe.jpg` },
  { name: 'Suite', image: `${imageBase}/rooms/suite.jpg` },
  { name: 'Family', image: `${imageBase}/rooms/family.jpg` }
]

const facilities = [
  { name: 'Restaurant', image: `${imageBase}/facilities/restaurant.jpg` },
  { name: 'Pool', image: `${imageBase}/facilities/pool.jpg` },
  { name: 'Spa', image: `${imageBase}/facilities/spa.jpg` }
]
```

## Tailwind CSS Image Examples

```vue
<template>
  <!-- With Tailwind classes -->
  <img 
    src="/images/hero/hero.jpg" 
    alt="Hotel Hero" 
    class="w-full h-96 object-cover rounded-lg shadow-lg"
  />

  <!-- Responsive Image -->
  <img 
    src="/images/rooms/deluxe.jpg" 
    alt="Deluxe Room" 
    class="w-full h-auto md:h-64 lg:h-80 object-cover"
  />

  <!-- Avatar with Border -->
  <img 
    src="/images/testimonials/guest1.jpg" 
    alt="Guest" 
    class="h-16 w-16 rounded-full object-cover ring-4 ring-white"
  />
</template>
```
