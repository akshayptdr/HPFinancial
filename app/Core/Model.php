<?php
namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';

    protected static function db(): PDO { return Database::pdo(); }

    public static function find($id): ?array
    {
        $st = self::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return self::db()->query("SELECT * FROM " . static::$table . " ORDER BY $orderBy")->fetchAll();
    }

    public static function where(string $col, $val, string $orderBy = 'id DESC'): array
    {
        $st = self::db()->prepare("SELECT * FROM " . static::$table . " WHERE $col = ? ORDER BY $orderBy");
        $st->execute([$val]);
        return $st->fetchAll();
    }

    public static function firstWhere(string $col, $val): ?array
    {
        $st = self::db()->prepare("SELECT * FROM " . static::$table . " WHERE $col = ? LIMIT 1");
        $st->execute([$val]);
        return $st->fetch() ?: null;
    }

    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $ph = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO " . static::$table . " (" . implode(',', $cols) . ") VALUES ($ph)";
        self::db()->prepare($sql)->execute(array_values($data));
        return (int) self::db()->lastInsertId();
    }

    public static function update($id, array $data): void
    {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        $sql = "UPDATE " . static::$table . " SET $set WHERE id = ?";
        $vals = array_values($data);
        $vals[] = $id;
        self::db()->prepare($sql)->execute($vals);
    }

    public static function delete($id): void
    {
        self::db()->prepare("DELETE FROM " . static::$table . " WHERE id = ?")->execute([$id]);
    }

    public static function query(string $sql, array $params = []): array
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function scalar(string $sql, array $params = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }
}
