<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\GroupeLangue;
use App\Entity\Langue;
use App\Enum\Role;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\LangueForm.
 *
 * @author kevin
 */
class LangueType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $documentLabel = 'Téléversez un document PDF (abécédaire)';
        if ($options['hasDocumentUrl']) {
            $documentLabel = 'Téléversez un nouveau document PDF (abécédaire) pour remplacer le document existant';
        }

        $builder
            ->add('label', TextType::class, [
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
                'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('i')->orderBy('i.label', 'ASC'),
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
                 * new File([
                 * 'mimeTypes' => [
                 * 'application/pdf',
                 * 'application/x-pdf',
                 * ],
                 * 'mimeTypesMessage' => 'Veuillez choisir un document PDF valide.',
                 * ])
                 * ],*/
            ]);

        // Écriture réservée à la cohérence et aux règles (ADMIN les hérite), lecture seule pour les autres
        if ($this->security->isGranted(Role::REGLE->value) || $this->security->isGranted(Role::COHERENCE->value)) {
            $builder->add('conditionApprentissage', TextareaType::class, [
                'label' => 'Condition d\'apprentissage',
                'required' => false,
                'attr' => ['rows' => 5],
            ]);
        }
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

    /*
     * Nom du formulaire.
     */
}
