@echo off
rem Cambia la ruta del archivo Python si es necesario
set SCRIPT_PATH="qr.py"

rem Asegúrate de usar la ruta completa a tu instalación de Python
set PYTHON_EXECUTABLE="C:\Users\User\AppData\Local\Programs\Python\Python310\python.exe"

rem Ejecuta el script Python
%PYTHON_EXECUTABLE% %SCRIPT_PATH%

rem Espera a que el usuario presione una tecla antes de cerrar la ventana de la consola
pause
