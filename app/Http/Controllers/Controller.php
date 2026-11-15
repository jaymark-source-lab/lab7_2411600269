<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // gives every controller $this->authorize()
    use AuthorizesRequests;
}
