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
        $builder->add('type', 'choice', [
            'required' => true,
            'choices' => [
                'nom' => 'Nom',
                'numero' => 'Numero']])
            ->add('value', 'text', ['required' => true]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'searchObjetForm';
    }
}
