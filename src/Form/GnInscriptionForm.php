<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\GnInscriptionForm.
 *
 * @author kevin
 */
class GnInscriptionForm extends AbstractType
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
    public function getName(): string
    {
        return 'inscriptionGn';
    }
}
