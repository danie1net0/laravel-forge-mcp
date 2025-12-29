<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class SecurityBestPracticesResource extends Resource
{
    protected string $uri = 'forge://best-practices/security';

    protected string $name = 'Laravel Forge Security Best Practices';

    protected string $description = 'Essential security configurations and hardening steps for Laravel applications on Forge';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # Security Best Practices for Laravel on Forge

        ## Essential Security Checklist

        ### Server Level

        - ✅ SSH key authentication only (disable password auth)
        - ✅ Firewall configured (only necessary ports open)
        - ✅ Regular security updates applied
        - ✅ Non-root user for deployments
        - ✅ Fail2ban enabled for brute force protection
        - ✅ Server monitoring and alerts configured

        ### Application Level

        - ✅ HTTPS enforced on all routes
        - ✅ HSTS headers enabled
        - ✅ Environment variables never committed to Git
        - ✅ Database credentials rotated regularly
        - ✅ Debug mode disabled in production
        - ✅ Error reporting configured correctly
        - ✅ CSRF protection enabled
        - ✅ SQL injection prevention (use Eloquent/Query Builder)
        - ✅ XSS protection (escape output)
        - ✅ File upload validation
        - ✅ Rate limiting on API routes
        - ✅ Authentication secured (password hashing, 2FA)

        ## 1. HTTPS and SSL Configuration

        ### Force HTTPS in Laravel

        In `App\Providers\AppServiceProvider`:

        ```php
        public function boot()
        {
            if ($this->app->environment('production')) {
                URL::forceScheme('https');
            }
        }
        ```

        ### Enable HSTS Headers

        Add to `config/secure-headers.php`:

        ```php
        'strict-transport-security' => 'max-age=31536000; includeSubDomains',
        ```

        Or in Nginx config:

        ```nginx
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
        ```

        ### SSL/TLS Best Practices

        - Use Let's Encrypt for free SSL certificates
        - Enable auto-renewal (Forge handles this)
        - Use TLS 1.2 minimum (TLS 1.3 preferred)
        - Disable weak ciphers
        - Test SSL configuration: https://www.ssllabs.com/ssltest/

        ## 2. Environment Variables Security

        ### Never Commit Secrets

        Add to `.gitignore`:
        ```
        .env
        .env.*
        !.env.example
        ```

        ### Secure Environment Variables

        In Forge UI, set:

        ```env
        # Application
        APP_KEY=base64:...  # Use `php artisan key:generate`
        APP_DEBUG=false     # CRITICAL: Always false in production
        APP_ENV=production

        # Database
        DB_PASSWORD=strong-random-password-here

        # AWS/Third-party
        AWS_SECRET_ACCESS_KEY=secret
        STRIPE_SECRET=secret

        # Mail
        MAIL_PASSWORD=secret
        ```

        ### Rotate Credentials Regularly

        - Change database passwords quarterly
        - Rotate API keys after employee departure
        - Update deploy keys periodically
        - Regenerate APP_KEY if compromised (will invalidate sessions)

        ## 3. Firewall Configuration

        ### Default Ports to Open

        ```
        22   - SSH (from specific IPs only if possible)
        80   - HTTP (for Let's Encrypt challenges)
        443  - HTTPS (application traffic)
        ```

        ### Ports to NEVER Open Publicly

        ```
        3306 - MySQL
        5432 - PostgreSQL
        6379 - Redis
        ```

        Access databases via SSH tunnel instead:

        ```bash
        ssh -L 3306:localhost:3306 forge@your-server-ip
        ```

        ### Recommended Firewall Rules

        ```bash
        # SSH from office IP only (if possible)
        # Create rule: port 22, IP: your.office.ip/32

        # Database access via application only
        # No firewall rule needed (bound to localhost)

        # Redis access via application only
        # No firewall rule needed (bound to localhost)
        ```

        ## 4. Database Security

        ### Strong Passwords

        ```bash
        # Generate strong password (32 characters)
        openssl rand -base64 32
        ```

        ### Principle of Least Privilege

        Create separate database users for each application:

        ```sql
        -- Application user (limited permissions)
        CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'strong-password';
        GRANT SELECT, INSERT, UPDATE, DELETE ON app_db.* TO 'app_user'@'localhost';

        -- Admin user (full permissions, used rarely)
        GRANT ALL PRIVILEGES ON app_db.* TO 'admin_user'@'localhost';
        ```

        ### Protect Against SQL Injection

        ✅ **DO:** Use Eloquent or Query Builder
        ```php
        User::where('email', $request->email)->first();
        DB::table('users')->where('email', $request->email)->get();
        ```

        ❌ **DON'T:** Use raw queries with user input
        ```php
        DB::select("SELECT * FROM users WHERE email = '$email'");
        ```

        If you must use raw queries, use bindings:
        ```php
        DB::select("SELECT * FROM users WHERE email = ?", [$email]);
        ```

        ### Backup Encryption

        Encrypt database backups before storing:

        ```bash
        mysqldump -u root -p database_name | gzip | \
          openssl enc -aes-256-cbc -salt -out backup.sql.gz.enc
        ```

        ## 5. Authentication & Authorization

        ### Password Hashing

        Laravel uses bcrypt by default (good!):

        ```php
        // Hash password
        $hashed = Hash::make('password');

        // Verify
        if (Hash::check('password', $hashed)) {
            // Correct!
        }
        ```

        ### Rate Limiting

        Protect login endpoints:

        ```php
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1'); // 5 attempts per minute
        ```

        ### Two-Factor Authentication

        Consider packages like:
        - `laravel/fortify` with 2FA
        - `pragmarx/google2fa-laravel`

        ### Session Security

        In `config/session.php`:

        ```php
        'secure' => true,        // HTTPS only
        'http_only' => true,     // Prevent JavaScript access
        'same_site' => 'strict', // CSRF protection
        ```

        ## 6. File Upload Security

        ### Validate File Types

        ```php
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png|max:2048',
            'document' => 'required|file|mimes:pdf,docx|max:10240',
        ]);
        ```

        ### Store Outside Web Root

        ```php
        // Store in storage/app (not public)
        $path = $request->file('avatar')->store('avatars');

        // Serve via controller with authentication
        Route::get('/avatars/{file}', function ($file) {
            return response()->file(storage_path("app/avatars/{$file}"));
        })->middleware('auth');
        ```

        ### Scan Uploaded Files

        Consider virus scanning for user uploads:

        ```bash
        # Install ClamAV
        sudo apt-get install clamav clamav-daemon

        # Scan file
        clamscan /path/to/uploaded/file
        ```

        ## 7. XSS Prevention

        ### Always Escape Output

        Blade automatically escapes:
        ```blade
        {{ $user->name }}  <!-- Safe -->
        ```

        Only use raw output when necessary:
        ```blade
        {!! $trustedHtml !!}  <!-- Only for trusted content -->
        ```

        ### Content Security Policy

        Add CSP header to prevent XSS:

        ```php
        // In middleware or kernel
        return $response->withHeaders([
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'",
        ]);
        ```

        ### Sanitize User Input

        ```php
        use Illuminate\Support\Str;

        $clean = Str::of($userInput)->trim()->strip_tags();
        ```

        ## 8. CSRF Protection

        ### Enable CSRF for All Forms

        Laravel includes this by default:

        ```blade
        <form method="POST" action="/profile">
            @csrf
            <!-- form fields -->
        </form>
        ```

        ### Exempt API Routes (if using tokens)

        In `App\Http\Middleware\VerifyCsrfToken`:

        ```php
        protected $except = [
            'api/*',  // If using API token authentication
        ];
        ```

        ## 9. API Security

        ### Use API Tokens

        ```php
        // Generate token
        $token = $user->createToken('api-token')->plainTextToken;

        // Protect routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
        });
        ```

        ### Rate Limit API Endpoints

        ```php
        Route::middleware('throttle:60,1')->group(function () {
            // API routes - 60 requests per minute
        });
        ```

        ### Validate All Input

        ```php
        $request->validate([
            'email' => 'required|email',
            'age' => 'required|integer|min:18|max:120',
        ]);
        ```

        ### Use CORS Correctly

        In `config/cors.php`:

        ```php
        'allowed_origins' => [
            'https://your-frontend.com',
            // Never use '*' in production
        ],
        ```

        ## 10. Dependency Security

        ### Keep Dependencies Updated

        ```bash
        # Check for vulnerabilities
        composer audit

        # Update dependencies
        composer update

        # Laravel security updates
        composer require laravel/framework
        ```

        ### Use Composer Lock File

        Always commit `composer.lock`:
        - Ensures consistent versions across environments
        - Security patches applied consistently

        ### Regular Security Audits

        ```bash
        # Install security checker
        composer require --dev enlightn/security-checker

        # Run audit
        php artisan security:check
        ```

        ## 11. Logging and Monitoring

        ### Log Security Events

        ```php
        // Failed login attempts
        Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        // Suspicious activity
        Log::alert('Unusual activity detected', [
            'user_id' => $user->id,
            'action' => 'mass_delete',
        ]);
        ```

        ### Monitor for Intrusions

        - Setup log monitoring (e.g., Papertrail, Loggly)
        - Alert on failed login spikes
        - Monitor for SQL injection attempts
        - Track API rate limit violations

        ### Audit Trail

        Log important actions:

        ```php
        // Track critical changes
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User role changed from editor to admin');
        ```

        ## 12. Regular Maintenance

        ### Weekly Tasks

        - [ ] Review application logs for errors
        - [ ] Check failed login attempts
        - [ ] Monitor disk usage
        - [ ] Verify backups completed successfully

        ### Monthly Tasks

        - [ ] Update dependencies
        - [ ] Review user access permissions
        - [ ] Check SSL certificate expiry
        - [ ] Audit database users
        - [ ] Review firewall rules
        - [ ] Security scan with tools

        ### Quarterly Tasks

        - [ ] Rotate database passwords
        - [ ] Rotate API keys
        - [ ] Review and remove unused users/apps
        - [ ] Full security audit
        - [ ] Penetration testing (for critical apps)

        ## 13. Incident Response Plan

        ### If Security Breach Suspected

        1. **Immediate Actions:**
           - Take affected service offline
           - Preserve logs for investigation
           - Block suspected attacker IPs

        2. **Assess Damage:**
           - Review access logs
           - Check what data was accessed
           - Identify attack vector

        3. **Contain Breach:**
           - Rotate all credentials
           - Close security holes
           - Patch vulnerabilities

        4. **Restore Service:**
           - Restore from clean backup if needed
           - Verify system integrity
           - Bring service back online

        5. **Post-Incident:**
           - Document what happened
           - Implement prevention measures
           - Notify affected users if required (GDPR, etc.)
           - Review and update security policies

        ## 14. Compliance Considerations

        ### GDPR Compliance

        - Encrypt personal data at rest
        - Provide data export functionality
        - Implement data deletion
        - Log data access for auditing
        - Obtain consent for data collection

        ### PCI DSS (if handling payments)

        - Never store credit card data
        - Use payment gateways (Stripe, PayPal)
        - Implement proper encryption
        - Regular security scans
        - Access logging

        ## 15. Security Tools & Resources

        ### Recommended Tools

        **Scanning:**
        - OWASP ZAP - Web app security scanner
        - Nessus - Vulnerability scanner
        - SSL Labs - SSL/TLS checker

        **Monitoring:**
        - Fail2ban - Brute force protection
        - ModSecurity - Web application firewall
        - Cloudflare - DDoS protection

        **Auditing:**
        - Laravel Enlightn - Security auditor
        - Snyk - Dependency vulnerability scanner
        - SonarQube - Code quality/security

        ### Security Resources

        - https://owasp.org/www-project-top-ten/
        - https://laravel.com/docs/security
        - https://forge.laravel.com/docs
        - https://cheatsheetseries.owasp.org/

        ## Conclusion

        Security is an ongoing process, not a one-time setup. Regular monitoring, updates, and audits are essential for maintaining a secure Laravel application on Forge.

        **Remember:** The most secure system is useless if not maintained. Make security a regular part of your development and operations workflow.
        MD;

        return Response::text($content);
    }
}
