<?php

namespace App\Support\Content;

class MembershipContent
{
    public const ADHESION_FEE = 10000;

    public const CURRENCY = 'FCFA';

    public const ADHESION_PERIOD = 'an';

    public static function profiles(): array
    {
        return [
            ['id' => 'etudiant', 'label' => 'Étudiant & jeune diplômé'],
            ['id' => 'porteur', 'label' => 'Porteur de projet'],
            ['id' => 'entrepreneur', 'label' => 'Entrepreneur confirmé'],
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            ['id' => 'wave', 'label' => 'Wave'],
            ['id' => 'orange', 'label' => 'Orange Money'],
            ['id' => 'djamo', 'label' => 'Djamo'],
        ];
    }

    public static function formatPrice(int $n): string
    {
        return number_format($n, 0, ',', ' ');
    }
}
