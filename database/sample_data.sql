USE sakuracloud;

-- Insert admin user (password: Admin123!)
INSERT INTO users (
    email, 
    password, 
    full_name, 
    phone, 
    role, 
    is_active, 
    email_verified
) VALUES (
    'admin@sakuracloud.id',
    '$argon2id$v=19$m=65536,t=4,p=1$WHE4TDFMcXN5NDlVeTRGNA$3mPQUw/9/UFtBbV1pN4FSKNGpTVJI+t6Ux6zXyH5Ims',
    'System Administrator',
    '+6281234567890',
    'super_admin',
    TRUE,
    TRUE
);

-- Insert VPS products
INSERT INTO products (
    name,
    description,
    type,
    specs,
    region,
    hourly_price,
    monthly_price,
    yearly_price,
    stock,
    is_available
) VALUES 
-- Starter VPS
(
    'VPS Starter',
    'Perfect for development and testing environments',
    'vps',
    '{
        "cpu": "1 vCPU",
        "ram": "1 GB",
        "storage": "25 GB NVMe SSD",
        "bandwidth": "1 TB",
        "backup": "Weekly",
        "ddos_protection": true
    }',
    'id-jkt',
    0.007, -- IDR 100/hour
    50000,  -- IDR 50,000/month
    500000, -- IDR 500,000/year
    100,
    TRUE
),
-- Professional VPS
(
    'VPS Professional',
    'Ideal for small to medium websites and applications',
    'vps',
    '{
        "cpu": "2 vCPU",
        "ram": "4 GB",
        "storage": "80 GB NVMe SSD",
        "bandwidth": "3 TB",
        "backup": "Daily",
        "ddos_protection": true
    }',
    'id-jkt',
    0.015, -- IDR 200/hour
    150000, -- IDR 150,000/month
    1500000, -- IDR 1,500,000/year
    50,
    TRUE
),
-- Enterprise VPS
(
    'VPS Enterprise',
    'High-performance solution for business-critical applications',
    'vps',
    '{
        "cpu": "4 vCPU",
        "ram": "8 GB",
        "storage": "160 GB NVMe SSD",
        "bandwidth": "5 TB",
        "backup": "Daily",
        "ddos_protection": true
    }',
    'id-jkt',
    0.030, -- IDR 400/hour
    300000, -- IDR 300,000/month
    3000000, -- IDR 3,000,000/year
    25,
    TRUE
);

-- Insert Singapore region products
INSERT INTO products (
    name,
    description,
    type,
    specs,
    region,
    hourly_price,
    monthly_price,
    yearly_price,
    stock,
    is_available
) VALUES 
-- Starter VPS Singapore
(
    'VPS Starter SG',
    'Singapore-based VPS perfect for regional applications',
    'vps',
    '{
        "cpu": "1 vCPU",
        "ram": "1 GB",
        "storage": "25 GB NVMe SSD",
        "bandwidth": "1 TB",
        "backup": "Weekly",
        "ddos_protection": true
    }',
    'sg-sin',
    0.009, -- IDR 120/hour
    60000,  -- IDR 60,000/month
    600000, -- IDR 600,000/year
    100,
    TRUE
),
-- Professional VPS Singapore
(
    'VPS Professional SG',
    'High-performance Singapore VPS for business applications',
    'vps',
    '{
        "cpu": "2 vCPU",
        "ram": "4 GB",
        "storage": "80 GB NVMe SSD",
        "bandwidth": "3 TB",
        "backup": "Daily",
        "ddos_protection": true
    }',
    'sg-sin',
    0.018, -- IDR 240/hour
    180000, -- IDR 180,000/month
    1800000, -- IDR 1,800,000/year
    50,
    TRUE
);

-- Insert Tokyo region products
INSERT INTO products (
    name,
    description,
    type,
    specs,
    region,
    hourly_price,
    monthly_price,
    yearly_price,
    stock,
    is_available
) VALUES 
-- Starter VPS Tokyo
(
    'VPS Starter JP',
    'Tokyo-based VPS with low latency to East Asia',
    'vps',
    '{
        "cpu": "1 vCPU",
        "ram": "1 GB",
        "storage": "25 GB NVMe SSD",
        "bandwidth": "1 TB",
        "backup": "Weekly",
        "ddos_protection": true
    }',
    'jp-tky',
    0.010, -- IDR 140/hour
    70000,  -- IDR 70,000/month
    700000, -- IDR 700,000/year
    100,
    TRUE
),
-- Professional VPS Tokyo
(
    'VPS Professional JP',
    'Premium Tokyo VPS for Japanese market applications',
    'vps',
    '{
        "cpu": "2 vCPU",
        "ram": "4 GB",
        "storage": "80 GB NVMe SSD",
        "bandwidth": "3 TB",
        "backup": "Daily",
        "ddos_protection": true
    }',
    'jp-tky',
    0.020, -- IDR 280/hour
    200000, -- IDR 200,000/month
    2000000, -- IDR 2,000,000/year
    50,
    TRUE
);

-- Insert promo codes
INSERT INTO promo_codes (
    code,
    description,
    discount_percentage,
    max_usage,
    start_date,
    end_date,
    is_active
) VALUES 
(
    'WELCOME2024',
    'New Year Special Discount',
    10.00,
    100,
    '2024-01-01 00:00:00',
    '2024-12-31 23:59:59',
    TRUE
),
(
    'STARTUP50',
    'Special 50% off for Startups',
    50.00,
    50,
    '2024-01-01 00:00:00',
    '2024-06-30 23:59:59',
    TRUE
);

-- Insert announcements
INSERT INTO announcements (
    title,
    content,
    type,
    priority,
    is_active,
    start_date,
    end_date
) VALUES 
(
    'Welcome to SakuraCloud!',
    'Experience high-performance cloud hosting with data centers in Indonesia, Singapore, and Japan.',
    'info',
    1,
    TRUE,
    '2024-01-01 00:00:00',
    '2024-12-31 23:59:59'
),
(
    'Scheduled Maintenance Notice',
    'We will be performing system upgrades on January 15, 2024, from 02:00 AM to 04:00 AM (GMT+7). Some services may experience brief interruptions.',
    'maintenance',
    2,
    TRUE,
    '2024-01-10 00:00:00',
    '2024-01-15 04:00:00'
);
