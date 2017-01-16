# crad

1. `composer install`
2. `bin/newkey > /path/to/keyfile`
3. `cp config/config.php.example config/config.php`

   `'keyfile' => '/path/to/keyfile'`

4. `bin/initdb`
5. `bin/ccread`
   - scan a card with a magstripe reader
   - issue commands like `!help`
