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
class Version2102Date20200712203332 extends SimpleMigrationStep {

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

        if (!$schema->hasTable('audioplayer_whats_new')) {
            $table = $schema->createTable('audioplayer_whats_new');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 4,
                'unsigned' => true,
            ]);
            $table->addColumn('version', 'string', [
                'notnull' => true,
                'length' => 64,
                'default' => '11',
            ]);
            $table->addColumn('etag', 'string', [
                'notnull' => true,
                'length' => 64,
                'default' => '',
            ]);
            $table->addColumn('last_check', 'integer', [
                'notnull' => true,
                'length' => 4,
                'unsigned' => true,
                'default' => 0,
            ]);
            $table->addColumn('data', 'text', [
                'notnull' => true,
                'default' => '',
            ]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['version'], 'audioplayer_whats_new_v_idx');
            $table->addIndex(['version', 'etag'], 'audioplayer_whats_new_v_e_idx');
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