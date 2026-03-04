<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\GnInscriptionForm.
 *
 * @author kevin
 */
class GnInscriptionType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('idGn', 'hidden');
    }

    /**
     * Nom du formulaire.
     */
}
