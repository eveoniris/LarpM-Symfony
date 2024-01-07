<?php


namespace App\Form\Culture;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\CultureForm.
 *
 * @author kevin
 */
class CultureForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text')
            ->add('description', 'textarea', [
                'required' => false,
                'label' => 'Description succinte',
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('descriptionComplete', 'textarea', [
                'required' => false,
                'label' => 'Description complète de la culture (accessible aux joueurs membres des territoires correspondant à cette culture)',
                'attr' => [
                    'class' => 'tinymce',
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
            'data_class' => '\\'.\App\Entity\Culture::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'culture';
    }
}
