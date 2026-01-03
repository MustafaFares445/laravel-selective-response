<?php

namespace MustafaFares\SelectiveResponse\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use MustafaFares\SelectiveResponse\Http\Resources\MissingAttribute;
use MustafaFares\SelectiveResponse\Traits\SelectiveResponse;

class BaseApiResource extends JsonResource
{
    use SelectiveResponse;

    protected $useSelectiveResponse = true;
    protected $alwaysInclude = [];

    public function __get($key)
    {
        if ($this->resource && method_exists($this->resource, 'getAttributes')) {
            $attributes = $this->resource->getAttributes();
            
            if (!array_key_exists($key, $attributes)) {
                if (method_exists($this->resource, 'hasGetMutator') && $this->resource->hasGetMutator($key)) {
                    return parent::__get($key);
                }
                
                if (method_exists($this->resource, 'relationLoaded') && $this->resource->relationLoaded($key)) {
                    return parent::__get($key);
                }
                
                try {
                    return parent::__get($key);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'does not exist or was not retrieved')) {
                        return new MissingAttribute();
                    }
                    throw $e;
                }
            }
        }

        return parent::__get($key);
    }

    public function toArray($request)
    {
        $data = parent::toArray($request);
        
        if ($this->useSelectiveResponse && $this->resource && is_array($data)) {
            $data = $this->applySelectiveFiltering($data);
        }
        
        return $data;
    }

    public function resolve($request = null)
    {
        $data = parent::resolve($request);

        if ($this->useSelectiveResponse && $this->resource && is_array($data)) {
            $data = $this->applySelectiveFiltering($data);
        }

        return $data;
    }

    protected function applySelectiveFiltering(array $data): array
    {
        $filtered = $this->filterToSelectedAttributes($data);

        foreach ($this->alwaysInclude as $attr) {
            if (isset($data[$attr]) && !isset($filtered[$attr])) {
                $value = $data[$attr];
                if (!($value instanceof MissingAttribute)) {
                    $filtered[$attr] = $value;
                }
            }
        }

        return $this->cleanMissingAttributes($filtered);
    }

    protected function cleanMissingAttributes(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if ($value instanceof MissingAttribute) {
                continue;
            }
            
            if (is_array($value)) {
                $value = $this->cleanMissingAttributes($value);
                if (empty($value)) {
                    continue;
                }
            }
            
            $cleaned[$key] = $value;
        }
        
        return $cleaned;
    }

    public function withoutSelectiveFiltering(): self
    {
        $this->useSelectiveResponse = false;
        return $this;
    }

    public function alwaysInclude(array $attributes): self
    {
        $this->alwaysInclude = array_merge($this->alwaysInclude, $attributes);
        return $this;
    }
}

