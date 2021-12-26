<?php

declare(strict_types=1);

namespace OCA\audioplayer\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version3203Date20211226193332 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('audioplayer_albums')) {
            $table = $schema->createTable('audioplayer_albums');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => false,
                'length' => 256,
            ]);
            $table->addColumn('year', 'integer', [
                'notnull' => false,
                'unsigned' => true,
            ]);
            $table->addColumn('genre_id', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('cover', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('bgcolor', 'string', [
                'notnull' => false,
                'length' => 40,
            ]);
            $table->addColumn('artist_id', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('folder_id', 'integer', [
                'notnull' => false,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'albums_user_id_idx');
            $table->addIndex(['id', 'user_id'], 'albums_album_user_idx');
        } else {
            $table = $schema->getTable('audioplayer_albums');
            if (!$table->hasColumn('id')) {
                $table->addColumn('id', 'integer', [
                    'autoincrement' => true,
                    'notnull' => true,
                    'unsigned' => true,
                ]);
            }
            if (!$table->hasColumn('user_id')) {
                $table->addColumn('user_id', 'string', [
                    'notnull' => true,
                    'length' => 64,
                ]);
            }
            if (!$table->hasColumn('name')) {
                $table->addColumn('name', 'string', [
                    'notnull' => false,
                    'length' => 256,
                ]);
            }
            if (!$table->hasColumn('year')) {
                $table->addColumn('year', 'integer', [
                    'notnull' => false,
                    'unsigned' => true,
                ]);
            }
            if (!$table->hasColumn('genre_id')) {
                $table->addColumn('genre_id', 'integer', [
                    'notnull' => false,
                ]);
            }
            if (!$table->hasColumn('cover')) {
                $table->addColumn('cover', 'text', [
                    'notnull' => false,
                ]);
            }
            if (!$table->hasColumn('bgcolor')) {
                $table->addColumn('bgcolor', 'string', [
                    'notnull' => false,
                    'length' => 40,
                ]);
            }
            if (!$table->hasColumn('artist_id')) {
                $table->addColumn('artist_id', 'integer', [
                    'notnull' => false,
                ]);
            }
            if (!$table->hasColumn('folder_id')) {
                $table->addColumn('folder_id', 'integer', [
                    'notnull' => false,
                ]);
            }
        }
        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }
}