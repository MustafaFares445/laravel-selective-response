<?php

namespace MustafaFares\SelectiveResponse\Traits;

trait SelectiveResponse
{
    protected function filterToSelectedAttributes(array $data): array
    {
        if (!$this->resource) {
            return $this->removeMissingAttributes($data);
        }

        $loadedAttributes = array_keys($this->resource->getAttributes());
        if (empty($loadedAttributes)) {
            return $this->removeMissingAttributes($data);
        }

        $filteredData = [];
        foreach ($data as $key => $value) {
            if ($value instanceof \MustafaFares\SelectiveResponse\Http\Resources\MissingAttribute) {
                continue;
            }
            
            $value = $this->removeMissingAttributes($value);
            
            // Filter out null values for keys that aren't in loaded attributes
            // This handles cases where MissingAttribute was converted to null
            if ($value === null && !in_array($key, $loadedAttributes)) {
                continue;
            }
            
            // Only include keys that are in the loaded/selected attributes
            if (in_array($key, $loadedAttributes)) {
                $filteredData[$key] = $value;
            } elseif ($this->shouldIncludeKey($key, $value)) {
                $filteredData[$key] = $value;
            }
        }

        return $filteredData;
    }

    protected function removeMissingAttributes($value)
    {
        if ($value instanceof \MustafaFares\SelectiveResponse\Http\Resources\MissingAttribute) {
            return null;
        }

        if (is_array($value)) {
            $cleaned = [];
            foreach ($value as $key => $item) {
                if ($item instanceof \MustafaFares\SelectiveResponse\Http\Resources\MissingAttribute) {
                    continue;
                }
                $cleaned[$key] = $this->removeMissingAttributes($item);
            }
            return $cleaned;
        }

        return $value;
    }

    protected function shouldIncludeKey(string $key, $value): bool
    {
        return false;
    }
}

