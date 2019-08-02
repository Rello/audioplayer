<?php
namespace OCA\audioplayer\Service;

use OCP\IDbConnection;

class StatementService {
    private $db;
    private $statements;
    private $output;

    public function __construct(IDbConnection $db) {
        $this->db = $db;
        $this->statements = array();
        $this->output = $output;
    }

    public function selectTrackId() {
        $key = 'selectTrackId';
        $SQL = 'SELECT `id` FROM `*PREFIX*audioplayer_tracks` WHERE `user_id`= ? AND `title`= ? AND `number`= ?
                AND `artist_id`= ? AND `album_id`= ? AND `length`= ? AND `bitrate`= ?
                AND `mimetype`= ? AND `genre_id`= ? AND `year`= ?
                AND `disc`= ? AND `composer`= ? AND `subtitle`= ?';
        return $this->getStatement($key, $SQL);
    }

    public function insertTrack() {
        $key = 'insertTrack';
        $SQL = 'INSERT INTO `*PREFIX*audioplayer_tracks`
                (`user_id`,`title`,`number`,`artist_id`,
                `album_id`,`length`,`file_id`,`bitrate`,
                `mimetype`,`genre_id`,`year`,`folder_id`,
                `disc`,`composer`,`subtitle`,`isrc`,`copyright`)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        return $this->getStatement($key, $SQL);
    }

    public function getSessionValue() {
        $key = 'getSessionValue';
        $SQL = 'SELECT `configvalue` FROM `*PREFIX*preferences`
                WHERE `userid`= ? AND `appid`= ? AND `configkey`= ?';
        return $this->getStatement($key, $SQL);
    }

    public function selectGenreId() {
        $key = 'selectGenreId';
        $SQL = 'SELECT `id` FROM `*PREFIX*audioplayer_genre` WHERE `user_id` = ? AND `name` = ?';
        return $this->getStatement($key, $SQL);
    }

    public function insertGenre() {
        $key = 'insertGenre';
        $SQL = 'INSERT INTO `*PREFIX*audioplayer_genre` (`user_id`,`name`) VALUES(?,?)';
        return $this->getStatement($key, $SQL);
    }

    public function selectArtistId() {
        $key = 'selectArtistId';
        $SQL = 'SELECT `id` FROM `*PREFIX*audioplayer_artists` WHERE `user_id` = ? AND `name` = ?';
        return $this->getStatement($key, $SQL);
    }

    public function insertArtist() {
        $key = 'insertArtist';
        $SQL = 'INSERT INTO `*PREFIX*audioplayer_artists` (`user_id`,`name`) VALUES(?,?)';
        return $this->getStatement($key, $SQL);
    }

    public function selectAlbumIdArtistId() {
        $key = 'selectAlbumIdArtistId';
        $SQL = 'SELECT `id`, `artist_id` FROM `*PREFIX*audioplayer_albums`
                WHERE `user_id` = ? AND `name` = ? AND `folder_id` = ?';
        return $this->getStatement($key, $SQL);
    }

    public function updateAlbumArtistId() {
        $key = 'updateAlbumArtistId';
        $SQL = 'UPDATE `*PREFIX*audioplayer_albums` SET `artist_id`= ? WHERE `id` = ? AND `user_id` = ?';
        return $this->getStatement($key, $SQL);
    }

    public function updateAlbumYearArtistId() {
        $key = 'updateAlbumYearArtistId';
        $SQL = 'UPDATE `*PREFIX*audioplayer_albums` SET `year`= ?, `artist_id`= ?
                WHERE `id` = ? AND `user_id` = ?';
        return $this->getStatement($key, $SQL);
    }

    public function updateAlbumYear() {
        $key = 'updateAlbumYear';
        $SQL = 'UPDATE `*PREFIX*audioplayer_albums` SET `year`= ? WHERE `id` = ? AND `user_id` = ?';
        return $this->getStatement($key, $SQL);
    }

    public function insertAlbum() {
        $key = 'insertAlbum';
        $SQL = 'INSERT INTO `*PREFIX*audioplayer_albums` (`user_id`,`name`,`folder_id`) VALUES(?,?,?)';
        return $this->getStatement($key, $SQL);
    }

    public function updateAlbumCover() {
        $key = 'updateAlbumCover';
        $SQL = 'UPDATE `*PREFIX*audioplayer_albums` SET `cover`= ?, `bgcolor`= ?
                WHERE `id` = ? AND `user_id` = ?';
        return $this->getStatement($key, $SQL);
    }

    private function getStatement(string $key, string $SQL) {
        if (isset($this->statements[$key])) {
            return $this->statements[$key];
        }
        $stmt = $this->db->prepare($SQL);
        $this->statements[$key] = $stmt;
        return $stmt;
    }
}
