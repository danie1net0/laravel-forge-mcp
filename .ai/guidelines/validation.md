# Validação - Essentials

## Princípio Fundamental

**SEMPRE use Form Requests**, nunca validação inline no controller.

## Criando Form Requests

```bash
php artisan make:request StoreUserRequest
```

## Estrutura Básica

```php
class StoreUserRequest extends FormRequest
{
    // Omitir se não valida autorização
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.min' => 'A senha deve ter no mínimo :min caracteres.',
        ];
    }
}

// Controller:
public function store(StoreUserRequest $request)
{
    $user = User::create($request->validated());
    return redirect()->route('users.show', $user);
}
```

## Rules Comuns

```php
'name' => ['required', 'string', 'max:255']
'email' => ['required', 'email', 'unique:users']
'age' => ['integer', 'min:18', 'max:100']
'price' => ['numeric', 'decimal:2']
'active' => ['boolean']
'data' => ['array']
'file' => ['file', 'max:2048']
'image' => ['image', 'mimes:jpg,png']
'date' => ['date', 'after:today']

// Database
'email' => ['unique:users,email,' . $userId]  // Ignorar próprio registro
'plan_id' => ['exists:plans,id']

// Condicionais
'email' => ['required_if:type,email']
'phone' => ['required_without:email']
```

## Validação de Arrays

```php
public function rules(): array
{
    return [
        'products' => ['required', 'array', 'min:1'],
        'products.*.id' => ['required', 'exists:products,id'],
        'products.*.quantity' => ['required', 'integer', 'min:1'],
    ];
}
```

## Boas Práticas

```php
✅ StoreUserRequest extends FormRequest
✅ ['required', 'string', 'max:255']  // Array syntax
✅ Mensagens em português
✅ Omitir authorize() se não valida

❌ $request->validate() no controller
❌ 'required|string|max:255'  // String syntax
❌ authorize() { return true; }  // Desnecessário
```
