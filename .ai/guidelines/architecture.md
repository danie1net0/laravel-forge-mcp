# Arquitetura - Essentials

## Princípios

- **Controllers magros** - apenas coordenam
- **Models focados** - lógica de domínio
- **Actions para negócio** - lógica complexa isolada
- **Services para externo** - APIs e integrações
- **DTOs com spatie/laravel-data** - sempre que instalado
- **Single Responsibility** - uma classe, uma responsabilidade

## Estrutura

```
app/
├── Actions/              # Lógica de negócio
├── Services/             # Integrações externas
├── DTOs/                 # Data Transfer Objects
├── Enums/                # Valores fixos
├── Jobs/                 # Trabalhos em fila
├── Events/               # Eventos
├── Listeners/            # Ouvintes de eventos
├── Observers/            # Observers de models
└── Traits/               # Traits reutilizáveis
```

## Actions

**Quando usar:**
- Lógica de negócio complexa
- Operações multi-step
- Código reutilizável
- Múltiplos models envolvidos

**Nomenclatura:** `[Verbo][Substantivo]Action`

```php
class CreateSubscriptionAction
{
    public function __construct(
        private StripeService $stripe
    ) {}

    public function execute(User $user, Plan $plan): Subscription
    {
        $stripeSubscription = $this->stripe->createSubscription(
            $user->stripe_customer_id,
            $plan->stripe_price_id
        );

        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'stripe_subscription_id' => $stripeSubscription->id,
            'status' => 'active',
        ]);

        event(new SubscriptionCreated($subscription));

        return $subscription;
    }
}
```

## Services

**Quando usar:**
- APIs externas (Stripe, AWS)
- Envio de emails
- Upload de arquivos
- Comunicação externa

```php
class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createSubscription(string $customerId, string $priceId)
    {
        return $this->stripe->subscriptions->create([
            'customer' => $customerId,
            'items' => [['price' => $priceId]],
        ]);
    }
}
```

## DTOs

**⚠️ IMPORTANTE:** Sempre use `spatie/laravel-data` quando instalado.

```php
use Spatie\LaravelData\Data;

class CreateSubscriptionData extends Data
{
    public function __construct(
        public int $userId,
        public int $planId,
        public ?string $couponCode = null,
    ) {}
}

// Uso:
$data = CreateSubscriptionData::from($request->all());
```

## Enums

```php
enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Ativa',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }
}
```

## Jobs

```php
class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new WelcomeMail($this->user));
    }
}

// Dispatch:
SendWelcomeEmailJob::dispatch($user);
```

## Regras de Decisão

**Controllers:**
- ✅ Validar entrada
- ✅ Chamar Actions/Services
- ✅ Retornar views/JSON
- ❌ Lógica de negócio
- ❌ Queries diretas

**Models:**
- ✅ Relationships
- ✅ Scopes
- ✅ Casts
- ❌ Lógica complexa
- ❌ Integrações externas

**Actions:**
- ✅ Lógica de negócio
- ✅ Orquestração
- ✅ Reutilização
- ❌ APIs externas

**Services:**
- ✅ APIs externas
- ✅ Infraestrutura
- ❌ Lógica de negócio
- ❌ Queries de banco
