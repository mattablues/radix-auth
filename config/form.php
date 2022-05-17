<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: form.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-17, 14:44
 */

return [
    'revalidate' => [
        'max' => 2
    ],
    'throttle' => [
        'times' => 3,
        'delay' => 1,
        'block' => 5
    ]
];
