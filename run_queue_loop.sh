#!/bin/bash

cd /home/cgshrdaphosting/domains/copier.chitchit.store

for i in {1..6}
do
  /usr/local/bin/php artisan queue:work --once --stop-when-empty
  sleep 10
done
