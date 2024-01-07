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
        $builder->add('database_name', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => true])
            ->add('database_host', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => true])
            ->add('database_port', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
            ->add('database_User', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => true])
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
