#!/usr/bin/env bash
# =============================================================================
# setup.sh — Script de configuración del entorno de laboratorio
# =============================================================================
# Uso: bash scripts/setup.sh
# =============================================================================

set -euo pipefail

# ── Colores ANSI ──────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ── Helpers ───────────────────────────────────────────────────────────────────
info()    { echo -e "${CYAN}ℹ️  $*${NC}"; }
success() { echo -e "${GREEN}✅ $*${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $*${NC}"; }
error()   { echo -e "${RED}❌ $*${NC}" >&2; }
step()    { echo -e "\n${BOLD}${BLUE}══ $* ══${NC}"; }

# ── Banner ────────────────────────────────────────────────────────────────────
echo -e "${BOLD}${CYAN}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║    🎓 PHP · JavaScript · MySQL — Laboratorio Interactivo   ║"
echo "║                      Setup Automático                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# ── Verificar directorio ──────────────────────────────────────────────────────
if [ ! -f "composer.json" ]; then
    error "Debes ejecutar este script desde la raíz del proyecto."
    error "Uso: bash scripts/setup.sh"
    exit 1
fi

# ── Paso 1: Verificar requisitos ──────────────────────────────────────────────
step "Verificando requisitos"

check_command() {
    local cmd=$1
    local name=$2
    local install_hint=$3

    if command -v "$cmd" &>/dev/null; then
        local version
        version=$("$cmd" --version 2>&1 | head -1)
        success "$name encontrado: $version"
    else
        error "$name no encontrado. $install_hint"
        return 1
    fi
}

MISSING_DEPS=0

# Verificar Docker
if ! check_command docker "Docker" "Instala Docker: https://docs.docker.com/get-docker/"; then
    MISSING_DEPS=1
fi

# Verificar Docker Compose
if command -v docker &>/dev/null; then
    if docker compose version &>/dev/null 2>&1; then
        success "Docker Compose (plugin) encontrado"
    elif command -v docker-compose &>/dev/null; then
        success "Docker Compose (standalone) encontrado"
    else
        warning "Docker Compose no encontrado. Necesario para levantar los servicios."
        MISSING_DEPS=1
    fi
fi

# Verificar PHP (opcional si usamos Docker)
if command -v php &>/dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    if php -r "exit(version_compare(PHP_VERSION, '8.1', '>=') ? 0 : 1);"; then
        success "PHP $PHP_VERSION encontrado"
    else
        warning "PHP $PHP_VERSION encontrado, pero se requiere 8.1+. Usando Docker."
    fi
else
    warning "PHP no encontrado localmente. Se usará el contenedor Docker."
fi

# Verificar Composer (opcional)
if command -v composer &>/dev/null; then
    success "Composer encontrado: $(composer --version 2>&1 | head -1)"
    HAS_COMPOSER=true
else
    warning "Composer no encontrado localmente. Se instalará dentro del contenedor."
    HAS_COMPOSER=false
fi

# Verificar Node.js (para labs de JavaScript)
if command -v node &>/dev/null; then
    success "Node.js encontrado: $(node --version)"
else
    warning "Node.js no encontrado. Necesario para labs de JavaScript."
fi

if [ "$MISSING_DEPS" -eq 1 ]; then
    error "Faltan dependencias críticas. Por favor instálalas y vuelve a ejecutar."
    exit 1
fi

# ── Paso 2: Configurar .env ───────────────────────────────────────────────────
step "Configurando archivo .env"

if [ ! -f ".env" ]; then
    cp .env.example .env
    success ".env creado desde .env.example"
    info "👉 Edita .env si necesitas cambiar las credenciales de la BD"
else
    success ".env ya existe, no se sobreescribe"
fi

# ── Paso 3: Instalar dependencias PHP ────────────────────────────────────────
step "Instalando dependencias PHP (Composer)"

if [ "$HAS_COMPOSER" = true ]; then
    info "Ejecutando composer install..."
    composer install --no-interaction 2>&1
    success "Dependencias PHP instaladas"
else
    info "Se instalarán dentro del contenedor Docker"
fi

# ── Paso 4: Levantar Docker ───────────────────────────────────────────────────
step "Levantando servicios Docker"

info "Ejecutando docker compose up -d..."

COMPOSE_CMD="docker compose"
if ! docker compose version &>/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
fi

$COMPOSE_CMD up -d --build 2>&1

success "Contenedores iniciados"

# ── Paso 5: Esperar MySQL ─────────────────────────────────────────────────────
step "Esperando a que MySQL esté listo"

MAX_RETRIES=30
RETRY_COUNT=0
DB_READY=false

info "Esperando MySQL (máximo ${MAX_RETRIES} intentos)..."

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if $COMPOSE_CMD exec -T db mysqladmin ping -h localhost -u labuser --password=labpassword &>/dev/null 2>&1; then
        DB_READY=true
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo -n "."
    sleep 2
done

echo ""

if [ "$DB_READY" = true ]; then
    success "MySQL está listo después de $((RETRY_COUNT * 2)) segundos"
else
    warning "MySQL tardó más de lo esperado. Puede que aún se esté iniciando."
    info "Espera unos segundos y verifica con: docker compose logs db"
fi

# ── Paso 6: Mensaje final ─────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}${GREEN}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              ✅ ¡Setup completado con éxito!               ║"
echo "╠════════════════════════════════════════════════════════════╣"
echo "║                                                            ║"
echo "║  🌐 Dashboard:    http://localhost:8000                    ║"
echo "║  🗄️  phpMyAdmin:   http://localhost:8080                    ║"
echo "║  🐬 MySQL:        localhost:3306                           ║"
echo "║                                                            ║"
echo "║  Credenciales MySQL:                                       ║"
echo "║    Usuario: labuser                                        ║"
echo "║    Contraseña: labpassword                                 ║"
echo "║    Base de datos: php_js_mysql_lab                         ║"
echo "║                                                            ║"
echo "╠════════════════════════════════════════════════════════════╣"
echo "║  Comandos útiles:                                          ║"
echo "║    php scripts/run_labs.php --list                         ║"
echo "║    php scripts/run_labs.php --lab=php/01                   ║"
echo "║    ./vendor/bin/phpunit --testdox                          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"
