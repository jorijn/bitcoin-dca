#!/bin/sh
set -e

if [ $1 == "composer" ]; then
  exec $@
  exit $?
fi

exec /app/bin/bitcoin-dca "$@"
