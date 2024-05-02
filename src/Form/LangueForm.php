<?php


namespace App\Form;

use App\Entity\GroupeLangue;
use App\Entity\Langue;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('diffusion', ChoiceType::class, [
                'label' => 'Degré de diffusion (de 0 à 2 : rare, courante, commune)',
                'required' => false,
                'placeholder' => 'Inconnue',
                'choices' => [
                    '0 - Rare' => 0,
                    '1 - Courante' => 1,
                    '2 - Commune' => 2,
                ],
            ])
            ->add('groupeLangue', EntityType::class, [
                'label' => 'Choisissez le groupe de langue associé',
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'class' => GroupeLangue::class,
                'choice_label' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('i')->orderBy('i.label', 'ASC');
                },
            ])
            ->add('secret', ChoiceType::class, [
                'label' => 'Secret',
                'required' => true,
                'choices' => [
                    'Langue visible' => false,
                    'Langue secrète' => true,
                ],
            ])
            ->add('document', FileType::class, [
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
            'data_class' => Langue::class,
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
