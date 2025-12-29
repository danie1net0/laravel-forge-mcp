<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use Laravel\Mcp\{Request, Response};
use Laravel\Mcp\Server\Resource;

class SecurityHardeningResource extends Resource
{
    protected string $uri = 'forge://guides/security-hardening';

    protected string $name = 'Server Security Hardening Guide';

    protected string $description = 'Advanced server hardening and security configuration for Laravel Forge servers';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $content = <<<'MD'
        # Server Security Hardening Guide for Laravel Forge

        ## Overview

        This guide covers advanced security configurations beyond basic best practices.
        These steps help protect against sophisticated attacks and ensure compliance.

        ## SSH Hardening

        ### 1. Disable Password Authentication

        Forge does this by default, but verify:

        `/etc/ssh/sshd_config`:
        ```
        PasswordAuthentication no
        PermitRootLogin no
        ChallengeResponseAuthentication no
        UsePAM no
        ```

        ### 2. Limit SSH Access

        **Restrict to specific users:**
        ```
        AllowUsers forge admin
        ```

        **Use IP-based restrictions:**

        Using MCP:
        ```
        Use create-firewall-rule-tool with:
        - name: SSH Access
        - port: 22
        - ip_address: your.office.ip
        ```

        ### 3. Change SSH Port (Optional)

        ```
        Port 2222
        ```

        Update firewall accordingly.

        ### 4. Enable Key-Based 2FA

        Install Google Authenticator:
        ```bash
        sudo apt install libpam-google-authenticator
        google-authenticator
        ```

        Update `/etc/pam.d/sshd`:
        ```
        auth required pam_google_authenticator.so
        ```

        ### 5. SSH Idle Timeout

        ```
        ClientAliveInterval 300
        ClientAliveCountMax 2
        ```

        ## Firewall Configuration

        ### Default Deny Policy

        Forge configures UFW, verify rules:

        ```bash
        sudo ufw status verbose
        ```

        **Essential ports only:**
        ```
        22/tcp  - SSH (restrict to IPs if possible)
        80/tcp  - HTTP (for SSL renewal)
        443/tcp - HTTPS (application traffic)
        ```

        ### Block Common Attack Ports

        Using MCP:
        ```
        Use create-firewall-rule-tool to allow only necessary ports
        ```

        **Never expose:**
        - 3306 (MySQL)
        - 5432 (PostgreSQL)
        - 6379 (Redis)
        - 11211 (Memcached)

        ### Rate Limiting with IPtables

        ```bash
        # Limit SSH connections
        sudo iptables -A INPUT -p tcp --dport 22 -m state --state NEW -m recent --set
        sudo iptables -A INPUT -p tcp --dport 22 -m state --state NEW -m recent --update --seconds 60 --hitcount 4 -j DROP
        ```

        ## Fail2Ban Configuration

        ### Install and Enable

        ```bash
        sudo apt install fail2ban
        sudo systemctl enable fail2ban
        ```

        ### Configure Jails

        `/etc/fail2ban/jail.local`:
        ```ini
        [DEFAULT]
        bantime = 3600
        findtime = 600
        maxretry = 5

        [sshd]
        enabled = true
        port = 22
        filter = sshd
        logpath = /var/log/auth.log
        maxretry = 3

        [nginx-http-auth]
        enabled = true
        filter = nginx-http-auth
        port = http,https
        logpath = /var/log/nginx/error.log

        [nginx-botsearch]
        enabled = true
        filter = nginx-botsearch
        port = http,https
        logpath = /var/log/nginx/access.log
        maxretry = 2
        ```

        ### Custom Laravel Filter

        `/etc/fail2ban/filter.d/laravel-auth.conf`:
        ```ini
        [Definition]
        failregex = ^.*"POST /login.*" 401
        ignoreregex =
        ```

        `/etc/fail2ban/jail.local`:
        ```ini
        [laravel-auth]
        enabled = true
        port = http,https
        filter = laravel-auth
        logpath = /home/forge/*/storage/logs/laravel.log
        maxretry = 5
        bantime = 3600
        ```

        ## Database Security

        ### MySQL/MariaDB Hardening

        ```bash
        sudo mysql_secure_installation
        ```

        This will:
        - Set root password
        - Remove anonymous users
        - Disable remote root login
        - Remove test database
        - Reload privileges

        **Verify secure settings:**
        ```sql
        SELECT user, host FROM mysql.user;
        SHOW GRANTS FOR 'forge'@'localhost';
        ```

