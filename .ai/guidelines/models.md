# Models - Essentials

## Estrutura Base

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    // 1. Traits
    use HasFactory, SoftDeletes;

    // 2. Configuração
    protected $table = 'users';
    protected $primaryKey = 'id';

    // 3. Mass Assignment
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 4. Casts
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 5. Relationships
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // 6. Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // 7. Accessors/Mutators
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
        );
    }

    // 8. Business Logic
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

## Relationships

```php
// HasMany
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}

// BelongsTo
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// BelongsToMany
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class)
        ->withPivot('active')
        ->withTimestamps();
}

// HasOne
public function profile(): HasOne
{
    return $this->hasOne(Profile::class);
}

// MorphMany
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

## Scopes

```php
// Query Scope
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

// Uso:
User::active()->get();

// Scope com parâmetro
public function scopeOfType($query, string $type)
{
    return $query->where('type', $type);
}

// Uso:
User::ofType('premium')->get();
```

## Casts

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'status' => SubscriptionStatus::class,  // Enum
    ];
}
```

## Accessors/Mutators

```php
// Accessor
protected function firstName(): Attribute
{
    return Attribute::make(
        get: fn ($value, $attributes) => explode(' ', $attributes['name'])[0],
    );
}

// Mutator
protected function password(): Attribute
{
    return Attribute::make(
        set: fn ($value) => Hash::make($value),
    );
}

// Ambos
protected function name(): Attribute
{
    return Attribute::make(
        get: fn ($value) => ucfirst($value),
        set: fn ($value) => strtolower($value),
    );
}
```

## Query Builders Customizados

```php
// App/Models/Builders/UserBuilder.php
class UserBuilder extends Builder
{
    public function active(): self
    {
        return $this->where('status', 'active');
    }

    public function verified(): self
    {
        return $this->whereNotNull('email_verified_at');
    }
}

// Model
public function newEloquentBuilder($query): UserBuilder
{
    return new UserBuilder($query);
}

// Uso com type hints
User::query()->active()->verified()->get();
```

## Factories

```php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }

    public function admin(): static
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }
}

// Uso:
User::factory()->count(10)->create();
User::factory()->admin()->create();
```

## Performance

**Eager Loading:**
```php
// ✅ N+1 resolvido
$users = User::with('posts')->get();

// ✅ Nested
$users = User::with('posts.comments')->get();

// ✅ Conditional
$users = User::with(['posts' => fn($q) => $q->published()])->get();
```

**Select específico:**
```php
User::select('id', 'name', 'email')->get();
```

**Chunking:**
```php
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process
    }
});
```

## Observação

- Usar `fillable` (whitelist) ao invés de `guarded` (blacklist)
- Sempre type hint relationships
- Evitar lógica complexa em models
- Preferir Query Builders para queries complexas
- Usar Factories para testes
- Cuidado com N+1 (sempre usar `with()`)
