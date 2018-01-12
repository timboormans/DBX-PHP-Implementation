# DBX-PHP-Implementation
This PHP class imports the functionality of the original ANSI C module. The DBX module was originally part of the PHP core
but got deprecated in PHP5. Therefor a replacement was needed until all software using DBX was rewritten to do so. Especially
on hosting platforms which were required to perform upgrades ran into trouble with customers still using the old DBX database
driver. This library solved the situation.

This module is my port of the original dbx.c module. All constants, defaults, settings are based on documentation
as provided on www.php.net and the official DBX sources. When you include this class into your project it imports
all necessary DBX MySQL functionality making it easy to run your old scripts on nowadays hosting platforms.


`Maintenance note`

As of 2018 this mysql native driver would need to be rewritten to PDO. This is just small work, so if you make the needed
changes please commit them to the repository!