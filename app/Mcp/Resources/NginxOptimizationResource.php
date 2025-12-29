<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class NginxOptimizationResource extends Resource
{
    protected string $uri = 'forge://guides/nginx-optimization';

    protected string $name = 'Nginx Optimization Guide';

    protected string $description = 'Nginx performance tuning and configuration optimization for Laravel on Forge';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # Nginx Optimization Guide for Laravel Forge

        ## Overview

        Nginx is the web server used by Forge. Proper configuration can significantly
        improve performance, security, and reliability of your Laravel applications.

        ## Forge Nginx Configuration

        ### Default Configuration Location

        - Site config: `/etc/nginx/sites-available/[domain]`
        - Main config: `/etc/nginx/nginx.conf`
        - Includes: `/etc/nginx/forge-conf/[domain]/*`

        ### Editing via Forge

        1. Server → Sites → [Your Site]
        2. Click "Nginx Configuration"
        3. Edit and save

        ### Editing via MCP

        ```
        Use get-nginx-template-tool to view current config
        Use update-nginx-template-tool to modify
        ```

        ## Performance Optimization

        ### 1. Worker Processes

        In `/etc/nginx/nginx.conf`:

        ```nginx
        # Auto-detect CPU cores
        worker_processes auto;

        # Max connections per worker
        events {
            worker_connections 4096;
            multi_accept on;
            use epoll;
        }
        ```

        **Recommended:**
        - `worker_processes auto` - matches CPU cores
        - `worker_connections 4096` - for high-traffic sites

        ### 2. Gzip Compression

        Add to site config or nginx.conf:

        ```nginx
        gzip on;
        gzip_vary on;
        gzip_proxied any;
        gzip_comp_level 6;
        gzip_min_length 1000;
        gzip_types
            text/plain
            text/css
            text/javascript
            application/javascript
            application/json
            application/xml
            image/svg+xml
            application/font-woff
            application/font-woff2;
        ```

        **Impact:** Reduces transfer size by 60-80%.

        ### 3. Static Asset Caching

        ```nginx
        location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
            access_log off;
            log_not_found off;
        }
        ```

        **Impact:** Reduces server load, faster repeat visits.

        ### 4. FastCGI Caching

        For read-heavy sites, cache PHP responses:

        ```nginx
        # In http block
        fastcgi_cache_path /tmp/nginx-cache levels=1:2 keys_zone=LARAVEL:100m inactive=60m;
        fastcgi_cache_key "$scheme$request_method$host$request_uri";

        # In server block
        set $skip_cache 0;

        # Don't cache logged in users
        if ($http_cookie ~* "laravel_session") {
            set $skip_cache 1;
        }

        # Don't cache POST requests
        if ($request_method = POST) {
            set $skip_cache 1;
        }

        location ~ \.php$ {
            fastcgi_cache LARAVEL;
            fastcgi_cache_valid 200 60m;
            fastcgi_cache_bypass $skip_cache;
            fastcgi_no_cache $skip_cache;
            add_header X-FastCGI-Cache $upstream_cache_status;

            # ... rest of fastcgi config
        }
        ```

        ### 5. Buffer Optimization

        ```nginx
        # Buffers
        client_body_buffer_size 128k;
        client_max_body_size 64m;
        client_header_buffer_size 1k;
        large_client_header_buffers 4 32k;

        # FastCGI buffers
        fastcgi_buffer_size 32k;
        fastcgi_buffers 8 32k;
        fastcgi_busy_buffers_size 64k;

        # Proxy buffers (if using upstream)
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
        proxy_busy_buffers_size 256k;
        ```

        ### 6. Connection Optimization

        ```nginx
        # Keep connections alive
        keepalive_timeout 65;
        keepalive_requests 1000;

        # Timeouts
        send_timeout 60;
        client_body_timeout 60;
        client_header_timeout 60;

        # TCP optimization
        tcp_nopush on;
        tcp_nodelay on;
        sendfile on;
        ```

        ## Security Hardening

        ### 1. Security Headers

        ```nginx
        # Hide Nginx version
        server_tokens off;

        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin" always;

        # HSTS (only after confirming HTTPS works)
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

        # Content Security Policy (customize as needed)
        add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;
        ```

        ### 2. Rate Limiting

        ```nginx
        # In http block
        limit_req_zone $binary_remote_addr zone=one:10m rate=10r/s;
        limit_conn_zone $binary_remote_addr zone=addr:10m;

        # In server/location block
        limit_req zone=one burst=20 nodelay;
        limit_conn addr 10;
        ```

        ### 3. Block Bad Bots

        ```nginx
        # Block bad bots
        if ($http_user_agent ~* (scrapy|curl|wget|python|java|Go-http-client)) {
            return 403;
        }

        # Block empty user agent
        if ($http_user_agent = "") {
            return 403;
        }
        ```

        ### 4. Protect Sensitive Files

        ```nginx
        # Block access to hidden files
        location ~ /\. {
            deny all;
            access_log off;
            log_not_found off;
        }

        # Block access to sensitive files
        location ~* (composer\.json|composer\.lock|\.env|package\.json|webpack\.mix\.js) {
            deny all;
        }

        # Block PHP files in uploads
        location ~* /(?:uploads|files)/.*\.php$ {
            deny all;
        }
        ```

        ### 5. SSL Configuration

        ```nginx
        # SSL settings
        ssl_protocols TLSv1.2 TLSv1.3;
        ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
        ssl_prefer_server_ciphers off;
        ssl_session_cache shared:SSL:10m;
        ssl_session_timeout 1d;
        ssl_session_tickets off;

        # OCSP stapling
        ssl_stapling on;
        ssl_stapling_verify on;
        resolver 8.8.8.8 8.8.4.4 valid=300s;
        resolver_timeout 5s;
        ```

        ## Laravel-Specific Configuration

        ### 1. Standard Laravel Config

        ```nginx
        server {
            listen 80;
            listen [::]:80;
            server_name example.com;
            root /home/forge/example.com/public;

            index index.php;

            charset utf-8;

            location / {
                try_files $uri $uri/ /index.php?$query_string;
            }

            location = /favicon.ico { access_log off; log_not_found off; }
            location = /robots.txt  { access_log off; log_not_found off; }

            error_page 404 /index.php;

            location ~ \.php$ {
                fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
                fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
                include fastcgi_params;
            }

            location ~ /\.(?!well-known).* {
                deny all;
            }
        }
        ```

        ### 2. Livewire Configuration

        ```nginx
        # Increase timeouts for Livewire requests
        location /livewire {
            proxy_read_timeout 120s;
        }
        ```

        ### 3. WebSocket (Laravel Reverb/Echo)

        ```nginx
        location /app {
            proxy_pass http://127.0.0.1:8080;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_read_timeout 86400;
        }
        ```

        ### 4. API Rate Limiting

        ```nginx
        # Rate limit API endpoints
        location /api/ {
            limit_req zone=one burst=10 nodelay;

            try_files $uri $uri/ /index.php?$query_string;
        }
        ```

        ## Load Balancing

        ### Upstream Configuration

        ```nginx
        upstream laravel {
            least_conn;
            server 192.168.1.10:80 weight=5;
            server 192.168.1.11:80 weight=5;
            server 192.168.1.12:80 backup;
            keepalive 32;
        }

        server {
            location / {
                proxy_pass http://laravel;
                proxy_http_version 1.1;
                proxy_set_header Connection "";
                proxy_set_header Host $host;
                proxy_set_header X-Real-IP $remote_addr;
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_set_header X-Forwarded-Proto $scheme;
            }
        }
        ```

        ### Session Stickiness

        ```nginx
        upstream laravel {
            ip_hash;  # Same client goes to same server
            server 192.168.1.10:80;
            server 192.168.1.11:80;
        }
        ```

        ## Monitoring and Debugging

        ### Enable Status Page

        ```nginx
        location /nginx_status {
            stub_status on;
            access_log off;
            allow 127.0.0.1;
            allow YOUR_IP;
            deny all;
        }
        ```

        ### Access Log Format

        ```nginx
        log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                        '$status $body_bytes_sent "$http_referer" '
                        '"$http_user_agent" $request_time';

        access_log /var/log/nginx/access.log main;
        ```

        ### Debug Slow Requests

        Log slow requests:

        ```nginx
        log_format timed '$remote_addr - $remote_user [$time_local] "$request" '
                         '$status $body_bytes_sent "$http_referer" '
                         '$request_time $upstream_response_time';
        ```

        ## Common Issues and Solutions

        ### Issue: 502 Bad Gateway

        **Causes:**
        - PHP-FPM not running
        - Socket path mismatch
        - PHP-FPM timeout

        **Solution:**
        ```bash
        # Check PHP-FPM
        sudo systemctl status php8.4-fpm

        # Verify socket exists
        ls -la /var/run/php/php8.4-fpm.sock

        # Restart services
        sudo systemctl restart php8.4-fpm
        sudo systemctl restart nginx
        ```

        ### Issue: 413 Request Entity Too Large

        **Solution:**
        ```nginx
        client_max_body_size 64m;
        ```

        Also update PHP:
        ```ini
        upload_max_filesize = 64M
        post_max_size = 64M
        ```

        ### Issue: 504 Gateway Timeout

        **Solution:**
        ```nginx
        fastcgi_read_timeout 300;
        proxy_read_timeout 300;
        ```

        Also update PHP:
        ```ini
        max_execution_time = 300
        ```

        ### Issue: Assets Not Updating

        **Solution:**
        Check cache headers:
        ```nginx
        location ~* \.(css|js)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
        ```

        Use cache-busting (Laravel Mix handles this):
        ```php
        <link href="{{ mix('css/app.css') }}" rel="stylesheet">
        ```

        ## Testing Configuration

        ### Validate Config

        ```bash
        sudo nginx -t
        ```

        ### Test Performance

        ```bash
        # Simple benchmark
        ab -n 1000 -c 100 https://example.com/

        # More detailed
        wrk -t12 -c400 -d30s https://example.com/
        ```

        ### Check Headers

        ```bash
        curl -I https://example.com/
        ```

        ## Related Tools

        - `get-nginx-template-tool` - Get current Nginx config
        - `update-nginx-template-tool` - Update Nginx config
        - `get-nginx-default-template-tool` - Get default template
        - `create-nginx-template-tool` - Create custom template
        - `list-nginx-templates-tool` - List all templates

        MD;

        return Response::text($content);
    }
}
