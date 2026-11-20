import paramiko
import json

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.13.8', username='root', password='sopoqangen', timeout=15)

cmds = {
    'php_cli_version': 'php -v',
    'php_fpm_version': '/www/server/php/84/bin/php -v 2>/dev/null',
    'php_fpm_socket': 'ls -la /tmp/php-cgi*.sock 2>/dev/null',
    'ci4_version': 'grep -A 2 \'"name": "codeigniter4/framework"\' /www/wwwroot/info.ajip-api.cloud/sidp/composer.lock',
    'php84_ini_security': '/www/server/php/84/bin/php -i 2>/dev/null | grep -E "expose_php|display_errors|allow_url_include|disable_functions|open_basedir|session.cookie_httponly|session.cookie_secure"',
    'env_security': 'grep -E "^(CI_ENVIRONMENT|database.default.username|database.default.password|database.default.hostname|encryption.key)" /www/wwwroot/info.ajip-api.cloud/sidp/.env',
    'nginx_php_conf': 'cat /www/server/nginx/conf/enable-php-84.conf 2>/dev/null',
    'nginx_deny_uploads': 'grep -rn "uploads" /www/server/panel/vhost/nginx/info.ajip-api.cloud.conf 2>/dev/null || echo "NOT_FOUND"',
    'upload_dir_writable': 'ls -ld /www/wwwroot/info.ajip-api.cloud/sidp/public/uploads',
    'check_cve_php_cgi': 'test -f /www/server/php/84/bin/php-cgi && echo "php-cgi exists" || echo "no php-cgi"',
}

results = {}
for k, cmd in cmds.items():
    try:
        stdin, stdout, stderr = client.exec_command(cmd, timeout=15)
        out = stdout.read().decode('utf-8', errors='replace').strip()
        err = stderr.read().decode('utf-8', errors='replace').strip()
        results[k] = out if out else (f"[ERR] {err}" if err else "[EMPTY]")
    except Exception as e:
        results[k] = f"[EXCEPTION] {str(e)}"

client.close()

with open(r'c:\laragon\www\infoajip\writable\audit_result.json', 'w', encoding='utf-8') as f:
    json.dump(results, f, indent=2)

print("Audit completed successfully. Results saved to audit_result.json")
