<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020133342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, conducteur_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, note INT DEFAULT NULL, commentaire LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_8F91ABF0F16F4AC6 (conducteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE avisvalidation (id INT AUTO_INCREMENT NOT NULL, conducteur_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, note INT DEFAULT NULL, commentaire LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_FD65E5B2F16F4AC6 (conducteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, name VARCHAR(255) NOT NULL, mail VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE horaire (id INT AUTO_INCREMENT NOT NULL, horaires VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_57698A6AFF7747B4 (titre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajets (id INT AUTO_INCREMENT NOT NULL, conducteur_id INT NOT NULL, voiture_id INT NOT NULL, depart VARCHAR(255) NOT NULL, arrive VARCHAR(255) NOT NULL, date DATETIME NOT NULL, duree INT NOT NULL, prix INT NOT NULL, INDEX IDX_FF2B5BA9F16F4AC6 (conducteur_id), INDEX IDX_FF2B5BA9181A8BA (voiture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajets_user (trajets_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_16B18765451BDEFF (trajets_id), INDEX IDX_16B18765A76ED395 (user_id), PRIMARY KEY(trajets_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajetsencours (id INT AUTO_INCREMENT NOT NULL, conducteur_id INT NOT NULL, voiture_id INT NOT NULL, depart VARCHAR(255) NOT NULL, arrive VARCHAR(255) NOT NULL, date DATETIME NOT NULL, duree INT NOT NULL, prix INT NOT NULL, INDEX IDX_919ADFFAF16F4AC6 (conducteur_id), INDEX IDX_919ADFFA181A8BA (voiture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajetsencours_user (trajetsencours_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_34DBC11CC868902D (trajetsencours_id), INDEX IDX_34DBC11CA76ED395 (user_id), PRIMARY KEY(trajetsencours_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajetsfini (id INT AUTO_INCREMENT NOT NULL, conducteur_id INT NOT NULL, voiture_id INT NOT NULL, depart VARCHAR(255) NOT NULL, arrive VARCHAR(255) NOT NULL, date DATETIME NOT NULL, duree INT NOT NULL, INDEX IDX_BCE79D83F16F4AC6 (conducteur_id), INDEX IDX_BCE79D83181A8BA (voiture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trajetsfini_user (trajetsfini_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8D0772F87FB3E3D9 (trajetsfini_id), INDEX IDX_8D0772F8A76ED395 (user_id), PRIMARY KEY(trajetsfini_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, api_token VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, credits INT NOT NULL, INDEX IDX_8D93D649D60322AC (role_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE voitures (id INT AUTO_INCREMENT NOT NULL, voiture VARCHAR(255) NOT NULL, dateimat DATETIME NOT NULL, fumeur VARCHAR(255) NOT NULL, annimaux VARCHAR(255) NOT NULL, marque VARCHAR(255) NOT NULL, place INT NOT NULL, modele VARCHAR(255) NOT NULL, couleur VARCHAR(255) NOT NULL, image LONGBLOB DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES trajetsfini (id)');
        $this->addSql('ALTER TABLE avisvalidation ADD CONSTRAINT FK_FD65E5B2F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES trajetsfini (id)');
        $this->addSql('ALTER TABLE trajets ADD CONSTRAINT FK_FF2B5BA9F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE trajets ADD CONSTRAINT FK_FF2B5BA9181A8BA FOREIGN KEY (voiture_id) REFERENCES voitures (id)');
        $this->addSql('ALTER TABLE trajets_user ADD CONSTRAINT FK_16B18765451BDEFF FOREIGN KEY (trajets_id) REFERENCES trajets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajets_user ADD CONSTRAINT FK_16B18765A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajetsencours ADD CONSTRAINT FK_919ADFFAF16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE trajetsencours ADD CONSTRAINT FK_919ADFFA181A8BA FOREIGN KEY (voiture_id) REFERENCES voitures (id)');
        $this->addSql('ALTER TABLE trajetsencours_user ADD CONSTRAINT FK_34DBC11CC868902D FOREIGN KEY (trajetsencours_id) REFERENCES trajetsencours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajetsencours_user ADD CONSTRAINT FK_34DBC11CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajetsfini ADD CONSTRAINT FK_BCE79D83F16F4AC6 FOREIGN KEY (conducteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE trajetsfini ADD CONSTRAINT FK_BCE79D83181A8BA FOREIGN KEY (voiture_id) REFERENCES voitures (id)');
        $this->addSql('ALTER TABLE trajetsfini_user ADD CONSTRAINT FK_8D0772F87FB3E3D9 FOREIGN KEY (trajetsfini_id) REFERENCES trajetsfini (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajetsfini_user ADD CONSTRAINT FK_8D0772F8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0F16F4AC6');
        $this->addSql('ALTER TABLE avisvalidation DROP FOREIGN KEY FK_FD65E5B2F16F4AC6');
        $this->addSql('ALTER TABLE trajets DROP FOREIGN KEY FK_FF2B5BA9F16F4AC6');
        $this->addSql('ALTER TABLE trajets DROP FOREIGN KEY FK_FF2B5BA9181A8BA');
        $this->addSql('ALTER TABLE trajets_user DROP FOREIGN KEY FK_16B18765451BDEFF');
        $this->addSql('ALTER TABLE trajets_user DROP FOREIGN KEY FK_16B18765A76ED395');
        $this->addSql('ALTER TABLE trajetsencours DROP FOREIGN KEY FK_919ADFFAF16F4AC6');
        $this->addSql('ALTER TABLE trajetsencours DROP FOREIGN KEY FK_919ADFFA181A8BA');
        $this->addSql('ALTER TABLE trajetsencours_user DROP FOREIGN KEY FK_34DBC11CC868902D');
        $this->addSql('ALTER TABLE trajetsencours_user DROP FOREIGN KEY FK_34DBC11CA76ED395');
        $this->addSql('ALTER TABLE trajetsfini DROP FOREIGN KEY FK_BCE79D83F16F4AC6');
        $this->addSql('ALTER TABLE trajetsfini DROP FOREIGN KEY FK_BCE79D83181A8BA');
        $this->addSql('ALTER TABLE trajetsfini_user DROP FOREIGN KEY FK_8D0772F87FB3E3D9');
        $this->addSql('ALTER TABLE trajetsfini_user DROP FOREIGN KEY FK_8D0772F8A76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE avisvalidation');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE trajets');
        $this->addSql('DROP TABLE trajets_user');
        $this->addSql('DROP TABLE trajetsencours');
        $this->addSql('DROP TABLE trajetsencours_user');
        $this->addSql('DROP TABLE trajetsfini');
        $this->addSql('DROP TABLE trajetsfini_user');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE voitures');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
