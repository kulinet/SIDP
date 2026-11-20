import paramiko

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

print("--- Check composer.json ---")
out, _ = run_cmd("cat /www/wwwroot/info.ajip-api.cloud/sidp/composer.json")
print(out)

print("--- Check log_errors and error_reporting in php.ini ---")
out, _ = run_cmd("grep -n -E '^(log_errors|error_reporting|display_errors)' /www/server/php/84/etc/php.ini")
print(out)

print("--- Check PHP-FPM service status ---")
out, _ = run_cmd("systemctl status php-fpm-84 || /etc/init.d/php-fpm-84 status")
print(out[:400])

ssh.close()
