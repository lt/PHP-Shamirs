Shamir's Secret Sharing Scheme in PHP
=====================================

This library contains an implementation of the [Shamir's Secret Sharing Scheme](https://en.wikipedia.org/wiki/Shamir%27s_Secret_Sharing).

### Usage:

A prerequisite to generating shares is having a prime that fully covers the size of the secret you wish to protect.

Examples:

```php
// (2**128)+51 - Smallest prime that covers all 128 bit secrets (i.e. AES keys)
const P128 = '0x100000000000000000000000000000033';

// (2**256)+297 - Smallest prime that covers all 256 bit secrets (i.e. ChaCha20 keys)
const P256 = '0x10000000000000000000000000000000000000000000000000000000000000129';

$prime = gmp_init(P256);
```

The scheme requires k of n shares to recover a secret, so initially at least k shares need to be generated. n can be changed later, but k cannot.

```php
// Turn the private data into a GMP number so we can do maths with it
$secret = gmp_import($privateData);

$requiredShares = 3; // k
$initialShares = 5;  // n

$scheme = new \SSSS\Scheme($prime);
$shares = $scheme->initialShares($secret, $requiredShares, $initialShares);
```

Providing you have enough shares, you can recover the secret.

```php
$shares = [
    new \SSSS\Share(1, ...),
    new \SSSS\Share(3, ...),
    new \SSSS\Share(5, ...)
];

$secret = $scheme->recoverSecret($shares);
```

New shares can be created but this also requires k shares to do. Shares are numbered. Do not re-use share numbers for new shares.

```php
$shares = [
    new \SSSS\Share(1, ...),
    new \SSSS\Share(3, ...),
    new \SSSS\Share(5, ...)
];

$newShare = $scheme->addShare(6, $shares);
```
