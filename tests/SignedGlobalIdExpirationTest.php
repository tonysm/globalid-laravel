<?php

namespace Tonysm\GlobalId\Tests;

use Carbon\CarbonInterface;
use Tonysm\GlobalId\GlobalId;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdExpirationTest extends TestCase
{
    private Person $model;
    private string $uri;

    public function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->startOfDay());

        $this->model = Person::create(['name' => 'Testing']);
        $this->uri = GlobalId::create($this->model)->toString();
    }

    /** @test */
    public function expires_at_defaults_to_class_level_expiration()
    {
        $this->withExpiration(now()->addHour(), function (): void {
            $encodedSgid = (new SignedGlobalId($this->uri))->toString();

            $this->travelTo(now()->addMinutes(59));
            $this->assertNotNull(SignedGlobalId::parse($encodedSgid));

            $this->travelTo(now()->addMinutes(2));
            $this->assertNull(SignedGlobalId::parse($encodedSgid));
        });
    }

    /** @test */
    public function passing_in_expires_at_overrides_class_level_expiration()
    {
        $this->withExpiration(now()->addHour(), function (): void {
            $encodedSgid = (new SignedGlobalId($this->uri, ['expires_at' => now()->addHours(2)]))->toString();

            $this->travelTo(now()->addMinutes(60));
            $this->assertNotNull(SignedGlobalId::parse($encodedSgid));

            $this->travelTo(now()->addMinutes(60 + 2));
            $this->assertNull(SignedGlobalId::parse($encodedSgid));
        });
    }

    /** @test */
    public function passing_expires_at_less_than_a_second_is_not_expired()
    {
        $this->withExpiration(now()->addHour(), function (): void {
            $encodedSgid = (new SignedGlobalId($this->uri, ['expires_at' => now()->addSecond()]))->toString();

            $this->travelTo(now()->addMilliseconds(500), function () use ($encodedSgid): void {
                $this->assertNotNull(SignedGlobalId::parse($encodedSgid));
            });

            $this->travelTo(now()->addSeconds(2), function () use ($encodedSgid): void {
                $this->assertNull(SignedGlobalId::parse($encodedSgid));
            });
        });
    }

    /** @test */
    public function passing_expires_at_null_turns_off_expiration_checking()
    {
        $this->withExpiration(now()->addHour(), function (): void {
            $encodedSgid = (new SignedGlobalId($this->uri, ['expires_at' => null]))->toString();

            $this->travelTo(now()->addHour(), function () use ($encodedSgid): void {
                $this->assertNotNull(SignedGlobalId::parse($encodedSgid));
            });

            $this->travelTo(now()->addHours(2), function () use ($encodedSgid): void {
                $this->assertNotNull(SignedGlobalId::parse($encodedSgid));
            });
        });
    }

    /** @test */
    public function passing_expires_at_null_off_expiration_checking()
    {
        $date = now()->endOfDay();
        $sgid = new SignedGlobalId($this->uri, ['expires_at' => $date]);

        $this->assertTrue($date->eq($sgid->expiresAt()));

        $this->travelTo(now()->addDay());
        $this->assertNull(SignedGlobalId::parse($sgid->toString()));
    }

    private function withExpiration(CarbonInterface $expiration, callable $callback)
    {
        try {
            $original = SignedGlobalId::$expiresInResolver;

            SignedGlobalId::useExpirationResolver(fn () => $expiration);

            $callback();
        } finally {
            SignedGlobalId::useExpirationResolver($original);
        }
    }
}
