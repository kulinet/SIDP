CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (`key`, `value`) VALUES 
('chatbot_model', 'gpt-5.4-mini'),
('chatbot_api_key', 'sk-PAknjOEGubNCIaULNnJdYHxwV1fCiqELWQ2jzPSBfGqc2Byf'),
('chatbot_api_url', 'https://api.ai.cc/v1/chat/completions')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);
