<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250526230534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE cicd_pipelines (id INT AUTO_INCREMENT NOT NULL, run_id VARCHAR(255) NOT NULL, workflow_id VARCHAR(255) NOT NULL, workflow_name VARCHAR(255) NOT NULL, repository VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, conclusion VARCHAR(50) NOT NULL, branch VARCHAR(255) NOT NULL, commit_sha VARCHAR(255) NOT NULL, commit_message VARCHAR(500) DEFAULT NULL, actor VARCHAR(255) DEFAULT NULL, event VARCHAR(50) NOT NULL, duration INT DEFAULT NULL, html_url VARCHAR(500) DEFAULT NULL, started_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, recorded_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_repository (repository), INDEX idx_status (status), INDEX idx_started_at (started_at), INDEX idx_workflow_name (workflow_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE docker_services (id INT AUTO_INCREMENT NOT NULL, service_id VARCHAR(255) NOT NULL, service_name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, replicas INT NOT NULL, running_replicas INT NOT NULL, image VARCHAR(255) NOT NULL, ports JSON DEFAULT NULL, environment JSON DEFAULT NULL, labels JSON DEFAULT NULL, last_error LONGTEXT DEFAULT NULL, recorded_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_service_name (service_name), INDEX idx_status (status), INDEX idx_recorded_at (recorded_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE infrastructure_metrics (id INT AUTO_INCREMENT NOT NULL, metric_name VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, value DOUBLE PRECISION NOT NULL, unit VARCHAR(50) DEFAULT NULL, labels JSON DEFAULT NULL, metadata JSON DEFAULT NULL, status VARCHAR(50) NOT NULL, threshold DOUBLE PRECISION DEFAULT NULL, alert_level VARCHAR(20) DEFAULT NULL, recorded_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX idx_metric_name (metric_name), INDEX idx_source (source), INDEX idx_recorded_at (recorded_at), INDEX idx_metric_source_time (metric_name, source, recorded_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE cicd_pipelines
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE docker_services
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE infrastructure_metrics
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }
}
