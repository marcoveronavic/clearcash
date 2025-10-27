<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultBudgetCategories extends Model
{
    protected $table = 'default_budget_categories';

    protected $fillable = ['slug', 'name'];

    public $timestamps = false;
}
