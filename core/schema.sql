-- Anant Ceramic Sales CRM schema (MySQL 5.7+ / MariaDB 10.3+)
-- The app creates these tables automatically on first visit.
-- You can also import this file in phpMyAdmin.

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  max_discount DECIMAL(5,2) NOT NULL DEFAULT 0,
  perms TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  mobile VARCHAR(15) NOT NULL UNIQUE,
  pin_hash VARCHAR(255) NOT NULL,
  role_id INT NULL,
  showroom VARCHAR(100) NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'active',
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grp VARCHAR(20) NOT NULL,            -- tiles | sanitary | fittings
  name VARCHAR(80) NOT NULL,           -- GVT, PGVT, Wooden, Basin, WC ...
  unit VARCHAR(10) NOT NULL DEFAULT 'sqft',  -- sqft | pc
  code_prefix VARCHAR(10) NOT NULL DEFAULT 'AC-1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  code_key VARCHAR(30) NOT NULL,       -- code without dashes/spaces, used by QR URL
  name VARCHAR(160) NOT NULL,
  category_id INT NULL,
  size VARCHAR(40) NULL,
  finish VARCHAR(60) NULL,
  thickness VARCHAR(20) NULL,
  pcs_per_box INT NULL,
  coverage_per_box DECIMAL(10,2) NULL,
  weight_per_box VARCHAR(20) NULL,
  where_to_use VARCHAR(200) NULL,
  features TEXT NULL,                  -- JSON array
  description TEXT NULL,
  unit VARCHAR(10) NOT NULL DEFAULT 'sqft',
  mrp DECIMAL(12,2) NULL,
  offer_price DECIMAL(12,2) NULL,
  dealer_price DECIMAL(12,2) NULL,
  min_price DECIMAL(12,2) NULL,
  purchase_cost DECIMAL(12,2) NULL,
  gst DECIMAL(5,2) NOT NULL DEFAULT 18,
  video_url VARCHAR(300) NULL,
  look_group VARCHAR(80) NULL,         -- "same look, other sizes"
  visibility TEXT NULL,                -- JSON per-product override (NULL = category template)
  qr_status VARCHAR(12) NOT NULL DEFAULT 'generated',  -- generated | printed
  reprint_requested TINYINT NOT NULL DEFAULT 0,
  active TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (code_key), INDEX (category_id), INDEX (look_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  path VARCHAR(255) NOT NULL,
  kind VARCHAR(10) NOT NULL DEFAULT 'photo',  -- photo | room
  sort INT NOT NULL DEFAULT 0,
  INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  location VARCHAR(80) NOT NULL,
  rack VARCHAR(40) NULL,
  batch VARCHAR(60) NULL,
  qty INT NOT NULL DEFAULT 0,
  incoming TINYINT NOT NULL DEFAULT 0,
  eta DATE NULL,
  INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visibility_templates (
  grp VARCHAR(20) NOT NULL PRIMARY KEY,
  data TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  type VARCHAR(10) NOT NULL,           -- staff | customer
  staff_id INT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (product_id), INDEX (created_at), INDEX (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NULL,
  mobile VARCHAR(15) NOT NULL UNIQUE,
  city VARCHAR(80) NULL,
  address VARCHAR(255) NULL,
  architect VARCHAR(120) NULL,
  source VARCHAR(30) NOT NULL DEFAULT 'quotation',
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  product_id INT NULL,
  channel VARCHAR(20) NOT NULL,        -- callback | whatsapp | call | visit
  area VARCHAR(40) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'new',  -- new | contacted | converted | closed
  assigned_staff_id INT NULL,
  callback_at DATETIME NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (customer_id), INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  number VARCHAR(30) NOT NULL UNIQUE,
  share_token VARCHAR(40) NOT NULL,
  staff_id INT NULL,
  customer_id INT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'draft', -- draft | approval | ready | sent | won | lost
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
  gst_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  total DECIMAL(14,2) NOT NULL DEFAULT 0,
  delivery_address VARCHAR(255) NULL,
  delivery VARCHAR(80) NULL,
  architect VARCHAR(120) NULL,
  follow_up_at DATETIME NULL,
  valid_till DATE NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (staff_id), INDEX (customer_id), INDEX (status), INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotation_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT NOT NULL,
  product_id INT NOT NULL,
  boxes DECIMAL(10,2) NOT NULL DEFAULT 0,  -- boxes (sqft products) or pieces (pc products)
  area DECIMAL(12,2) NOT NULL DEFAULT 0,   -- sq.ft billed
  rate DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  gst DECIMAL(5,2) NOT NULL DEFAULT 18,
  discount_pct DECIMAL(6,2) NOT NULL DEFAULT 0,
  note VARCHAR(200) NULL,
  approval VARCHAR(12) NOT NULL DEFAULT 'none',  -- none | pending | approved | rejected | countered
  approval_reason VARCHAR(200) NULL,
  counter_rate DECIMAL(12,2) NULL,
  approval_at DATETIME NULL,
  sort INT NOT NULL DEFAULT 0,
  INDEX (quotation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
