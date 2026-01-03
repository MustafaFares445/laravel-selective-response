<?php

namespace MustafaFares\SelectiveResponse\Tests\Unit;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use MustafaFares\SelectiveResponse\Http\Resources\BaseApiResource;
use MustafaFares\SelectiveResponse\Tests\TestCase;

class BaseApiResourceTest extends TestCase
{
    public function test_filters_response_based_on_selected_attributes()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';
            public $email = 'john@example.com';
            public $phone = '1234567890';

            public function getAttributes()
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                ];
            }
        };

        $resource = new class($model) extends BaseApiResource {
            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                ];
            }
        };

        $data = $resource->resolve(new Request());

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('phone', $data);
    }

    public function test_returns_all_fields_when_no_select_used()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';
            public $email = 'john@example.com';

            public function getAttributes()
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                ];
            }
        };

        $resource = new class($model) extends BaseApiResource {
            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                ];
            }
        };

        $data = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
    }

    public function test_always_include_fields()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';

            public function getAttributes()
            {
                return [
                    'name' => $this->name,
                ];
            }
        };

        $resource = new class($model) extends BaseApiResource {
            protected $alwaysInclude = ['id'];

            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                ];
            }
        };

        $data = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function test_without_selective_filtering()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';
            public $email = 'john@example.com';

            public function getAttributes()
            {
                return [
                    'id' => $this->id,
                ];
            }
        };

        $resource = new class($model) extends BaseApiResource {
            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                ];
            }
        };

        $resource->withoutSelectiveFiltering();
        $data = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
    }

    public function test_always_include_method()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';
            public $email = 'john@example.com';

            public function getAttributes()
            {
                return [
                    'name' => $this->name,
                ];
            }
        };

        $resource = new class($model) extends BaseApiResource {
            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                ];
            }
        };

        $resource->alwaysInclude(['id', 'email']);
        $data = $resource->toArray(new Request());

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
    }

    public function test_handles_missing_attributes_gracefully()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';

            public function getAttributes()
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                ];
            }

            public function __get($key)
            {
                $attributes = $this->getAttributes();
                if (!array_key_exists($key, $attributes)) {
                    throw new \Exception("The attribute [{$key}] either does not exist or was not retrieved for model [App\\Models\\User].");
                }
                return $attributes[$key];
            }

            public function hasGetMutator($key)
            {
                return false;
            }

            public function relationLoaded($key)
            {
                return false;
            }
        };

        $resource = new class($model) extends BaseApiResource {
            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'phoneNumber' => $this->phone_number ?? null,
                ];
            }
        };

        $resolved = $resource->resolve(new Request());

        $this->assertArrayHasKey('id', $resolved);
        $this->assertArrayHasKey('name', $resolved);
        $this->assertArrayNotHasKey('email', $resolved);
        $this->assertArrayNotHasKey('phoneNumber', $resolved);
    }

    public function test_handles_method_calls_on_missing_attributes()
    {
        $model = new class {
            public $id = 1;
            public $name = 'John';

            public function getAttributes()
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                ];
            }

            public function __get($key)
            {
                $attributes = $this->getAttributes();
                if (!array_key_exists($key, $attributes)) {
                    throw new \Exception("The attribute [{$key}] either does not exist or was not retrieved for model [App\\Models\\User].");
                }
                return $attributes[$key];
            }

            public function hasGetMutator($key)
            {
                return false;
            }

            public function relationLoaded($key)
            {
                return false;
            }
        };

        $resource = new class($model) extends BaseApiResource {
            public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'createdAt' => $this->created_at->toDateTimeString(),
                    'updatedAt' => $this->updated_at->toDateTimeString(),
                ];
            }
        };

        $resolved = $resource->resolve(new Request());

        $this->assertArrayHasKey('id', $resolved);
        $this->assertArrayHasKey('name', $resolved);
        $this->assertArrayNotHasKey('createdAt', $resolved);
        $this->assertArrayNotHasKey('updatedAt', $resolved);
        
        $json = json_encode($resolved);
        $decoded = json_decode($json, true);
        $this->assertArrayNotHasKey('createdAt', $decoded);
        $this->assertArrayNotHasKey('updatedAt', $decoded);
        $this->assertStringNotContainsString('createdAt', $json);
        $this->assertStringNotContainsString('updatedAt', $json);
    }
}

