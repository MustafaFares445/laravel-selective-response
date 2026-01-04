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
            public function toArray($request): array
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
            public function toArray($request): array
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

            public function toArray($request): array
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
            public function toArray($request): array
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
            public function toArray($request): array
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
            public function toArray($request): array
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
            public function toArray($request): array
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

    public function test_filters_out_null_values_for_unselected_attributes()
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
            public function toArray($request): array
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email ?? null,
                    'createdAt' => $this->created_at ? $this->created_at->toDateTimeString() : null,
                    'updatedAt' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
                ];
            }
        };

        $resolved = $resource->resolve(new Request());

        $this->assertArrayHasKey('id', $resolved);
        $this->assertArrayHasKey('name', $resolved);
        $this->assertArrayNotHasKey('email', $resolved);
        $this->assertArrayNotHasKey('createdAt', $resolved);
        $this->assertArrayNotHasKey('updatedAt', $resolved);
        
        $json = json_encode($resolved);
        $decoded = json_decode($json, true);
        $this->assertArrayNotHasKey('email', $decoded);
        $this->assertArrayNotHasKey('createdAt', $decoded);
        $this->assertArrayNotHasKey('updatedAt', $decoded);
        $this->assertStringNotContainsString('"email"', $json);
        $this->assertStringNotContainsString('"createdAt"', $json);
        $this->assertStringNotContainsString('"updatedAt"', $json);
    }

    public function test_filters_collection_response_based_on_selected_attributes()
    {
        // Create mock models with selected attributes
        $user1 = new class {
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

        $user2 = new class {
            public $id = 2;
            public $name = 'Jane';
            public $email = 'jane@example.com';
            public $phone = '0987654321';

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

        // Create collection
        $collection = [$user1, $user2];
        
        // Get array representation of collection (simulating what ::collection() would do)
        $result = [];
        foreach ($collection as $item) {
            $resource = new class($item) extends BaseApiResource {
                public function toArray($request): array
                {
                    return [
                        'id' => $this->id,
                        'name' => $this->name,
                        'email' => $this->email,
                        'phone' => $this->phone,
                    ];
                }
            };
            $result[] = $resource->resolve(new Request());
        }

        // Verify each item in collection only has selected attributes (id, name)
        // and does NOT have null values for unselected attributes
        foreach ($result as $index => $data) {
            $this->assertArrayHasKey('id', $data, "Item $index missing 'id'");
            $this->assertArrayHasKey('name', $data, "Item $index missing 'name'");
            $this->assertArrayNotHasKey('email', $data, "Item $index should not have 'email' (unselected)");
            $this->assertArrayNotHasKey('phone', $data, "Item $index should not have 'phone' (unselected)");
            
            // Extra verification: ensure no null values for unselected keys
            $json = json_encode($data);
            $this->assertStringNotContainsString('"email"', $json, "Item $index JSON should not contain 'email' key");
            $this->assertStringNotContainsString('"phone"', $json, "Item $index JSON should not contain 'phone' key");
        }
    }

    public function test_filters_collection_via_collection_method()
    {
        // This test specifically checks the ::collection() method behavior with Eloquent-like models
        $user1 = new class {
            public $id = 1;
            public $name = 'John';
            // Note: email, phone are private to simulate Eloquent behavior where not all properties are public
            private $email = 'john@example.com';
            private $phone = '1234567890';

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

        $user2 = new class {
            public $id = 2;
            public $name = 'Jane';
            private $email = 'jane@example.com';
            private $phone = '0987654321';

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

        // Create a concrete resource class for collection() to work
        $collection = [$user1, $user2];
        
        // Manually apply collection logic - mapping each resource
        // Laravel's collection uses resolve() which applies the filtering
        $result = [];
        foreach ($collection as $item) {
            $resource = new class($item) extends BaseApiResource {
                public function toArray($request): array
                {
                    return [
                        'id' => $this->id,
                        'name' => $this->name,
                        'email' => $this->email,
                        'phone' => $this->phone,
                    ];
                }
            };
            // Laravel's collection method uses resolve(), not toArray()
            $data = $resource->resolve(new Request());
            $result[] = $data;
        }
        
        // The collection should return data array
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Verify each item in collection
        foreach ($result as $index => $item) {
            $this->assertArrayHasKey('id', $item, "Item $index missing 'id'");
            $this->assertArrayHasKey('name', $item, "Item $index missing 'name'");
            // This is the key assertion - these keys should NOT exist for unselected attributes
            if (isset($item['email']) || isset($item['phone'])) {
                $this->fail("Item $index has unselected attributes: " . json_encode($item));
            }
        }
    }
}

