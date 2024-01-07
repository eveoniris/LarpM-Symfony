<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\SearchObjetForm.
 *
 * @author kevin
 */
class SearchObjetForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'required' => true,
            'choices' => [
                'nom' => 'Nom',
                'numero' => 'Numero']])
            ->add('value', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => true]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'searchObjetForm';
    }
}
