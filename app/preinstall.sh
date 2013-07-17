#!/bin/sh
[ -d "cache/smarty/" ] || mkdir -p cache/smarty
[ -d "cache/smarty_bin/" ] || mkdir -p cache/smarty_bin
[ -d "cache/adodb/" ] || mkdir -p cache/adodb
[ -d "logs/" ] || mkdir -p logs
chmod -R 777 cache/
chmod -R 777 logs/
