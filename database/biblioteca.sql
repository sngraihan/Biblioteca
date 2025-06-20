-- Create Database
CREATE DATABASE biblioteca;
USE biblioteca;

-- Table: categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: books
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher VARCHAR(255),
    publication_year YEAR,
    isbn VARCHAR(20) UNIQUE,
    category_id INT,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    status ENUM('available', 'borrowed', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table: members
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    join_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: admins
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: loans
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_code VARCHAR(20) UNIQUE NOT NULL,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    admin_id INT NOT NULL,
    loan_date DATE DEFAULT (CURRENT_DATE),
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('active', 'returned', 'overdue', 'cancelled') DEFAULT 'active',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO categories (name, description) VALUES
('Fiction', 'Fictional books and novels'),
('Non-Fiction', 'Educational and factual books'),
('Science', 'Scientific and technical books'),
('History', 'Historical books and biographies'),
('Technology', 'Computer and technology books');

INSERT INTO admins (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@biblioteca.com', 'admin'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Library Staff', 'staff@biblioteca.com', 'staff');
-- Default password for both accounts is: password

INSERT INTO books (title, author, publisher, publication_year, isbn, category_id, total_copies, available_copies) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 1925, '9780743273565', 1, 3, 3),
('To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott & Co.', 1960, '9780061120084', 1, 2, 2),
('1984', 'George Orwell', 'Secker & Warburg', 1949, '9780451524935', 1, 4, 4);

INSERT INTO members (member_code, name, email, phone, address) VALUES
('MBR001', 'John Doe', 'john.doe@email.com', '081234567890', '123 Main Street'),
('MBR002', 'Jane Smith', 'jane.smith@email.com', '081234567891', '456 Oak Avenue');