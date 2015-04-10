FreeOTP Decoder
=

Decodes the tokens preference file from FreeOTP for Android.

URIs are designed to support the Google Authenticator format:
https://github.com/google/google-authenticator/wiki/Key-Uri-Format

Warning
==
Using this script is a terrible idea. It will expose your one-time-password secrets,
which can be used to generate codes to pass two-factor authentication checks.

This whole process should only be attempted on a secure machine with an encoded disk.
Care should be taken to redirect output and/or clear scrollback.

Requirements
==
PHP 5.4+
PHP SimpleXML extension (enabled by default)

Preparation
==
Before running the decoder you must get and extract a backup file of your FreeOTP data.
The most direct way is to use the android debugging tool.

The general command for backup is `adb backup -f ~/freeotp.ab -noapk org.fedorahosted.freeotp`

The commands to extract are or `dd if=freeotp.ab bs=1 skip=24 | openssl zlib -d | tar -xvf -`
or `dd if=freeotp.ab bs=1 skip=24 | python -c "import zlib,sys;sys.stdout.write(zlib.decompress(sys.stdin.read()))" | tar -xvf -`

The files will be extracted into the subdirectory `apps/org.fedorahosted.freeotp`

Detailed instructions are available at http://blog.shvetsov.com/2013/02/access-android-app-data-without-root.html

Usage
==
`php decoder.php /path/to/apps/org.fedorahosted.freeotp/sp/tokens.xml`

License
==
This script is released under the same Apache License, Version 2.0, as FreeOTP and
Google Authenticator