# crad

1. `bin/newkey > /path/to/keyfile`
2. `cp config/config.php.example config/config.php`

   `'keyfile' => '/path/to/keyfile'`

3. `bin/initdb`
4. `bin/ccread`
   - scan a card with a magstripe reader
   - issue commands like `!help`
