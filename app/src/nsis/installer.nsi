/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

!define UNINST_KEY "SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\WebIssues Client 2.0"

Unicode true

!include "MUI2.nsh"

SetCompressor /SOLID lzma
SetCompressorDictSize 32

OutFile "${OUTDIR}\${OUTFILE}"

!define MULTIUSER_EXECUTIONLEVEL "Highest"
!define MULTIUSER_MUI
!define MULTIUSER_INSTALLMODE_COMMANDLINE
!define MULTIUSER_INSTALLMODE_DEFAULT_REGISTRY_KEY "${UNINST_KEY}"
!define MULTIUSER_INSTALLMODE_DEFAULT_REGISTRY_VALUENAME "UninstallString"
!define MULTIUSER_INSTALLMODE_INSTDIR "WebIssues Client\2.0"
!define MULTIUSER_INSTALLMODE_INSTDIR_REGISTRY_KEY "${UNINST_KEY}"
!define MULTIUSER_INSTALLMODE_INSTDIR_REGISTRY_VALUENAME "InstallLocation"
!if ${ARCHITECTURE} == "x64"
    !define MULTIUSER_USE_PROGRAMFILES64
!endif
!include "MultiUser.nsh"

Name "WebIssues"

!define MUI_ICON "..\icons\webissues.ico"
!define MUI_UNICON "${NSISDIR}\Contrib\Graphics\Icons\modern-uninstall-blue-full.ico"

!define MUI_WELCOMEFINISHPAGE_BITMAP "..\images\nsis-wizard.bmp"
!define MUI_UNWELCOMEFINISHPAGE_BITMAP "..\images\nsis-wizard.bmp"

!define MUI_HEADERIMAGE
!define MUI_HEADERIMAGE_BITMAP "..\images\nsis-header.bmp"
!define MUI_HEADERIMAGE_RIGHT

!define MUI_WELCOMEPAGE_TITLE "WebIssues ${VERSION}"
!define MUI_WELCOMEPAGE_TEXT "Setup will guide you through the installation of WebIssues.$\r$\n$\r$\nIf you are upgrading an existing version of WebIssues, make sure it is not running.$\r$\n$\r$\nClick Next to continue."
!insertmacro MUI_PAGE_WELCOME

!define MUI_LICENSEPAGE_CHECKBOX
!insertmacro MUI_PAGE_LICENSE "${SRCDIR}\LICENSE"

!insertmacro MULTIUSER_PAGE_INSTALLMODE

!insertmacro MUI_PAGE_DIRECTORY

ShowInstDetails nevershow
!insertmacro MUI_PAGE_INSTFILES

!define MUI_FINISHPAGE_TITLE "WebIssues ${VERSION}"
!insertmacro MUI_PAGE_FINISH

!define MUI_WELCOMEPAGE_TITLE "WebIssues ${VERSION}"
!insertmacro MUI_UNPAGE_WELCOME

!insertmacro MUI_UNPAGE_CONFIRM

ShowUninstDetails nevershow
!insertmacro MUI_UNPAGE_INSTFILES

!define MUI_FINISHPAGE_TITLE "WebIssues ${VERSION}"
!insertmacro MUI_UNPAGE_FINISH

!insertmacro MUI_LANGUAGE "English"

VIProductVersion "${BUILDVERSION}"
VIAddVersionKey /LANG=${LANG_ENGLISH} "CompanyName" "WebIssues Team"
VIAddVersionKey /LANG=${LANG_ENGLISH} "FileDescription" "WebIssues Setup"
VIAddVersionKey /LANG=${LANG_ENGLISH} "FileVersion" "${VERSION}"
VIAddVersionKey /LANG=${LANG_ENGLISH} "LegalCopyright" "Copyright (C) 2007-2020 WebIssues Team"
VIAddVersionKey /LANG=${LANG_ENGLISH} "OriginalFilename" "${OUTFILE}"
VIAddVersionKey /LANG=${LANG_ENGLISH} "ProductName" "WebIssues"
VIAddVersionKey /LANG=${LANG_ENGLISH} "ProductVersion" "${VERSION}"

!if ${ARCHITECTURE} == "x64"
    !define SUFFIX "(64-bit)"
!else
    !define SUFFIX "(32-bit)"
!endif

Function .onInit

!if ${ARCHITECTURE} == "x64"
    SetRegView 64
!endif

    !insertmacro MULTIUSER_INIT

FunctionEnd

