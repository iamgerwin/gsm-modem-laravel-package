<?php

declare(strict_types=1);

test('it will not use debugging functions')->skip(
    !function_exists('arch'),
    'Architecture testing not available in this Pest version'
);
