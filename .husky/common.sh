#!/usr/bin/env sh

# Detecta automaticamente como executar comandos PHP
# Verifica se Laravel Sail existe E se o container está rodando
# Caso contrário, usa PHP local
run_php() {
    if [ -x "./vendor/bin/sail" ] && docker ps -q --filter "name=laravel.test" | grep -q .; then
        ./vendor/bin/sail php "$@"
    else
        php "$@"
    fi
}