Section

    SetOutPath "$INSTDIR"

    Delete "$INSTDIR\natives_blob.bin"

    File "${SRCDIR}\WebIssues.exe"

    File "${SRCDIR}\d3dcompiler_47.dll"
    File "${SRCDIR}\ffmpeg.dll"
    File "${SRCDIR}\libEGL.dll"
    File "${SRCDIR}\libGLESv2.dll"
    File "${SRCDIR}\vk_swiftshader.dll"
    File "${SRCDIR}\vulkan-1.dll"

    File "${SRCDIR}\chrome_100_percent.pak"
    File "${SRCDIR}\chrome_200_percent.pak"
    File "${SRCDIR}\icudtl.dat"
    File "${SRCDIR}\resources.pak"
    File "${SRCDIR}\snapshot_blob.bin"
    File "${SRCDIR}\v8_context_snapshot.bin"
    File "${SRCDIR}\vk_swiftshader_icd.json"

    File "${SRCDIR}\LICENSE"
    File "${SRCDIR}\LICENSE.electron"
    File "${SRCDIR}\LICENSES.chromium.html"
    File "${SRCDIR}\version"

    SetOutPath "$INSTDIR\locales"

    Delete "$INSTDIR\locales\*.pak"

    File "${SRCDIR}\locales\*.pak"

    SetOutPath "$INSTDIR\resources\app"

    File "${SRCDIR}\resources\app\index.html"
    File "${SRCDIR}\resources\app\package.json"

    RMDir /r "$INSTDIR\resources\app\assets"

    SetOutPath "$INSTDIR\resources\app\assets"

    File /r "${SRCDIR}\resources\app\assets\*.*"

    RMDir /r "$INSTDIR\swiftshader"

    SetOutPath "$INSTDIR"

    WriteUninstaller "uninstall.exe"

    CreateShortCut "$SMPROGRAMS\WebIssues.lnk" "$INSTDIR\WebIssues.exe"
    CreateShortCut "$DESKTOP\WebIssues.lnk" "$INSTDIR\WebIssues.exe"

    WriteRegStr SHCTX "${UNINST_KEY}" "DisplayIcon" '"$INSTDIR\WebIssues.exe"'
    WriteRegStr SHCTX "${UNINST_KEY}" "DisplayName" "WebIssues ${VERSION} ${SUFFIX}"
    WriteRegStr SHCTX "${UNINST_KEY}" "DisplayVersion" "${VERSION}"
    WriteRegStr SHCTX "${UNINST_KEY}" "UninstallString" '"$INSTDIR\uninstall.exe" /$MultiUser.InstallMode'
    WriteRegStr SHCTX "${UNINST_KEY}" "InstallLocation" "$INSTDIR"
    WriteRegStr SHCTX "${UNINST_KEY}" "Publisher" "WebIssues Team"
    WriteRegStr SHCTX "${UNINST_KEY}" "HelpLink" "https://webissues.mimec.org"
    WriteRegStr SHCTX "${UNINST_KEY}" "URLInfoAbout" "https://webissues.mimec.org"
    WriteRegStr SHCTX "${UNINST_KEY}" "URLUpdateInfo" "https://webissues.mimec.org/downloads"
    WriteRegDWORD SHCTX "${UNINST_KEY}" "NoModify" 1
    WriteRegDWORD SHCTX "${UNINST_KEY}" "NoRepair" 1

SectionEnd

Function un.onInit

!if ${ARCHITECTURE} == "x64"
    SetRegView 64
!endif

    !insertmacro MULTIUSER_UNINIT

FunctionEnd

Section "Uninstall"

    DeleteRegKey SHCTX "${UNINST_KEY}"

    Delete "$SMPROGRAMS\WebIssues.lnk"
    Delete "$DESKTOP\WebIssues.lnk"

    Delete "$INSTDIR\WebIssues.exe"

    Delete "$INSTDIR\d3dcompiler_47.dll"
    Delete "$INSTDIR\ffmpeg.dll"
    Delete "$INSTDIR\libEGL.dll"
    Delete "$INSTDIR\libGLESv2.dll"
    Delete "$INSTDIR\vk_swiftshader.dll"
    Delete "$INSTDIR\vulkan-1.dll"

    Delete "$INSTDIR\chrome_100_percent.pak"
    Delete "$INSTDIR\chrome_200_percent.pak"
    Delete "$INSTDIR\icudtl.dat"
    Delete "$INSTDIR\natives_blob.bin"
    Delete "$INSTDIR\resources.pak"
    Delete "$INSTDIR\snapshot_blob.bin"
    Delete "$INSTDIR\v8_context_snapshot.bin"
    Delete "$INSTDIR\vk_swiftshader_icd.json"

    Delete "$INSTDIR\LICENSE"
    Delete "$INSTDIR\LICENSE.electron"
    Delete "$INSTDIR\LICENSES.chromium.html"
    Delete "$INSTDIR\version"

    Delete "$INSTDIR\locales\*.pak"
    RMDir "$INSTDIR\locales"

    Delete "$INSTDIR\resources\app\index.html"
    Delete "$INSTDIR\resources\app\package.json"

    RMDir /r "$INSTDIR\resources\app\assets"

    RMDir "$INSTDIR\resources\app"
    RMDir "$INSTDIR\resources"

    Delete "$INSTDIR\uninstall.exe"

    RMDir "$INSTDIR"

SectionEnd
