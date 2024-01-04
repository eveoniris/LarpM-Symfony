<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\InstallUserAdminForm.
 *
 * @author kevin
 */
class InstallUserAdminForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', 'text', ['required' => true])
            ->add('email', 'email', ['required' => true])
            ->add('password', 'password', ['required' => true]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'installUserAdminForm';
    }
}
