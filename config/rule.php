<?php

declare(strict_types=1);

/**
 * Project name: radix-auth
 * Filename: rule.php
 * @author Mats Åkebrand <mats@akebrands.se>
 * Created at: 2022-05-17, 14:44
 */

return [
    'required' => 'fyll i fältet {field}',
    'max' => '{field} får innehålla högst {max} tecken',
    'min' => '{field} måste innehålla minst {min} tecken',
    'num' => '{field} måste innehålla minst {num} siffr{prefix}',
    'let' => '{field} måste innehålla minst {let} bokst{prefix}',
    'match' => '{field} stämmer inte överens med {match}',
    'spec' => '{field} måste innehålla minst {spec} special tecken !@#$%^&*?_- etc...',
    'email' => 'ogiltig e-postadress',
    'url' => 'ogiltig url',
    'unique' => '{field} existerar redan',
    'space' => '{field} får inte ha några mellanslag',
    'numeric' => "{field} får bara inehålla siffror",
    'letters' => "{field} får bara inehålla bokstäver",
    'add' => [
        'activate' => 'Kontot är inte aktiverat',
        'blocked' => 'Kontot är blockerat, kontakta {placeholder}',
        'closed' => 'Kontot är stängt',
        'throttle' => 'För många misslyckade försök, du måste vänta {placeholder} innan du kan logga in på nytt',
        'failed' => 'Fel inloggnings uppgifter',
        'revalidate' => 'Fel validerings uppgifter',
    ]
];
