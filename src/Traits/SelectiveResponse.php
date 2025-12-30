<?php

namespace MustafaFares\SelectiveResponse\Traits;

trait SelectiveResponse
{
    protected function filterToSelectedAttributes(array $data): array
    {
        if (!$this->resource) {
            return $data;
        }

        $loadedAttributes = array_keys($this->resource->getAttributes());
        if (empty($loadedAttributes)) {
            return $data;
        }

        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $loadedAttributes) || $this->shouldIncludeKey($key, $value)) {
                $filteredData[$key] = $value;
            }
        }

        return $filteredData;
    }

    protected function shouldIncludeKey(string $key, $value): bool
    {
        return false;
    }
}

