# Importa un dump de MySQL 8 (hosting) en el MariaDB local de XAMPP.
# MariaDB 10.4 no tiene las collations utf8mb4_*0900*: se sustituyen al vuelo.
#
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\importar_dump.ps1 hosting.sql iseller
#
# Se usa utf8mb4_unicode_ci porque existe en MySQL 5.7/8 y en MariaDB, asi el
# dump sigue siendo importable en el hosting tras trabajar en local.
param(
    [Parameter(Mandatory = $true)][string]$Dump,
    [Parameter(Mandatory = $true)][string]$BaseDatos,
    [string]$Usuario = 'root',
    [string]$Password = '',
    [string]$Mysql = 'C:\xampp\mysql\bin\mysql.exe'
)

$ErrorActionPreference = 'Stop'
if (-not (Test-Path $Dump)) { throw "No existe el archivo: $Dump" }

$temporal = Join-Path $env:TEMP ("dump_{0}.sql" -f [guid]::NewGuid().ToString('N'))
$lineasCambiadas = 0
$lector = [System.IO.StreamReader]::new((Resolve-Path $Dump), [System.Text.Encoding]::UTF8)
$escritor = [System.IO.StreamWriter]::new($temporal, $false, [System.Text.UTF8Encoding]::new($false))
try {
    while ($null -ne ($linea = $lector.ReadLine())) {
        $nueva = $linea -replace 'utf8mb4_[a-z_]*0900[a-z_]*(as_cs|bin)', 'utf8mb4_bin'
        $nueva = $nueva -replace 'utf8mb4_[a-z_]*0900[a-z_]*', 'utf8mb4_unicode_ci'
        if ($nueva -ne $linea) { $lineasCambiadas++ }
        $escritor.WriteLine($nueva)
    }
} finally {
    $lector.Dispose()
    $escritor.Dispose()
}

try {
    $env:MYSQL_PWD = $Password
    & cmd /c "`"$Mysql`" -u $Usuario --default-character-set=utf8mb4 `"$BaseDatos`" < `"$temporal`""
    $codigo = $LASTEXITCODE
} finally {
    Remove-Item $temporal -Force -ErrorAction SilentlyContinue
}
if ($codigo -ne 0) { throw "mysql termino con codigo $codigo (ver el mensaje de arriba)" }

Write-Host "OK: $Dump importado en '$BaseDatos'. Lineas con collation sustituida: $lineasCambiadas"
