{ pkgs, ... }:

{
  languages.php = {
    enable = true;
    ini = ''
      memory_limit = -1
      opcache.enable = 1
      opcache.revalidate_freq = 0
      opcache.validate_timestamps = 1
      opcache.max_accelerated_files = 30000
      opcache.memory_consumption = 256M
      opcache.interned_strings_buffer = 20
      realpath_cache_ttl = 3600
      xdebug.idekey = "PHPSTORM"
      xdebug.start_with_request = "yes"
      date.timezone = "Europe/Paris"
    '';
  };
}
