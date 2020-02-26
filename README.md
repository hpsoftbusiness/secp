#System Ewidencji Czasu Pracy - SECP

## **INSTRUKCJA INSTALACJI**

## Wymagania
- [PHP](http://php.net/releases/7_2_0.php) w wersji min 7.2
- [Postgres](https://www.postgresql.org/) w wersji min 9.6.0
- [Composer](https://getcomposer.org/)

### Wymagane rozszerzenia PHP:
- curl
- calendar
- ctype
- iconv
- ldap
- json
- pgsql
- sqlite - do testów w środowisku dev
- mbstring
- intl
- xml
- zip

## Instalacja

Do folderu aplikacji ***app_root/*** należy wgrać kod (np: git clone).
 
- Dodać foldery:
    - ***app_root/var***
- zainstalować wszystkie niezbędne komponenty:
    ```shell script
    composer install
    ```
- Utworzyć plik:
    - ***app_root/.env.local***
    - do tego pliku należy przekopiować konfiguracje z pliku .env dopasowując ją do docelowego środowiska
    - Jeśli potrzebny jest konfig dla różnych środowisk np. "dev", "prod" utworzyć pliki ***app_root/.env.$APP_ENV.local*** - gdzie ***$APP_ENV*** oznacza środowisko. Sterowanie przez zmienną APP_ENV w pliku ***app_root/.env.local***
- Przyznać uprawnienia zapisu dla użytkownika www-data do folderu
    - ***app_root/var***
- Generowanie kluczy JWT:
    - utworzyć folder ***app_root/config/jwt***
    - w celu wygenerowania kluczy należy wykonać komendy:
        ```shell script
        $ openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
        $ openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
        ``` 
    - hasło użyte do podpisania kluczy należy wprowadzić w konfiguracji ***app_root/.env.local***
    - klucz publiczny i prywatny musi być odczytywalny dla PHP

## Konfiguracja

Konfiguracja jest zapisana w pliku ***app_root/.env.local***, znaczenie poszczególnych kluczy:
- **APP_ENV** - ustawienie środowiska w jakim zostanie uruchomione Symfony
- **APP_SECRET** - ciąg znaków do generowania losowych identyfikatorów używanych do sesji użytkownika, tokenów, itp.
- **DATABASE_URL** - konfiguracja połączenia z bazą 
http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
- **JWT_PASSPHRASE** - hasło podpisujące klucze JWT, użyte również do generowania kluczy
- **CORS_ALLOW_ORIGIN** - konfiguracja [CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS) - wstawić adres systemu np. '^https?://secp-test.parp.gov.pl(:[0-9]+)?$'
- **LDAP_HOST** - adres serwera LDAP
- **LDAP_USERS_BASE_DN** - domena AD dla aktywnych użytkowników
- **LDAP_INACTIVE_USERS_BASE_DN** - domena AD dla nieaktywnych użytkowników
- **LDAP_BIND_FORMAT** - 
- **LDAP_ADMIN_USERNAME** - użytkownik techniczny do zalogowania się do LDAP
- **LDAP_ADMIN_PASSWORD** - hasło użytkownika technicznego do zalogowania się do LDAP

## Baza danych - nowa instalacja
Utworzenie użytkownika i tabeli w bazie danych
```sql
CREATE USER "user_name" WITH PASSWORD 'password';
CREATE DATABASE "db_name" WITH OWNER "user_name";
```
Po uzupełnieniu konfiguracji o dane do połączenia się z bazą należy utworzyć strukturę bazy poprzez wydanie polecenia:
```shell script
php bin/console doctrine:migrations:migrate --no-interaction
```
Bazę można wypełnić testowymi danymi poprzez wykonanie instrukcji:
```shell script
php bin/console doctrine:fixtures:load --purge-with-truncate 
```
Zostaną założone m.in. konta testowe:
- admin : test
- manager : test
- user : test
 
## Baza danych - aktualizacja istniejącej
Po każdorazowej aktualizacji aplikacji należy wykonać migracje które naniosą zmiany struktury bazy danych:
```shell script
php bin/console doctrine:migrations:migrate --no-interaction
```

## Synchronizacja danych użytkowników z danymi z AD
Do wykonania synchronizacji należy wykonać komendę:
```shell script
php bin/console app:ldap-import
```
Komenda ta powinna być wykonywana okresowo poprzez system

## Testy jednostkowe
Do wykonania testów jednostkowych służy komenda:
```shell script
php bin/phpunit
```

# secp_backend
# secp_backend
# secp
