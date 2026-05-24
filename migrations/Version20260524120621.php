<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260524120621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE meeting (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, meeting_date DATE NOT NULL, meeting_time TIME NOT NULL, meet_link VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, company_id INT NOT NULL, INDEX IDX_F515E139979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE meeting_employees (meeting_id INT NOT NULL, employee_id INT NOT NULL, INDEX IDX_CE0E587467433D9C (meeting_id), INDEX IDX_CE0E58748C03F15C (employee_id), PRIMARY KEY (meeting_id, employee_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE meeting ADD CONSTRAINT FK_F515E139979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE meeting_employees ADD CONSTRAINT FK_CE0E587467433D9C FOREIGN KEY (meeting_id) REFERENCES meeting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meeting_employees ADD CONSTRAINT FK_CE0E58748C03F15C FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_applications DROP FOREIGN KEY `cv_applications_ibfk_1`');
        $this->addSql('ALTER TABLE cv_applications DROP FOREIGN KEY `cv_applications_ibfk_2`');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY `invoices_ibfk_1`');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY `job_offers_ibfk_1`');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY `job_offers_ibfk_2`');
        $this->addSql('ALTER TABLE meetings DROP FOREIGN KEY `meetings_ibfk_1`');
        $this->addSql('ALTER TABLE meeting_employees DROP FOREIGN KEY `meeting_employees_ibfk_1`');
        $this->addSql('ALTER TABLE meeting_employees DROP FOREIGN KEY `meeting_employees_ibfk_2`');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY `sales_ibfk_1`');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY `sales_ibfk_2`');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY `sales_ibfk_3`');
        $this->addSql('ALTER TABLE sale_items DROP FOREIGN KEY `sale_items_ibfk_1`');
        $this->addSql('ALTER TABLE sale_items DROP FOREIGN KEY `sale_items_ibfk_2`');
        $this->addSql('ALTER TABLE service_sale_items DROP FOREIGN KEY `service_sale_items_ibfk_1`');
        $this->addSql('ALTER TABLE service_sale_items DROP FOREIGN KEY `service_sale_items_ibfk_2`');
        $this->addSql('DROP TABLE cv_applications');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE job_icons');
        $this->addSql('DROP TABLE job_offers');
        $this->addSql('DROP TABLE meetings');
        $this->addSql('DROP TABLE meeting_employees');
        $this->addSql('DROP TABLE sales');
        $this->addSql('DROP TABLE sale_items');
        $this->addSql('DROP TABLE service_sale_items');
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY `articles_ibfk_1`');
        $this->addSql('DROP INDEX company_id ON articles');
        $this->addSql('ALTER TABLE articles DROP created_at, CHANGE author_name author_name VARCHAR(150) NOT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE category category VARCHAR(100) NOT NULL, CHANGE ar_date ar_date DATE DEFAULT NULL, CHANGE ar_description ar_description LONGTEXT DEFAULT NULL, CHANGE link link VARCHAR(500) DEFAULT NULL, CHANGE ar_image ar_image VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY `clients_ibfk_1`');
        $this->addSql('DROP INDEX idx_company_client ON clients');
        $this->addSql('DROP INDEX IDX_C82E74979B1AD6 ON clients');
        $this->addSql('ALTER TABLE clients DROP company_id, DROP total_spent, DROP last_purchase_date, DROP created_at, DROP updated_at, CHANGE email email VARCHAR(255) NOT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE client_type client_type VARCHAR(10) DEFAULT \'B2C\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'Active\' NOT NULL');
        $this->addSql('ALTER TABLE companies DROP created_at, CHANGE industry industry VARCHAR(100) DEFAULT NULL, CHANGE address address LONGTEXT DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE companies RENAME INDEX user_id TO UNIQ_8244AA3AA76ED395');
        $this->addSql('ALTER TABLE employees DROP FOREIGN KEY `employees_ibfk_1`');
        $this->addSql('ALTER TABLE employees DROP FOREIGN KEY `employees_ibfk_2`');
        $this->addSql('DROP INDEX user_id ON employees');
        $this->addSql('DROP INDEX company_id ON employees');
        $this->addSql('ALTER TABLE employees ADD cv_path VARCHAR(255) DEFAULT NULL, DROP salary, DROP created_at, CHANGE position position VARCHAR(100) NOT NULL, CHANGE department department VARCHAR(100) NOT NULL, CHANGE hire_date hire_date DATE DEFAULT NULL, CHANGE email email VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE expenses CHANGE category category VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE expenses RENAME INDEX company_id TO IDX_2496F35B979B1AD6');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY `products_ibfk_1`');
        $this->addSql('DROP INDEX company_id ON products');
        $this->addSql('ALTER TABLE products DROP company_id, DROP min_threshold, DROP created_at, CHANGE sku sku VARCHAR(100) DEFAULT NULL, CHANGE category category VARCHAR(100) DEFAULT NULL, CHANGE stock_quantity stock_quantity INT DEFAULT 0 NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE services DROP FOREIGN KEY `services_ibfk_1`');
        $this->addSql('DROP INDEX company_id ON services');
        $this->addSql('ALTER TABLE services DROP company_id, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP created_at, DROP updated_at, CHANGE role role VARCHAR(50) NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX email TO UNIQ_1483A5E9E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cv_applications (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, offre_id INT DEFAULT NULL, first_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, last_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, nationality VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, address TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, linkedin VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, file_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status ENUM(\'Pending\', \'Reviewed\', \'Accepted\', \'Rejected\') CHARACTER SET utf8mb4 DEFAULT \'\'\'Pending\'\'\' COLLATE `utf8mb4_general_ci`, submitted_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX company_id (company_id), INDEX offre_id (offre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, sale_id INT NOT NULL, issue_date DATE NOT NULL, due_date DATE DEFAULT \'NULL\', created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX invoice_number (invoice_number), UNIQUE INDEX sale_id (sale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE job_icons (id INT AUTO_INCREMENT NOT NULL, icon_name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, bootstrap_class VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, default_color VARCHAR(7) CHARACTER SET utf8mb4 DEFAULT \'\'\'#388087\'\'\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE job_offers (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, icon_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type ENUM(\'Full-time\', \'Contract\', \'Urgent\', \'Part-time\', \'Internship\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, category ENUM(\'tech\', \'design\', \'data\', \'marketing\', \'finance\', \'hr\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, salary_min INT DEFAULT NULL, salary_max INT DEFAULT NULL, experience_level ENUM(\'junior\', \'mid\', \'senior\', \'lead\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, tags TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status ENUM(\'active\', \'closed\') CHARACTER SET utf8mb4 DEFAULT \'\'\'active\'\'\' COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX company_id (company_id), INDEX icon_id (icon_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE meetings (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, meeting_date DATE NOT NULL, meeting_time TIME NOT NULL, meet_link VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status ENUM(\'scheduled\', \'done\', \'cancelled\') CHARACTER SET utf8mb4 DEFAULT \'\'\'scheduled\'\'\' COLLATE `utf8mb4_general_ci`, INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE meeting_employees (id INT AUTO_INCREMENT NOT NULL, meeting_id INT NOT NULL, employee_id INT NOT NULL, INDEX employee_id (employee_id), INDEX meeting_id (meeting_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sales (id INT AUTO_INCREMENT NOT NULL, transaction_id VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, company_id INT NOT NULL, employee_id INT DEFAULT NULL, client_id INT NOT NULL, sale_date DATE NOT NULL, subtotal NUMERIC(12, 2) NOT NULL, discount NUMERIC(12, 2) DEFAULT \'0.00\', tax NUMERIC(12, 2) DEFAULT \'0.00\', total_amount NUMERIC(12, 2) NOT NULL, payment_method ENUM(\'Cash\', \'Credit Card\', \'Bank Transfer\', \'Mobile Payment\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, payment_status ENUM(\'Paid\', \'Pending\') CHARACTER SET utf8mb4 DEFAULT \'\'\'Pending\'\'\' COLLATE `utf8mb4_general_ci`, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX client_id (client_id), INDEX idx_transaction (transaction_id), UNIQUE INDEX transaction_id (transaction_id), INDEX idx_company_date (company_id, sale_date), INDEX employee_id (employee_id), INDEX IDX_6B817044979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sale_items (id INT AUTO_INCREMENT NOT NULL, sale_id INT NOT NULL, product_id INT DEFAULT NULL, product_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(12, 2) NOT NULL, INDEX sale_id (sale_id), INDEX product_id (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE service_sale_items (id INT AUTO_INCREMENT NOT NULL, sale_id INT NOT NULL, service_id INT DEFAULT NULL, service_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, quantity_hours NUMERIC(10, 2) DEFAULT \'1.00\' NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(12, 2) NOT NULL, INDEX sale_id (sale_id), INDEX service_id (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cv_applications ADD CONSTRAINT `cv_applications_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_applications ADD CONSTRAINT `cv_applications_ibfk_2` FOREIGN KEY (offre_id) REFERENCES job_offers (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offers ADD CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offers ADD CONSTRAINT `job_offers_ibfk_2` FOREIGN KEY (icon_id) REFERENCES job_icons (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE meetings ADD CONSTRAINT `meetings_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meeting_employees ADD CONSTRAINT `meeting_employees_ibfk_1` FOREIGN KEY (meeting_id) REFERENCES meetings (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meeting_employees ADD CONSTRAINT `meeting_employees_ibfk_2` FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sale_items ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sale_items ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE service_sale_items ADD CONSTRAINT `service_sale_items_ibfk_1` FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_sale_items ADD CONSTRAINT `service_sale_items_ibfk_2` FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE meeting DROP FOREIGN KEY FK_F515E139979B1AD6');
        $this->addSql('ALTER TABLE meeting_employee DROP FOREIGN KEY FK_CE0E587467433D9C');
        $this->addSql('ALTER TABLE meeting_employee DROP FOREIGN KEY FK_CE0E58748C03F15C');
        $this->addSql('DROP TABLE meeting');
        $this->addSql('DROP TABLE meeting_employee');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE articles ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE title title VARCHAR(100) DEFAULT \'NULL\', CHANGE category category VARCHAR(100) DEFAULT \'NULL\', CHANGE ar_description ar_description TEXT DEFAULT NULL, CHANGE author_name author_name VARCHAR(255) NOT NULL, CHANGE ar_date ar_date DATE DEFAULT \'NULL\', CHANGE link link VARCHAR(250) DEFAULT \'NULL\', CHANGE ar_image ar_image VARCHAR(250) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX company_id ON articles (company_id)');
        $this->addSql('ALTER TABLE clients ADD company_id INT NOT NULL, ADD total_spent NUMERIC(12, 2) DEFAULT \'0.00\', ADD last_purchase_date DATE DEFAULT \'NULL\', ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE email email VARCHAR(255) DEFAULT \'NULL\', CHANGE phone phone VARCHAR(50) DEFAULT \'NULL\', CHANGE address address TEXT DEFAULT NULL, CHANGE client_type client_type ENUM(\'B2B\', \'B2C\', \'B2G\') DEFAULT \'\'\'B2C\'\'\', CHANGE status status ENUM(\'Active\', \'Inactive\', \'Prospect\') DEFAULT \'\'\'Active\'\'\'');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_company_client ON clients (company_id, client_name)');
        $this->addSql('CREATE INDEX IDX_C82E74979B1AD6 ON clients (company_id)');
        $this->addSql('ALTER TABLE companies ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE industry industry VARCHAR(100) DEFAULT \'NULL\', CHANGE address address TEXT DEFAULT NULL, CHANGE phone phone VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE companies RENAME INDEX uniq_8244aa3aa76ed395 TO user_id');
        $this->addSql('ALTER TABLE employees ADD salary NUMERIC(10, 2) DEFAULT \'NULL\', ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, DROP cv_path, CHANGE position position VARCHAR(100) DEFAULT \'NULL\', CHANGE department department VARCHAR(100) DEFAULT \'NULL\', CHANGE email email VARCHAR(250) DEFAULT \'NULL\', CHANGE hire_date hire_date DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE employees ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employees ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX user_id ON employees (user_id)');
        $this->addSql('CREATE INDEX company_id ON employees (company_id)');
        $this->addSql('ALTER TABLE expenses CHANGE category category ENUM(\'Rent\', \'Salary\', \'Tools\', \'Marketing\', \'Supply\', \'Other\') DEFAULT \'\'\'Other\'\'\' NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE expenses RENAME INDEX idx_2496f35b979b1ad6 TO company_id');
        $this->addSql('ALTER TABLE products ADD company_id INT NOT NULL, ADD min_threshold INT DEFAULT 20, ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE sku sku VARCHAR(100) DEFAULT \'NULL\', CHANGE category category VARCHAR(100) DEFAULT \'NULL\', CHANGE stock_quantity stock_quantity INT DEFAULT 0, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX company_id ON products (company_id)');
        $this->addSql('ALTER TABLE services ADD company_id INT NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE services ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX company_id ON services (company_id)');
        $this->addSql('ALTER TABLE users ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE role role ENUM(\'normal\', \'employee\', \'company\') NOT NULL, CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9e7927c74 TO email');
    }
}
