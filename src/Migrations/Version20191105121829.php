<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\DBALException;

/**
* Class Version20191105121829
*/
final class Version20191105121829 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @param Schema $schema
     *
     * @return void
     * @throws DBALException
     *
     * @SuppressWarnings("unused")
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql(<<<'SQL'
INSERT INTO dictionary.absence_types (id, short_name, name, active) 
VALUES (
        nextval('dictionary.absence_types_id_seq'), 
        'OG', 
        'odbiór godzin nadliczbowych', 
        true
        );
SQL
        );
        $this->addSql(
            'UPDATE dictionary.presence_types SET active=false WHERE name=\'dyżur domowy\' or name=\'dyżur w pracy\''
        );
    }

    /**
     * @param Schema $schema
     *
     * @return void
     *
     * @SuppressWarnings("unused")
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Downgrade migration can only be executed by next migration.');
    }
}
