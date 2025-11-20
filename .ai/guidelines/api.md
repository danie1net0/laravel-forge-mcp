# API Resources - Essentials

## Quando Usar

✅ **SEMPRE use API Resources** para retornar dados JSON, exceto respostas muito simples.

## Criando Resources

```bash
php artisan make:resource UserResource
```

## Resource Básico

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

// Uso:
return new UserResource($user);
return UserResource::collection($users);
```

## Atributos Condicionais

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,

        // Apenas para admin
        'email_verified_at' => $this->when(
            $request->user()?->isAdmin(),
            $this->email_verified_at
        ),

        // Múltiplos campos sensíveis
        $this->mergeWhen($request->user()?->isAdmin(), [
            'last_login_at' => $this->last_login_at,
            'stripe_customer_id' => $this->stripe_customer_id,
        ]),
    ];
}
```

## Relacionamentos (Evita N+1)

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,

        // Apenas se foi eager loaded
        'author' => UserResource::make($this->whenLoaded('user')),
        'comments' => CommentResource::collection($this->whenLoaded('comments')),

        // Contagens
        'comments_count' => $this->whenCounted('comments'),
    ];
}

// Controller:
$post->load(['user', 'comments']);
return new PostResource($post);
```

## Paginação

```php
public function index()
{
    $users = User::paginate(15);
    return UserResource::collection($users);
}
```

## Boas Práticas

```php
✅ return new UserResource($user);
✅ Use whenLoaded() para relacionamentos
✅ Use whenCounted() para contagens
✅ Oculte dados sensíveis com when()

❌ return response()->json($user);
❌ 'posts' => $this->posts (causa N+1)
❌ Expor passwords, tokens
```
