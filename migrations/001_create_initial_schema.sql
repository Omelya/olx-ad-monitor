CREATE TABLE IF NOT EXISTS search_filters (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    filters JSON NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_checked DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_active_filters (is_active),
    INDEX idx_last_checked (last_checked)
);

CREATE TABLE IF NOT EXISTS listings (
    id VARCHAR(36) PRIMARY KEY,
    external_id VARCHAR(50) NOT NULL UNIQUE,
    filter_id VARCHAR(36) NULL,
    title TEXT NOT NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'UAH',
    url TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    images JSON NULL,
    published_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_external_id (external_id),
    INDEX idx_filter_id (filter_id),
    INDEX idx_active_listings (is_active),
    INDEX idx_price (price),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (filter_id) REFERENCES search_filters(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS price_history (
    id VARCHAR(36) PRIMARY KEY,
    listing_id VARCHAR(36) NOT NULL,
    old_price DECIMAL(12,2) NOT NULL,
    new_price DECIMAL(12,2) NOT NULL,
    changed_at DATETIME NOT NULL,
    INDEX idx_listing_id (listing_id),
    INDEX idx_changed_at (changed_at),
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_filters (
    filter_id VARCHAR(36) NOT NULL,
    chat_id BIGINT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT NOW(),
    PRIMARY KEY(filter_id, chat_id),
    FOREIGN KEY (filter_id) REFERENCES search_filters(id) ON DELETE CASCADE
);
