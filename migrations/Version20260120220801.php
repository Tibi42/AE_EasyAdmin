<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120220801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des index pour optimiser les performances des requêtes fréquentes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_category_name ON category (name)');
        $this->addSql('CREATE INDEX idx_category_slug ON category (slug)');
        $this->addSql('CREATE INDEX idx_order_status ON `order` (status)');
        $this->addSql('CREATE INDEX idx_order_date ON `order` (dateat)');
        $this->addSql('CREATE INDEX idx_order_user_date ON `order` (user_id, dateat)');
        $this->addSql('CREATE INDEX idx_product_category ON product (category)');
        $this->addSql('CREATE INDEX idx_product_featured ON product (is_featured)');
        $this->addSql('CREATE INDEX idx_product_price ON product (price)');
        $this->addSql('CREATE INDEX idx_product_name ON product (name)');
        $this->addSql('CREATE INDEX idx_product_category_featured ON product (category, is_featured)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_category_name ON category');
        $this->addSql('DROP INDEX idx_category_slug ON category');
        $this->addSql('DROP INDEX idx_order_status ON `order`');
        $this->addSql('DROP INDEX idx_order_date ON `order`');
        $this->addSql('DROP INDEX idx_order_user_date ON `order`');
        $this->addSql('DROP INDEX idx_product_category ON product');
        $this->addSql('DROP INDEX idx_product_featured ON product');
        $this->addSql('DROP INDEX idx_product_price ON product');
        $this->addSql('DROP INDEX idx_product_name ON product');
        $this->addSql('DROP INDEX idx_product_category_featured ON product');
    }
}
