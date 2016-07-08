#!/bin/bash

x11vnc -rfbauth ~/schoolkit/signage/passwd -forever &

while true; do
     chromium "https://docs.google.com/a/ffcsd.org/document/d/1x7zY9sIPYGpIM9WIFsQyybs4_xQ2tpQphzYrYI_RlJ4/edit" --kiosk
done

