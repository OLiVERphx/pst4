#!/bin/sh
# Escucha en "::" (IPv6, que también acepta IPv4): la red privada de Railway puede ser solo IPv6.
# Si el contenedor no tiene IPv6 (docker local), cae a 0.0.0.0. ML_HOST fuerza un valor.
set -eu

if [ -z "${ML_HOST:-}" ]; then
    if python -c 'import socket; socket.socket(socket.AF_INET6).bind(("::", 0))' 2>/dev/null; then
        ML_HOST="::"
    else
        ML_HOST="0.0.0.0"
    fi
fi

exec uvicorn app.main:app --host "$ML_HOST" --port "${PORT:-8001}"