        ### PostgreSQL Hardening

        `/etc/postgresql/*/main/pg_hba.conf`:
        ```
        # TYPE  DATABASE  USER  ADDRESS    METHOD
        local   all       all              peer
        host    all       all   127.0.0.1/32  scram-sha-256
        ```

        Never allow remote connections without SSL/VPN.

        ### Redis Security

        `/etc/redis/redis.conf`:
        ```
        bind 127.0.0.1
        requirepass your_strong_password
        rename-command FLUSHALL ""
        rename-command FLUSHDB ""
        rename-command CONFIG ""
        rename-command DEBUG ""
        ```

        Update Laravel `.env`:
        ```
        REDIS_PASSWORD=your_strong_password
        ```

        ## File System Security

        ### Set Proper Permissions

        ```bash
        # Application files
        find /home/forge/site.com -type f -exec chmod 644 {} \;
        find /home/forge/site.com -type d -exec chmod 755 {} \;

        # Writable directories
        chmod -R 775 /home/forge/site.com/storage
        chmod -R 775 /home/forge/site.com/bootstrap/cache

        # Ownership
        chown -R forge:www-data /home/forge/site.com
        ```

        ### Secure Sensitive Files

        ```bash
        # .env file
        chmod 600 /home/forge/site.com/.env

        # SSL certificates (if manual)
        chmod 600 /etc/ssl/private/*
        ```

        ### Mount Options

        `/etc/fstab` options for security:
        ```
        /tmp  tmpfs  defaults,noexec,nosuid,nodev  0  0
        ```

        ## Process Isolation

        ### PHP-FPM Pool Isolation

        `/etc/php/8.4/fpm/pool.d/site.conf`:
        ```ini
        [site.com]
        user = forge
        group = www-data
        listen.owner = www-data
        listen.group = www-data

        ; Chroot for isolation (advanced)
        ; chroot = /home/forge/site.com

        ; Limit open files
        rlimit_files = 1024

        ; Limit memory
        php_admin_value[memory_limit] = 256M

        ; Disable dangerous functions
        php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
        ```

        ### AppArmor Profiles

        Create profile for your application:

        `/etc/apparmor.d/forge.site`:
        ```
        #include <tunables/global>

        /home/forge/site.com/** {
          #include <abstractions/base>
          #include <abstractions/php>

          /home/forge/site.com/** r,
          /home/forge/site.com/storage/** rw,
          /home/forge/site.com/bootstrap/cache/** rw,
        }
        ```

        ## Kernel Hardening

        ### Sysctl Security Settings

        `/etc/sysctl.d/99-security.conf`:
        ```
        # Disable IP forwarding
        net.ipv4.ip_forward = 0

        # Ignore ICMP broadcasts
        net.ipv4.icmp_echo_ignore_broadcasts = 1

        # Ignore bogus ICMP errors
        net.ipv4.icmp_ignore_bogus_error_responses = 1

        # Enable SYN cookies
        net.ipv4.tcp_syncookies = 1

        # Disable source routing
        net.ipv4.conf.all.accept_source_route = 0
        net.ipv6.conf.all.accept_source_route = 0

        # Enable reverse path filtering
        net.ipv4.conf.all.rp_filter = 1

        # Disable ICMP redirects
        net.ipv4.conf.all.accept_redirects = 0
        net.ipv6.conf.all.accept_redirects = 0

        # Disable sending ICMP redirects
        net.ipv4.conf.all.send_redirects = 0

        # Log martian packets
        net.ipv4.conf.all.log_martians = 1

        # Randomize virtual address space
        kernel.randomize_va_space = 2
        ```

        Apply:
        ```bash
        sudo sysctl -p /etc/sysctl.d/99-security.conf
        ```

        ## Logging and Auditing

        ### Enable Audit Daemon

        ```bash
        sudo apt install auditd
        sudo systemctl enable auditd
        ```

        `/etc/audit/rules.d/audit.rules`:
        ```
        # Log all root commands
        -a always,exit -F arch=b64 -F euid=0 -S execve -k root-commands

        # Log file deletions
        -a always,exit -F arch=b64 -S unlink -S unlinkat -S rename -S renameat -k file-deletion

        # Log sudo usage
        -w /etc/sudoers -p wa -k sudoers

        # Log SSH key changes
        -w /home/forge/.ssh -p wa -k ssh-changes
        ```

        ### Centralized Logging

        Send logs to external service:

