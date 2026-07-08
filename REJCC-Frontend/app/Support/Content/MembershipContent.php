<?php

namespace App\Support\Content;

class MembershipContent
{
    public static function profiles(): array
    {
        return [
            ['id' => 'etudiant', 'label' => 'Étudiant & jeune diplômé'],
            ['id' => 'porteur', 'label' => 'Porteur de projet'],
            ['id' => 'entrepreneur', 'label' => 'Entrepreneur confirmé'],
        ];
    }
}
