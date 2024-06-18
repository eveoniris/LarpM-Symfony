<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class InstallDatabaseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('database_name', TextType::class, ['required' => true])
            ->add('database_host', TextType::class, ['required' => true])
            ->add('database_port', TextType::class, ['required' => false])
            ->add('database_User', TextType::class, ['required' => true])
            ->add('database_password', PasswordType::class, ['required' => false]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'installDatabaseForm';
    }
}
