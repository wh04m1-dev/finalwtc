-- Users Table (No change needed)
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255),
    phone_number VARCHAR(15),
    role VARCHAR(20) DEFAULT 'Customer', -- 'Customer' or 'Organizer'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories Table (No change needed)
CREATE TABLE Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(255) UNIQUE,
    category_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events Table (No change needed)
CREATE TABLE Events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    organizer_id INT,
    event_name VARCHAR(255),
    event_description TEXT,
    event_date DATETIME,
    event_location VARCHAR(255),
    category_id INT, -- Foreign Key to Categories
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
);

-- Ticket Types Table (No change needed)
CREATE TABLE Ticket_Types (
    ticket_type_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    ticket_name VARCHAR(50),
    price DECIMAL(10, 2),
    quantity_available INT,
    discount DECIMAL(5, 2) DEFAULT 0.00, -- Discount in percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE
);

-- Updated Orders Table with new purchase flow (No change needed)
CREATE TABLE Orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_status VARCHAR(20) DEFAULT 'Cart', -- Cart, Confirmed, Paid, Cancelled
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2),
    payment_status VARCHAR(20) DEFAULT 'Pending', -- Pending, Paid, Failed
    purchased_at TIMESTAMP NULL, -- When the purchase is confirmed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Order Details Table with QR Code
CREATE TABLE Order_Details (
    order_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    ticket_type_id INT,
    quantity INT,
    price_at_purchase DECIMAL(10, 2),
    qr_code VARCHAR(255) DEFAULT NULL, -- Field to store the QR code (link or code string)
    is_scanned BOOLEAN DEFAULT FALSE, -- To track if the QR code has been scanned
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_type_id) REFERENCES Ticket_Types(ticket_type_id) ON DELETE CASCADE
);

-- Discounts Table (No change needed)
CREATE TABLE Discounts (
    discount_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    discount_code VARCHAR(50),
    discount_percentage DECIMAL(5, 2),
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES Events(event_id) ON DELETE CASCADE
);

-- Payments Table with payment status and handling (No change needed)
CREATE TABLE Payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    payment_method VARCHAR(50), -- E.g., Credit Card, PayPal
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_amount DECIMAL(10, 2),
    payment_status VARCHAR(20) DEFAULT 'Pending', -- Pending, Successful, Failed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE
);