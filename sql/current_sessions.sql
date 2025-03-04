CREATE TABLE IF NOT EXISTS current_sessions (
    date DATE PRIMARY KEY,
    count INT DEFAULT 0
);