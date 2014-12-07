# phpseclib - PHP Secure Communications Library

MIT-licensed pure-PHP implementations of an arbitrary-precision integer
arithmetic library, fully PKCS#1 (v2.1) compliant RSA, DES, 3DES, RC4, Rijndael,
AES, Blowfish, Twofish, SSH-1, SSH-2, SFTP, and X.509

Please see LICENSE and AUTHORS for futher licensing information.

# FuelPHP Additions
This is a manual update of phpseclib by Rob Thomas based on git commit 
cd10ded72e8be3d9160bedf28e8b21e5d63b3f89 in the php5 branch.

Normally, a sub-project like this would be brought in with Composer, but, as it's
not namespaced, it requires manual patching.

Great care has been taken to allow for easy tracking and updating of phpseclib,
to ensure that critical security issues are easy to apply in the future.  

Git will allow you to cherry pick individual patches into this tree using the
command 'git format-patch -1 shahash', and then patch -p1 the resulting output.

Packager: Rob Thomas <rob.thomas@schmoozecom.com>
Source: https://github.com/phpseclib/phpseclib
License: MIT
Commit: cd10ded72e8be3d9160bedf28e8b21e5d63b3f89
Date: 2014-12-03
