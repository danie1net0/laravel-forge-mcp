# Code Style - Essentials

## Padrões

- **PSR-12** - Padrão oficial PHP
- **Laravel Pint** - Formatação automática
- **PHPStan nível 5+** - Análise estática
- **Strict types** - Sempre declarar
- **Type hints** - Obrigatório em tudo

## Nomenclatura

**Classes:**
```php
UserController          // Controllers
CreateUserAction        // Actions
StripeService          // Services
CreateUserData         // DTOs
SubscriptionStatus     // Enums
```

**Métodos:**
```php
public function getUserById(int $id): User        // camelCase
public function createSubscription(): void        // verbos
public function isActive(): bool                  // predicados com 'is/has'
```

**Variáveis:**
```php
$userId = 1;              // camelCase
$stripeCustomerId = ''; // descritivo
```

## Type Safety

**Sempre usar:**
```php
<?php

declare(strict_types=1);

namespace App\Actions;

class CreateUserAction
{
    public function execute(CreateUserData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
        ]);
    }
}
```

## Early Returns

**Preferir:**
```php
public function store(Request $request): Response
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    if (!$request->has('email')) {
        return back()->withErrors(['email' => 'Required']);
    }

    $user = $this->createUser($request->validated());

    return redirect()->route('users.show', $user);
}
```

**Evitar:**
```php
public function store(Request $request): Response
{
    if (auth()->check()) {
        if ($request->has('email')) {
            $user = $this->createUser($request->validated());
            return redirect()->route('users.show', $user);
        } else {
            return back()->withErrors(['email' => 'Required']);
        }
    } else {
        return redirect()->route('login');
    }
}
```

## Named Arguments

```php
// Usar quando melhora legibilidade
$subscription = $service->create(
    customerId: $user->stripe_id,
    priceId: $plan->stripe_price,
    quantity: 1
);
```

## Imports

**Organizar:**
```php
<?php

namespace App\Http\Controllers;

// 1. PHP nativo
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// 2. Vendor
use Spatie\LaravelData\Data;

// 3. App
use App\Actions\CreateUserAction;
use App\Models\User;
```

## Arrays

**Preferir sintaxe curta:**
```php
$users = ['John', 'Jane'];  // ✅
$users = array('John', 'Jane');  // ❌

// Trailing comma em multiline
$data = [
    'name' => 'John',
    'email' => 'john@example.com',  // ← trailing comma
];
```

## Strings

**Usar aspas simples quando possível:**
```php
$name = 'John';           // ✅
$name = "John";           // ❌
$name = "Hello $user";    // ✅ (interpolação)
```

## Comparações

**Usar strict:**
```php
if ($status === 'active') {}     // ✅
if ($status == 'active') {}      // ❌

if (in_array($id, $ids, true)) {} // ✅
```

## Null Coalescing

```php
$name = $request->input('name') ?? 'Guest';

$user->name ??= 'Guest';  // Assign if null
```

## Métodos Estáticos

**Evitar quando possível. Preferir injeção:**
```php
// Preferir:
public function __construct(
    private StripeService $stripe
) {}

// Evitar:
StripeService::create();
```

## Comentários

**Apenas quando necessário:**
```php
// ❌ Óbvio
// Get user by ID
public function getUserById(int $id): User

// ✅ Útil
// Sync user with Stripe and update local status
public function syncWithStripe(User $user): void
```
