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

        $data = $resource->toArray(new Request());

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
}

