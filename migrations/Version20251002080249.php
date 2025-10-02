<?php

declare (strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251002080249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP first_name, DROP last_name, DROP guest_number, CHANGE email email VARCHAR(180) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE roles roles JSON NOT NULL, CHANGE allergy api_token VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL ON user');
        $this->addSql('ALTER TABLE user ADD first_name VARCHAR(64) NOT NULL, ADD last_name VARCHAR(64) NOT NULL, ADD guest_number INT NOT NULL, CHANGE email email VARCHAR(64) NOT NULL, CHANGE roles roles VARCHAR(32) NOT NULL, CHANGE password password VARCHAR(32) NOT NULL, CHANGE api_token allergy VARCHAR(255) NOT NULL');
    }
}
