-- =====================================
-- Users table (all columns from migrations)
-- =====================================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    access_key VARCHAR(50) UNIQUE,
    is_filter BOOLEAN DEFAULT TRUE,
    can_edit BOOLEAN DEFAULT TRUE,
    can_delete BOOLEAN DEFAULT TRUE,
    can_reply BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    is_confetti_animation BOOLEAN DEFAULT TRUE,
    tenor_key VARCHAR(100),
    tz VARCHAR(70) DEFAULT 'Asia/Jakarta',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================
-- Comments table (all columns from migrations) 
-- =====================================
CREATE TABLE IF NOT EXISTS comments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    uuid VARCHAR(255) UNIQUE,
    name VARCHAR(255),
    presence BOOLEAN DEFAULT FALSE,
    comment TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    parent_id VARCHAR(255) REFERENCES comments(uuid) ON DELETE CASCADE,
    own VARCHAR(255) UNIQUE,
    is_admin BOOLEAN DEFAULT FALSE,
    gif_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================
-- Likes table (all columns from migrations)
-- =====================================
CREATE TABLE IF NOT EXISTS likes (
    id SERIAL PRIMARY KEY,
    uuid VARCHAR(255),
    comment_id VARCHAR(255) REFERENCES comments(uuid) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id);
CREATE INDEX IF NOT EXISTS idx_comments_uuid ON comments(uuid);
CREATE INDEX IF NOT EXISTS idx_comments_parent_id ON comments(parent_id);
CREATE INDEX IF NOT EXISTS idx_likes_comment_id ON likes(comment_id);
CREATE INDEX IF NOT EXISTS idx_likes_user_id ON likes(user_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_access_key ON users(access_key);
