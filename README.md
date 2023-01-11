<p align="center" style="margin-top: 2rem; margin-bottom: 2rem;"><img src="/art/globalids-laravel-logo.svg" alt="GlobalIds Laravel" /></p>

<p align="center">
    <a href="https://github.com/tonysm/globalid-laravel/workflows/run-tests/badge.svg">
        <img src="https://github.com/tonysm/globalid-laravel/workflows/run-tests/badge.svg" />
    </a>
    <a href="https://packagist.org/packages/tonysm/globalid-laravel">
        <img src="https://img.shields.io/packagist/dt/tonysm/globalid-laravel" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/tonysm/globalid-laravel">
        <img src="https://img.shields.io/github/license/tonysm/globalid-laravel" alt="License">
    </a>
</p>

# Introduction

Identify app models with a URI.

A Global ID is an app wide URI that uniquely identifies a model instance:

```
gid://YourApp/Some\\Model/id
```

This is helpful when you need a single identifier to reference different classes of objects.

One example is storing model references in places where you cannot enforce constraints or cannot make use of the convenient Eloquent relationships, such as storing model references in a rich text content field. We need to reference a model object rather than serialize the object itself. We can pass a Global ID that can be used to locate the model when it's time to render the rich text content. The rendering doesn't need to know the details of model naming and IDs, just that it has a global identifier that references a model.

Another example is a drop-down list of options, consisting of both Users and Groups. Normally we'd need to come up with our own ad hoc scheme to reference them. With Global IDs, we have a universal identifier that works for objects of both classes.

### Inspiration

