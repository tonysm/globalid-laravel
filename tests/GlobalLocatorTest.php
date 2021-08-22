<?php

namespace Tonysm\GlobalId\Tests;

use Facades\Tonysm\GlobalId\Locator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\GlobalIdException;
use Tonysm\GlobalId\Locators\LocatorContract;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;
use Tonysm\GlobalId\Tests\Stubs\Models\PersonUuid;

class GlobalLocatorTest extends TestCase
{
    private Person $model;
    private GlobalId $gid;
    private SignedGlobalId $sgid;

    public function setUp(): void
    {
        parent::setUp();

        $this->model = Person::create(['name' => 'a person']);
        $this->gid = GlobalId::create($this->model);
        $this->sgid = SignedGlobalId::create($this->model);
    }

    /** @test */
    public function by_gid()
    {
        $found = Locator::locate($this->gid);
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_gid_with_only_with_matching_class()
    {
        $found = Locator::locate($this->gid, ['only' => Person::class]);
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_gid_with_only_as_subclass()
    {
        $found = Locator::locate($this->gid, ['only' => Model::class]);
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_gid_with_only_as_no_matching_class()
    {
        $found = Locator::locate($this->gid, ['only' => PersonUuid::class]);
        $this->assertNull($found);
    }

    /** @test */
    public function by_gid_with_multiple_types()
    {
        $found = Locator::locate($this->gid, ['only' => [Person::class, PersonUuid::class]]);
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_gid_with_multiples_types_no_matching()
    {
        $found = Locator::locate($this->gid, ['only' => [GlobalId::class, PersonUuid::class]]);
        $this->assertNull($found);
    }

    /** @test */
    public function by_many_gids_of_one_class()
    {
        $p1 = Person::create(['name' => 'first user']);
        $p2 = Person::create(['name' => 'second user']);

        $gid1 = GlobalId::create($p1);
        $gid2 = GlobalId::create($p2);

        $found = Locator::locateMany([$gid1, $gid2]);

        $this->assertCount(2, $found);

        $this->assertNotNull($found[0]->is($p1));
        $this->assertNotNull($found[1]->is($p2));
    }

    /** @test */
    public function by_many_gids_of_mixed_classes()
    {
        $p1 = Person::create(['name' => 'first user']);
        $p2 = PersonUuid::create(['id' => (string) Str::uuid(), 'name' => 'second user']);

        $gid1 = GlobalId::create($p1);
        $gid2 = GlobalId::create($p2);

        $found = Locator::locateMany([$gid1, $gid2]);

        $this->assertCount(2, $found);

        $this->assertNotNull($found[0]->is($p1));
        $this->assertNotNull($found[1]->is($p2));
    }

    /** @test */
    public function by_many_with_only()
    {
        $p1 = Person::create(['name' => 'first']);
        $p2 = Person::create(['name' => 'second']);
        $uuidP1 = PersonUuid::create(['id' => (string) Str::uuid(), 'name' => 'second']);

        $found = Locator::locateMany([GlobalId::create($p1), GlobalId::create($p2), GlobalId::create($uuidP1)], ['only' => PersonUuid::class]);

        $this->assertCount(1, $found);

        $this->assertTrue($found->first()->is($uuidP1));
    }

    /** @test */
    public function by_sgid()
    {
        $found = Locator::locateSigned($this->sgid);

        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_sgid_with_only()
    {
        $found = Locator::locateSigned($this->sgid, ['only' => Person::class]);

        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_sgid_with_only_matching_subclass()
    {
        $found = Locator::locateSigned($this->sgid, ['only' => Model::class]);

        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_sgid_with_only_no_matching_class()
    {
        $this->assertNull(Locator::locateSigned($this->sgid, ['only' => PersonUuid::class]));
    }

    /** @test */
    public function by_sgid_with_only_multiple_types()
    {
        $found = Locator::locateSigned($this->sgid, ['only' => [Person::class, PersonUuid::class]]);

        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_sgid_with_only_multiple_types_no_match()
    {
        $this->assertNull(Locator::locateSigned($this->sgid, ['only' => [GlobalId::class, PersonUuid::class]]));
    }

    /** @test */
    public function by_many_sgid_of_one_class()
    {
        $found = Locator::locateSigned($this->sgid, ['only' => [Person::class, PersonUuid::class]]);

        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_many_sgid_of_mixed_classes()
    {
        $p1 = Person::create(['name' => 'first']);
        $p2 = PersonUuid::create(['id' => (string) Str::uuid(),'name' => 'second']);

        $sgid1 = SignedGlobalId::create($p1);
        $sgid2 = SignedGlobalId::create($p2);

        $found = Locator::locateManySigned([$sgid1, $sgid2]);

        $this->assertCount(2, $found);
        $this->assertTrue($found[0]->is($p1));
        $this->assertTrue($found[1]->is($p2));
    }

    /** @test */
    public function by_many_sgids_with_only_matching_subclass()
    {
        $p1 = Person::create(['name' => 'first']);
        $p2 = PersonUuid::create(['id' => (string) Str::uuid(), 'name' => 'second']);

        $sgid1 = SignedGlobalId::create($p1);
        $sgid2 = SignedGlobalId::create($p2);

        $found = Locator::locateManySigned([$sgid1, $sgid2], ['only' => Person::class]);

        $this->assertCount(1, $found);
        $this->assertTrue($found[0]->is($p1));
    }

    /** @test */
    public function by_gid_string()
    {
        $found = Locator::locate($this->gid->toString());
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_sgid_string()
    {
        $found = Locator::locateSigned($this->sgid->toString());
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_many_sgid_strings_with_for_restriction_to_matching_purpose()
    {
        $p1 = Person::create(['name' => 'first']);
        $p2 = Person::create(['name' => 'second']);
        $p3 = PersonUuid::create(['id' => (string) Str::uuid(), 'name' => 'uuid']);

        $sgid1 = SignedGlobalId::create($p1, ['for' => 'adoption']);
        $sgid2 = SignedGlobalId::create($p2);
        $sgid3 = SignedGlobalId::create($p3, ['for' => 'adoption']);

        $found = Locator::locateManySigned([$sgid1, $sgid2, $sgid3], [
            'only' => Person::class,
            'for' => 'adoption',
        ]);

        $this->assertCount(1, $found);
        $this->assertTrue($p1->is($found->first()));
    }

    /** @test */
    public function by_to_param()
    {
        $found = Locator::locate($this->gid->toParam());
        $this->assertTrue($this->model->is($found));
    }

    /** @test */
    public function by_non_gid_returns_null()
    {
        $this->assertNull(Locator::locate('This should fail'));
    }

    /** @test */
    public function by_non_sgid_returns_null()
    {
    }

    /** @test */
    public function by_invalid_gid_uri_returns_null()
    {
        $this->assertNull(Locator::locate('http://app/Person/1'));
        $this->assertNull(Locator::locate('gid://Person/1'));
        $this->assertNull(Locator::locate('gid://app/Person'));
        $this->assertNull(Locator::locate('gid://app/Person/1/2'));
    }

    /** @test */
    public function use_locator()
    {
        Locator::use('foo', new class() implements LocatorContract {
            public function locate(GlobalId $globalId, $only = null)
            {
                return 'mocked';
            }

            public function locateMany(Collection $globalIds, $only = null): Collection
            {
                return collect(['mocked']);
            }
        });

        $this->withApp('foo', function () {
            $this->assertEquals('mocked', Locator::locate('gid://foo/Person/1'));
            $this->assertEquals('mocked', Locator::locateMany(['gid://foo/Person/1'])->first());
        });
    }

    /** @test */
    public function use_locator_app_is_case_insensitive()
    {
        Locator::use('foo', new class() implements LocatorContract {
            public function locate(GlobalId $globalId, $only = null)
            {
                return 'mocked';
            }

            public function locateMany(Collection $globalIds, $only = null): Collection
            {
                return collect(['mocked']);
            }
        });

        $this->withApp('foo', function () {
            $this->assertEquals('mocked', Locator::locate('gid://FOo/Person/1'));
            $this->assertEquals('mocked', Locator::locateMany(['gid://FoO/Person/1'])->first());
        });
    }

    /** @test */
    public function use_locator_app_cannot_have_underscore()
    {
        $this->expectException(GlobalIdException::class);

        Locator::use('foo_lorem', new class() implements LocatorContract {
            public function locate(GlobalId $globalId, $only = null)
            {
                return 'mocked';
            }

            public function locateMany(Collection $globalIds, $only = null): Collection
            {
                return collect(['mocked']);
            }
        });
    }

    /** @test */
    public function by_valid_purpose_returns_right_model()
    {
    }

    /** @test */
    public function by_valid_purpose_with_sgid_returns_right_model()
    {
    }

    /** @test */
    public function by_invalid_purpose_returns_null()
    {
    }

    /** @test */
    public function by_invalid_purpose_with_sgid_returns_null()
    {
    }

    /** @test */
    public function by_many_with_one_record_missing()
    {
    }

    private function withApp($app, callable $callback)
    {
        $oldApp = GlobalId::$app;

        GlobalId::useAppName($app);

        try {
            $callback();
        } finally {
            GlobalId::useAppName($oldApp);
        }
    }
}
