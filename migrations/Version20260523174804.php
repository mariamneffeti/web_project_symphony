<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260523174804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE articles DROP FOREIGN KEY `articles_ibfk_1`');
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY `companies_ibfk_1`');
        $this->addSql('ALTER TABLE cv_applications DROP FOREIGN KEY `cv_applications_ibfk_1`');
        $this->addSql('ALTER TABLE cv_applications DROP FOREIGN KEY `cv_applications_ibfk_2`');
        $this->addSql('ALTER TABLE employees DROP FOREIGN KEY `employees_ibfk_1`');
        $this->addSql('ALTER TABLE employees DROP FOREIGN KEY `employees_ibfk_2`');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY `invoices_ibfk_1`');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY `job_offers_ibfk_1`');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY `job_offers_ibfk_2`');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY `products_ibfk_1`');
        $this->addSql('ALTER TABLE sale_items DROP FOREIGN KEY `sale_items_ibfk_1`');
        $this->addSql('ALTER TABLE sale_items DROP FOREIGN KEY `sale_items_ibfk_2`');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY `sales_ibfk_1`');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY `sales_ibfk_2`');
        $this->addSql('ALTER TABLE sales DROP FOREIGN KEY `sales_ibfk_3`');
        $this->addSql('ALTER TABLE service_sale_items DROP FOREIGN KEY `service_sale_items_ibfk_1`');
        $this->addSql('ALTER TABLE service_sale_items DROP FOREIGN KEY `service_sale_items_ibfk_2`');
        $this->addSql('ALTER TABLE services DROP FOREIGN KEY `services_ibfk_1`');
        $this->addSql('DROP TABLE articles');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE cv_applications');
        $this->addSql('DROP TABLE employees');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('DROP TABLE job_icons');
        $this->addSql('DROP TABLE job_offers');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE sale_items');
        $this->addSql('DROP TABLE sales');
        $this->addSql('DROP TABLE service_sale_items');
        $this->addSql('DROP TABLE services');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE clients DROP FOREIGN KEY `clients_ibfk_1`');
        $this->addSql('DROP INDEX idx_company_client ON clients');
        $this->addSql('DROP INDEX IDX_C82E74979B1AD6 ON clients');
        $this->addSql('ALTER TABLE clients ADD engagement_score DOUBLE PRECISION DEFAULT NULL, DROP company_id, DROP total_spent, DROP last_purchase_date, DROP created_at, DROP updated_at, CHANGE email email VARCHAR(255) NOT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE client_type client_type VARCHAR(10) DEFAULT \'B2C\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'Active\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE articles (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, author_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, title VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, category VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, date DATE DEFAULT NULL, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, link VARCHAR(250) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, image VARCHAR(250) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE companies (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, company_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, industry VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, address TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE INDEX user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE cv_applications (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, offre_id INT DEFAULT NULL, first_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, last_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, nationality VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, address TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, linkedin VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, file_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, status ENUM(\'Pending\', \'Reviewed\', \'Accepted\', \'Rejected\') CHARACTER SET utf8mb4 DEFAULT \'Pending\' COLLATE `utf8mb4_0900_ai_ci`, submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX offre_id (offre_id), INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE employees (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, company_id INT NOT NULL, first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, last_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, position VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, department VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, hire_date DATE DEFAULT NULL, email VARCHAR(250) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, salary NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX user_id (user_id), INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, sale_id INT NOT NULL, issue_date DATE NOT NULL, due_date DATE DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE INDEX invoice_number (invoice_number), UNIQUE INDEX sale_id (sale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE job_icons (id INT AUTO_INCREMENT NOT NULL, icon_name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, bootstrap_class VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, default_color VARCHAR(7) CHARACTER SET utf8mb4 DEFAULT \'#388087\' COLLATE `utf8mb4_0900_ai_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE job_offers (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, icon_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, type ENUM(\'Full-time\', \'Contract\', \'Urgent\', \'Part-time\', \'Internship\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, category ENUM(\'tech\', \'design\', \'data\', \'marketing\', \'finance\', \'hr\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, salary_min INT DEFAULT NULL, salary_max INT DEFAULT NULL, experience_level ENUM(\'junior\', \'mid\', \'senior\', \'lead\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, tags TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, status ENUM(\'active\', \'closed\') CHARACTER SET utf8mb4 DEFAULT \'active\' COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX icon_id (icon_id), INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, product_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, sku VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, category VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, price NUMERIC(10, 2) NOT NULL, stock_quantity INT DEFAULT 0, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sale_items (id INT AUTO_INCREMENT NOT NULL, sale_id INT NOT NULL, product_id INT DEFAULT NULL, product_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(12, 2) NOT NULL, INDEX sale_id (sale_id), INDEX product_id (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sales (id INT AUTO_INCREMENT NOT NULL, transaction_id VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, company_id INT NOT NULL, employee_id INT DEFAULT NULL, client_id INT NOT NULL, sale_date DATE NOT NULL, subtotal NUMERIC(12, 2) NOT NULL, discount NUMERIC(12, 2) DEFAULT \'0.00\', tax NUMERIC(12, 2) DEFAULT \'0.00\', total_amount NUMERIC(12, 2) NOT NULL, payment_method ENUM(\'Cash\', \'Credit Card\', \'Bank Transfer\', \'Mobile Payment\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, payment_status ENUM(\'Paid\', \'Pending\') CHARACTER SET utf8mb4 DEFAULT \'Pending\' COLLATE `utf8mb4_0900_ai_ci`, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_company_date (company_id, sale_date), INDEX idx_transaction (transaction_id), INDEX client_id (client_id), INDEX employee_id (employee_id), UNIQUE INDEX transaction_id (transaction_id), INDEX IDX_6B817044979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE service_sale_items (id INT AUTO_INCREMENT NOT NULL, sale_id INT NOT NULL, service_id INT DEFAULT NULL, service_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, quantity_hours NUMERIC(10, 2) DEFAULT \'1.00\' NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(12, 2) NOT NULL, INDEX sale_id (sale_id), INDEX service_id (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE services (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, service_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, base_price NUMERIC(10, 2) NOT NULL, INDEX company_id (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, role ENUM(\'normal\', \'employee\', \'company\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE INDEX email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_applications ADD CONSTRAINT `cv_applications_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv_applications ADD CONSTRAINT `cv_applications_ibfk_2` FOREIGN KEY (offre_id) REFERENCES job_offers (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE employees ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employees ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (sale_id) REFERENCES sales (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offers ADD CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offers ADD CONSTRAINT `job_offers_ibfk_2` FOREIGN KEY (icon_id) REFERENCES job_icons (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sale_items ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (sale_id) REFERENCES sales (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sale_items ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES products (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (employee_id) REFERENCES employees (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE sales ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (client_id) REFERENCES clients (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_sale_items ADD CONSTRAINT `service_sale_items_ibfk_1` FOREIGN KEY (sale_id) REFERENCES sales (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_sale_items ADD CONSTRAINT `service_sale_items_ibfk_2` FOREIGN KEY (service_id) REFERENCES services (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE services ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE clients ADD company_id INT NOT NULL, ADD total_spent NUMERIC(12, 2) DEFAULT \'0.00\', ADD last_purchase_date DATE DEFAULT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP, ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, DROP engagement_score, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE address address TEXT DEFAULT NULL, CHANGE client_type client_type ENUM(\'B2B\', \'B2C\', \'B2G\') DEFAULT \'B2C\', CHANGE status status ENUM(\'Active\', \'Inactive\', \'Prospect\') DEFAULT \'Active\'');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_company_client ON clients (company_id, client_name)');
        $this->addSql('CREATE INDEX IDX_C82E74979B1AD6 ON clients (company_id)');
    }
}