Heavily inspired by the [globalid gem](https://github.com/rails/globalid).

## Installation

Via Composer:

```
composer require tonysm/globalid-laravel
```

## Usage

Add the `HasGlobalIdentification` trait to any Eloquent model (or any class with a `find($id)`, `findMany($ids): Collection` static methods, and a `getKey()` instance method):

```php
use Tonysm\GlobalId\Models\HasGlobalIdentification;

class Person extends Model
{
    use HasGlobalIdentification;
}
```

Then you can create GlobalIds and SignedGlobalIds like so:

```php
$personGid = Person::find(1)->toGlobalId();
# => Tonysm\GlobalId\GlobalId {#5010}

$personGid->toString();
# => "gid://laravel/App%5CModels%5CPerson/1"

# Returns a URL-safe base64 encoded version of the SGID...
$personGid->toParam();
# => "Z2lkOi8vbGFyYXZlbC9BcHAlNUNNb2RlbHMlNUNQZXJzb24vMQ"

Tonysm\GlobalId\Facades\Locator::locate('gid://laravel/App%5CModels%5CPerson/1');
# => App\Models\Person {#5022 id:1...

# You can also pass the base64 encoded to it and it will just work...
Tonysm\GlobalId\Facades\Locator::locate('Z2lkOi8vbGFyYXZlbC9BcHAlNUNNb2RlbHMlNUNQZXJzb24vMQ');
# => App\Models\Person {#5022 id:1...

# You can also call the locate method on the GlobalId object...
$personGid->locate();
# => App\Models\Person {#5022 id:1...
```

You may customize the location logic using a [custom locator](#custom-locators).

### Signed Global IDs

For added security GlobalIDs can also be signed to ensure that the data hasn't been tampered with.

```php
$personSgid = Person::find(1)->toSignedGlobalId();
# => Tonysm\GlobalId\SignedGlobalId {#5005}

$personSgid = Person::find(1)->toSgid();
# => Tonysm\GlobalId\SignedGlobalId {#5026}

$personSgid->toString();
# => "BAhJIh5naWQ6Ly9pZGluYWlkaS9Vc2VyLzM5NTk5BjoGRVQ=--81d7358dd5ee2ca33189bb404592df5e8d11420e"

Tonysm\GlobalId\Facades\Locator::locateSigned($personSgid);
# => App\Models\Person {#5009 id: 1, ...

# You can also call the locate method on the SignedGlobalId object...
$personSgid->locate();
# => App\Models\Person {#5022 id:1...
```

**Expiration**

Signed Global IDs can expire some time in the future. This is useful if there's a resource people shouldn't have indefinite access to, like a share link.

```php
$expiringSgid = Document::find(5)->toSgid([
    'expires_at' => now()->addHours(2),
    'for' => 'sharing',
]);
# => Tonysm\GlobalId\SignedGlobalId {#5026}

# Within 2 hours...
Tonysm\GlobalId\Facades\Locator::locateSigned($expiringSgid->toString(), [
    'for' => 'sharing',
]);
# => App\Models\Document {#5009 id: 5, ...

# More than 2 hours later...
Tonysm\GlobalId\Facades\Locator::locateSigned($expiringSgid->toString(), [
    'for' => 'sharing',
]);
# => null
```

**An auto-expiry of 1 month is set by default.** You can override this default by passing a expiration resolver Closure from any Service Provider boot method. This resolver will get called every time a SGID is created:

```php
SignedGlobalId::useExpirationResolver(() => now()->addMonths(3));
```

This way any generated SGID will use that relative expiry.

It's worth noting that _expiring SGIDs are not idempotent_ because they encode the current timestamp; repeated calls to `to_sgid` will produce different results. For example:

```php
Document::find(5)->toSgid()->toString() == Document::find(5)->toSgid()->toString()
# => false
```

You need to explicitly pass `['expires_at' => null]` to generate a permanent SGID that will not expire,

```php
# Passing a false value to either expiry option turns off expiration entirely.
$neverExpiringSgid = Document::find(5)->toSgid(['expires_at' => null]);
# => Tonysm\GlobalId\SignedGlobalId {#5026}

# Any time later...
Tonysm\GlobalId\Facades\Locator::locateSigned($neverExpiringSgid);
# => App\Models\Document {#5009 id: 5, ...
```

**Purpose**

You can even bump the security up some more by explaining what purpose a Signed Global ID is for. In this way evildoers can't reuse a sign-up form's SGID on the login page. For example:

```php
$signupPersonSgid = Person::find(1)->toSgid(['for' => 'signup_form']);
# => Tonysm\GlobalId\SignedGlobalId {#5026}

Tonysm\GlobalId\Facades\Locator::locateSigned($signupPersonSgid, ['for' => 'signup_form']);
# => App\Models\Person {#5009 id: 1, ...

Tonysm\GlobalId\Facades\Locator::locateSigned($signupPersonSgid, ['for' => 'login']);
# => null
```

### Locating many Global IDs

When needing to locate many Global IDs use `Tonysm\GlobalId\Facades\Locator->locateMany` or `Tonysm\GlobalId\Facades\Locator::locateManySigned()` for Signed Global IDs to allow loading Global IDs more efficiently.

For instance, the default locator passes every `$modelId` per `$modelName` thus using `$modelName::findMany($ids)` versus `Tonysm\GlobalId\Facades\Locator->locate()`'s `$modelName::find($id)`.

In the case of looking up Global IDs from a database, it's only necessary to query once per `$modelName` as shown here:

```php
$gids = $users->merge($students)->sortBy('id')->map(fn ($model) => $model->toGlobalId());
# => [#<Tonysm\GlobalId\GlobalId {#5026} @gid=#GID<gid://app/User/1>>,
#<Tonysm\GlobalId\GlobalId {#5027} @gid=#GID<gid://app/Student/1>>,
#<Tonysm\GlobalId\GlobalId {#5028} @gid=#<GID gid://app/User/2>>,
#<Tonysm\GlobalId\GlobalId {#5029} @gid=#<GID gid://app/Student/2>>,
#<Tonysm\GlobalId\GlobalId {#5030} @gid=#<GID gid://app/User/3>>,
#<Tonysm\GlobalId\GlobalId {#5031} @gid=#<GID gid://app/Student/3>>]

Tonysm\GlobalId\Facades\Locator::locateMany($gids);
# SELECT "users".* FROM "users" WHERE "users"."id" IN ($1, $2, $3)  [["id", 1], ["id", 2], ["id", 3]]
# SELECT "students".* FROM "students" WHERE "students"."id" IN ($1, $2, $3)  [["id", 1], ["id", 2], ["id", 3]]
# => [#<User id: 1>, #<Student id: 1>, #<User id: 2>, #<Student id: 2>, #<User id: 3>, #<Student id: 3>]
```

Note the order is maintained in the returned results.

### Custom App Locator
<a name="custom-locators"></a>

A custom locator can be set for an app by calling `Tonysm\GlobalId\Locator::use()` and providing an app locator to use for that app. A custom app locator is useful when different apps collaborate and reference each others' Global IDs. When finding a Global ID's model, the locator to use is based on the app name provided in the Global ID url.

Using a custom Locator:

```php
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\Facades\Locator;
use Tonysm\GlobalId\Locators\LocatorContract;
use Illuminate\Support\Collection;

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

### Custom Polymorphic Types

When using the [Custom Polymorphic Types](https://laravel.com/docs/8.x/eloquent-relationships#custom-polymorphic-types) feature from Eloquent, the model name inside the GID URI will use your alias instead of the model's FQCN.

```php
use App\Models\Person;

Relation::enforceMorphMap([
    'person' => Person::class,
]);

$gid = GlogalId::create(Person::create(['name' => 'a person']), [
    'app' => 'laravel',
]);

$gid->toString();
# => "gid://laravel/person/1"
```

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
