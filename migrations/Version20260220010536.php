<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220010536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed initial ROLE_ROOT user (admin/admin/12345678)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO `user` (login, pass, phone, roles) VALUES ('admin', 'admin', '12345678', '[\"ROLE_ROOT\"]')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `user` WHERE login = 'admin' AND pass = 'admin'");
    }
}
