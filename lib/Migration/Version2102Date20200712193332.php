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
class Version2102Date20200712193332 extends SimpleMigrationStep {

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

        if (!$schema->hasTable('audioplayer_artists')) {
            $table = $schema->createTable('audioplayer_artists');
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
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'artists_user_id_idx');
        }

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
        }

        if (!$schema->hasTable('audioplayer_genre')) {
            $table = $schema->createTable('audioplayer_genre');
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
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'genre_user_id_idx');
        }

        if (!$schema->hasTable('audioplayer_playlists')) {
            $table = $schema->createTable('audioplayer_playlists');
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
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'playlists_user_id_idx');
        }

        if (!$schema->hasTable('audioplayer_playlist_tracks')) {
            $table = $schema->createTable('audioplayer_playlist_tracks');
            $table->addColumn('playlist_id', 'integer', [
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('track_id', 'integer', [
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('sortorder', 'smallint', [
                'notnull' => true,
                'length' => 1,
                'default' => 0,
                'unsigned' => true,
            ]);
            $table->addIndex(['playlist_id'], 'playlist_tracks_idx');
            $table->addUniqueIndex(['playlist_id', 'track_id'], 'playlist_tracks_unique_idx');
        }

        if (!$schema->hasTable('audioplayer_tracks')) {
            $table = $schema->createTable('audioplayer_tracks');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('title', 'string', [
                'notnull' => true,
                'length' => 256,
            ]);
            $table->addColumn('number', 'integer', [
                'notnull' => false,
                'unsigned' => true,
            ]);
            $table->addColumn('artist_id', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('album_id', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('length', 'string', [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('file_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('bitrate', 'integer', [
                'notnull' => false,
                'unsigned' => true,
            ]);
            $table->addColumn('mimetype', 'string', [
                'notnull' => true,
                'length' => 256,
            ]);
            $table->addColumn('genre_id', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('year', 'integer', [
                'notnull' => false,
                'unsigned' => true,
            ]);
            $table->addColumn('folder_id', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('disc', 'integer', [
                'notnull' => false,
                'unsigned' => true,
            ]);
            $table->addColumn('composer', 'string', [
                'notnull' => false,
                'length' => 256,
            ]);
            $table->addColumn('subtitle', 'string', [
                'notnull' => false,
                'length' => 256,
            ]);
            $table->addColumn('isrc', 'string', [
                'notnull' => false,
                'length' => 12,
            ]);
            $table->addColumn('copyright', 'string', [
                'notnull' => false,
                'length' => 256,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['artist_id', 'user_id'], 'tracks_artist_id_idx');
            $table->addIndex(['genre_id', 'user_id'], 'tracks_genre_id_idx');
            $table->addIndex(['year', 'user_id'], 'tracks_year_idx');
            $table->addIndex(['album_id', 'user_id'], 'tracks_album_id_idx');
            $table->addIndex(['user_id'], 'tracks_user_id_idx');
            $table->addIndex(['folder_id', 'user_id'], 'tracks_folder_id_idx');
        }

        if (!$schema->hasTable('audioplayer_streams')) {
            $table = $schema->createTable('audioplayer_streams');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('title', 'string', [
                'notnull' => true,
                'length' => 256,
            ]);
            $table->addColumn('file_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('mimetype', 'string', [
                'notnull' => true,
                'length' => 256,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'streams_user_id_idx');
        }

        if (!$schema->hasTable('audioplayer_stats')) {
            $table = $schema->createTable('audioplayer_stats');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('track_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('playtime', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('playcount', 'integer', [
                'notnull' => false,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['track_id', 'user_id'], 'stats_file_user_id_idx');
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