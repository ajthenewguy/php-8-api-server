<?php declare(strict_types=1);

namespace Tests\Unit\Routing;

use Ajthenewguy\Php8ApiServer\Routing\Guard;
use Tests\TestCase;

class GuardTest extends TestCase
{
    public function testValidate()
    {
        $Guard = new Guard(['user_id' => ['required']]);

        $claims = new \stdClass();
        $claims->exp = time() - 1000;
        $claims->user_id = 17;

        $Guard->validate($claims)->then(function ($result) {
            $this->assertFalse($result);
        });
        

        $claims = new \stdClass();
        $claims->exp = time() + 5;

        $Guard->validate($claims)->then(function ($result) {
            $this->assertFalse($result);
        });

        $claims = new \stdClass();
        $claims->exp = time() + 5;
        $claims->user_id = null;

        $Guard->validate($claims)->then(function ($result) {
            $this->assertFalse($result);
        });

        $claims = new \stdClass();
        $claims->exp = time() + 50;
        $claims->user_id = 17;

        $Guard->validate($claims)->then(function ($result) {
            $this->assertTrue($result);
        });
    }
}