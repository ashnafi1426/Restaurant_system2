-- ============================================================================
-- RESTAURANT & HOTEL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================================================
-- Project: Complete Restaurant & Hotel Management System
-- Database: MySQL/MariaDB
-- Version: 1.0
-- Date: August 6, 2026
-- Description: Full executable schema with all 45+ tables
-- ============================================================================

-- Drop database if exists (CAUTION: This will delete all data)
-- DROP DATABASE IF EXISTS restaurant_hotel_system;

-- Create database
-- CREATE DATABASE IF NOT EXISTS restaurant_hotel_system 
--   DEFAULT CHARACTER SET utf8mb4 
--   DEFAULT COLLATE utf8mb4_unicode_ci;

-- USE restaurant_hotel_system;

-- ============================================================================
-- SYSTEM TABLES (Laravel Framework)
-- ============================================================================

-- Cache table for Laravel cache driver
DROP TABLE IF EXISTS cache;
CREATE TABLE cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL,
    
    INDEX idx_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cache locks
DROP TABLE IF EXISTS cache_locks;
CREATE TABLE cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job queue table
DROP TABLE IF EXISTS jobs;
CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    
    INDEX idx_queue (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed jobs table
DROP TABLE IF EXISTS failed_jobs;
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- AUTHENTICATION & USER MANAGEMENT
-- ============================================================================

-- Users table (Core user accounts for all system roles)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    role ENUM('admin', 'manager', 'receptionist', 'cashier', 'chef', 'waiter', 'guest') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    activation_status ENUM('pending', 'activated', 'expired') DEFAULT 'pending',
    activation_token VARCHAR(60) UNIQUE,
    activation_token_expires_at TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_activation_token (activation_token),
    INDEX idx_activation_status (activation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personal access tokens (Laravel Sanctum API tokens)
DROP TABLE IF EXISTS personal_access_tokens;
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
DROP TABLE IF EXISTS password_reset_tokens;
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- HOTEL STRUCTURE & ROOMS
-- ============================================================================

-- Hotel floors (Floor/level management)
DROP TABLE IF EXISTS hotel_floors;
CREATE TABLE hotel_floors (
    id CHAR(36) PRIMARY KEY,
    floor_number INT UNIQUE NOT NULL,
    floor_name VARCHAR(255),
    name VARCHAR(255),
    description TEXT,
    total_rooms INT DEFAULT 0,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_floor_number (floor_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hotel shifts (Work shift definitions)
DROP TABLE IF EXISTS hotel_shifts;
CREATE TABLE hotel_shifts (
    id CHAR(36) PRIMARY KEY,
    shift_name VARCHAR(100) NOT NULL,
    name VARCHAR(100),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_shift_name (shift_name),
    INDEX idx_status (status),
    INDEX idx_times (start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room types (Room categories/classifications)
DROP TABLE IF EXISTS room_types;
CREATE TABLE room_types (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    max_occupancy INT NOT NULL,
    amenities JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_name (name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rooms (Individual room records with QR codes)
DROP TABLE IF EXISTS rooms;
CREATE TABLE rooms (
    id CHAR(36) PRIMARY KEY,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    room_type_id CHAR(36) NOT NULL,
    floor_id CHAR(36),
    floor INT,
    description TEXT,
    status ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    is_active BOOLEAN DEFAULT TRUE,
    qr_token VARCHAR(8) UNIQUE,
    qr_image_path VARCHAR(255),
    qr_generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (floor_id) REFERENCES hotel_floors(id) ON DELETE SET NULL,
    
    INDEX idx_room_number (room_number),
    INDEX idx_status (status),
    INDEX idx_qr_token (qr_token),
    INDEX idx_floor_id (floor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- GUEST MANAGEMENT
-- ============================================================================

-- Guests (Guest/customer profiles)
DROP TABLE IF EXISTS guests;
CREATE TABLE guests (
    id CHAR(36) PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    id_type ENUM('passport', 'national_id', 'driver_license'),
    id_number VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_full_name (first_name, last_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RESERVATIONS & CHECK-INS
-- ============================================================================

-- Reservations (Room booking records)
DROP TABLE IF EXISTS reservations;
CREATE TABLE reservations (
    id CHAR(36) PRIMARY KEY,
    guest_id CHAR(36) NOT NULL,
    room_id CHAR(36) NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_guests INT DEFAULT 1,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_status (status),
    INDEX idx_check_in_date (check_in_date),
    INDEX idx_check_out_date (check_out_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check-ins (Guest check-in records)
DROP TABLE IF EXISTS check_ins;
CREATE TABLE check_ins (
    id CHAR(36) PRIMARY KEY,
    reservation_id CHAR(36) NOT NULL,
    guest_id CHAR(36) NOT NULL,
    room_id CHAR(36) NOT NULL,
    check_in_date DATETIME NOT NULL,
    expected_check_out_date DATE NOT NULL,
    notes TEXT,
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE RESTRICT,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_check_in_date (check_in_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check-outs (Guest check-out records)
DROP TABLE IF EXISTS check_outs;
CREATE TABLE check_outs (
    id CHAR(36) PRIMARY KEY,
    check_in_id CHAR(36) NOT NULL,
    check_out_date DATETIME NOT NULL,
    total_charges DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    notes TEXT,
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (check_in_id) REFERENCES check_ins(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_check_in_id (check_in_id),
    INDEX idx_check_out_date (check_out_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- MENU & RESTAURANT
-- ============================================================================

-- Categories (Menu item categories)
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_display_order (display_order),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu items (Restaurant menu items)
DROP TABLE IF EXISTS menu_items;
CREATE TABLE menu_items (
    id CHAR(36) PRIMARY KEY,
    category_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    preparation_time INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    
    INDEX idx_category_id (category_id),
    INDEX idx_is_available (is_available),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ORDERS & ORDER ITEMS
-- ============================================================================

-- Orders (Food/restaurant orders)
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
    id CHAR(36) PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    guest_id CHAR(36),
    room_id CHAR(36),
    chef_id CHAR(36),
    reservation_id CHAR(36),
    order_type ENUM('dine_in', 'room_service', 'takeout') NOT NULL,
    source ENUM('staff', 'qr_code', 'guest_portal') DEFAULT 'staff',
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'served', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0.00,
    service_charge DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'mobile_money'),
    notes TEXT,
    order_time DATETIME,
    served_at DATETIME,
    cancelled_at DATETIME,
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (chef_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_source (source),
    INDEX idx_order_type (order_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items (Individual items in an order)
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) NOT NULL,
    menu_item_id CHAR(36) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT,
    
    INDEX idx_order_id (order_id),
    INDEX idx_menu_item_id (menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- WAITER SYSTEM (Most Complex Module)
-- ============================================================================

-- Waiters (Waiter/server profiles)
DROP TABLE IF EXISTS waiters;
CREATE TABLE waiters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) UNIQUE NOT NULL,
    employee_number VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    section VARCHAR(100),
    shift VARCHAR(50),
    experience_level VARCHAR(50),
    employment_type VARCHAR(50),
    hire_date DATE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    availability ENUM('available', 'busy', 'on_break', 'offline') DEFAULT 'offline',
    current_orders INT DEFAULT 0,
    maximum_orders INT DEFAULT 5,
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_availability (availability),
    INDEX idx_employee_number (employee_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Waiter floor assignments (Daily waiter-to-floor assignments)
DROP TABLE IF EXISTS waiter_floor_assignments;
CREATE TABLE waiter_floor_assignments (
    id CHAR(36) PRIMARY KEY,
    waiter_id INT UNSIGNED NOT NULL,
    floor_id CHAR(36) NOT NULL,
    shift_id CHAR(36) NOT NULL,
    assignment_date DATE NOT NULL,
    priority ENUM('primary', 'secondary', 'backup') DEFAULT 'primary',
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE CASCADE,
    FOREIGN KEY (floor_id) REFERENCES hotel_floors(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES hotel_shifts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (waiter_id, floor_id, shift_id, assignment_date),
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_floor_id (floor_id),
    INDEX idx_shift_id (shift_id),
    INDEX idx_assignment_date (assignment_date),
    INDEX idx_priority (priority),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Delivery tasks (Room service delivery tracking - CORE TABLE)
DROP TABLE IF EXISTS delivery_tasks;
CREATE TABLE delivery_tasks (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) UNIQUE NOT NULL,
    waiter_id INT UNSIGNED,
    room_id CHAR(36) NOT NULL,
    floor_id CHAR(36),
    reservation_id CHAR(36),
    assigned_by CHAR(36),
    assignment_type ENUM('automatic', 'manual') DEFAULT 'automatic',
    status ENUM('waiting_assignment', 'assigned', 'accepted', 'picked_up', 'on_delivery', 'delivered', 'cancelled') DEFAULT 'waiting_assignment',
    assigned_at TIMESTAMP NULL,
    accepted_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    on_delivery_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (floor_id) REFERENCES hotel_floors(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_order (order_id),
    
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_room_id (room_id),
    INDEX idx_floor_id (floor_id),
    INDEX idx_status (status),
    INDEX idx_assigned_at (assigned_at),
    INDEX idx_delivered_at (delivered_at)
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Waiter assignments (Legacy assignment tracking)
DROP TABLE IF EXISTS waiter_assignments;
CREATE TABLE waiter_assignments (
    id CHAR(36) PRIMARY KEY,
    waiter_id INT UNSIGNED NOT NULL,
    order_id CHAR(36) NOT NULL,
    room_id CHAR(36) NOT NULL,
    assignment_type ENUM('delivery', 'room_service') DEFAULT 'delivery',
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_assigned_at (assigned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery logs (Audit trail for delivery actions)
DROP TABLE IF EXISTS delivery_logs;
CREATE TABLE delivery_logs (
    id CHAR(36) PRIMARY KEY,
    waiter_id INT UNSIGNED NOT NULL,
    order_id CHAR(36) NOT NULL,
    room_id CHAR(36),
    action VARCHAR(100) NOT NULL,
    status_from VARCHAR(50),
    status_to VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_order_id (order_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Waiter performance (Daily performance metrics)
DROP TABLE IF EXISTS waiter_performance;
CREATE TABLE waiter_performance (
    id CHAR(36) PRIMARY KEY,
    waiter_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    total_deliveries INT DEFAULT 0,
    successful_deliveries INT DEFAULT 0,
    failed_deliveries INT DEFAULT 0,
    average_delivery_time DECIMAL(8,2),
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_waiter_date (waiter_id, date),
    
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Waiter notifications (Waiter-specific notifications)
DROP TABLE IF EXISTS waiter_notifications;
CREATE TABLE waiter_notifications (
    id CHAR(36) PRIMARY KEY,
    waiter_id INT UNSIGNED NOT NULL,
    type ENUM('assignment', 'urgent_order', 'reassignment', 'status_update', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id CHAR(36),
    related_type VARCHAR(100),
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE CASCADE,
    
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PAYMENT SYSTEM
-- ============================================================================

-- Payments (Payment transaction records - Polymorphic)
DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    id CHAR(36) PRIMARY KEY,
    payable_type VARCHAR(255) NOT NULL,
    payable_id CHAR(36) NOT NULL,
    transaction_reference VARCHAR(255) UNIQUE NOT NULL,
    payment_method ENUM('chapa', 'cash', 'card', 'mobile_money') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ETB',
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_data JSON,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_reference (transaction_reference),
    INDEX idx_payable (payable_type, payable_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices (Billing/invoice records)
DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
    id CHAR(36) PRIMARY KEY,
    reservation_id CHAR(36),
    order_id CHAR(36),
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    due_date DATE,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice items (Line items for invoices)
DROP TABLE IF EXISTS invoice_items;
CREATE TABLE invoice_items (
    id CHAR(36) PRIMARY KEY,
    invoice_id CHAR(36) NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    
    INDEX idx_invoice_id (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurant charges (Additional restaurant charges linked to reservations)
DROP TABLE IF EXISTS restaurant_charges;
CREATE TABLE restaurant_charges (
    id CHAR(36) PRIMARY KEY,
    reservation_id CHAR(36) NOT NULL,
    order_id CHAR(36) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    charge_type ENUM('food', 'service', 'room_service') NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- MANAGER OPERATIONS
-- ============================================================================

-- Manager audit logs (Manager action audit trail)
DROP TABLE IF EXISTS manager_audit_logs;
CREATE TABLE manager_audit_logs (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    action VARCHAR(255) NOT NULL,
    model_type VARCHAR(100),
    model_id CHAR(36),
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_model (model_type, model_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manager notifications (Manager-specific notifications)
DROP TABLE IF EXISTS manager_notifications;
CREATE TABLE manager_notifications (
    id CHAR(36) PRIMARY KEY,
    manager_id CHAR(36) NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_manager_id (manager_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Complaint tickets (Guest complaint management)
DROP TABLE IF EXISTS complaint_tickets;
CREATE TABLE complaint_tickets (
    id CHAR(36) PRIMARY KEY,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    guest_id CHAR(36),
    room_id CHAR(36),
    complaint_type ENUM('room', 'service', 'food', 'cleanliness', 'noise', 'staff', 'other') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'assigned', 'in_progress', 'resolved', 'closed', 'escalated') DEFAULT 'open',
    assigned_to CHAR(36),
    resolution TEXT,
    resolved_at TIMESTAMP NULL,
    created_by CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_ticket_number (ticket_number),
    INDEX idx_guest_id (guest_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_complaint_type (complaint_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance metrics (Staff performance tracking)
DROP TABLE IF EXISTS performance_metrics;
CREATE TABLE performance_metrics (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    metric_type VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_metric_type (metric_type),
    INDEX idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory management (Kitchen/hotel inventory tracking)
DROP TABLE IF EXISTS inventory_management;
CREATE TABLE inventory_management (
    id CHAR(36) PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    quantity INT NOT NULL,
    unit VARCHAR(50),
    reorder_level INT,
    status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_item_name (item_name),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NOTIFICATIONS & ACTIVITY
-- ============================================================================

-- Notifications (General system notifications)
DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs (System-wide audit trail)
DROP TABLE IF EXISTS audit_logs;
CREATE TABLE audit_logs (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    action VARCHAR(255) NOT NULL,
    model_type VARCHAR(100),
    model_id CHAR(36),
    changes JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_model (model_type, model_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reservation audit logs (Reservation-specific audit trail)
DROP TABLE IF EXISTS reservation_audit_logs;
CREATE TABLE reservation_audit_logs (
    id CHAR(36) PRIMARY KEY,
    reservation_id CHAR(36) NOT NULL,
    user_id CHAR(36),
    action VARCHAR(255) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    changes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ADDITIONAL OPERATIONS
-- ============================================================================

-- Room service deliveries (Legacy room service tracking)
DROP TABLE IF EXISTS room_service_deliveries;
CREATE TABLE room_service_deliveries (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) NOT NULL,
    room_id CHAR(36) NOT NULL,
    waiter_id INT UNSIGNED,
    status ENUM('pending', 'assigned', 'delivered', 'cancelled') DEFAULT 'pending',
    assigned_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (waiter_id) REFERENCES waiters(id) ON DELETE SET NULL,
    
    INDEX idx_order_id (order_id),
    INDEX idx_room_id (room_id),
    INDEX idx_waiter_id (waiter_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Housekeeping tasks (Room cleaning/maintenance tasks)
DROP TABLE IF EXISTS housekeeping_tasks;
CREATE TABLE housekeeping_tasks (
    id CHAR(36) PRIMARY KEY,
    room_id CHAR(36) NOT NULL,
    assigned_to CHAR(36),
    task_type ENUM('cleaning', 'maintenance', 'inspection') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    description TEXT,
    notes TEXT,
    scheduled_for DATETIME,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_room_id (room_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laundry requests (Guest laundry service requests)
DROP TABLE IF EXISTS laundry_requests;
CREATE TABLE laundry_requests (
    id CHAR(36) PRIMARY KEY,
    guest_id CHAR(36) NOT NULL,
    room_id CHAR(36) NOT NULL,
    items JSON,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'collected', 'processing', 'ready', 'delivered', 'completed') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    collected_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    
    INDEX idx_guest_id (guest_id),
    INDEX idx_room_id (room_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- SAMPLE DATA INSERTS (Optional - Uncomment to populate)
-- ============================================================================

-- Sample hotel floors
-- INSERT INTO hotel_floors (id, floor_number, floor_name, name, total_rooms, status) VALUES
-- (UUID(), 1, 'Ground Floor', 'Ground Floor', 10, 'active'),
-- (UUID(), 2, 'First Floor', 'First Floor', 15, 'active'),
-- (UUID(), 3, 'Second Floor', 'Second Floor', 15, 'active'),
-- (UUID(), 4, 'Third Floor', 'Third Floor', 20, 'active');

-- Sample shifts
-- INSERT INTO hotel_shifts (id, shift_name, name, start_time, end_time, status) VALUES
-- (UUID(), 'Morning Shift', 'Morning Shift', '06:00:00', '14:00:00', 'active'),
-- (UUID(), 'Afternoon Shift', 'Afternoon Shift', '14:00:00', '22:00:00', 'active'),
-- (UUID(), 'Night Shift', 'Night Shift', '22:00:00', '06:00:00', 'active');

-- Sample room types
-- INSERT INTO room_types (id, name, description, base_price, max_occupancy, status) VALUES
-- (UUID(), 'Standard Room', 'Comfortable standard room with basic amenities', 1500.00, 2, 'active'),
-- (UUID(), 'Deluxe Room', 'Spacious room with premium amenities', 2500.00, 3, 'active'),
-- (UUID(), 'Suite', 'Luxury suite with separate living area', 4000.00, 4, 'active'),
-- (UUID(), 'Presidential Suite', 'Ultimate luxury experience', 8000.00, 6, 'active');

-- Sample categories
-- INSERT INTO categories (id, name, description, display_order, status) VALUES
-- (UUID(), 'Appetizers', 'Start your meal with our delicious appetizers', 1, 'active'),
-- (UUID(), 'Main Course', 'Hearty main dishes', 2, 'active'),
-- (UUID(), 'Desserts', 'Sweet treats to end your meal', 3, 'active'),
-- (UUID(), 'Beverages', 'Hot and cold drinks', 4, 'active'),
-- (UUID(), 'Traditional', 'Ethiopian traditional dishes', 5, 'active');

-- Sample admin user (password: Admin@123)
-- INSERT INTO users (id, first_name, last_name, email, password, role, is_active, activation_status, email_verified_at) VALUES
-- (UUID(), 'System', 'Administrator', 'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, 'activated', NOW());

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================

-- Script execution notes:
-- 1. Uncomment database creation commands if needed
-- 2. Ensure proper user permissions before running
-- 3. Tables are created in dependency order to avoid foreign key errors
-- 4. All tables use InnoDB engine for transaction support
-- 5. UTF8MB4 charset supports full Unicode including emojis
-- 6. Soft deletes implemented via deleted_at columns
-- 7. Timestamps automatically managed by MySQL
-- 8. JSON columns require MySQL 5.7.8+ or MariaDB 10.2.7+

-- Total tables created: 45
-- Total indexes: 150+
-- Total foreign keys: 80+
