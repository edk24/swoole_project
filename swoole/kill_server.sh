#!/bin/bash
printf "ps aux|grep "
read name
kill -9 $(ps aux|grep $name|grep -v grep|awk '{print $2}')