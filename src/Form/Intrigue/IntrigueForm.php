<?php


namespace App\Form\Intrigue;

use App\Entity\Intrigue;
use App\Form\Type\IntrigueHasDocumentType;
use App\Form\Type\IntrigueHasEvenementType;
use App\Form\Type\IntrigueHasGroupeSecondaireType;
use App\Form\Type\IntrigueHasGroupeType;
use App\Form\Type\IntrigueHasLieuType;
use App\Form\Type\IntrigueHasObjectifType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class IntrigueForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', TextType::class, [
            'label' => 'Le titre de votre intrigue',
            'required' => true,
        ])
            ->add('intrigueHasGroupes', CollectionType::class, [
                'label' => 'Groupes concernés',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => IntrigueHasGroupeType::class,
            ])
            ->add('intrigueHasGroupeSecondaires', CollectionType::class, [
                'label' => 'Groupes secondaires concernés',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => IntrigueHasGroupeSecondaireType::class,
            ])
            ->add('intrigueHasDocuments', CollectionType::class, [
                'label' => 'Documents concernées',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => IntrigueHasDocumentType::class,
            ])
            ->add('intrigueHasLieus', CollectionType::class, [
                'label' => 'Instances concernées',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => IntrigueHasLieuType::class,
            ])
            ->add('intrigueHasEvenements', CollectionType::class, [
                'label' => 'Evénements',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => IntrigueHasEvenementType::class,
            ])
            ->add('intrigueHasObjectifs', CollectionType::class, [
                'label' => 'Objectifs',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => IntrigueHasObjectifType::class,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre intrigue',
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                    'help' => 'Une courte description de votre intrigue.',
                ],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Votre intrigue',
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                    'help' => 'Développez votre intrigue içi. N\'oubliez pas d\'ajouter les groupes concernés et les événements si votre intrigue y fait référence',
                ],
            ])
            ->add('resolution', TextareaType::class, [
                'label' => 'Résolution de votre intrigue',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                    'help' => 'Indiquez de quelle manière les joueurs peuvent résoudre cette intrigue. Il s\'agit içi de la ou des différentes solutions que vous prévoyez à votre intrigue',
                ],
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Intrigue::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'intrigue';
    }
}
