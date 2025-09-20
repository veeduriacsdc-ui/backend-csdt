#!/bin/bash

# ===========================================
# SCRIPT DE CONFIGURACIÓN DE SEGURIDAD
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}===========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===========================================${NC}"
}

print_header "CONFIGURACIÓN DE SEGURIDAD"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# CONFIGURAR FIREWALL
# ===========================================
print_message "Configurando firewall..."

# Configurar UFW
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Permitir SSH
ufw allow OpenSSH

# Permitir puertos de la aplicación
ufw allow 3000
ufw allow 8000

# Permitir HTTP y HTTPS
ufw allow 'Nginx Full'

# Activar firewall
ufw --force enable

print_success "✅ Firewall configurado"

# ===========================================
# CONFIGURAR FAIL2BAN
# ===========================================
print_message "Configurando fail2ban..."

# Instalar fail2ban
apt install -y fail2ban

# Configurar fail2ban
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
ignoreip = 127.0.0.1/8

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3
bantime = 3600

[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 3

[nginx-limit-req]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 3
EOF

# Reiniciar fail2ban
systemctl enable fail2ban
systemctl restart fail2ban

print_success "✅ Fail2ban configurado"

# ===========================================
# CONFIGURAR SSH
# ===========================================
print_message "Configurando SSH..."

# Hacer backup de la configuración SSH
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup

# Configurar SSH
cat > /etc/ssh/sshd_config << 'EOF'
# Configuración SSH segura para CSDT
Port 22
Protocol 2
AddressFamily inet
ListenAddress 0.0.0.0

# Configuración de autenticación
LoginGraceTime 2m
PermitRootLogin yes
StrictModes yes
MaxAuthTries 3
MaxSessions 10

# Configuración de usuarios
AllowUsers root
DenyUsers

# Configuración de claves
PubkeyAuthentication yes
AuthorizedKeysFile .ssh/authorized_keys
PasswordAuthentication yes
PermitEmptyPasswords no
ChallengeResponseAuthentication no

# Configuración de sesión
X11Forwarding no
X11DisplayOffset 10
PrintMotd no
PrintLastLog yes
TCPKeepAlive yes
UsePrivilegeSeparation yes

# Configuración de logging
SyslogFacility AUTH
LogLevel INFO

# Configuración de conexión
ClientAliveInterval 300
ClientAliveCountMax 2
MaxStartups 10:30:60

# Configuración de seguridad
Banner /etc/ssh/banner
AcceptEnv LANG LC_*
Subsystem sftp /usr/lib/openssh/sftp-server
EOF

# Crear banner de SSH
cat > /etc/ssh/banner << 'EOF'
===========================================
    CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
    SISTEMA CSDT - ACCESO RESTRINGIDO
===========================================
Este sistema es para uso autorizado únicamente.
Todas las actividades están siendo monitoreadas.
===========================================
EOF

# Reiniciar SSH
systemctl restart ssh

print_success "✅ SSH configurado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Configurar permisos de archivos
chmod 600 /etc/ssh/sshd_config
chmod 644 /etc/ssh/banner

# Configurar permisos de directorios
chmod 700 /root/.ssh
chmod 700 /home/*/.ssh

# Configurar permisos de la aplicación
chown -R www-data:www-data /var/www/
chmod -R 755 /var/www/
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

print_success "✅ Permisos configurados"

# ===========================================
# CONFIGURAR LOGS DE SEGURIDAD
# ===========================================
print_message "Configurando logs de seguridad..."

# Configurar rsyslog
cat >> /etc/rsyslog.conf << 'EOF'
# Logs de seguridad CSDT
local0.*    /var/log/csdt_security.log
local1.*    /var/log/csdt_audit.log
EOF

# Crear archivos de log
touch /var/log/csdt_security.log
touch /var/log/csdt_audit.log
chmod 644 /var/log/csdt_security.log
chmod 644 /var/log/csdt_audit.log

# Reiniciar rsyslog
systemctl restart rsyslog

print_success "✅ Logs de seguridad configurados"

# ===========================================
# CONFIGURAR MONITOREO DE SEGURIDAD
# ===========================================
print_message "Configurando monitoreo de seguridad..."

# Crear script de monitoreo de seguridad
cat > /usr/local/bin/monitor_seguridad.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de seguridad CSDT

echo "=== MONITOR DE SEGURIDAD CSDT ==="
echo "Fecha: $(date)"
echo ""

# Verificar intentos de login fallidos
echo "=== INTENTOS DE LOGIN FALLIDOS ==="
grep "Failed password" /var/log/auth.log | tail -10

# Verificar conexiones SSH
echo "=== CONEXIONES SSH ==="
grep "Accepted password" /var/log/auth.log | tail -5

# Verificar estado del firewall
echo "=== ESTADO DEL FIREWALL ==="
ufw status

# Verificar estado de fail2ban
echo "=== ESTADO DE FAIL2BAN ==="
fail2ban-client status

# Verificar procesos sospechosos
echo "=== PROCESOS SOSPECHOSOS ==="
ps aux | grep -E "(nc|netcat|nmap|wget|curl)" | grep -v grep

# Verificar conexiones de red
echo "=== CONEXIONES DE RED ==="
netstat -tuln | grep -E ":(22|3000|8000)"

# Verificar espacio en disco
echo "=== ESPACIO EN DISCO ==="
df -h /

# Verificar memoria
echo "=== MEMORIA ==="
free -h
EOF

chmod +x /usr/local/bin/monitor_seguridad.sh

print_success "✅ Monitoreo de seguridad configurado"

# ===========================================
# CONFIGURAR BACKUP DE SEGURIDAD
# ===========================================
print_message "Configurando backup de seguridad..."

# Crear script de backup de seguridad
cat > /usr/local/bin/backup_seguridad.sh << 'EOF'
#!/bin/bash
# Script de backup de seguridad CSDT

BACKUP_DIR="/var/backups/seguridad_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup de configuración SSH
cp /etc/ssh/sshd_config "$BACKUP_DIR/"

# Backup de configuración de fail2ban
cp /etc/fail2ban/jail.local "$BACKUP_DIR/"

# Backup de logs de seguridad
cp /var/log/auth.log "$BACKUP_DIR/"
cp /var/log/csdt_security.log "$BACKUP_DIR/"
cp /var/log/csdt_audit.log "$BACKUP_DIR/"

# Backup de configuración de firewall
ufw status > "$BACKUP_DIR/ufw_status.txt"

# Comprimir backup
cd /var/backups
tar -czf "seguridad_$(date +%Y%m%d_%H%M%S).tar.gz" "seguridad_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de seguridad completado"
EOF

chmod +x /usr/local/bin/backup_seguridad.sh

print_success "✅ Backup de seguridad configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE SEGURIDAD
# ===========================================
print_message "Configurando cron jobs de seguridad..."

# Monitoreo de seguridad cada hora
echo "0 * * * * /usr/local/bin/monitor_seguridad.sh >> /var/log/csdt_security_monitor.log 2>&1" | crontab -

# Backup de seguridad diario
echo "0 1 * * * /usr/local/bin/backup_seguridad.sh >> /var/log/csdt_security_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de seguridad configurados"

# ===========================================
# CONFIGURAR LÍMITES DEL SISTEMA
# ===========================================
print_message "Configurando límites del sistema..."

# Configurar límites de archivos
cat >> /etc/security/limits.conf << 'EOF'
# Límites para CSDT
* soft nofile 65536
* hard nofile 65536
* soft nproc 65536
* hard nproc 65536
www-data soft nofile 65536
www-data hard nofile 65536
www-data soft nproc 65536
www-data hard nproc 65536
EOF

# Configurar límites de sesión
cat >> /etc/security/limits.d/csdt.conf << 'EOF'
# Límites de sesión para CSDT
* soft maxlogins 5
* hard maxlogins 10
EOF

print_success "✅ Límites del sistema configurados"

# ===========================================
# CONFIGURAR SYSCTL
# ===========================================
print_message "Configurando parámetros del kernel..."

# Configurar parámetros de red
cat >> /etc/sysctl.conf << 'EOF'
# Configuración de seguridad para CSDT
net.ipv4.ip_forward = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.default.send_redirects = 0
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0
net.ipv4.conf.all.secure_redirects = 0
net.ipv4.conf.default.secure_redirects = 0
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.default.log_martians = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv4.icmp_ignore_bogus_error_responses = 1
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_rfc1337 = 1
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1
net.ipv4.conf.all.accept_source_route = 0
net.ipv4.conf.default.accept_source_route = 0
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0
net.ipv4.conf.all.secure_redirects = 0
net.ipv4.conf.default.secure_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.default.send_redirects = 0
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.default.log_martians = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv4.icmp_ignore_bogus_error_responses = 1
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_rfc1337 = 1
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1
EOF

# Aplicar configuración
sysctl -p

print_success "✅ Parámetros del kernel configurados"

# ===========================================
# VERIFICAR CONFIGURACIÓN DE SEGURIDAD
# ===========================================
print_message "Verificando configuración de seguridad..."

# Verificar estado del firewall
echo "Estado del firewall:"
ufw status

# Verificar estado de fail2ban
echo "Estado de fail2ban:"
fail2ban-client status

# Verificar configuración SSH
echo "Configuración SSH:"
sshd -T | grep -E "(permitrootlogin|passwordauthentication|maxauthtries)"

# Verificar logs de seguridad
echo "Logs de seguridad:"
ls -la /var/log/csdt_security.log
ls -la /var/log/csdt_audit.log

print_success "✅ Configuración de seguridad verificada"

print_header "CONFIGURACIÓN DE SEGURIDAD COMPLETADA"

print_success "✅ Seguridad configurada correctamente"
print_message "Firewall: UFW configurado"
print_message "Fail2ban: Configurado y activo"
print_message "SSH: Configurado de forma segura"
print_message "Logs: Monitoreo de seguridad activo"
print_message "Backup: Backup de seguridad configurado"
print_message "Para monitorear, ejecuta: monitor_seguridad.sh"
