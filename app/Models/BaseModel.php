<?php
// app/Models/BaseModel.php

namespace App\Models;

use App\Core\Database;

abstract class BaseModel
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    // Заполнение атрибутов
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key) || in_array($key, $this->getFillable())) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    // Получение заполняемых полей
    protected function getFillable(): array
    {
        return isset($this->fillable) ? $this->fillable : [];
    }

    // Магический метод get
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (method_exists($this, $key)) {
            return $this->$key();
        }

        return null;
    }

    // Магический метод set
    public function __set(string $key, $value): void
    {
        if (in_array($key, $this->getFillable()) || property_exists($this, $key)) {
            $this->attributes[$key] = $value;
        }
    }

    // Получение записи по ID
    public static function find(int $id): ?self
    {
        $table = static::$table;
        $primaryKey = static::$primaryKey;

        $data = Database::fetchOne(
            "SELECT * FROM {$table} WHERE {$primaryKey} = :id",
            ['id' => $id]
        );

        if ($data) {
            $model = new static($data);
            $model->exists = true;
            $model->original = $data;
            return $model;
        }

        return null;
    }

    // Получение всех записей
    public static function all(array $columns = ['*']): array
    {
        $table = static::$table;
        $columns = implode(', ', $columns);

        $data = Database::fetchAll("SELECT {$columns} FROM {$table}");

        $models = [];
        foreach ($data as $item) {
            $model = new static($item);
            $model->exists = true;
            $model->original = $item;
            $models[] = $model;
        }

        return $models;
    }

    // Поиск с условиями
    public static function where(string $column, string $operator, $value = null): array
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $table = static::$table;
        $sql = "SELECT * FROM {$table} WHERE {$column} {$operator} :value";

        $data = Database::fetchAll($sql, ['value' => $value]);

        $models = [];
        foreach ($data as $item) {
            $model = new static($item);
            $model->exists = true;
            $model->original = $item;
            $models[] = $model;
        }

        return $models;
    }

    // Получение первой записи
    public static function first(): ?self
    {
        $table = static::$table;

        $data = Database::fetchOne("SELECT * FROM {$table} LIMIT 1");

        if ($data) {
            $model = new static($data);
            $model->exists = true;
            $model->original = $data;
            return $model;
        }

        return null;
    }

    // Создание новой записи
    public function create(array $data): self
    {
        $table = static::$table;

        $id = Database::insert($table, $data);

        if ($id) {
            $data[static::$primaryKey] = $id;
            $this->fill($data);
            $this->exists = true;
            $this->original = $data;
        }

        return $this;
    }

    // Обновление записи
    public function update(array $data): bool
    {
        if (!$this->exists) {
            return false;
        }

        $table = static::$table;
        $primaryKey = static::$primaryKey;

        $affected = Database::update(
            $table,
            $data,
            "{$primaryKey} = :id",
            ['id' => $this->attributes[$primaryKey]]
        );

        if ($affected > 0) {
            $this->fill($data);
            return true;
        }

        return false;
    }

    // Сохранение (create или update)
    public function save(): bool
    {
        if ($this->exists) {
            $changes = array_diff_assoc($this->attributes, $this->original);
            if (!empty($changes)) {
                return $this->update($changes);
            }
            return true;
        } else {
            $id = Database::insert(static::$table, $this->attributes);
            if ($id) {
                $this->attributes[static::$primaryKey] = $id;
                $this->exists = true;
                $this->original = $this->attributes;
                return true;
            }
            return false;
        }
    }

    // Удаление записи
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $table = static::$table;
        $primaryKey = static::$primaryKey;

        $affected = Database::delete(
            $table,
            "{$primaryKey} = :id",
            ['id' => $this->attributes[$primaryKey]]
        );

        if ($affected > 0) {
            $this->exists = false;
            return true;
        }

        return false;
    }

    // Получение атрибутов как массива
    public function toArray(): array
    {
        return $this->attributes;
    }

    // Счетчик записей
    public static function count(): int
    {
        $table = static::$table;
        $result = Database::fetchOne("SELECT COUNT(*) as count FROM {$table}");
        return (int) ($result['count'] ?? 0);
    }
}