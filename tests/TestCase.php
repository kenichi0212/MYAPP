<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // usersのcompany_idは外部キー制約があるため、RefreshDatabase使用時は
    // companies(id=1)が事前に存在する必要がある。
    protected bool $seed = true;
}
