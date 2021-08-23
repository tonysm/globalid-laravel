<p align="center" style="margin-top: 2rem; margin-bottom: 2rem;"><img src="/art/globalids-laravel-logo.svg" alt="GlobalIds Laravel" /></p>

<p align="center">
    <a href="https://github.com/tonysm/globalid-laravel/workflows/run-tests/badge.svg">
        <img src="https://img.shields.io/github/workflow/status/tonysm/globalid-laravel/run-tests?label=tests" />
    </a>
    <a href="https://packagist.org/packages/tonysm/globalid-laravel">
        <img src="https://img.shields.io/packagist/dt/tonysm/globalid-laravel" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/tonysm/globalid-laravel">
        <img src="https://img.shields.io/github/license/tonysm/globalid-laravel" alt="License">
    </a>
</p>

Identify app models with a URI. _heavily inspired by the [globalid gem](https://github.com/rails/globalid)_.

# Global ID - Reference models by URI

A Global ID is an app wide URI that uniquely identifies a model instance:

```
gid://YourApp/Some::Model/id
```

This is helpful when you need a single identifier to reference different classes of objects.

One example is storing model references in places where you cannot enforce constraints or cannot make use of the convenient Eloquent relationships, such as storing model references in a rich text content field. We need to reference a model object rather than serialize the object itself. We can pass a Global ID that can be used to locate the model when it's time to render the rich text content. The rendering doesn't need to know the details of model naming and IDs, just that it has a global identifier that references a model.

Another example is a drop-down list of options, consisting of both Users and Groups. Normally we'd need to come up with our own ad hoc scheme to reference them. With Global IDs, we have a universal identifier that works for objects of both classes.

## Usage

Add the `HasGlobalIdentification` trait into any model with a `::find($id)` and `findMany($ids)` static methods.

```php
$personGid = Person::find(1)->toGlobalId();
# => #<GlobalID ...

$personGid->toString();
# => "gid://app/App\\Models\\Person/1"

Tonysm\GlobalId\Locator::locate($personGid)
# => #<Person:0x007fae94bf6298 @id="1">
```

### Signed Global IDs

For added security GlobalIDs can also be signed to ensure that the data hasn't been tampered with.

```php
$personSgid = Person::find(1)->toSignedGlobalId()
# => #<SignedGlobalID:0x007fea1944b410>

$personSgid = Person::find(1)->toSgid()
# => #<SignedGlobalID:0x007fea1944b410>

$personSgid->toString()
# => "BAhJIh5naWQ6Ly9pZGluYWlkaS9Vc2VyLzM5NTk5BjoGRVQ=--81d7358dd5ee2ca33189bb404592df5e8d11420e"

Tonysm\GlobalId\Locator::locateSigned($personSgid)
# => #<Person:0x007fae94bf6298 @id="1">
```

**Expiration**

Signed Global IDs can expire some time in the future. This is useful if there's a resource people shouldn't have indefinite access to, like a share link.

```ruby
$expiringSgid = Document::find(5)->toSgid([
    'expires_at' => now()->addHours(2),
    'for' => 'sharing',
])
# => #<SignedGlobalID:0x008fde45df8937 ...>

# Within 2 hours...
Tonysm\GlobalId\Locator::locateSigned($expiringSgid->toString(), [
    'for' => 'sharing',
]);
# => #<Document:0x007fae94bf6298 @id="5">

# More than 2 hours later...
Tonysm\GlobalId\Locator::locateSigned($expiringSgid->toString(), [
    'for' => 'sharing',
])
# => null
```

**An auto-expiry of 1 month is set by default.** You can alter that deal by with from a boot method of any service provider:

```php
SignedGlobalId::useExpirationResolver(() => now()->addMonths(3));
```

This way any generated SGID will use that relative expiry.

It's worth noting that _expiring SGIDs are not idempotent_ because they encode the current timestamp; repeated calls to `to_sgid` will produce different results. For example, in Rails

```ruby
Document::find(5)->toSgid()->toString() == Document::find(5)->toSgid()->toString()
# => false
```

You need to explicitly pass `['expires_at' => null]` to generate a permanent SGID that will not expire,

```php
# Passing a false value to either expiry option turns off expiration entirely.
$neverExpiringSgid = Document::find(5)->toSgid(['expires_at' => null])
# => #<SignedGlobalID:0x008fde45df8937 ...>

# Any time later...
Tonysm\GlobalId\Locator::locateSigned($neverExpiringSgid)
# => #<Document:0x007fae94bf6298 @id="5">
```

**Purpose**

You can even bump the security up some more by explaining what purpose a Signed Global ID is for. In this way evildoers can't reuse a sign-up form's SGID on the login page. For example:

```php
$signupPersonSgid = Person::find(1)->toSgid(['for' => 'signup_form']);
# => #<SignedGlobalID:0x007fea1984b520

Tonysm\GlobalId\Locator::locateSigned($signupPersonSgid, ['for' => 'signup_form'])
# => #<Person:0x007fae94bf6298 @id="1">

Tonysm\GlobalId\Locator::locateSigned($signupPersonSgid, ['for' => 'login'])
# => null
```

### Locating many Global IDs

When needing to locate many Global IDs use `Tonysm\GlobalId\Locator->locateMany` or `Tonysm\GlobalId\Locator::locateManySigned()` for Signed Global IDs to allow loading Global IDs more efficiently.

For instance, the default locator passes every `$modelId` per `$modelName` thus using `$modelName::findMany($ids)` versus `Tonysm\GlobalId\Locator->locate()`'s `$modelName::find($id)`.

In the case of looking up Global IDs from a database, it's only necessary to query once per `$modelName` as shown here:

```php
$gids = $users->merge($people)->sortBy('id')->map(fn ($model) => $model->toGlobalId())
# => [#<GlobalID:0x00007ffd6a8411a0 @uri=#<URI::GID gid://app/User/1>>,
#<GlobalID:0x00007ffd675d32b8 @uri=#<URI::GID gid://app/Student/1>>,
#<GlobalID:0x00007ffd6a840b10 @uri=#<URI::GID gid://app/User/2>>,
#<GlobalID:0x00007ffd675d2c28 @uri=#<URI::GID gid://app/Student/2>>,
#<GlobalID:0x00007ffd6a840480 @uri=#<URI::GID gid://app/User/3>>,
#<GlobalID:0x00007ffd675d2598 @uri=#<URI::GID gid://app/Student/3>>]

Tonysm\GlobalId\Locator::locateMany($gids)
# SELECT "users".* FROM "users" WHERE "users"."id" IN ($1, $2, $3)  [["id", 1], ["id", 2], ["id", 3]]
# SELECT "students".* FROM "students" WHERE "students"."id" IN ($1, $2, $3)  [["id", 1], ["id", 2], ["id", 3]]
# => [#<User id: 1>, #<Student id: 1>, #<User id: 2>, #<Student id: 2>, #<User id: 3>, #<Student id: 3>]
```

Note the order is maintained in the returned results.

### Custom App Locator

A custom locator can be set for an app by calling `Tonysm\GlobalId\Locator::use()` and providing an app locator to use for that app. A custom app locator is useful when different apps collaborate and reference each others' Global IDs. When finding a Global ID's model, the locator to use is based on the app name provided in the Global ID url.

Using a custom Locator:

```php
use Tonysm\GlobalId\Locator;
use Tonysm\GlobalId\Locators\LocatorContract;

Locator::use('foo', new class implements LocatorContract {
    public function locate(GlobalId $globalId)
    {
        // ...
    }

    public function locateMany(Collection $globalIds, array $options = []): Collection
    {
        // ...
    }
});
```

After defining locators as above, URIs like `gid://foo/Person/1` will now use that locator. Other apps will still keep using the default locator.

## Testing the Package

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

You're encouraged to submit pull requests, propose features and discuss issues.

## Security Vulnerabilities

Drop me an email at [tonysm@hey.com](mailto:tonysm@hey.com?subject=Security%20Vulnerability) if you want to report
security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Tony Messias](https://github.com/tonysm)
- [All Contributors](./CONTRIBUTORS.md)
