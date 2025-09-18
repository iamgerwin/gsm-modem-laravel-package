<?php

namespace Iamgerwin\GsmModem\Tests;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();
