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

  scripts.sdz-link.exec = ''
    cd ../SdZv4 || exit 1
    composer config repositories.api-tester-local '{"type": "path", "url": "../APITester", "options": {"symlink": true}}' --json
    composer update openclassrooms/api-tester --no-interaction --ignore-platform-reqs
  '';

  scripts.sdz-unlink.exec = ''
    cd ../SdZv4 || exit 1
    composer config --unset repositories.api-tester-local
    composer update openclassrooms/api-tester --no-interaction --ignore-platform-reqs
  '';
}
