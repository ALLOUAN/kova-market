@echo off
rem KOVA MARKET - demarrage local (double-clic).
rem Ouvre 2 fenetres :
rem   1. le site sur http://%HOST%:%PORT%
rem   2. les taches programmees : campagnes newsletter a leur date, prix promo, commandes non payees...
rem Les e-mails, SMS et WhatsApp partent tout de suite (QUEUE_CONNECTION=sync dans .env) : pas de file d'attente
rem a faire tourner. Fermez une fenetre pour arreter son processus.
rem Attention : les WhatsApp partent reellement si WHATSAPP_DRIVER=twilio dans .env.

cd /d "%~dp0"

set HOST=192.168.1.100
set PORT=8083

set PHP=php
if exist "C:\laragon\bin\php\php-8.4.26-nts-Win32-vs17-x64\php.exe" set PHP=C:\laragon\bin\php\php-8.4.26-nts-Win32-vs17-x64\php.exe

start "KOVA - site %HOST%:%PORT%" cmd /k %PHP% artisan serve --host=%HOST% --port=%PORT%
start "KOVA - taches programmees" cmd /k %PHP% artisan schedule:work

echo.
echo KOVA MARKET demarre : http://%HOST%:%PORT%
echo Les 2 fenetres doivent rester ouvertes.
echo.
timeout /t 5 >nul
