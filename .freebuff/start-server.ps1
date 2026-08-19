$p = Start-Process -FilePath 'C:\php84\php.exe' -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8000' -RedirectStandardOutput 'D:\jdc\.freebuff\php-server.log' -RedirectStandardError 'D:\jdc\.freebuff\php-server.log.err' -WindowStyle Hidden -PassThru
Write-Output $p.Id
