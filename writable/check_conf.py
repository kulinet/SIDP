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

print("--- enable-php-84.conf ---")
out, _ = run_cmd("cat /www/server/nginx/conf/enable-php-84.conf")
print(out)

print("--- uploads directory check ---")
out, _ = run_cmd("ls -la /www/wwwroot/info.ajip-api.cloud/sidp/public/")
print(out)

print("--- Check extension conf dir ---")
out, _ = run_cmd("ls -la /www/server/panel/vhost/nginx/extension/info.ajip-api.cloud/")
print(out)

ssh.close()
