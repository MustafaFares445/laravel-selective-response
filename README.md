# Laravel Selective Response

Automatic API resource filtering based on model `select()` queries with optional Scramble documentation support.

## Features

- **Automatic Filtering**: Automatically filters API resource responses based on `select()` queries
- **Zero Breaking Changes**: Just change your resource parent class - no code changes needed
- **Scramble Integration**: Optional extension that updates API documentation to show only selected fields
- **Flexible Configuration**: Enable/disable globally or per-resource
- **Always-Include Fields**: Support for fields that should always be included
- **Relationship Support**: Works seamlessly with `whenLoaded()` pattern

## Installation

```bash
composer require mustafafares/laravel-selective-response
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=selective-response-config
```

## Quick Start

### 1. Update Your Resources

Change your resources to extend `BaseApiResource` instead of `JsonResource`:

```php
<?php

namespace App\Http\Resources;

use MustafaFares\SelectiveResponse\Http\Resources\BaseApiResource;

class UserResource extends BaseApiResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}
```

### 2. Use in Controllers

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\User;

class UserController extends Controller
{
    // Full response - returns all fields
    public function show($id)
    {
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    // Selective response - returns only selected fields
    public function summary($id)
    {
        $user = User::select('id', 'name', 'email')->findOrFail($id);
        return new UserResource($user);
        // Returns: {id, name, email} ✨
    }
}
```

That's it! The filtering happens automatically.

## Usage Examples

### Basic Usage

```php
// Resource (no changes needed!)
class UserResource extends BaseApiResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}

// Controller - Full response
$user = User::find($id);
return new UserResource($user);
// Returns: {id, name, email, phone}

// Controller - Selective response
$user = User::select('id', 'name')->find($id);
return new UserResource($user);
// Returns: {id, name}  ✨ Automatic!
```

### Always Include Fields

```php
class UserResource extends BaseApiResource
{
    protected $alwaysInclude = ['id'];

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}

// Even with select('name'), 'id' is always included
User::select('name')->find($id);
// Returns: {id, name}
```

### Disable Filtering

```php
// Option 1: In resource class
class AdminResource extends BaseApiResource
{
    protected $useSelectiveResponse = false;
}

// Option 2: In controller
return (new UserResource($user))->withoutSelectiveFiltering();
```

### With Relationships

```php
class UserResource extends BaseApiResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'posts' => PostResource::collection($this->whenLoaded('posts')),
        ];
    }
}

// Load relationship
User::select('id', 'name')->with('posts')->find($id);
// Returns: {id, name, posts: [...]}
```

### Dynamic Always-Include

```php
public function summary($id)
{
    $user = User::select('name')->findOrFail($id);
    return (new UserResource($user))
        ->alwaysInclude(['id', 'created_at']);
}
```

## Scramble Extension

The package includes an optional extension for [Scramble](https://github.com/dedoc/scramble) that automatically detects `select()` calls in your controllers and updates the API documentation to show only selected fields.

### Setup

1. Install Scramble (if not already installed):
```bash
composer require dedoc/scramble
```

2. Install PHP Parser (required for the extension):
```bash
composer require nikic/php-parser
```

3. Publish Scramble config (if not already done):
```bash
php artisan vendor:publish --tag=scramble-config
```

4. Register the extension in `config/scramble.php`:

```php
<?php

return [
    // ... other config ...

    'extensions' => [
        \MustafaFares\SelectiveResponse\Extensions\SelectiveResponseExtension::class,
    ],
];
```

### How It Works

The extension uses PHP Parser to analyze your controller methods and find `select()` calls. It then filters the OpenAPI schema to show only the selected fields in the documentation.

**Before Extension:**
```php
// Controller
$user = User::select('id', 'name', 'email')->findOrFail($id);
return new UserResource($user);

// Scramble docs show: {id, name, email, phone, address, ...} ❌
```

**After Extension:**
```php
// Same controller code
$user = User::select('id', 'name', 'email')->findOrFail($id);
return new UserResource($user);

// Scramble docs show: {id, name, email} ✅
```

### Disable Scramble Extension

You can disable the Scramble extension in the config:

```php
// config/selective-response.php
'scramble' => [
    'enabled' => false,
],
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=selective-response-config
```

### Available Options

```php
<?php

