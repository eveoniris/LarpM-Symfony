<?php


namespace App\Form\Question;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\QuestionForm.
 *
 * @author kevin
 */
class QuestionForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text')
            ->add('text', 'textarea', [
                'required' => false,
                'label' => 'Description succinte',
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('choix', 'textarea', [
                'required' => false,
                'label' => 'les réponses possibles, séparée par un point-virgule',
                'attr' => [
                    'row' => 9,
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Question::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'question';
    }
}
