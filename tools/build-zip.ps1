# ∞ INFINITY CODER — création originale de Derouiche Oussama
#
# Construit un zip WordPress conforme : entrées en slash (/), jamais en
# antislash. (Compress-Archive de Windows PowerShell 5.1 écrit des « \ »
# dans les noms d'entrées : le zip s'extrait mal sur Linux et WordPress
# répond « Le fichier de l'extension n'existe pas ».)
#
# Usage : powershell -NoProfile -File tools/build-zip.ps1 -Source <dossier> -Dest <fichier.zip>

param(
	[string]$Source = '',
	[string]$Dest = ''
)

$ErrorActionPreference = 'Stop'
if ( '' -eq $Source -or '' -eq $Dest ) {
	throw 'Usage : build-zip.ps1 -Source <dossier> -Dest <fichier.zip>'
}
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

if (Test-Path -LiteralPath $Dest) {
	Remove-Item -LiteralPath $Dest -Force
}

$root = (Resolve-Path -LiteralPath $Source).Path.TrimEnd('\')

# Le zip doit contenir un dossier racine (loginfennec/...) : c'est ce que
# WordPress attend d'un zip d'extension.
$prefix = [System.IO.Path]::GetFileName($root.TrimEnd('\')) + '/'
$zip = [System.IO.Compression.ZipFile]::Open($Dest, [System.IO.Compression.ZipArchiveMode]::Create)
try {
	Get-ChildItem -LiteralPath $root -Recurse -File | ForEach-Object {
		$relative = $prefix + $_.FullName.Substring($root.Length + 1).Replace('\', '/')
		[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
			$zip, $_.FullName, $relative,
			[System.IO.Compression.CompressionLevel]::Optimal
		) | Out-Null
	}
}
finally {
	$zip.Dispose()
}

Write-Output "ZIP-OK $Dest"
