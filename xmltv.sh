#!/bin/sh
#fetch -q http://static.xmltv.info/tv.xml.gz
#gunzip tv.xml.gz
wget -q -O tv.xml "http://xmltv.info/xw/default/run/xmltv?offset=-2"
php xmltv2sqlite.php
