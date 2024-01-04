<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\InstallDatabaseForm.
 *
 * @author kevin
 */
class InstallDatabaseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('database_name', 'text', ['required' => true])
            ->add('database_host', 'text', ['required' => true])
            ->add('database_port', 'text', ['required' => false])
            ->add('database_User', 'text', ['required' => true])
            ->add('database_password', 'password', ['required' => false]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'installDatabaseForm';
    }
}
