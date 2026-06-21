CREATE TABLE Finger_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    weight FLOAT,
    classes_name VARCHAR(255),
    size VARCHAR(50),
    Farm VARCHAR(255),
    Classes ENUM('25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'), 
    conf FLOAT,
    x1 FLOAT,
    y1 FLOAT,
    x2 FLOAT,
    y2 FLOAT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
