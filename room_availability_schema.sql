-- Room availability feature schema/migration

CREATE TABLE IF NOT EXISTS room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    total_rooms INT NOT NULL DEFAULT 0,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00
);

ALTER TABLE bookings
    ADD COLUMN IF NOT EXISTS room_type_id INT DEFAULT NULL;

-- Useful for availability lookups and overlap counting
CREATE INDEX idx_bookings_room_type_dates
    ON bookings (room_type_id, check_in, check_out, status);
