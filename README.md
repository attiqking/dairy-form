# Dairy Farm Management System - Database & API Reference

## Project Overview

This document provides the complete **database schema** and **API structure** for a comprehensive Dairy Farm Management System. Use this as a reference to implement the system in any technology stack.

### Current System Features
- 🐄 **Animal Management** - Track livestock with detailed records
- 🥛 **Milk Production Tracking** - Daily morning/evening session records
- 🏥 **Health Records Management** - Vaccinations, treatments, and checkups
- 💰 **Financial Management** - Expenses and payments tracking
- 👥 **User Management** - Role-based access control (Admin, Manager, Worker)
- 📊 **Dashboard & Analytics** - Real-time charts and reports
- 🔐 **Authentication & Security** - Secure login with session management

## Database Schema

## Database Tables Structure

### 1. Animals Table
```sql
CREATE TABLE animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_number VARCHAR(20) UNIQUE NOT NULL,
    breed VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    purchase_date DATE NOT NULL,
    purchase_price DECIMAL(10,2),
    status ENUM('Active', 'Inactive', 'Recovering', 'Pregnant') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. Milk Production Table
```sql
CREATE TABLE milk_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    date DATE NOT NULL,
    session ENUM('Morning', 'Evening') NOT NULL,
    quantity DECIMAL(5,2) NOT NULL,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);
```

### 3. Health Records Table
```sql
CREATE TABLE health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    record_type ENUM('Vaccination', 'Treatment', 'Checkup', 'Breeding', 'Other') NOT NULL,
    date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    treatment TEXT,
    veterinarian VARCHAR(100),
    cost DECIMAL(10,2),
    next_followup DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE
);
```

### 4. Expenses Table
```sql
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    category ENUM('Fodder', 'Medicines', 'Labor', 'Equipment', 'Utilities', 'Maintenance', 'Other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Credit Card') DEFAULT 'Cash',
    receipt_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5. Payments Table
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    payment_type ENUM('Salary', 'Utility Bill', 'Supplier Payment', 'Loan Payment', 'Other') NOT NULL,
    recipient VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check') DEFAULT 'Cash',
    reference TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 6. Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('Admin', 'Manager', 'Worker') DEFAULT 'Worker',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 7. Animal Assignments Table
```sql
CREATE TABLE animal_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    user_id INT NOT NULL,
    start_date DATE NOT NULL DEFAULT (CURDATE()),
    end_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_animal_user (animal_id, user_id),
    INDEX idx_active_assignments (user_id, end_date)
);
```

## API Endpoints Structure

### Authentication Endpoints
```
POST   /api/auth/login
POST   /api/auth/register
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/me
```

### Animal Management
```
GET    /api/animals              // Get all animals
POST   /api/animals              // Create new animal
GET    /api/animals/:id          // Get animal by ID
PUT    /api/animals/:id          // Update animal
DELETE /api/animals/:id          // Delete animal
GET    /api/animals/:id/milk     // Get milk production for animal
GET    /api/animals/:id/health   // Get health records for animal
GET    /api/animals/assignments  // Get animal assignments
POST   /api/animals/assignments  // Assign animal to user
PUT    /api/animals/assignments/:id  // Update assignment
DELETE /api/animals/assignments/:id // End assignment
```

### Milk Production 
```
GET    /api/milk                 // Get all milk records
POST   /api/milk                 // Record milk production
GET    /api/milk/:id             // Get specific record
PUT    /api/milk/:id             // Update record
DELETE /api/milk/:id             // Delete record
GET    /api/milk/daily           // Get daily production
GET    /api/milk/monthly         // Get monthly production
GET    /api/milk/production      // Get production report with filters
GET    /api/milk/stats           // Get milk production statistics
GET    /api/milk/chart-data      // Get data for charts (last 30 days)
```

### Health Records
```
GET    /api/health               // Get all health records
POST   /api/health               // Create health record
GET    /api/health/:id           // Get specific record
PUT    /api/health/:id           // Update record
DELETE /api/health/:id           // Delete record
GET    /api/health/alerts        // Get upcoming treatments
GET    /api/health/vaccinations  // Get vaccination schedule
GET    /api/health/by-animal/:id // Get health records for specific animal
```

### Financial Management
```
GET    /api/expenses             // Get all expenses
POST   /api/expenses             // Create expense
GET    /api/expenses/:id         // Get specific expense
PUT    /api/expenses/:id         // Update expense
DELETE /api/expenses/:id         // Delete expense
GET    /api/expenses/by-category // Get expenses grouped by category
GET    /api/expenses/monthly     // Get monthly expense summary

GET    /api/payments             // Get all payments
POST   /api/payments             // Create payment
GET    /api/payments/:id         // Get specific payment
PUT    /api/payments/:id         // Update payment
DELETE /api/payments/:id         // Delete payment
GET    /api/payments/by-type     // Get payments grouped by type
GET    /api/payments/monthly     // Get monthly payment summary
```

### User Management
```
GET    /api/users                // Get all users (Admin only)
POST   /api/users                // Create user (Admin only)
GET    /api/users/:id            // Get user details
PUT    /api/users/:id            // Update user
DELETE /api/users/:id            // Delete user (Admin only)
GET    /api/users/:id/animals    // Get user's assigned animals
GET    /api/users/:id/activities // Get user's recent activities
```

### Dashboard & Reports
```
GET    /api/dashboard/stats      // Get dashboard statistics
GET    /api/dashboard/user-stats // Get user-specific dashboard stats
GET    /api/reports/milk         // Milk production reports
GET    /api/reports/financial    // Financial reports
GET    /api/reports/health       // Health reports
GET    /api/reports/production   // Production analysis report
GET    /api/reports/profitability // Profitability analysis
GET    /api/reports/export/pdf   // Export reports as PDF
```

This database schema and API structure provides everything you need to recreate the dairy farm management system functionality.
