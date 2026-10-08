--  database tables
DROP TABLE IF EXISTS risk_scores CASCADE;
DROP TABLE IF EXISTS alerts CASCADE;
DROP TABLE IF EXISTS transactions CASCADE;
DROP TABLE IF EXISTS geo_locations CASCADE;
DROP TABLE IF EXISTS merchant_types CASCADE;
DROP TABLE IF EXISTS accounts CASCADE;
DROP TABLE IF EXISTS customers CASCADE;

-- Create tables
CREATE TABLE customers (
    customer_id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts (
    account_id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(customer_id) ON DELETE CASCADE,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_type VARCHAR(20) CHECK (account_type IN ('SAVINGS', 'CHECKING', 'BUSINESS'))
);

CREATE TABLE merchant_types (
    merchant_type_id SERIAL PRIMARY KEY,
    mcc_code VARCHAR(10) UNIQUE NOT NULL,
    category_name VARCHAR(50) NOT NULL,
    risk_weight INT CHECK (risk_weight BETWEEN 1 AND 10)
);

CREATE TABLE geo_locations (
    geo_id SERIAL PRIMARY KEY,
    country_code VARCHAR(3) NOT NULL,
    country_risk VARCHAR(10) CHECK (country_risk IN ('LOW', 'MEDIUM', 'HIGH'))
);

CREATE TABLE transactions (
    txn_id SERIAL PRIMARY KEY,
    account_id INT REFERENCES accounts(account_id),
    merchant_type_id INT REFERENCES merchant_types(merchant_type_id),
    geo_id INT REFERENCES geo_locations(geo_id),
    amount NUMERIC(12, 2) NOT NULL,
    txn_type VARCHAR(20) CHECK (txn_type IN ('CARD_PURCHASE', 'CASH_WITHDRAWAL', 'TRANSFER_OUT', 'CASH_DEPOSIT')),
    txn_time TIMESTAMP NOT NULL
);

CREATE TABLE alerts (
    alert_id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(customer_id),
    txn_id INT REFERENCES transactions(txn_id),
    rule_code VARCHAR(30) NOT NULL,
    severity VARCHAR(10) CHECK (severity IN ('LOW', 'MEDIUM', 'HIGH')),
    status VARCHAR(20) DEFAULT 'OPEN' CHECK (status IN ('OPEN', 'UNDER_REVIEW', 'ESCALATED', 'CLOSED')),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE risk_scores (
    score_id SERIAL PRIMARY KEY,
    customer_id INT REFERENCES customers(customer_id),
    score_date DATE NOT NULL,
    velocity_score INT DEFAULT 0,
    mcc_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    risk_band VARCHAR(10) CHECK (risk_band IN ('LOW', 'MEDIUM', 'HIGH')),
    CONSTRAINT unique_customer_score_date UNIQUE (customer_id, score_date)
);

-- Insert seed data
INSERT INTO customers (full_name) VALUES ('John Doe'), ('Jane Smith'), ('Alice Johnson');
INSERT INTO accounts (customer_id, account_number, account_type) VALUES (1, 'ACC1001', 'CHECKING'), (2, 'ACC1002', 'SAVINGS'), (3, 'ACC1003', 'BUSINESS');
INSERT INTO merchant_types (mcc_code, category_name, risk_weight) VALUES ('7995', 'Gambling', 9), ('5944', 'Jewelry', 8), ('5411', 'Grocery', 2);
INSERT INTO geo_locations (country_code, country_risk) VALUES ('USA', 'LOW'), ('PRK', 'HIGH'), ('CAN', 'LOW');

INSERT INTO transactions (account_id, merchant_type_id, geo_id, amount, txn_type, txn_time) VALUES
(1, 1, 1, 12000.00, 'TRANSFER_OUT', NOW()),
(2, 2, 2, 8500.00, 'CARD_PURCHASE', NOW() - INTERVAL '1 hour'),
(3, 3, 3, 150.00, 'CARD_PURCHASE', NOW() - INTERVAL '2 hours');