import paramiko
import json

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

print("--- Nginx Config test ---")
out, err = run_cmd("nginx -t")
print("nginx -t:", out, err)

print("--- Check Nginx vhost file ---")
vhost_path = "/www/server/panel/vhost/nginx/info.ajip-api.cloud.conf"
out, _ = run_cmd(f"cat {vhost_path}")
print("VHOST CONTENT:\n", out)

print("--- Check PHP 8.4 php.ini location and disable_functions line ---")
out, _ = run_cmd("grep -n '^disable_functions' /www/server/php/84/etc/php.ini")
print("php.ini disable_functions:", out)

out, _ = run_cmd("grep -n '^display_errors' /www/server/php/84/etc/php.ini")
print("php.ini display_errors:", out)

print("--- Check if php-fpm pool has separate config ---")
out, _ = run_cmd("ls -la /www/server/php/84/etc/php-fpm.d/")
print("php-fpm.d:", out)

ssh.close()