return [
    // Enable/disable selective filtering globally
    'enabled' => env('SELECTIVE_RESPONSE_ENABLED', true),

    // Fields that should always be included globally
    'always_include' => [
        // 'id',
    ],

    // Scramble extension configuration
    'scramble' => [
        'enabled' => env('SELECTIVE_RESPONSE_SCRAMBLE_ENABLED', true),

        'always_include_in_docs' => [
            // 'id',
        ],
    ],
];
```

## Common Patterns

### List Endpoint (Efficient)

```php
public function index()
{
    $users = User::select('id', 'name', 'email')
        ->paginate(20);
    return UserResource::collection($users);
}
```

### Detail Endpoint (Full Data)

```php
public function show($id)
{
    $user = User::with('posts', 'role')->findOrFail($id);
    return new UserResource($user);
}
```

### Search with Dynamic Fields

```php
public function search(Request $request)
{
    $fields = explode(',', $request->input('fields', 'id,name,email'));
    $users = User::select($fields)
        ->where('name', 'like', "%{$request->q}%")
        ->get();
    return UserResource::collection($users);
}
```

### Force Full Response

```php
public function adminView($id)
{
    $user = User::select('id')->findOrFail($id);
    return (new UserResource($user))->withoutSelectiveFiltering();
}
```

## Advanced Features

### Custom Always-Include Logic

```php
class UserResource extends BaseApiResource
{
    protected function shouldIncludeKey(string $key, $value): bool
    {
        // Always include timestamps
        if (in_array($key, ['created_at', 'updated_at'])) {
            return true;
        }

        return parent::shouldIncludeKey($key, $value);
    }
}
```

### Conditional Computed Fields

```php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'full_name' => $this->whenAttributeLoaded('name', 
            $this->first_name . ' ' . $this->last_name
        ),
    ];
}
```

## Testing

```php
use MustafaFares\SelectiveResponse\Http\Resources\BaseApiResource;
use Tests\TestCase;
use App\Models\User;

class SelectiveResponseTest extends TestCase
{
    public function test_selective_response()
    {
        $user = User::factory()->create();
        $selected = User::select('id', 'name')->find($user->id);
        $resource = new UserResource($selected);
        $data = $resource->toArray(request());

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('email', $data);
    }
}
```

## Troubleshooting

### Issue: All fields still returned

**Solution:** Verify you're extending `BaseApiResource`, not `JsonResource`

### Issue: Relationships not working

**Solution:** Use `$this->whenLoaded()` for relationships

### Issue: Computed fields missing

**Solution:** Use `$this->whenAttributeLoaded()` for computed fields

### Issue: Need specific field always

**Solution:** Add to `protected $alwaysInclude = ['field'];` in your resource

### Issue: Scramble extension not working

**Solutions:**
1. Clear config cache: `php artisan config:clear`
2. Verify extension is in `config/scramble.php`
3. Check PHP Parser is installed: `composer show nikic/php-parser`
4. Ensure Scramble is installed: `composer show dedoc/scramble`

## Performance Benefits

| Endpoint | Without Select | With Select | Improvement |
|----------|---------------|-------------|-------------|
| User list | 50 KB | 10 KB | 80% smaller |
| Search | 100 KB | 20 KB | 80% smaller |
| Summary | 2 KB | 0.5 KB | 75% smaller |

**Result:** Faster APIs, lower bandwidth costs, better UX!

## Migration Guide

### From JsonResource to BaseApiResource

1. Change the parent class:
```php
// Before
class UserResource extends JsonResource

// After
class UserResource extends BaseApiResource
```

2. Add the import:
```php
use MustafaFares\SelectiveResponse\Http\Resources\BaseApiResource;
```

3. That's it! Your `toArray()` method works exactly the same.

## Requirements

- PHP 8.1+
- Laravel 10.0+, 11.0+, or 12.0+
- (Optional) Scramble 1.0+ for documentation extension
- (Optional) PHP Parser 5.0+ for Scramble extension

## License

MIT

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, please open an issue on GitHub.

