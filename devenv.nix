{ pkgs, ... }:

{
  languages.php = {
    enable = true;
    ini = ''
      xdebug.idekey = "PHPSTORM"
      xdebug.start_with_request = "yes"
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
