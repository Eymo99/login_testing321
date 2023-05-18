CREATE TABLE user_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  timestamp DATETIME NOT NULL,
  activity VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  operating_system VARCHAR(255) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users (id)
);