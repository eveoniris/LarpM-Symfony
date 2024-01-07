<?php


namespace App\Form\Groupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\GroupeInscriptionForm.
 *
 * @author kevin
 */
class GroupeInscriptionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Entrez le code du groupe',
            'required' => true,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeInscription';
    }
}
