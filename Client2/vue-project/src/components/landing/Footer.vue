<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h4>LUXE HERITAGE</h4>
          <p>Experience luxury like never before. Our commitment to excellence is unwavering.</p>
        </div>

        <div class="footer-section">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#home">Home</a></li>
            <li><a href="#rooms">Rooms</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Services</h4>
          <ul>
            <li><a href="#">Room Booking</a></li>
            <li><a href="#">Events & Conferences</a></li>
            <li><a href="#">Dining Reservations</a></li>
            <li><a href="#">Spa & Wellness</a></li>
          </ul>
        </div>

        <div class="footer-section">
          <h4>Follow Us</h4>
          <div class="social-links">
            <a href="#" class="social-link">
              <i class="fab fa-facebook"></i>
            </a>
            <a href="#" class="social-link">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="#" class="social-link">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="social-link">
              <i class="fab fa-linkedin"></i>
            </a>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; 2024 Luxe Heritage Hotel. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts"></script>

<style scoped>
.footer {
  background-color: #1a1a1a;
  color: white;
  padding: 3rem 1rem 1.5rem;
  /* Mobile base */
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.footer-content {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* Small mobile (sm:640px) */
@media (min-width: 640px) {
  .footer {
    padding: 4rem 1.5rem 2rem;
  }

  .footer-content {
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-bottom: 2.5rem;
    padding-bottom: 2.5rem;
  }
}

/* Tablet (md:768px) */
@media (min-width: 768px) {
  .footer {
    padding: 5rem 2rem 2.5rem;
  }

  .footer-content {
    grid-template-columns: repeat(4, 1fr);
    gap: 2.5rem;
    margin-bottom: 3rem;
    padding-bottom: 3rem;
  }
}

/* Desktop (lg:1024px) */
@media (min-width: 1024px) {
  .footer {
    padding: 6rem 2.5rem 3rem;
  }

  .footer-content {
    grid-template-columns: repeat(4, 1fr);
    gap: 3rem;
    margin-bottom: 3.5rem;
    padding-bottom: 3.5rem;
  }
}

.footer-section h4 {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 1px;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
}

@media (min-width: 640px) {
  .footer-section h4 {
    font-size: 0.9rem;
    margin-bottom: 1rem;
  }
}

@media (min-width: 768px) {
  .footer-section h4 {
    font-size: 1rem;
    margin-bottom: 1.25rem;
  }
}

@media (min-width: 1024px) {
  .footer-section h4 {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
  }
}

.footer-section p {
  font-size: 0.8rem;
  line-height: 1.5;
  opacity: 0.8;
  margin: 0;
}

@media (min-width: 640px) {
  .footer-section p {
    font-size: 0.85rem;
    line-height: 1.6;
  }
}

@media (min-width: 768px) {
  .footer-section p {
    font-size: 0.9rem;
  }
}

@media (min-width: 1024px) {
  .footer-section p {
    font-size: 0.95rem;
  }
}

.footer-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-section ul li {
  margin-bottom: 0.5rem;
}

@media (min-width: 640px) {
  .footer-section ul li {
    margin-bottom: 0.6rem;
  }
}

@media (min-width: 768px) {
  .footer-section ul li {
    margin-bottom: 0.7rem;
  }
}

.footer-section a {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-size: 0.8rem;
  transition: color 0.3s ease;
}

@media (min-width: 640px) {
  .footer-section a {
    font-size: 0.85rem;
  }
}

@media (min-width: 768px) {
  .footer-section a {
    font-size: 0.9rem;
  }
}

.footer-section a:hover {
  color: white;
}

.social-links {
  display: flex;
  gap: 0.75rem;
}

@media (min-width: 640px) {
  .social-links {
    gap: 1rem;
  }
}

@media (min-width: 768px) {
  .social-links {
    gap: 1.25rem;
  }
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

@media (min-width: 640px) {
  .social-link {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }
}

@media (min-width: 768px) {
  .social-link {
    width: 44px;
    height: 44px;
    font-size: 1.1rem;
  }
}

.social-link:hover {
  border-color: white;
  background-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.footer-bottom {
  text-align: center;
  padding-top: 1rem;
  font-size: 0.75rem;
  opacity: 0.7;
}

@media (min-width: 640px) {
  .footer-bottom {
    padding-top: 1.25rem;
    font-size: 0.8rem;
  }
}

@media (min-width: 768px) {
  .footer-bottom {
    padding-top: 1.5rem;
    font-size: 0.85rem;
  }
}

@media (min-width: 1024px) {
  .footer-bottom {
    padding-top: 2rem;
    font-size: 0.9rem;
  }
}
</style>
