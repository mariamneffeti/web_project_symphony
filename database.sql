Drop database web_project_symphony;
CREATE DATABASE IF NOT EXISTS web_project_symphony;
USE web_project_symphony;

-- Users & Authentication


CREATE TABLE users (
                       id INT PRIMARY KEY AUTO_INCREMENT,
                       first_name VARCHAR(50) NOT NULL,
                       last_name VARCHAR(50) NOT NULL,
                       email VARCHAR(255) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       role ENUM('normal', 'employee', 'company') NOT NULL,
                       image VARCHAR(255),
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE companies (
                           id INT PRIMARY KEY AUTO_INCREMENT,
                           user_id INT UNIQUE NOT NULL,
                           company_name VARCHAR(255) NOT NULL,
                           industry VARCHAR(100),
                           address TEXT,
                           phone VARCHAR(50),
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE articles (
                          id INT PRIMARY KEY AUTO_INCREMENT,
                          company_id INT NOT NULL,
                          author_name VARCHAR(255) NOT NULL,
                          title VARCHAR(100),
                          category VARCHAR(100),
                          ar_date DATE,
                          ar_description TEXT,
                          link VARCHAR(250),
                          ar_image VARCHAR(250),
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);


CREATE TABLE employees (
                           id INT PRIMARY KEY AUTO_INCREMENT,
                           user_id INT NOT NULL,
                           company_id INT NOT NULL,
                           first_name VARCHAR(100) NOT NULL,
                           last_name VARCHAR(100) NOT NULL,
                           position VARCHAR(100),
                           department VARCHAR(100),
                           hire_date DATE,
                           email VARCHAR(250),
                           salary DECIMAL(10, 2),
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                           FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Clients Management

CREATE TABLE clients (
                         id INT PRIMARY KEY AUTO_INCREMENT,
                         company_id INT NOT NULL,
                         client_name VARCHAR(255) NOT NULL,
                         email VARCHAR(255),
                         phone VARCHAR(50),
                         address TEXT,
                         client_type ENUM('B2B', 'B2C', 'B2G') DEFAULT 'B2C',
                         status ENUM('Active', 'Inactive', 'Prospect') DEFAULT 'Active',
                         total_spent DECIMAL(12, 2) DEFAULT 0.00,
                         last_purchase_date DATE,
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                         FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                         INDEX idx_company_client (company_id, client_name)
);

-- Sales Management

CREATE TABLE products (
                          id INT PRIMARY KEY AUTO_INCREMENT,
                          company_id INT NOT NULL,
                          product_name VARCHAR(255) NOT NULL,
                          sku VARCHAR(100),
                          category VARCHAR(100),
                          price DECIMAL(10, 2) NOT NULL,
                          stock_quantity INT DEFAULT 0,
                          min_threshold INT DEFAULT 20,
                          description TEXT,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE sales (
                       id INT PRIMARY KEY AUTO_INCREMENT,
                       transaction_id VARCHAR(50) UNIQUE NOT NULL,
                       company_id INT NOT NULL,
                       employee_id INT,
                       client_id INT NOT NULL,
                       sale_date DATE NOT NULL,
                       subtotal DECIMAL(12, 2) NOT NULL,
                       discount DECIMAL(12, 2) DEFAULT 0.00,
                       tax DECIMAL(12, 2) DEFAULT 0.00,
                       total_amount DECIMAL(12, 2) NOT NULL,
                       payment_method ENUM('Cash', 'Credit Card', 'Bank Transfer', 'Mobile Payment') NOT NULL,
                       payment_status ENUM('Paid', 'Pending') DEFAULT 'Pending',
                       notes TEXT,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                       FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                       FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL,
                       FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                       INDEX idx_transaction (transaction_id),
                       INDEX idx_company_date (company_id, sale_date)
);

CREATE TABLE sale_items (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            sale_id INT NOT NULL,
                            product_id INT,
                            product_name VARCHAR(255) NOT NULL,
                            quantity INT NOT NULL,
                            unit_price DECIMAL(10, 2) NOT NULL,
                            total_price DECIMAL(12, 2) NOT NULL,
                            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- expenses management
CREATE TABLE expenses (
                          id INT PRIMARY KEY AUTO_INCREMENT,
                          company_id INT NOT NULL,
                          expense_date DATE NOT NULL,
                          category ENUM('Rent', 'Salary', 'Tools', 'Marketing', 'Supply' , 'Other') NOT NULL default 'Other',
                          amount DECIMAL(12, 2) NOT NULL,
                          description TEXT,
                          FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- services management
CREATE TABLE services (
                          id INT PRIMARY KEY AUTO_INCREMENT,
                          company_id INT NOT NULL,
                          service_name VARCHAR(255) NOT NULL,
                          description TEXT,
                          base_price DECIMAL(10, 2) NOT NULL,
                          FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
CREATE TABLE service_sale_items (
                                    id INT PRIMARY KEY AUTO_INCREMENT,
                                    sale_id INT NOT NULL,
                                    service_id INT,
                                    service_name VARCHAR(255) NOT NULL,
                                    quantity_hours DECIMAL(10, 2) NOT NULL DEFAULT 1,
                                    unit_price DECIMAL(10, 2) NOT NULL,
                                    total_price DECIMAL(12, 2) NOT NULL,
                                    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                                    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);
-- Invoices

CREATE TABLE invoices (
                          id INT PRIMARY KEY AUTO_INCREMENT,
                          invoice_number VARCHAR(50) UNIQUE NOT NULL,
                          sale_id INT UNIQUE NOT NULL,
                          issue_date DATE NOT NULL,
                          due_date DATE,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

-- Job Offers Management

CREATE TABLE IF NOT EXISTS job_icons (
                                         id INT PRIMARY KEY AUTO_INCREMENT,
                                         icon_name VARCHAR(50) NOT NULL,
    bootstrap_class VARCHAR(100) NOT NULL,
    default_color VARCHAR(7) DEFAULT '#388087'
    );

CREATE TABLE IF NOT EXISTS job_offers (
                                          id INT PRIMARY KEY AUTO_INCREMENT,
                                          company_id INT NOT NULL,
                                          icon_id INT,
                                          title VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    type ENUM('Full-time', 'Contract', 'Urgent', 'Part-time', 'Internship') NOT NULL,
    category ENUM('tech', 'design', 'data', 'marketing', 'finance', 'hr') NOT NULL,
    salary_min INT,
    salary_max INT,
    experience_level ENUM('junior', 'mid', 'senior', 'lead') NOT NULL,
    description TEXT,
    tags TEXT,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (icon_id) REFERENCES job_icons(id) ON DELETE SET NULL
    );
CREATE TABLE cv_applications (
                                 id INT PRIMARY KEY AUTO_INCREMENT,
                                 company_id INT NOT NULL,
                                 offre_id INT,
                                 first_name VARCHAR(100),
                                 last_name VARCHAR(100),
                                 email VARCHAR(255),
                                 phone VARCHAR(50),
                                 nationality VARCHAR(50),
                                 address TEXT,
                                 linkedin VARCHAR(255),
                                 file_path VARCHAR(255) NOT NULL,
                                 status ENUM('Pending', 'Reviewed', 'Accepted', 'Rejected') DEFAULT 'Pending',
                                 submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                 FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                                 FOREIGN KEY (offre_id) REFERENCES job_offers (id) ON DELETE SET NULL
);

-- Meetings & Calendar
CREATE TABLE meetings (
                          id INT PRIMARY KEY AUTO_INCREMENT,
                          company_id INT NOT NULL,
                          title VARCHAR(255) NOT NULL,
                          meeting_date DATE NOT NULL,
                          meeting_time TIME NOT NULL,
                          meet_link VARCHAR(255),
                          notes TEXT,
                          status ENUM('scheduled','done','cancelled') DEFAULT 'scheduled',
                          FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
CREATE TABLE meeting_employees (
                                   id INT PRIMARY KEY AUTO_INCREMENT,
                                   meeting_id INT NOT NULL,
                                   employee_id INT NOT NULL,
                                   FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
                                   FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Sample Data


-- Insert sample company user
INSERT INTO users (first_name, last_name, email, password, role, image) VALUES
                                                                            ('Yasmine', 'Bouziri', 'admin@techcorp.tn', '$2y$10$ZFsT1Sb/cCT3fiPe35fiD.nWI1DHNky1fwpyy3ko.sWkgDGOkx4Eu', 'company', 'profiles/yasmine.jpg'),
                                                                            ('Sarah', 'Connor', 'employee@demo.com', '$2y$10$ZFsT1Sb/cCT3fiPe35fiD.nWI1DHNky1fwpyy3ko.sWkgDGOkx4Eu', 'c', 'profiles/sarah.jpg'),
                                                                            ('Meriam', 'Cherif', 'meriam.cherif2005@gmail.com', '$2y$10$ZFsT1Sb/cCT3fiPe35fiD.nWI1DHNky1fwpyy3ko.sWkgDGOkx4Eu', 'normal', 'profiles/meriam.jpg');

-- Insert sample Company
INSERT INTO companies (user_id, company_name, industry, address, phone) VALUES
    (1, 'TechCorp Solutions', 'Technology', 'Technopark El Ghazala, Ariana', '+216 71 123 456');

-- Insert sample Employee
INSERT INTO employees (user_id, company_id, first_name, last_name, position, department, hire_date, salary) VALUES
    (2, 1, 'Sarah', 'Connor', 'Sales Manager', 'Sales', '2023-01-15', 45000.00);

-- Insert sample clients
INSERT INTO clients (company_id, client_name, email, phone, client_type, status, total_spent, last_purchase_date) VALUES
                                                                                                                      (1, 'Acme Corporation', 'contact@acme.com', '+216-71-234-567', 'B2B', 'Active', 15420.00, '2024-01-15'),
                                                                                                                      (1, 'John Smith LLC', 'john@company.com', '+216-71-345-678', 'B2B', 'Active', 8750.50, '2024-01-10'),
                                                                                                                      (1, 'Global Industries', 'info@global.com', '+216-71-456-789', 'B2B', 'Active', 23100.00, '2024-01-20'),
                                                                                                                      (1, 'Sincere Risk Corp', 'warning@riskcorp.com', '+216-71-999-000', 'B2B', 'Inactive', 200.00, '2023-10-01'),
                                                                                                                      (1, 'Meriam Company', 'meriam.cherif2005@gmail.com', '+216-71-999-111', 'B2B', 'Inactive', 200.00, '2025-10-01');
-- 🟢 LOW RISK CLIENTS (recent activity)

INSERT INTO clients (company_id, client_name, email, phone, client_type, status, total_spent, last_purchase_date) VALUES
                                                                                                                      (1, 'Alpha Tech', 'alpha@tech.com', '+216-70-000-001', 'B2B', 'Active', 25000.00, CURDATE()),
                                                                                                                      (1, 'Beta Solutions', 'beta@solutions.com', '+216-70-000-002', 'B2B', 'Active', 18000.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
                                                                                                                      (1, 'Gamma Corp', 'gamma@corp.com', '+216-70-000-003', 'B2B', 'Active', 32000.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
                                                                                                                      (1, 'Delta Systems', 'delta@systems.com', '+216-70-000-004', 'B2B', 'Active', 14000.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY));


INSERT INTO clients (company_id, client_name, email, phone, client_type, status, total_spent, last_purchase_date) VALUES
                                                                                                                      (1, 'Epsilon Group', 'epsilon@group.com', '+216-70-000-005', 'B2B', 'Active', 9000.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
                                                                                                                      (1, 'Zeta Industries', 'zeta@ind.com', '+216-70-000-006', 'B2B', 'Active', 7000.00, DATE_SUB(CURDATE(), INTERVAL 40 DAY)),
                                                                                                                      (1, 'Eta Services', 'eta@services.com', '+216-70-000-007', 'B2C', 'Active', 5000.00, DATE_SUB(CURDATE(), INTERVAL 45 DAY)),
                                                                                                                      (1, 'Theta Consulting', 'theta@consult.com', '+216-70-000-008', 'B2B', 'Active', 11000.00, DATE_SUB(CURDATE(), INTERVAL 35 DAY));


INSERT INTO clients (company_id, client_name, email, phone, client_type, status, total_spent, last_purchase_date) VALUES
                                                                                                                      (1, 'Iota Holdings', 'iota@hold.com', '+216-70-000-009', 'B2B', 'Inactive', 4000.00, DATE_SUB(CURDATE(), INTERVAL 90 DAY)),
                                                                                                                      (1, 'Kappa Ventures', 'kappa@ventures.com', '+216-70-000-010', 'B2B', 'Inactive', 3000.00, DATE_SUB(CURDATE(), INTERVAL 120 DAY)),
                                                                                                                      (1, 'Lambda LLC', 'lambda@llc.com', '+216-70-000-011', 'B2C', 'Inactive', 2500.00, DATE_SUB(CURDATE(), INTERVAL 150 DAY)),
                                                                                                                      (1, 'Mu Enterprises', 'mu@enterprises.com', '+216-70-000-012', 'B2B', 'Inactive', 6000.00, DATE_SUB(CURDATE(), INTERVAL 80 DAY));
-- Insert sample products
INSERT INTO products (company_id, product_name, sku, category, price, stock_quantity, description) VALUES
                                                                                                       (1, 'Cloud CRM Pro', 'CRM-001', 'Software', 29.99, 999, 'Monthly subscription with full access'),
                                                                                                       (1, 'Security Suite', 'SEC-9021', 'Security', 150.00, 250, 'Advanced firewall & antivirus protection'),
                                                                                                       (1, 'Premium Support Pack', 'SUP-300', 'Support', 500.00, 100, '24/7 premium support & consulting'),
                                                                                                       (1, 'Enterprise Router', 'HW-450', 'Hardware', 299.00, 50, 'High-speed business router'),
                                                                                                       (1, 'Office Suite License', 'LIC-200', 'License', 450.00, 500, '5-user business license');
-- Insert sample articles
INSERT INTO articles (company_id, author_name, title, category, ar_date, ar_description, link, ar_image) VALUES
                                                                                                             (1, 'Alice Johnson', 'How Cloud CRM is Revolutionizing Businesses', 'Technology', '2024-01-05', 'An in-depth look at how cloud-based CRM solutions streamline sales and customer management.', 'https://techcorp.com/blog/cloud-crm
', "../image/hhh.jpg"),
                                                                                                             (1, 'Bob Smith', 'Top 5 Cybersecurity Tips for SMEs', 'Security', '2024-01-12', 'Practical tips for small and medium enterprises to enhance their cybersecurity posture.', 'https://techcorp.com/blog/cybersecurity-tips
', "../image/hhh.jpg"),
                                                                                                             (1, 'Carol Lee', 'Maximizing ROI with Premium Support Packages', 'Business', '2024-01-20', 'Learn how investing in premium support services can increase efficiency and customer satisfaction.', 'https://techcorp.com/blog/premium-support
', "../image/hhh.jpg"),
                                                                                                             (1, 'David Nguyen', 'Enterprise Networking: Choosing the Right Router', 'Hardware', '2024-01-25', 'A comprehensive guide to selecting enterprise routers for high-speed business networks.', 'https://techcorp.com/blog/enterprise-router
', "../image/hhh.jpg"),
                                                                                                             (1, 'Eva Martinez', 'The Future of Office Software Licenses', 'Software', '2024-02-01', 'Exploring trends in software licensing for businesses and how to optimize costs.', 'https://techcorp.com/blog/office-software-licenses
', "../image/hh.jpg");
-- Insert sample sales
INSERT INTO sales (transaction_id, company_id, employee_id, client_id, sale_date, subtotal, discount, tax, total_amount, payment_method, payment_status) VALUES
                                                                                                                                                             ('TX-2024-001', 1, 1, 1, '2024-01-15', 150.00, 15.00, 13.50, 148.50, 'Credit Card', 'Paid'),
                                                                                                                                                             ('TX-2024-002', 1, 1, 2, '2024-01-10', 500.00, 0.00, 50.00, 550.00, 'Bank Transfer', 'Paid'),
                                                                                                                                                             ('TX-2024-003', 1, 1, 3, '2024-01-20', 750.00, 50.00, 70.00, 770.00, 'Cash', 'Paid');

-- Insert sample sale items
INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price) VALUES
                                                                                                  (1, 2, 'Security Suite', 1, 150.00, 135.00),
                                                                                                  (2, 3, 'Premium Support Pack', 1, 500.00, 500.00),
                                                                                                  (3, 4, 'Enterprise Router', 2, 299.00, 568.10),
                                                                                                  (3, 5, 'Office Suite License', 1, 450.00, 405.00);

-- Insert sample expenses
INSERT INTO expenses (company_id, expense_date, category, amount, description) VALUES
                                                                                   (1, '2026-01-05', 'Rent', 1200.00, 'Office rent - January'),
                                                                                   (1, '2026-01-10', 'Salary', 2500.00, 'Employee salaries'),
                                                                                   (1, '2026-01-12', 'Tools', 150.00, 'Subscription to design software'),
                                                                                   (1, '2026-01-15', 'Marketing', 300.00, 'Facebook ads campaign'),
                                                                                   (1, '2026-01-18', 'Supply', 200.00, 'Office supplies and materials'),
                                                                                   (1, '2026-02-03', 'Rent', 1200.00, 'Office rent - February'),
                                                                                   (1, '2026-02-08', 'Salary', 2600.00, 'Employee salaries'),
                                                                                   (1, '2026-02-11', 'Tools', 180.00, 'Cloud hosting subscription'),
                                                                                   (1, '2026-02-14', 'Marketing', 400.00, 'Google Ads campaign'),
                                                                                   (1, '2026-02-20', 'Other', 90.00, 'Miscellaneous expenses'),
                                                                                   (1, '2026-03-02', 'Rent', 1200.00, 'Office rent - March'),
                                                                                   (1, '2026-03-07', 'Salary', 2550.00, 'Employee salaries'),
                                                                                   (1, '2026-03-10', 'Supply', 220.00, 'Printer ink and paper'),
                                                                                   (1, '2026-03-15', 'Marketing', 350.00, 'Instagram promotion'),
                                                                                   (1, '2026-03-22', 'Tools', 160.00, 'Project management tool'),
                                                                                   (1, '2026-04-01', 'Rent', 1200.00, 'Office rent - April'),
                                                                                   (1, '2026-04-05', 'Salary', 2700.00, 'Employee salaries'),
                                                                                   (1, '2026-04-09', 'Supply', 180.00, 'Cleaning and office materials'),
                                                                                   (1, '2026-04-12', 'Other', 120.00, 'Transport and small expenses');

-- insert services
INSERT INTO services (company_id, service_name, description, base_price) VALUES
                                                                             (1, 'Custom Software Development', 'Bespoke coding and feature development.', 120.00),
                                                                             (1, 'Network Infrastructure Audit', 'Comprehensive security and performance review.', 500.00),
                                                                             (1, 'Cloud Migration Support', 'Assistance moving local data to Azure/AWS.', 85.00),
                                                                             (1, 'On-site Technical Training', 'Staff training for new software suites.', 150.00);

-- insert service_sale_items
INSERT INTO service_sale_items (sale_id, service_id, service_name, quantity_hours, unit_price, total_price) VALUES
    (1, 3, 'Cloud Migration Support', 4.00, 85.00, 340.00);

INSERT INTO service_sale_items (sale_id, service_id, service_name, quantity_hours, unit_price, total_price) VALUES
    (2, 4, 'On-site Technical Training', 2.00, 150.00, 300.00);

INSERT INTO service_sale_items (sale_id, service_id, service_name, quantity_hours, unit_price, total_price) VALUES
    (3, 1, 'Custom Software Development', 10.50, 120.00, 1260.00);
-- Insert invoices
INSERT INTO invoices (invoice_number, sale_id, issue_date, due_date) VALUES
                                                                         ('INV-2024-001', 1, '2024-01-15', '2024-02-15'),
                                                                         ('INV-2024-002', 2, '2024-01-10', '2024-02-10'),
                                                                         ('INV-2024-003', 3, '2024-01-20', '2024-02-20');

-- Insert job icons
INSERT INTO job_icons (icon_name, bootstrap_class, default_color) VALUES
                                                                      ('Stripe', 'bi-stripe', '#635bff'),
                                                                      ('Nvidia', 'bi-nvidia', '#76b900'),
                                                                      ('Finance/Bank', 'bi-bank', '#0f172a'),
                                                                      ('Code/Tech', 'bi-code-slash', '#388087'),
                                                                      ('Data/Charts', 'bi-bar-chart-fill', '#8d9b6a'),
                                                                      ('Marketing/News', 'bi-megaphone', '#e23e5a'),
                                                                      ('Design/Art', 'bi-brush', '#a855f7'),
                                                                      ('Mobile/Phone', 'bi-phone', '#555555'),
                                                                      ('Cloud/Azure', 'bi-cloud', '#0078d4'),
                                                                      ('AI/CPU', 'bi-cpu', '#10b981'),
                                                                      ('Management', 'bi-kanban', '#0f172a'),
                                                                      ('DevOps/Gear', 'bi-gear-wide-connected', '#388087'),
                                                                      ('HR/People', 'bi-people', '#8d9b6a'),
                                                                      ('Audit/Check', 'bi-clipboard-check', '#de7200'),
                                                                      ('Growth/Graph', 'bi-graph-up', '#f97316'),
                                                                      ('Stack/Fullstack', 'bi-stack', '#0f172a'),
                                                                      ('Badge/ID', 'bi-person-badge', '#f97316'),
                                                                      ('Security/Shield', 'bi-shield-check', '#388087'),
                                                                      ('Play/Motion', 'bi-play-circle', '#ec4899'),
                                                                      ('Lock/Cyber', 'bi-shield-lock', '#ef4444'),
                                                                      ('Briefcase', 'bi-briefcase', '#8d9b6a');

-- Insert sample job offers
INSERT INTO job_offers (company_id, icon_id, title, location, type, category, salary_min, salary_max, experience_level, tags, description) VALUES
                                                                                                                                               (1, 1, 'Backend Engineer', 'Remote, Europe', 'Full-time', 'tech', 4000, 4500, 'senior', 'Ruby, Go, API', 'Build financial infrastructure.'),
                                                                                                                                               (1, 21, 'UI/UX Designer', 'Tunis', 'Contract', 'design', 3000, 3700, 'mid', 'Figma, Prototyping', 'Design beautiful experiences.'),
                                                                                                                                               (1, 2, 'AI Research Lead', 'Germany', 'Urgent', 'data', 2000, 3500, 'lead', 'PyTorch, CUDA', 'Push boundaries of deep learning.'),
                                                                                                                                               (1, 4, 'Frontend Developer', 'Tunis', 'Full-time', 'tech', 2500, 3200, 'mid', 'React, TypeScript', 'Build modern web interfaces.'),
                                                                                                                                               (1, 5, 'Data Analyst', 'Sfax', 'Full-time', 'data', 1800, 2400, 'junior', 'SQL, Python', 'Transform raw data into insights.'),
                                                                                                                                               (1, 11, 'Product Manager', 'Remote', 'Full-time', 'tech', 3500, 4800, 'senior', 'Agile, Roadmap', 'Own the product roadmap.'),
                                                                                                                                               (1, 6, 'Social Media Manager', 'Tunis', 'Contract', 'marketing', 1500, 2200, 'junior', 'Meta, Content', 'Grow social presence.'),
                                                                                                                                               (1, 12, 'DevOps Engineer', 'Tunis', 'Full-time', 'tech', 3000, 4000, 'mid', 'Docker, K8s', 'Optimize deployment pipelines.'),
                                                                                                                                               (1, 7, 'Graphic Designer', 'Tunis', 'Part-time', 'design', 1200, 1800, 'junior', 'Illustrator, Photoshop', 'Create visual assets.'),
                                                                                                                                               (1, 3, 'Financial Analyst', 'Tunis', 'Full-time', 'finance', 2200, 3000, 'mid', 'Excel, Risk', 'Analyze financial data.'),
                                                                                                                                               (1, 13, 'HR Business Partner', 'Tunis', 'Full-time', 'hr', 2000, 2800, 'mid', 'Recruitment, L&D', 'Partner with business leaders.'),
                                                                                                                                               (1, 8, 'Mobile Developer (iOS)', 'Tunis', 'Full-time', 'tech', 2800, 3600, 'mid', 'Swift, SwiftUI', 'Build sleek iOS applications.'),
                                                                                                                                               (1, 21, 'Content Strategist', 'Remote', 'Contract', 'marketing', 1400, 2000, 'junior', 'SEO, Copywriting', 'Craft content strategies.'),
                                                                                                                                               (1, 9, 'Cloud Architect', 'Remote', 'Full-time', 'tech', 5000, 6000, 'lead', 'Azure, Terraform', 'Design cloud infrastructure.'),
                                                                                                                                               (1, 14, 'Audit Intern', 'Tunis', 'Internship', 'finance', 600, 900, 'junior', 'Excel, Audit', 'Support audit teams.'),
                                                                                                                                               (1, 10, 'Machine Learning Engineer', 'Tunis', 'Full-time', 'data', 4000, 5500, 'senior', 'TensorFlow, Python', 'Develop ML models.'),
                                                                                                                                               (1, 15, 'Marketing Analyst', 'Tunis', 'Full-time', 'marketing', 1800, 2500, 'junior', 'Google Ads, GA4', 'Analyze campaign performance.'),
                                                                                                                                               (1, 16, 'Full-Stack Developer', 'Tunis', 'Full-time', 'tech', 2600, 3400, 'mid', 'Spring, Angular', 'Maintain core banking platforms.'),
                                                                                                                                               (1, 17, 'Talent Acquisition Specialist', 'Tunis', 'Contract', 'hr', 1700, 2300, 'junior', 'LinkedIn, ATS', 'Lead end-to-end recruitment.'),
                                                                                                                                               (1, 18, 'Risk Manager', 'Tunis', 'Full-time', 'finance', 2800, 3800, 'senior', 'Basel III, Risk', 'Oversee risk frameworks.'),
                                                                                                                                               (1, 19, 'Motion Designer', 'Tunis', 'Part-time', 'design', 1400, 2100, 'mid', 'After Effects, Lottie', 'Produce motion graphics.'),
                                                                                                                                               (1, 20, 'Cybersecurity Analyst', 'Tunis', 'Urgent', 'tech', 2500, 3500, 'mid', 'SOC, Pentest', 'Defend critical infrastructure.'),
                                                                                                                                               (1, 21, 'Business Developer', 'Tunis', 'Full-time', 'marketing', 2000, 3000, 'mid', 'B2B, Negotiation', 'Convert new opportunities.'),
                                                                                                                                               (1, 10, 'Embedded Systems Engineer', 'Sousse', 'Full-time', 'tech', 3200, 4200, 'senior', 'C, RTOS, CAN Bus', 'Develop automotive systems.');

INSERT INTO cv_applications
(company_id, offre_id, first_name, last_name, email, phone, nationality, address, linkedin, file_path, status, submitted_at)
VALUES
-- 🟡 Pending
(1, 1, 'Youssef', 'Ben Ali', 'youssef.benali@gmail.com', '+216-55-123-001', 'Tunisian', 'Tunis, Centre Ville', 'https://linkedin.com/in/youssef
', 'uploads/cv/youssef.pdf', 'Pending', NOW()),
(1, 4, 'Amira', 'Trabelsi', 'amira.trabelsi@gmail.com', '+216-55-123-002', 'Tunisian', 'Ariana', 'https://linkedin.com/in/amira
', 'uploads/cv/amira.pdf', 'Pending', NOW()),
-- 🔵 Reviewed
(1, 2, 'Karim', 'Gharbi', 'karim.gharbi@gmail.com', '+216-55-123-003', 'Tunisian', 'Sfax', 'https://linkedin.com/in/karim
', 'uploads/cv/karim.pdf', 'Reviewed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 5, 'Salma', 'Jaziri', 'salma.jaziri@gmail.com', '+216-55-123-004', 'Tunisian', 'Sousse', 'https://linkedin.com/in/salma
', 'uploads/cv/salma.pdf', 'Reviewed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
-- 🟢 Accepted
(1, 3, 'Ahmed', 'Bouazizi', 'ahmed.bouazizi@gmail.com', '+216-55-123-005', 'Tunisian', 'Monastir', 'https://linkedin.com/in/ahmed
', 'uploads/cv/ahmed.pdf', 'Accepted', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 8, 'Leila', 'Mansouri', 'leila.mansouri@gmail.com', '+216-55-123-006', 'Tunisian', 'Nabeul', 'https://linkedin.com/in/leila
', 'uploads/cv/leila.pdf', 'Accepted', DATE_SUB(NOW(), INTERVAL 6 DAY)),
-- 🔴 Rejected
(1, 6, 'Hatem', 'Kefi', 'hatem.kefi@gmail.com', '+216-55-123-007', 'Tunisian', 'Bizerte', 'https://linkedin.com/in/hatem
', 'uploads/cv/hatem.pdf', 'Rejected', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 7, 'Nour', 'Chaari', 'nour.chaari@gmail.com', '+216-55-123-008', 'Tunisian', 'Gabes', 'https://linkedin.com/in/nour
', 'uploads/cv/nour.pdf', 'Rejected', DATE_SUB(NOW(), INTERVAL 8 DAY)),
-- 🌍 International candidates
(1, 1, 'Lucas', 'Martin', 'lucas.martin@gmail.com', '+33-612-000-001', 'French', 'Paris, France', 'https://linkedin.com/in/lucas
', 'uploads/cv/lucas.pdf', 'Pending', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 3, 'Sara', 'Lopez', 'sara.lopez@gmail.com', '+34-611-000-002', 'Spanish', 'Madrid, Spain', 'https://linkedin.com/in/sara
', 'uploads/cv/sara.pdf', 'Reviewed', DATE_SUB(NOW(), INTERVAL 4 DAY)),
-- 🧠 Tech heavy profiles
(1, 1, 'Omar', 'Zitouni', 'omar.zitouni@gmail.com', '+216-55-123-009', 'Tunisian', 'Tunis', 'https://linkedin.com/in/omar
', 'uploads/cv/omar.pdf', 'Pending', NOW()),
(1, 4, 'Rania', 'Ben Youssef', 'rania.benyoussef@gmail.com', '+216-55-123-010', 'Tunisian', 'La Marsa', 'https://linkedin.com/in/rania
', 'uploads/cv/rania.pdf', 'Reviewed', DATE_SUB(NOW(), INTERVAL 2 DAY)),
-- 🎨 Design profiles
(1, 2, 'Mehdi', 'Hamdi', 'mehdi.hamdi@gmail.com', '+216-55-123-011', 'Tunisian', 'Sousse', 'https://linkedin.com/in/mehdi
', 'uploads/cv/mehdi.pdf', 'Accepted', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(1, 9, 'Aya', 'Khlifi', 'aya.khlifi@gmail.com', '+216-55-123-012', 'Tunisian', 'Tunis', 'https://linkedin.com/in/aya
', 'uploads/cv/aya.pdf', 'Pending', NOW());

-- Insert sample meetings
INSERT INTO meetings (company_id, title, meeting_date, meeting_time, meet_link, notes, status) VALUES

-- 🟢 Upcoming meetings
(1, 'Weekly Team Sync', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00',
 'https://meet.google.com/team-sync',
 'Discuss weekly progress and blockers',
 'scheduled'),

(1, 'Client Strategy Meeting', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:30:00',
 'https://zoom.us/client-strategy',
 'Presentation for Acme Corporation',
 'scheduled'),

(1, 'Backend Architecture Review', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00',
 'https://teams.microsoft.com/backend',
 'Review API structure and DB optimization',
 'scheduled'),

(1, 'Sales Pipeline Review', DATE_ADD(CURDATE(), INTERVAL 4 DAY), '11:00:00',
 'https://meet.google.com/sales-review',
 'Analyze leads and conversions',
 'scheduled'),

-- 🟡 Today
(1, 'Urgent Client Call', CURDATE(), '15:00:00',
 'https://zoom.us/urgent-call',
 'Client reported an issue with delivery',
 'scheduled'),

-- 🔵 Completed meetings
(1, 'Marketing Campaign Review', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00',
 'https://meet.google.com/marketing-review',
 'Review campaign performance',
 'done'),

(1, 'HR Interview - Developer', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '16:00:00',
 'https://teams.microsoft.com/hr-interview',
 'Candidate evaluation',
 'done'),

(1, 'Finance Audit Meeting', DATE_SUB(CURDATE(), INTERVAL 4 DAY), '13:00:00',
 'https://zoom.us/finance-audit',
 'Quarterly financial review',
 'done'),

-- 🔴 Cancelled
(1, 'Project Kickoff', DATE_SUB(CURDATE(), INTERVAL 3 DAY), '11:30:00',
 'https://meet.google.com/project-kickoff',
 'Initial kickoff postponed',
 'cancelled');

INSERT INTO meeting_employees (meeting_id, employee_id) VALUES
                                                            (1, 1),
                                                            (2, 1),
                                                            (3, 1),
                                                            (4, 1),
                                                            (5, 1);