        ```bash
        # Install rsyslog
        sudo apt install rsyslog

        # Configure remote logging
        echo "*.* @your-log-server:514" | sudo tee -a /etc/rsyslog.conf
        ```

        Or use Forge-integrated services:
        - Papertrail
        - Loggly
        - Datadog

        ### Log Rotation

        `/etc/logrotate.d/laravel`:
        ```
        /home/forge/*/storage/logs/*.log {
            daily
            missingok
            rotate 14
            compress
            delaycompress
            notifempty
            create 0644 forge www-data
        }
        ```

        ## Intrusion Detection

        ### AIDE (Advanced Intrusion Detection Environment)

        ```bash
        sudo apt install aide
        sudo aideinit
        sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
        ```

        Schedule daily checks:
        ```bash
        # /etc/cron.daily/aide
        #!/bin/bash
        /usr/bin/aide --check | mail -s "AIDE Report" admin@example.com
        ```

        ### RKHunter (Rootkit Hunter)

        ```bash
        sudo apt install rkhunter
        sudo rkhunter --update
        sudo rkhunter --propupd
        sudo rkhunter --check
        ```

        ### ClamAV (Antivirus)

        ```bash
        sudo apt install clamav clamav-daemon
        sudo freshclam
        sudo systemctl start clamav-daemon
        ```

        Scan uploads:
        ```bash
        clamscan -r /home/forge/site.com/storage/app/uploads
        ```

        ## Backup Security

        ### Encrypt Backups

        Using Forge backup with encryption:
        ```
        Use create-backup-configuration-tool with encryption enabled
        ```

        Manual encryption:
        ```bash
        # Encrypt backup
        gpg --symmetric --cipher-algo AES256 backup.sql

        # Decrypt
        gpg --decrypt backup.sql.gpg > backup.sql
        ```

        ### Offsite Backup Storage

        - AWS S3 with versioning
        - DigitalOcean Spaces
        - Backblaze B2

        Configure lifecycle policies for retention.

        ### Test Restore Regularly

        Schedule monthly restore tests to verify backups work.

        ## Security Monitoring

        ### Set Up Alerts

        Using MCP:
        ```
        Use create-monitor-tool for uptime monitoring
        ```

        **Alert on:**
        - Failed login attempts (Fail2Ban)
        - File system changes (AIDE)
        - Unusual network traffic
        - High CPU/memory usage
        - Disk space warnings

        ### Security Scanning

        **Regular vulnerability scans:**
        - Nessus
        - OpenVAS
        - Qualys

        **Web application scans:**
        - OWASP ZAP
        - Burp Suite
        - Nikto

        ## Incident Response

        ### Preparation

        1. **Document baseline:**
           - Normal process list
           - Normal network connections
           - Normal file checksums

        2. **Create response plan:**
           - Contact list
           - Escalation procedures
           - Recovery steps

        ### If Compromised

        1. **Isolate:**
           ```bash
           # Disable network (if SSH fails, use console)
           sudo ufw default deny incoming
           sudo ufw default deny outgoing
           ```

        2. **Preserve evidence:**
           ```bash
           # Snapshot logs
           cp -r /var/log /tmp/logs-backup
           cp -r /home/forge/*/storage/logs /tmp/app-logs-backup
           ```

        3. **Analyze:**
           - Review auth logs
           - Check running processes
           - Examine network connections
           - Review file changes

        4. **Remediate:**
           - Patch vulnerabilities
           - Rotate all credentials
           - Restore from clean backup if needed

        5. **Document and improve:**
           - Write incident report
           - Update security measures
           - Train team

        ## Compliance Checklist

        ### SOC 2 Relevant Controls

        - [ ] Access controls documented
        - [ ] Encryption at rest and in transit
        - [ ] Audit logging enabled
        - [ ] Backup and recovery tested
        - [ ] Incident response plan documented
        - [ ] Vulnerability management process

        ### PCI DSS Relevant Controls

        - [ ] Firewall configured
        - [ ] No default passwords
        - [ ] Encryption of cardholder data
        - [ ] Access restricted by role
        - [ ] Regular security testing
        - [ ] Security policy maintained

        ## Related Tools

        - `create-firewall-rule-tool` - Configure firewall
        - `create-ssh-key-tool` - Manage SSH keys
        - `create-backup-configuration-tool` - Setup backups
        - `create-security-rule-tool` - HTTP authentication
        - `get-server-log-tool` - Review logs

        MD;

        return Response::text($content);
    }
}
