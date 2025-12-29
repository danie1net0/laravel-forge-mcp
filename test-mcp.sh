#!/bin/bash

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variáveis
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
IMAGE_NAME="ddrcn/forge-mcp:latest"
ENV_FILE="${SCRIPT_DIR}/.env"
FORGE_TOKEN=$(grep FORGE_API_TOKEN "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '"' | tr -d "'")
TESTS_PASSED=0
TESTS_FAILED=0

# Funções auxiliares
print_header() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_test() {
    echo -e "\n${YELLOW}▶ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
    ((TESTS_PASSED++))
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
    ((TESTS_FAILED++))
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Testa se um JSON é válido
is_valid_json() {
    echo "$1" | jq empty 2>/dev/null
    return $?
}

# Testa se resposta contém campo específico
has_json_field() {
    local json="$1"
    local field="$2"
    echo "$json" | jq -e ".$field" >/dev/null 2>&1
    return $?
}

# ============================================================================
# INÍCIO DOS TESTES
# ============================================================================

print_header "🧪 Forge MCP - Suite de Testes Automatizados"

# ============================================================================
# TESTE 1: Verificar Docker
# ============================================================================
print_test "Verificando se Docker está rodando..."
if docker ps >/dev/null 2>&1; then
    print_success "Docker está rodando"
else
    print_error "Docker não está rodando. Execute: open -a OrbStack"
    exit 1
fi

# ============================================================================
# TESTE 2: Verificar imagem Docker
# ============================================================================
print_test "Verificando se imagem Docker existe..."
if docker images "$IMAGE_NAME" | grep -q "latest"; then
    IMAGE_ID=$(docker images "$IMAGE_NAME" --format "{{.ID}}" | head -1)
    IMAGE_SIZE=$(docker images "$IMAGE_NAME" --format "{{.Size}}" | head -1)
    print_success "Imagem encontrada (ID: $IMAGE_ID, Size: $IMAGE_SIZE)"
else
    print_error "Imagem não encontrada. Execute: docker build -t $IMAGE_NAME ."
    exit 1
fi

# ============================================================================
# TESTE 3: Verificar arquivo .env
# ============================================================================
print_test "Verificando arquivo .env..."
if [ -f "$ENV_FILE" ]; then
    if grep -q "FORGE_API_TOKEN=" "$ENV_FILE"; then
        print_success "Arquivo .env encontrado com FORGE_API_TOKEN"
    else
        print_error "FORGE_API_TOKEN não encontrado no .env"
        exit 1
    fi
else
    print_error "Arquivo .env não encontrado em $ENV_FILE"
    exit 1
fi

# ============================================================================
# TESTE 4: Verificar configuração PHP no container
# ============================================================================
print_test "Verificando configuração PHP no container..."
PHP_CONFIG=$(docker run --rm "$IMAGE_NAME" php -r "echo ini_get('display_errors');" 2>&1)
if [ "$PHP_CONFIG" = "" ] || [ "$PHP_CONFIG" = "0" ]; then
    print_success "display_errors está desabilitado (correto para MCP)"
else
    print_error "display_errors está habilitado (valor: $PHP_CONFIG)"
fi

# ============================================================================
# TESTE 5: Testar inicialização do MCP
# ============================================================================
print_test "Testando inicialização do servidor MCP..."
INIT_REQUEST='{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}'
INIT_RESPONSE=$(echo "$INIT_REQUEST" | timeout 5 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>&1)

if is_valid_json "$INIT_RESPONSE"; then
    if has_json_field "$INIT_RESPONSE" "result"; then
        SERVER_NAME=$(echo "$INIT_RESPONSE" | jq -r '.result.serverInfo.name')
        SERVER_VERSION=$(echo "$INIT_RESPONSE" | jq -r '.result.serverInfo.version')
        print_success "Servidor inicializado: $SERVER_NAME v$SERVER_VERSION"
    else
        print_error "Resposta não contém campo 'result'"
        print_info "Resposta: $INIT_RESPONSE"
    fi
else
    print_error "Resposta de inicialização não é JSON válido"
    print_info "Resposta: $INIT_RESPONSE"
fi

# ============================================================================
# TESTE 6: Listar tools disponíveis
# ============================================================================
print_test "Listando tools disponíveis..."
TOOLS_REQUEST='{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}'
TOOLS_RESPONSE=$(cat << EOF | timeout 10 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>&1 | tail -1
$INIT_REQUEST
$TOOLS_REQUEST
EOF
)

if is_valid_json "$TOOLS_RESPONSE"; then
    TOOLS_COUNT=$(echo "$TOOLS_RESPONSE" | jq '.result.tools | length')
    print_success "Listou $TOOLS_COUNT tools com sucesso"

    # Mostrar algumas tools
    print_info "Primeiras 5 tools:"
    echo "$TOOLS_RESPONSE" | jq -r '.result.tools[0:5] | .[] | "  • " + .name' 2>/dev/null
else
    print_error "Resposta de tools/list não é JSON válido"
fi

# ============================================================================
# TESTE 7: Testar tool específica - list-servers-tool
# ============================================================================
print_test "Testando tool: list-servers-tool..."
CALL_REQUEST='{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"list-servers-tool","arguments":{}}}'
CALL_RESPONSE=$(cat << EOF | timeout 15 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>&1 | tail -1
$INIT_REQUEST
$CALL_REQUEST
EOF
)

if is_valid_json "$CALL_RESPONSE"; then
    if has_json_field "$CALL_RESPONSE" "result"; then
        # Extrair conteúdo da resposta
        CONTENT=$(echo "$CALL_RESPONSE" | jq -r '.result.content[0].text')

        if is_valid_json "$CONTENT"; then
            SUCCESS_STATUS=$(echo "$CONTENT" | jq -r '.success')
            if [ "$SUCCESS_STATUS" = "true" ]; then
                SERVER_COUNT=$(echo "$CONTENT" | jq -r '.count')
                print_success "Tool executada com sucesso - $SERVER_COUNT servidor(es) encontrado(s)"

                # Mostrar servidores
                print_info "Servidores encontrados:"
                echo "$CONTENT" | jq -r '.servers[] | "  • [\(.id)] \(.name) - \(.ip_address) (PHP \(.php_version))"' 2>/dev/null
            else
                ERROR_MSG=$(echo "$CONTENT" | jq -r '.error // "Erro desconhecido"')
                print_error "Tool retornou erro: $ERROR_MSG"
            fi
        else
            print_error "Conteúdo da resposta não é JSON válido"
        fi
    else
        print_error "Resposta não contém campo 'result'"
    fi
else
    print_error "Resposta não é JSON válido"
    print_info "Resposta: $CALL_RESPONSE"
fi

# ============================================================================
# TESTE 8: Verificar que stdout está limpo (apenas JSON)
# ============================================================================
print_test "Verificando pureza do stdout (sem poluição de erros PHP)..."
STDOUT_TEST=$(echo "$INIT_REQUEST" | timeout 5 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>/dev/null)

# Contar linhas que não são JSON
NON_JSON_LINES=0
while IFS= read -r line; do
    if [ -n "$line" ]; then
        if ! is_valid_json "$line"; then
            ((NON_JSON_LINES++))
            print_info "Linha não-JSON encontrada: $line"
        fi
    fi
done <<< "$STDOUT_TEST"

if [ $NON_JSON_LINES -eq 0 ]; then
    print_success "Stdout está limpo - apenas JSON válido"
else
    print_error "Encontradas $NON_JSON_LINES linha(s) não-JSON no stdout"
fi

# ============================================================================
# TESTE 9: Listar resources disponíveis
# ============================================================================
print_test "Listando resources disponíveis..."
RESOURCES_REQUEST='{"jsonrpc":"2.0","id":4,"method":"resources/list","params":{}}'
RESOURCES_RESPONSE=$(cat << EOF | timeout 10 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>&1 | tail -1
$INIT_REQUEST
$RESOURCES_REQUEST
EOF
)

if is_valid_json "$RESOURCES_RESPONSE"; then
    RESOURCES_COUNT=$(echo "$RESOURCES_RESPONSE" | jq '.result.resources | length')
    print_success "Listou $RESOURCES_COUNT resource(s) com sucesso"

    # Mostrar resources
    if [ "$RESOURCES_COUNT" -gt 0 ]; then
        print_info "Resources disponíveis:"
        echo "$RESOURCES_RESPONSE" | jq -r '.result.resources[] | "  • " + .name' 2>/dev/null
    fi
else
    print_error "Resposta de resources/list não é JSON válido"
fi

# ============================================================================
# TESTE 10: Listar prompts disponíveis
# ============================================================================
print_test "Listando prompts disponíveis..."
PROMPTS_REQUEST='{"jsonrpc":"2.0","id":5,"method":"prompts/list","params":{}}'
PROMPTS_RESPONSE=$(cat << EOF | timeout 10 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>&1 | tail -1
$INIT_REQUEST
$PROMPTS_REQUEST
EOF
)

if is_valid_json "$PROMPTS_RESPONSE"; then
    PROMPTS_COUNT=$(echo "$PROMPTS_RESPONSE" | jq '.result.prompts | length')
    print_success "Listou $PROMPTS_COUNT prompt(s) com sucesso"

    # Mostrar prompts
    if [ "$PROMPTS_COUNT" -gt 0 ]; then
        print_info "Prompts disponíveis:"
        echo "$PROMPTS_RESPONSE" | jq -r '.result.prompts[] | "  • " + .name' 2>/dev/null
    fi
else
    print_error "Resposta de prompts/list não é JSON válido"
fi

# ============================================================================
# TESTE 11: Verificar tempo de resposta
# ============================================================================
print_test "Medindo tempo de resposta do servidor..."
START_TIME=$(date +%s%N)
PERF_RESPONSE=$(echo "$INIT_REQUEST" | timeout 10 docker run --rm -i -e "FORGE_API_TOKEN=$FORGE_TOKEN" "$IMAGE_NAME" 2>&1)
END_TIME=$(date +%s%N)
ELAPSED_MS=$(( (END_TIME - START_TIME) / 1000000 ))

if [ $ELAPSED_MS -lt 3000 ]; then
    print_success "Resposta em ${ELAPSED_MS}ms (excelente)"
elif [ $ELAPSED_MS -lt 5000 ]; then
    print_success "Resposta em ${ELAPSED_MS}ms (bom)"
else
    print_error "Resposta em ${ELAPSED_MS}ms (lento - esperado < 5000ms)"
fi

# ============================================================================
# TESTE 12: Verificar tamanho da imagem
# ============================================================================
print_test "Verificando tamanho da imagem Docker..."
IMAGE_SIZE_MB=$(docker images "$IMAGE_NAME" --format "{{.Size}}" | head -1 | sed 's/MB//')
if (( $(echo "$IMAGE_SIZE_MB < 300" | bc -l) )); then
    print_success "Tamanho da imagem: ${IMAGE_SIZE_MB}MB (otimizado)"
else
    print_info "Tamanho da imagem: ${IMAGE_SIZE_MB}MB (considere otimizar)"
fi

# ============================================================================
# RELATÓRIO FINAL
# ============================================================================
print_header "📊 Relatório de Testes"

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))
SUCCESS_RATE=$((TESTS_PASSED * 100 / TOTAL_TESTS))

echo ""
echo -e "Total de testes: ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Testes aprovados: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Testes falhados: ${RED}$TESTS_FAILED${NC}"
echo -e "Taxa de sucesso: ${GREEN}${SUCCESS_RATE}%${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                      ║${NC}"
    echo -e "${GREEN}║  ✓ TODOS OS TESTES PASSARAM COM SUCESSO!            ║${NC}"
    echo -e "${GREEN}║                                                      ║${NC}"
    echo -e "${GREEN}║  Seu servidor MCP está pronto para uso!             ║${NC}"
    echo -e "${GREEN}║                                                      ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
    echo ""
    print_info "Próximo passo: Reinicie o Claude Desktop para usar o servidor"
    exit 0
else
    echo -e "${RED}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║                                                      ║${NC}"
    echo -e "${RED}║  ✗ ALGUNS TESTES FALHARAM                           ║${NC}"
    echo -e "${RED}║                                                      ║${NC}"
    echo -e "${RED}║  Revise os erros acima antes de continuar           ║${NC}"
    echo -e "${RED}║                                                      ║${NC}"
    echo -e "${RED}╚══════════════════════════════════════════════════════╝${NC}"
    echo ""
    exit 1
fi
