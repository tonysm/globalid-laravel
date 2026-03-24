<?php

namespace Tonysm\GlobalId\Tests;

use PHPUnit\Framework\Attributes\Test;
use Tonysm\GlobalId\SignedGlobalId;
use Tonysm\GlobalId\Tests\Stubs\Models\Person;

class SignedGlobalIdKeyRotationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->parse('2021-09-21T18:07:45Z'));
    }

    #[Test]
    public function parses_sgid_signed_with_previous_key()
    {
        $model = Person::create(['name' => 'rotation test']);

        // Sign with the current key.
        $sgid = SignedGlobalId::create($model);
        $sgidString = $sgid->toString();

        // Rotate the key: move the current key to previous_keys and set a new one.
        $oldKey = config('app.key');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('app.previous_keys', [$oldKey]);

        // The SGID signed with the old key should still parse successfully.
        $parsed = SignedGlobalId::parse($sgidString);

        $this->assertNotNull($parsed);
        $this->assertEquals($model->id, $parsed->modelId());
    }

    #[Test]
    public function returns_null_when_key_is_completely_unknown()
    {
        $model = Person::create(['name' => 'unknown key test']);

        // Sign with the current key.
        $sgid = SignedGlobalId::create($model);
        $sgidString = $sgid->toString();

        // Rotate to a new key without preserving the old one.
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('app.previous_keys', []);

        $parsed = SignedGlobalId::parse($sgidString);

        $this->assertNull($parsed);
    }

    #[Test]
    public function new_sgids_are_signed_with_current_key()
    {
        $model = Person::create(['name' => 'new key test']);

        // Rotate the key.
        $oldKey = config('app.key');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('app.previous_keys', [$oldKey]);

        // Create a new SGID — should be signed with the new key.
        $sgid = SignedGlobalId::create($model);
        $sgidString = $sgid->toString();

        // Remove previous keys — should still verify with current key only.
        config()->set('app.previous_keys', []);

        $parsed = SignedGlobalId::parse($sgidString);

        $this->assertNotNull($parsed);
        $this->assertEquals($model->id, $parsed->modelId());
    }

    #[Test]
    public function handles_multiple_previous_keys()
    {
        $model = Person::create(['name' => 'multi rotation test']);

        // Sign with the original key.
        $sgid = SignedGlobalId::create($model);
        $sgidString = $sgid->toString();

        // Simulate two key rotations.
        $originalKey = config('app.key');
        $secondKey = 'base64:'.base64_encode(random_bytes(32));
        $thirdKey = 'base64:'.base64_encode(random_bytes(32));

        config()->set('app.key', $thirdKey);
        config()->set('app.previous_keys', [$secondKey, $originalKey]);

        $parsed = SignedGlobalId::parse($sgidString);

        $this->assertNotNull($parsed);
        $this->assertEquals($model->id, $parsed->modelId());
    }
}
