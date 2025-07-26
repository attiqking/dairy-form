-- Migration to add animal_assignments table
-- Run this script to add the missing animal_assignments table

USE sohailpk_dairy_farm_management;

-- Animal assignments table to track which user is assigned to which animal
CREATE TABLE IF NOT EXISTS animal_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    user_id INT NOT NULL,
    start_date DATE NOT NULL DEFAULT (CURDATE()),
    end_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_animal_user (animal_id, user_id),
    INDEX idx_active_assignments (user_id, end_date)
);

-- Optional: Insert some sample data for testing
-- Uncomment the lines below if you want to assign all animals to the first user
-- INSERT INTO animal_assignments (animal_id, user_id, start_date)
-- SELECT a.id, 1, CURDATE() 
-- FROM animals a 
-- WHERE NOT EXISTS (
--     SELECT 1 FROM animal_assignments aa WHERE aa.animal_id = a.id AND aa.end_date IS NULL
-- );
