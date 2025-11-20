# Testes - Essentials

## Princípios

- **SEMPRE use Pest** (nunca PHPUnit direto)
- Mensagens **sempre em português**
- Expectations **sempre encadeadas**
- Models **sempre com factories**

## Regra #1: Expectations Encadeadas

```php
// ✅ Sempre:
expect($user)
    ->name->toBe('John')
    ->email->toBe('john@example.com')
    ->and($user->isActive())->toBeTrue();

// ❌ Nunca:
expect($user->name)->toBe('John');
expect($user->email)->toBe('john@example.com');
```

## Estrutura de Diretórios

Estrutura DEVE espelhar o código testado:

```
app/Models/User.php                     → tests/Unit/Models/UserTest.php
app/Services/PaymentService.php         → tests/Unit/Services/PaymentServiceTest.php
app/Actions/CreateUserAction.php        → tests/Unit/Actions/CreateUserActionTest.php
app/Http/Controllers/Api/UserController → tests/Feature/Api/UserControllerTest.php
```

## Sintaxe Básica

```php
it('retorna uma resposta bem-sucedida', function () {
    $this->get('/')
        ->assertStatus(200);
});

test('usuário pode assinar um plano', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $this->actingAs($user)
        ->post('/subscribe', ['plan_id' => $plan->id])
        ->assertSuccessful();
});
```

## Expectations Comuns

```php
expect($value)
    ->toBeTrue()
    ->not->toBeNull()
    ->toBeGreaterThan(0);

expect($array)
    ->toHaveCount(5)
    ->toContain('item');

expect($string)
    ->toContain('substring')
    ->toStartWith('prefix');

// HTTP
$this->get('/api/users')
    ->assertSuccessful()
    ->assertJsonCount(10, 'data');

// Database
expect(User::count())->toBe(1);
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
```

## Boas Práticas

```php
✅ it('descrição clara em português')
✅ expect($user)->name->toBe('John')
✅ User::factory()->create()
✅ Arrange, Act, Assert

❌ it('funciona')
❌ Múltiplos expect() separados
❌ new User(['name' => 'John'])
```
