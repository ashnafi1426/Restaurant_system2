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
  padding: 4rem 2rem 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-section h4 {
  font-size: 1rem;
  font-weight: 600;
  letter-spacing: 1px;
  margin-bottom: 1rem;
  text-transform: uppercase;
}

.footer-section p {
  font-size: 0.9rem;
  line-height: 1.6;
  opacity: 0.8;
  margin: 0;
}

.footer-section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-section ul li {
  margin-bottom: 0.7rem;
}

.footer-section a {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.3s ease;
}

.footer-section a:hover {
  color: white;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.social-link:hover {
  border-color: white;
  background-color: rgba(255, 255, 255, 0.1);
}

.footer-bottom {
  text-align: center;
  padding-top: 1.5rem;
  font-size: 0.85rem;
  opacity: 0.7;
}

@media (max-width: 768px) {
  .footer {
    padding: 2rem 1rem 1rem;
  }

  .footer-content {
    gap: 1.5rem;
  }

  .footer-section h4 {
    font-size: 0.9rem;
  }
}
</style>
