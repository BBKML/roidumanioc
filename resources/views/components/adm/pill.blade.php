@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : $status;
    $label = ($status instanceof \BackedEnum && method_exists($status, 'label')) ? $status->label() : \Illuminate\Support\Str::headline((string) $value);

    $map = [
        // formations / enrollments / orders
        'publiee' => 'ok', 'validee' => 'ok', 'confirme' => 'ok', 'actif' => 'ok', 'livree' => 'ok', 'inscrit' => 'ok', 'ouvert' => 'ok', 'satisfait' => 'ok', 'acceptee' => 'ok', 'collaboration_confirmee' => 'ok', 'paiement_confirme' => 'ok', 'livraison_confirmee' => 'ok', 'terminee' => 'ok', 'commande_confirmee' => 'ok',
        'expediee' => 'info', 'negociation' => 'info', 'proposition' => 'info', 'en_cours' => 'info', 'livraison_en_cours' => 'info', 'negociation_livraison' => 'info', 'aide_livraison' => 'info', 'livraison_en_preparation' => 'info', 'livreur_contacte' => 'info', 'livraison_auto_organisee' => 'info',
        'brouillon' => 'neutral', 'masque' => 'neutral', 'termine' => 'neutral', 'ferme' => 'neutral', 'annulee' => 'neutral', 'perimee' => 'neutral',
        'a_verifier' => 'warn', 'paiement' => 'warn', 'en_attente' => 'warn', 'planifie' => 'warn', 'signale' => 'warn', 'suspendu' => 'warn', 'nouveau' => 'warn', 'indisponible' => 'warn', 'declare' => 'warn', 'paiement_declare' => 'warn', 'en_attente_producteur' => 'warn', 'demande_aide' => 'warn', 'en_attente_validation_admin' => 'warn',
        'contacte' => 'info',
        'refuse' => 'danger', 'abandonne' => 'danger', 'refusee' => 'danger', 'litige' => 'danger', 'conteste' => 'danger',
        'archivee' => 'neutral', 'expire' => 'neutral',
        'visible' => 'ok',
    ];
    $cls = $map[$value] ?? 'neutral';
@endphp
<span {{ $attributes->merge(['class' => "pill $cls"]) }}><span class="dot"></span>{{ $label }}</span>
