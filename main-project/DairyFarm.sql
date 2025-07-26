USE sohailpk_dairy_farm_management;

-- Animals table
CREATE TABLE IF NOT EXISTS animals (
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

-- Milk production table
CREATE TABLE IF NOT EXISTS milk_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    date DATE NOT NULL,
    session ENUM('Morning', 'Evening') NOT NULL,
    quantity DECIMAL(5,2) NOT NULL,
    notes TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE
);

-- Health records table
CREATE TABLE IF NOT EXISTS health_records (
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

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    category ENUM('Fodder', 'Medicines', 'Labor', 'Equipment', 'Utilities', 'Maintenance', 'Other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Credit Card') DEFAULT 'Cash',
    receipt_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    payment_type ENUM('Salary', 'Utility Bill', 'Supplier Payment', 'Loan Payment', 'Other') NOT NULL,
    recipient VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check') DEFAULT 'Cash',
    reference TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('Admin', 'Manager', 'Worker') DEFAULT 'Worker',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);