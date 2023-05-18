# Keycloak PHP SDK

This package aims to wrap the Keycloak API and provide an easy and consistent layer
for managing your keycloak realms.

![License](https://img.shields.io/badge/license-MIT-brightgreen)
[![PHP](https://img.shields.io/badge/%3C%2F%3E-PHP%207.4-blue)](https://www.php.net/)
[![Code Style](https://img.shields.io/badge/code%20style-psr--2-darkgreen)](https://www.php-fig.org/psr/psr-2/)

## Documentation

### Quick start

First create a KeycloakClient with your credentials.
```php
/*
 * Keycloak < v17
 */
$kcClient = new Keycloak\KeycloakClient(
    'my-client-id',
    'my-client-secret',
    'my-realm',
    'https://my-keycloak-base-url.com'
);

/*
 * Keycloak v17+
 */
$kcClient = new Keycloak\KeycloakClient(
    'my-client-id',
    'my-client-secret',
    'my-realm',
    'https://my-keycloak-base-url.com',
    null,
    ''
);
```

Then you can pass the client to any of the APIs.

```php
$userApi = new Keycloak\User\UserApi($kcClient);
$allUsers = $userApi->findAll();
```
#### Building the Keycloak API URL
Older versions of Keycloak included a URL path segment `/auth` to the API path by default.
The Client constructor does the same.

To add some flexibility but avoid breaking changes in this package, you have two options:
1. [Configure Keycloak](https://www.keycloak.org/server/hostname) to keep the `/auth` URL path segment
2. Provide empty string `''` to the new `$basePath` argument to the Client constructor.
   * If you have configured Keycloak to use some other path prefix between the host and `/realms/...` segment,
     make sure to match it with this argument value.

##### Example 1 (older default):

```php
$client = new KeycloakClient(
    'my-client-id',
    'my-client-secret',
    'my-realm'
);
```
builds the following:
```
https://my-keycloak-base-url.com/auth
# then Realm segments are added:
https://my-keycloak-base-url.com/auth/realms/my-realm
```
If you call `$client->sendRequest('GET', '.well-known/openid-configuration')`:
```
https://my-keycloak-base-url.com/auth/realms/my-realm/.well-known/openid-configuration
```

##### Example 2 (v17+ default):
```php
$client = new KeycloakClient(
    'my-client-id',
    'my-client-secret',
    'my-realm',
    null,
    ''
);
```
builds the following:
```
https://my-keycloak-base-url.com
# then Realm segments are added:
https://my-keycloak-base-url.com/realms/my-realm
```
If you call `$client->sendRequest('GET', '.well-known/openid-configuration')`:
```
https://my-keycloak-base-url.com/realms/my-realm/.well-known/openid-configuration
```

##### Example 3 (custom path):
```php
$client = new KeycloakClient(
    'my-client-id',
    'my-client-secret',
    'my-realm',
    null,
    'custom-path'
);
```
builds the following:
```
https://my-keycloak-base-url.com/custom-path/realms/my-realm
```
If you call `$client->sendRequest('GET', '.well-known/openid-configuration')`:
```
https://my-keycloak-base-url.com/custom-path/realms/my-realm/.well-known/openid-configuration
```

##### Example 4 (alternate token Realm):
```php
$client = new KeycloakClient(
    'my-client-id',
    'my-client-secret',
    'my-realm',
    'alternate-token-realm',
    ''
);
```
builds the following:
```
# Applies only to the token endpoint:
https://my-keycloak-base-url.com/realms/alternate-token-realm/protocol/openid-connect/token

# Everything else uses the original Realm:
https://my-keycloak-base-url.com/realms/my-realm/protocol/openid-connect/token
```
If you call `$client->sendRequest('GET', '.well-known/openid-configuration')`:
```
https://my-keycloak-base-url.com/realms/my-realm/.well-known/openid-configuration
```

---
### Tested platforms

These are the platforms which are officially supported by this package. Any other versions will probably work but is not guaranteed.

| Platform |   Version |
|----------|----------:|
| PHP      |      7.4+ |
| Keycloak | 11 - 21.1 |

### Running tests
Despite recommendations against it, all tests are executed on a live keycloak environment.

- Create a client on the master realm.
  - Access Type: confidential
  - Service Accounts Enabled: true
  - on tab service account roles attach admin permissions
- Create `/tests/.env` and configure your parameters, see `/tests/.env.sample` for an example.
  Your `.env` file will not be picked up by Git.
### Contributing

Please read our [contribution guidelines](./CONTRIBUTING.md) before contributing.
