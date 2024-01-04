<?php


namespace App\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * LarpManager\Form\LangueForm.
 *
 * @author kevin
 */
class LangueForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $documentLabel = 'Téléversez un document PDF (abécédaire)';
        if ($options['hasDocumentUrl']) {
            $documentLabel = 'Téléversez un nouveau document PDF (abécédaire) pour remplacer le document existant';
        }

        $builder->add('label', 'text', [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('diffusion', 'choice', [
                'label' => 'Degré de diffusion (de 0 à 2 : rare, courante, commune)',
                'required' => false,
                'placeholder' => 'Inconnue',
                'choices' => [
                    0 => '0 - Rare',
                    1 => '1 - Courante',
                    2 => '2 - Commune',
                ],
            ])
            ->add('groupeLangue', 'entity', [
                'label' => 'Choisissez le groupe de langue associé',
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'class' => \App\Entity\GroupeLangue::class,
                'property' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('i')->orderBy('i.label', 'ASC');
                },
            ])
            ->add('secret', 'choice', [
                'label' => 'Secret',
                'required' => true,
                'choices' => [
                    false => 'Langue visible',
                    true => 'Langue secrète',
                ],
            ])
            ->add('document', 'file', [
                'label' => $documentLabel,
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => '.pdf'],
                /* 'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir un document PDF valide.',
                    ])
                ],*/
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Langue::class,
            'hasDocumentUrl' => false,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'langue';
    }
}
