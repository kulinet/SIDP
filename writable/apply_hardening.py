import paramiko
import sys

HOST = "192.168.13.8"
PORT = 22
USER = "root"
PASS = "sopoqangen"

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=PORT, username=USER, password=PASS, timeout=15)

def run_cmd(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='ignore')
    err = stderr.read().decode('utf-8', errors='ignore')
    return out.strip(), err.strip()

print("=== STEP 1: BACKUP CONFIGURATIONS ===")
out, err = run_cmd("cp /www/server/php/84/etc/php.ini /www/server/php/84/etc/php.ini.bak.$(date +%Y%m%d%H%M%S)")
print("PHP INI Backup:", out, err)

out, err = run_cmd("cp /www/server/panel/vhost/nginx/info.ajip-api.cloud.conf /www/server/panel/vhost/nginx/info.ajip-api.cloud.conf.bak.$(date +%Y%m%d%H%M%S)")
print("Nginx Conf Backup:", out, err)

print("\n=== STEP 2: HARDEN PHP 8.4 CONFIGURATION ===")
# 1. Turn display_errors = Off
run_cmd("sed -i 's/^display_errors = On/display_errors = Off/' /www/server/php/84/etc/php.ini")
# 2. Ensure proc_open is added to disable_functions if not present
check_proc, _ = run_cmd("grep 'disable_functions' /www/server/php/84/etc/php.ini | grep 'proc_open'")
if not check_proc:
    # Add proc_open to disable_functions
    run_cmd("sed -i 's/^disable_functions = /disable_functions = proc_open,/' /www/server/php/84/etc/php.ini")
    print("Added proc_open to disable_functions.")
else:
    print("proc_open already present in disable_functions.")

# Check updated lines
out, _ = run_cmd("grep -n -E '^(display_errors|disable_functions)' /www/server/php/84/etc/php.ini")
print("Updated PHP config lines:\n", out)

# Graceful reload of PHP-FPM
print("Reloading PHP-FPM 8.4 gracefully...")
out, err = run_cmd("/etc/init.d/php-fpm-84 reload")
print("PHP-FPM reload output:", out, err)

print("\n=== STEP 3: HARDEN NGINX CONFIGURATION ===")
vhost_file = "/www/server/panel/vhost/nginx/info.ajip-api.cloud.conf"
# Check if already patched
check_deny, _ = run_cmd(f"grep 'deny all;' {vhost_file}")
if "location ~* ^/(uploads|writable)" not in check_deny:
    # Inject security block before 'include enable-php-84.conf;'
    security_block = """
    # SECURITY: Deny execution of PHP in upload and writable dirs
    location ~* ^/(uploads|writable)/.*\\\\.php$ {
        deny all;
        return 403;
    }

    # SECURITY: Deny direct access to log and backup files
    location ~* \\\\.(log|sql|bak|sh)$ {
        deny all;
        return 403;
    }
"""
    # Read file, insert before '#PHP-INFO-START'
    vhost_content, _ = run_cmd(f"cat {vhost_file}")
    target_anchor = "#PHP-INFO-START"
    if target_anchor in vhost_content:
        new_vhost = vhost_content.replace(
            target_anchor,
            f"{security_block}\n    {target_anchor}"
        )
        # Write to temp file on server
        sftp = ssh.open_sftp()
        with sftp.open("/tmp/info.ajip-api.cloud.conf.new", "w") as f:
            f.write(new_vhost)
        sftp.close()

        # Copy over vhost file
        run_cmd(f"cp /tmp/info.ajip-api.cloud.conf.new {vhost_file}")
        print("Updated Nginx vhost config.")
    else:
        print("Anchor not found in vhost, skipped modifying Nginx config directly.")

# Test Nginx syntax
test_out, test_err = run_cmd("nginx -t")
print("nginx -t result:", test_out, test_err)

if "successful" in test_err or "successful" in test_out:
    print("Nginx syntax OK! Reloading Nginx...")
    reload_out, reload_err = run_cmd("nginx -s reload")
    print("Nginx reload:", reload_out, reload_err)
else:
    print("WARNING: Nginx syntax test failed! Rolling back vhost config...")
    run_cmd(f"cp /www/server/panel/vhost/nginx/info.ajip-api.cloud.conf.bak.* {vhost_file}")
    run_cmd("nginx -s reload")
    print("Rolled back.")

print("\n=== STEP 4: VERIFY ENDPOINTS AND SECURITY BLOCKS ===")
# Test main app response
status_code, _ = run_cmd("curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ -H 'Host: info.ajip-api.cloud'")
print("HTTP Status for Homepage:", status_code)

# Test report summary data
report_status, _ = run_cmd("curl -s -o /dev/null -w '%{http_code}' 'http://127.0.0.1/report/getSummaryData?year=2026' -H 'Host: info.ajip-api.cloud'")
print("HTTP Status for /report/getSummaryData?year=2026:", report_status)

# Test blocked file access (e.g. debug_chat.log)
log_status, _ = run_cmd("curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/debug_chat.log -H 'Host: info.ajip-api.cloud'")
print("HTTP Status for debug_chat.log (should be 403):", log_status)

# Test blocked php execution in uploads
upload_php_status, _ = run_cmd("curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/uploads/test.php -H 'Host: info.ajip-api.cloud'")
print("HTTP Status for /uploads/test.php (should be 403):", upload_php_status)

ssh.close()
print("\n=== COMPLETED SUCCESSFULLY ===")
