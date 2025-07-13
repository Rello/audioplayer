<?php
declare(strict_types=1);

namespace OCA\audioplayer\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3500Date20240101000000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        if ($schema->hasTable('audioplayer_tracks')) {
            $table = $schema->getTable('audioplayer_tracks');
            if (!$table->hasColumn('comment')) {
                $table->addColumn('comment', 'text', [
                    'notnull' => false,
                ]);
            }
        }
        return $schema;
    }
}
