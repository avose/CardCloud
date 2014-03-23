#!/bin/bash

# http://www.mtgathering.ru/hqpics

for f in *.jpg ; do
    convert -resize 32768@ "$f" "$f.png"
    echo -n "data:image/png;base64," > "$f.png.deck"
    base64 -w 0 "$f.png" >> "$f.png.deck"
    echo -n " " >> "$f.png.deck"
    echo -n "$f" | sed -e 's/\..*$//' | sed -e 's/ /_/' >> "$f.png.deck"
done
