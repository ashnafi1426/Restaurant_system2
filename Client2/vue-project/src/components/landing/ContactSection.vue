<template>
  <section class="contact-section" id="contact">
    <div class="container">
      <h2 class="section-title">Connect With Us</h2>

      <div class="contact-content">
        <div class="contact-info">
          <div class="info-item">
            <div class="info-icon">📍</div>
            <div>
              <h3 class="info-heading">Address</h3>
              <p class="info-text">123 Heritage Lane, Luxury City, LC 12345</p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">📞</div>
            <div>
              <h3 class="info-heading">Phone</h3>
              <p class="info-text">+1 (555) 123-4567</p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">✉️</div>
            <div>
              <h3 class="info-heading">Email</h3>
              <p class="info-text">hello@luxeheritage.com</p>
            </div>
          </div>
        </div>

        <div class="contact-form">
          <form @submit.prevent="handleSubmit">
            <div class="form-group">
              <input type="text" placeholder="Your Name" v-model="form.name" required />
            </div>

            <div class="form-group">
              <input type="email" placeholder="Your Email" v-model="form.email" required />
            </div>

            <div class="form-group">
              <textarea
                placeholder="Your Message"
                rows="5"
                v-model="form.message"
                required
              ></textarea>
            </div>

            <button type="submit" class="submit-btn">SEND MESSAGE</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const form = ref({
  name: '',
  email: '',
  message: '',
})

const handleSubmit = () => {
  console.log('Form submitted:', form.value)
  // Reset form
  form.value = { name: '', email: '', message: '' }
  alert('Thank you for your message. We will get back to you soon!')
}
</script>

<style scoped>
/* SECTION PADDING - Mobile first with breakpoints */
.contact-section {
  padding: 3rem 1rem;
  background-color: #fafaf8;
}

@media (min-width: 640px) {
  .contact-section {
    padding: 4rem 1.5rem;
  }
}

