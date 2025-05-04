#!/bin/bash

cd /home/cgshrdaphosting/domains/copier.chitchit.store

for i in {1..6}
do
  echo "[`date`] Running queue:work iteration $i" >> storage/logs/queue_loop.log
  /usr/bin/php artisan queue:work --once --stop-when-empty >> storage/logs/queue_loop.log 2>&1
  sleep 10
done
