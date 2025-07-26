@echo off
echo Cleaning up remaining locked folders...

timeout /t 2 /nobreak > nul

echo Removing auth folder...
rmdir /s /q auth 2>nul

echo Removing includes folder...
rmdir /s /q includes 2>nul

echo Cleanup complete!
echo.
echo Your project now contains only:
echo - index.html (main frontend file)
echo - DairyFarm.js (JavaScript functionality)
echo - README.md (database and API documentation)
echo - .vscode/ (VS Code settings)
echo - .gitignore (git ignore rules)
echo.
echo To run the project, simply open index.html in a web browser
echo or use a local development server.
pause
