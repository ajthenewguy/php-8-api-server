<?php declare(strict_types=1);

namespace Tests\Unit\Facades;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Facades\DB;
use Ajthenewguy\Php8ApiServer\Facades\View;
use Jenssegers\Blade\Blade;
use Tests\TestCase;

class ViewTest extends TestCase
{
    public function testBlade()
    {
        $Blade = View::Blade();

        $this->assertEquals(Blade::class, get_debug_type($Blade));
    }

    public function testMake()
    {
        $View = View::make('index');

        $this->assertEquals(\Illuminate\View\View::class, get_debug_type($View));
    }

    public function testRender()
    {
        $string = View::render('index');

        $this->assertStringContainsString('<html', $string);
    }
}