@media (min-width: 768px) {
  .contact-section {
    padding: 5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .contact-section {
    padding: 6rem 2.5rem;
  }
}

/* CONTAINER - Max width with responsive padding */
.container {
  max-width: 1200px;
  margin: 0 auto;
}

/* SECTION TITLE - Responsive typography */
.section-title {
  font-size: 1.5rem;
  font-weight: 300;
  letter-spacing: 0.5px;
  color: #1a1a1a;
  text-align: center;
  margin-bottom: 2rem;
}

@media (min-width: 640px) {
  .section-title {
    font-size: 2rem;
    letter-spacing: 0.75px;
    margin-bottom: 2.5rem;
  }
}

@media (min-width: 768px) {
  .section-title {
    font-size: 2.5rem;
    letter-spacing: 1px;
    margin-bottom: 3rem;
  }
}

@media (min-width: 1024px) {
  .section-title {
    font-size: 3rem;
    letter-spacing: 1.25px;
    margin-bottom: 4rem;
  }
}

/* CONTACT CONTENT - Grid with responsive layout */
.contact-content {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

@media (min-width: 640px) {
  .contact-content {
    grid-template-columns: 1fr;
    gap: 2.5rem;
  }
}

@media (min-width: 768px) {
  .contact-content {
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
  }
}

@media (min-width: 1024px) {
  .contact-content {
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
  }
}

/* CONTACT INFO - Flex column with responsive gap */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

@media (min-width: 640px) {
  .contact-info {
    gap: 2rem;
  }
}

@media (min-width: 768px) {
  .contact-info {
    gap: 2.5rem;
  }
}

@media (min-width: 1024px) {
  .contact-info {
    gap: 3rem;
  }
}

/* INFO ITEM - Flex layout with responsive gap */
.info-item {
  display: flex;
  gap: 1rem;
}

@media (min-width: 640px) {
  .info-item {
    gap: 1.25rem;
  }
}

@media (min-width: 768px) {
  .info-item {
    gap: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .info-item {
    gap: 1.75rem;
  }
}

/* INFO ICON - Responsive font size */
.info-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

@media (min-width: 640px) {
  .info-icon {
    font-size: 1.75rem;
  }
}

@media (min-width: 768px) {
  .info-icon {
    font-size: 1.8rem;
  }
}

@media (min-width: 1024px) {
  .info-icon {
    font-size: 2rem;
  }
}

/* INFO HEADING - Responsive typography */
.info-heading {
  font-size: 1rem;
  font-weight: 500;
  color: #1a1a1a;
  margin-bottom: 0.25rem;
}

@media (min-width: 640px) {
  .info-heading {
    font-size: 1.05rem;
    margin-bottom: 0.35rem;
  }
}

@media (min-width: 768px) {
  .info-heading {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
  }
}

@media (min-width: 1024px) {
  .info-heading {
    font-size: 1.2rem;
    margin-bottom: 0.6rem;
  }
}

/* INFO TEXT - Responsive typography */
.info-text {
  color: #666;
  font-size: 0.85rem;
  margin: 0;
}

@media (min-width: 640px) {
  .info-text {
    font-size: 0.9rem;
  }
}

@media (min-width: 768px) {
  .info-text {
    font-size: 0.95rem;
  }
}

@media (min-width: 1024px) {
  .info-text {
    font-size: 1rem;
  }
}

/* CONTACT FORM - Responsive padding and shadow */
.contact-form {
  background-color: white;
  padding: 1.5rem;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

@media (min-width: 640px) {
  .contact-form {
    padding: 2rem;
    border-radius: 8px;
  }
}

@media (min-width: 768px) {
  .contact-form {
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

@media (min-width: 1024px) {
  .contact-form {
    padding: 3rem;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
  }
}

/* FORM GROUP - Responsive margin */
.form-group {
  margin-bottom: 1rem;
}

@media (min-width: 640px) {
  .form-group {
    margin-bottom: 1.25rem;
  }
}

@media (min-width: 768px) {
  .form-group {
    margin-bottom: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .form-group {
    margin-bottom: 1.75rem;
  }
}

/* FORM INPUTS - Responsive padding and font */
.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.7rem 0.8rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.85rem;
  font-family: inherit;
  transition: all 0.3s ease;
}

@media (min-width: 640px) {
  .form-group input,
  .form-group textarea {
    padding: 0.8rem 0.9rem;
    font-size: 0.9rem;
    border-radius: 6px;
  }
}

@media (min-width: 768px) {
  .form-group input,
  .form-group textarea {
    padding: 0.9rem 1rem;
    font-size: 0.95rem;
    border-radius: 8px;
  }
}

@media (min-width: 1024px) {
  .form-group input,
  .form-group textarea {
    padding: 1rem 1.1rem;
    font-size: 1rem;
    border-radius: 10px;
  }
}

/* FORM INPUT FOCUS - Responsive outline */
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #1a1a1a;
  box-shadow: 0 0 0 2px rgba(26, 26, 26, 0.08);
}

@media (min-width: 768px) {
  .form-group input:focus,
  .form-group textarea:focus {
    box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
  }
}

@media (min-width: 1024px) {
  .form-group input:focus,
  .form-group textarea:focus {
    box-shadow: 0 0 0 4px rgba(26, 26, 26, 0.12);
  }
}

/* PLACEHOLDER TEXT - Consistent styling */
.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #999;
}

/* SUBMIT BUTTON - Responsive styling */
.submit-btn {
  width: 100%;
  padding: 0.8rem 1rem;
  background-color: #1a1a1a;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.3s ease;
}

@media (min-width: 640px) {
  .submit-btn {
    padding: 0.9rem 1.2rem;
    font-size: 0.85rem;
    border-radius: 6px;
    letter-spacing: 1.2px;
  }
}

@media (min-width: 768px) {
  .submit-btn {
    padding: 1rem 1.5rem;
    font-size: 0.9rem;
    border-radius: 8px;
    letter-spacing: 1.5px;
  }
}

@media (min-width: 1024px) {
  .submit-btn {
    padding: 1.1rem 1.8rem;
    font-size: 0.95rem;
    border-radius: 10px;
    letter-spacing: 1.75px;
  }
}

/* SUBMIT BUTTON HOVER - Responsive effects */
.submit-btn:hover {
  background-color: #333;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

@media (min-width: 640px) {
  .submit-btn:hover {
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.18);
    transform: translateY(-1.5px);
  }
}

@media (min-width: 768px) {
  .submit-btn:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transform: translateY(-2px);
  }
}

@media (min-width: 1024px) {
  .submit-btn:hover {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22);
    transform: translateY(-2px);
  }
}
</style>
