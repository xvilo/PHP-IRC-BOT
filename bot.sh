#!/bin/bash
while true; do
php bot.php
STATUS=$?
if [$STATUS -eq 255]
then
sleep 30
continue
fi
if [$STATUS -eq 2]
then
sleep 120
continue
fi
break
done
