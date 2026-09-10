<?php

declare(strict_types=1);

// Messages de validation en français — seules les règles utilisées par
// l'application sont traduites (cf. $validationRules des modèles).
return [
    'noRuleSets'      => 'Aucun jeu de règles spécifié dans la configuration de validation.',
    'ruleNotFound'    => '"{0}" n\'est pas une règle valide.',
    'groupNotFound'   => '"{0}" n\'est pas un groupe de règles de validation.',
    'groupNotArray'   => 'Le groupe de règles "{0}" doit être un tableau.',
    'invalidTemplate' => '"{0}" n\'est pas un template de validation valide.',

    'required'           => 'Le champ {field} est requis.',
    'max_length'         => 'Le champ {field} ne peut pas dépasser {param} caractères.',
    'min_length'         => 'Le champ {field} doit contenir au moins {param} caractères.',
    'valid_email'        => 'Le champ {field} doit être une adresse e-mail valide.',
    'is_unique'          => 'Le champ {field} doit être une valeur unique.',
    'is_not_unique'      => 'Le champ {field} doit correspondre à une valeur existante en base.',
    'is_natural_no_zero' => 'Le champ {field} doit être un nombre entier positif.',
    'valid_date'         => 'Le champ {field} doit être une date valide.',
];
