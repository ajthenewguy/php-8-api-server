<?php declare(strict_types=1);

namespace Tests\Unit\Routing;

use Ajthenewguy\Php8ApiServer\Routing\Guard;
use PHPUnit\Framework\TestCase;

class GuardTest extends TestCase
{
    public function testValidate()
    {
        //validate
        $Guard = new Guard(['user_id' => ['required']]);

        $claims = new \stdClass();
        $claims->exp = time() - 1000;
        $claims->user_id = 17;

        $this->assertFalse($Guard->validate($claims));

        $claims = new \stdClass();
        $claims->exp = time() + 5;

        $this->assertFalse($Guard->validate($claims));

        $claims = new \stdClass();
        $claims->exp = time() + 5;
        $claims->user_id = null;

        $this->assertFalse($Guard->validate($claims));

        $claims = new \stdClass();
        $claims->exp = time() + 50;
        $claims->user_id = 17;

        $this->assertTrue($Guard->validate($claims));
    }
}