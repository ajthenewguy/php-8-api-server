<?php declare(strict_types=1);

namespace Tests\Feature;

use Ajthenewguy\Php8ApiServer\Models\Model;

class TestModel extends Model {
    protected static string $table = 'test_table';
    protected static $casts = ['quantity' => 'int'];
    protected array $dates = ['settlement'];
    protected const CREATED_FIELD = null;
    protected const UPDATED_FIELD = null;
}