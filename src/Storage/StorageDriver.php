<?php

namespace DefStudio\Telegraph\Storage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use UnexpectedValueException;

abstract class StorageDriver implements \DefStudio\Telegraph\Contracts\StorageDriver
{
    private const MODEL_CLASS_KEY = ':tgph_model_class:';
    private const MODEL_ID_KEY = ':tgph_model_id:';
    private const RAW_ARRAY_KEY = ':tgph_raw_array:';

    final public function set(string $key, mixed $value): static
    {
        $this->storeData($key, $this->dehydrate($value));

        return $this;
    }

    final public function get(string $key, mixed $default = null): mixed
    {
        return $this->hydrate($this->retrieveData($key, $default));
    }

    abstract protected function storeData(string $key, mixed $value): void;

    abstract protected function retrieveData(string $key, mixed $default): mixed;

    private function dehydrate(mixed $value): mixed
    {
        if (is_iterable($value)) {
            /** @var iterable<array-key, mixed> $value */
            $data = Collection::make($value)
                ->map(fn ($item) => $this->dehydrate($item))
                ->toArray();

            return array_key_exists(self::MODEL_CLASS_KEY, $data) || array_key_exists(self::RAW_ARRAY_KEY, $data)
                ? [self::RAW_ARRAY_KEY => $data]
                : $data;
        }

        if ($value instanceof Model) {
            return [
                self::MODEL_CLASS_KEY => $value::class,
                self::MODEL_ID_KEY => $value->getKey(),
            ];
        }

        return $value;
    }

    private function hydrate(mixed $value): mixed
    {
        if (is_iterable($value)) {
            /** @var iterable<array-key, mixed> $value */
            $collection = Collection::make($value);
            if ($collection->has(self::RAW_ARRAY_KEY)) {
                $data = $collection->get(self::RAW_ARRAY_KEY);
                if (!is_array($data)) {
                    throw new UnexpectedValueException('Invalid Telegraph storage array.');
                }

                return array_map(fn ($item) => $this->hydrate($item), $data);
            }

            if ($collection->has(self::MODEL_CLASS_KEY)) {
                $modelClass = $collection->get(self::MODEL_CLASS_KEY);
                if (!is_string($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                    throw new UnexpectedValueException('Invalid Telegraph storage model.');
                }

                return $modelClass::find($collection->get(self::MODEL_ID_KEY));
            }

            return array_map(fn ($item) => $this->hydrate($item), $collection->toArray());
        }

        return $value;
    }
}
