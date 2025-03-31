
# MKV proxy laag voor MorCore

[MorCore](https://github.com/forza-mor-rotterdam/mor-core) heeft op moment van ontwikkelen van deze proxy laag geen ondersteuning voor selectieve toegang voor API clients. Voor de aansluiting van een MKV applicatie is selectieve toegang noodzakelijk. Deze proxy laag voorkomt dat de MKV applicatie onbeperkte toegang tot de MorCore heeft door requests te filteren, controleren en enkele toegestane informatie uit te leveren.

## Open Source

[PHP](https://www.php.net) project op basis van [Symfony framework](https://www.symfony.com). Voor de [Docker](https://www.docker.io) images wordt gebruik gemaakt van [FrankenPHP](https://frankenphp.dev) en [Caddy](https://caddyserver.com) als webserver. Verder wordt gebruik gemaakt van [Composer](https://getcomposer.org) en [Monolog](https://seldaek.github.io/monolog/).

De applicatie wordt open source aangeboden onder de [EUPL 1.2 licentie](https://eupl.eu/1.2/nl/).

## Development

Gebruik [Symfony CLI](https://symfony.com/download), zorg voor een werkende PHP configuratie.

Clone het project via [Git](https://git-scm.com).

Maak een `.env.local`-bestand aan met hierin een `APP_SECRET`, `APP_MOR_CORE_DOMAIN`, `APP_PROXY_DOMAIN`, `APP_MOR_CORE_SECRET` en `APP_ENCRYPTION_KEY` parameter, zie *Environment variabels* voor betekenis.

Installeer dependency via `symfony composer install`.

Draai via `symfony server:start`.

## Productie deployment via Docker

De Docker images kunnen uit de GitHub registry worden gebruikt. Zie *Environment variabels* voor de beschikbare parameters. De service komt actief op poort 80. Er zijn geen volumes e.d. nodig.

De applicatie kan ook op traditionele omgevingen gedeployed worden. Zie hiervoor de Symfony deployment handleiding.

## Environment variabels

| Env | Waarvoor | Voorbeeld waarde | Opmerkingen |
|-----|----------|------------------|-------------|
| APP_ENV | app | `prod` | Symfony configuratie: `dev`, `test` of `prod` |
| APP_SECRET | app | `NYjRkgXE8jbP23Ij4svHTWwsa1I` | Willeukige waarde |
| APP_MOR_CORE_DOMAIN | app | `mor-core-test.forzamor.nl` | Domein waar de mor-core op draait, zonder protocol toevoeging, indien de poort afwijkend is voor 80 (http) of 443 (https) voeg dan het poortnummer toe met `:1234` |
| APP_PROXY_DOMAIN | app | `mkv-test.forzamor.nl` | Domein waar deze applicatie op gehost wordt, indien de poort afwijkend is voor 80 (http) of 443 (https) voeg dan het poortnummer toe met `:5678` |
| APP_MOR_CORE_SECRET | app | `64AJWLWLrEKrNvITXzjvj9jvkt` | In de proxy wordt het password met deze waarde gesuffixed. |
| APP_ENCRYPTION_KEY | app | `e+4+dcBYLVeAIz/GXuIq0g==` | Random gegevens voor de encryptie van het token. Genereer deze met `openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-128-cbc"))` |
| SERVER_NAME | Docker-image | `:80` | Configuratie voor de Caddy webserver (alleen van toepassing bij gebruik Docker images) |

## Api doc

De documentatie van de MorCore is ook geldig voor deze applicatie alleen de meeste API calls zijn geblokkeerd. Effectief zijn enkel `/api-token-auth/` en  `/api/v1/melding` als endpoints beschikbaar.

